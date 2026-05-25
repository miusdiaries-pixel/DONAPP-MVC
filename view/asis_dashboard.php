<?php
session_start();
include '../config/conexion.php';

// 1. Verificación básica de sesión iniciada
if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../index.php");
    exit();
}

$idUsuarioActual = $_SESSION['idUsuario'];

// 2. VALIDACIÓN EN TIEMPO REAL
$check = $conn->prepare("SELECT rol, estado, contrasena FROM usuario WHERE idUsuario = ?");
if (!$check) {
    die("Error en la preparación de la consulta: " . $conn->error);
}

$check->bind_param("i", $idUsuarioActual);
$check->execute();
$resCheck = $check->get_result();
$dataCheck = $resCheck->fetch_assoc();

// 3. LÓGICA DE EXPULSIÓN INTEGRAL
if (
    !$dataCheck || 
    $dataCheck['rol'] !== 'asistente' || 
    $dataCheck['estado'] !== 'activo' ||
        (isset($_SESSION['password_hash']) && $_SESSION['password_hash'] !== $dataCheck['contrasena'])
) {
    session_unset();
    session_destroy();
    header("Location: ../index.php?error=acceso_denegado");
    exit();
}

$idAsistente = $idUsuarioActual;

// ── FILTROS DONACIONES ────────────────────────────────────────────────────
$don_search  = isset($_GET['don_search'])  ? $conn->real_escape_string(trim($_GET['don_search'])) : '';
$don_estado  = isset($_GET['don_estado'])  ? $conn->real_escape_string($_GET['don_estado'])        : '';
$don_cat     = isset($_GET['don_cat'])     ? (int)$_GET['don_cat']                                 : 0;

// ── FILTROS SOLICITUDES ───────────────────────────────────────────────────
$sol_search  = isset($_GET['sol_search'])  ? $conn->real_escape_string(trim($_GET['sol_search'])) : '';
$sol_estado  = isset($_GET['sol_estado'])  ? $conn->real_escape_string($_GET['sol_estado'])        : '';
$sol_cat     = isset($_GET['sol_cat'])     ? (int)$_GET['sol_cat']                                 : 0;

// ── FILTROS CLIENTES ──────────────────────────────────────────────────────
$cli_search    = isset($_GET['cli_search'])    ? $conn->real_escape_string(trim($_GET['cli_search'])) : '';
$cli_prioridad = isset($_GET['cli_prioridad']) ? $conn->real_escape_string($_GET['cli_prioridad'])    : '';

// ── FILTROS CATEGORÍAS ────────────────────────────────────────────────────
$cat_search = isset($_GET['cat_search']) ? $conn->real_escape_string(trim($_GET['cat_search'])) : '';

// ── FILTROS EVENTOS ───────────────────────────────────────────────────────
$ev_search  = isset($_GET['ev_search'])  ? $conn->real_escape_string(trim($_GET['ev_search'])) : '';
$ev_estado  = isset($_GET['ev_estado'])  ? $conn->real_escape_string($_GET['ev_estado'])        : '';

// ── ORDENAMIENTO ─────────────────────────────────────────────────────────
$allowed_cli_sort = ['idUsuario','nombre','estado'];
$allowed_don_sort = ['idDonacion','descripcion','estado','fechaCreacion'];
$allowed_sol_sort = ['idSolicitud','descripcion','estado','fechaCreacion'];
$allowed_ev_sort  = ['idEvento','Nombre','estado'];
$allowed_cat_sort = ['nombre','idCategoria'];

function get_sort($param, $allowed, $default) {
    $col = isset($_GET[$param]) ? $_GET[$param] : $default;
    return in_array($col, $allowed) ? $col : $default;
}
function get_dir($param) {
    return (isset($_GET[$param]) && $_GET[$param] === 'ASC') ? 'ASC' : 'DESC';
}
function sort_toggle_url($col_param, $dir_param, $col, $tab = '') {
    $params = $_GET;
    // Siempre inyectar el tab correcto para que initTabs lo detecte
    if ($tab !== '') $params['tab'] = $tab;
    $cur_col = $params[$col_param] ?? '';
    $cur_dir = (isset($params[$dir_param]) && $params[$dir_param] === 'ASC') ? 'ASC' : 'DESC';
    $new_dir = ($cur_col === $col && $cur_dir === 'ASC') ? 'DESC' : 'ASC';
    $params[$col_param] = $col;
    $params[$dir_param] = $new_dir;
    return '?' . http_build_query($params);
}
function sort_icon($col_param, $dir_param, $col) {
    $active = (isset($_GET[$col_param]) && $_GET[$col_param] === $col);
    $dir = (isset($_GET[$dir_param]) && $_GET[$dir_param] === 'ASC') ? 'ASC' : 'DESC';
    if (!$active) return '<i class="fa-solid fa-sort" style="opacity:.35;font-size:.75rem;margin-left:3px;"></i>';
    return $dir === 'ASC'
        ? '<i class="fa-solid fa-sort-up" style="font-size:.75rem;margin-left:3px;color:#1565c0;"></i>'
        : '<i class="fa-solid fa-sort-down" style="font-size:.75rem;margin-left:3px;color:#1565c0;"></i>';
}

$cli_sort = get_sort('cli_sort', $allowed_cli_sort, 'idUsuario');
$cli_dir  = get_dir('cli_dir');
$don_sort = get_sort('don_sort', $allowed_don_sort, 'idDonacion');
$don_dir  = get_dir('don_dir');
$sol_sort = get_sort('sol_sort', $allowed_sol_sort, 'idSolicitud');
$sol_dir  = get_dir('sol_dir');
$ev_sort  = get_sort('ev_sort',  $allowed_ev_sort,  'idEvento');
$ev_dir   = get_dir('ev_dir');
$cat_sort = get_sort('cat_sort', $allowed_cat_sort, 'nombre');
$cat_dir  = get_dir('cat_dir');

// ── STATS GLOBALES ────────────────────────────────────────────────────────
$total_donaciones   = $conn->query("SELECT COUNT(*) AS c FROM donacion")->fetch_assoc()['c'];
$total_solicitudes  = $conn->query("SELECT COUNT(*) AS c FROM solicitud")->fetch_assoc()['c'];
$total_pendientes   = $conn->query("SELECT COUNT(*) AS c FROM donacion WHERE estado='pendiente'")->fetch_assoc()['c']
                    + $conn->query("SELECT COUNT(*) AS c FROM solicitud WHERE estado='pendiente'")->fetch_assoc()['c'];
$total_aprobadas    = $conn->query("SELECT COUNT(*) AS c FROM donacion WHERE estado='aprobada'")->fetch_assoc()['c'];
$total_eventos      = $conn->query("SELECT COUNT(*) AS c FROM evento")->fetch_assoc()['c'];
$total_clientes     = $conn->query("SELECT COUNT(*) AS c FROM usuario WHERE rol='donante'")->fetch_assoc()['c'];

// ── DONACIONES ────────────────────────────────────────────────────────────
$don_where = "WHERE 1=1";
if ($don_search)  $don_where .= " AND (d.descripcion LIKE '%$don_search%' OR u.nombre LIKE '%$don_search%')";
if ($don_estado)  $don_where .= " AND d.estado = '$don_estado'";
if ($don_cat > 0) $don_where .= " AND d.idCategoria = $don_cat";

$res_donaciones = $conn->query("
    SELECT d.*, c.nombre AS categoria,
           u.nombre AS donante
    FROM donacion d
    LEFT JOIN categoria c  ON d.idCategoria = c.idCategoria
    LEFT JOIN DonacionUsuario du ON d.idDonacion = du.idDonacion
    LEFT JOIN usuario u    ON du.idDonante = u.idUsuario
    $don_where
    ORDER BY d.$don_sort $don_dir
");

$donaciones_procesadas = [];
while ($row = $res_donaciones->fetch_assoc()) {
    if (!empty($row['imagen'])) {
        $row['imagen'] = 'data:image/jpeg;base64,' . base64_encode($row['imagen']);
    }
    $donaciones_procesadas[] = $row;
}

// ── SOLICITUDES (CORREGIDO) ───────────────────────────────────────────────────────────
$sol_where = "WHERE 1=1";
if ($sol_search)  $sol_where .= " AND (s.descripcion LIKE '%$sol_search%' OR us.nombre LIKE '%$sol_search%')";
if ($sol_estado)  $sol_where .= " AND s.estado = '$sol_estado'";
if ($sol_cat > 0) $sol_where .= " AND s.idCategoria = $sol_cat";

$res_solicitudes = $conn->query("
    SELECT s.*, c.nombre AS categoria,
           us.nombre AS solicitante,
           ug.nombre AS gestor
    FROM solicitud s
    LEFT JOIN categoria c   ON s.idCategoria = c.idCategoria
    LEFT JOIN usuario us    ON s.idSolicitante = us.idUsuario
    LEFT JOIN usuario ug    ON s.idGestor = ug.idUsuario 
    $sol_where
    ORDER BY s.$sol_sort $sol_dir
");

if (!$res_solicitudes) {
    die("Error en la consulta de solicitudes: " . $conn->error);
}

$solicitudes_procesadas = [];
while ($row = $res_solicitudes->fetch_assoc()) {
    if (!empty($row['imagen'])) {
        $row['imagen'] = 'data:image/jpeg;base64,' . base64_encode($row['imagen']);
    }
    $solicitudes_procesadas[] = $row;
}

// ── CLIENTES ──────────────────────────────────────────────────────────────
$cli_sql = "SELECT * FROM usuario WHERE rol='donante'";
if ($cli_search)    $cli_sql .= " AND (nombre LIKE '%$cli_search%' OR email LIKE '%$cli_search%')";
if ($cli_prioridad) $cli_sql .= " AND prioridad = '$cli_prioridad'";
$cli_sql .= " ORDER BY $cli_sort $cli_dir";
$res_clientes = $conn->query($cli_sql);

// ── EVENTOS ───────────────────────────────────────────────────────────────
$ev_where = "WHERE 1=1";
if ($ev_search) $ev_where .= " AND e.Nombre LIKE '%$ev_search%'";
if ($ev_estado) $ev_where .= " AND e.estado = '$ev_estado'";

$res_eventos = $conn->query("
    SELECT e.idEvento, e.Nombre, e.estado,
           pe.FechaEntrega, pe.Lugar,
           p.idPublicacion, p.titulo, p.contenido, p.imagen
    FROM evento e
    LEFT JOIN ProgramadorEventos pe ON pe.idProgramadorEventos = (
        SELECT idProgramadorEventos FROM ProgramadorEventos
        WHERE idEvento = e.idEvento
        ORDER BY idProgramadorEventos DESC LIMIT 1
    )
    LEFT JOIN publicacion p ON p.idPublicacion = (
        SELECT idPublicacion FROM publicacion
        WHERE idEvento = e.idEvento
        ORDER BY idPublicacion DESC LIMIT 1
    )
    $ev_where
    ORDER BY e.$ev_sort $ev_dir
");

// ── CATEGORÍAS ────────────────────────────────────────────────────────────
$cat_sql = "SELECT c.*, u.nombre AS creador FROM categoria c LEFT JOIN usuario u ON c.idUsuario = u.idUsuario WHERE 1=1";
if ($cat_search) $cat_sql .= " AND c.nombre LIKE '%$cat_search%'";
$cat_sql .= " ORDER BY c.$cat_sort $cat_dir";
$res_categorias     = $conn->query($cat_sql);
$res_categorias_sel = $conn->query("SELECT * FROM categoria ORDER BY nombre");

// ── PERFIL ASISTENTE ──────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM usuario WHERE idUsuario = ?");
$stmt->bind_param("i", $idAsistente);
$stmt->execute();
$asis_data = $stmt->get_result()->fetch_assoc();

// ── DATOS PARA REPORTES ───────────────────────────────────────────────────
$res_don_rpt = $conn->query("
    SELECT d.idDonacion, d.descripcion, d.estado, d.stock,
           d.observacion, d.fechaCreacion, c.nombre AS categoria,
           u.nombre AS donante
    FROM donacion d
    LEFT JOIN categoria c ON d.idCategoria = c.idCategoria
    LEFT JOIN DonacionUsuario du ON d.idDonacion = du.idDonacion
    LEFT JOIN usuario u ON du.idDonante = u.idUsuario
    ORDER BY d.idDonacion DESC
");
$donaciones_arr = [];
while ($row = $res_don_rpt->fetch_assoc()) $donaciones_arr[] = $row;

$res_sol_rpt = $conn->query("
    SELECT s.idSolicitud, s.descripcion, s.estado,
           s.observacion, s.fechaCreacion, c.nombre AS categoria,
           us.nombre AS solicitante
    FROM solicitud s
    LEFT JOIN categoria c ON s.idCategoria = c.idCategoria
    LEFT JOIN usuario us ON s.idSolicitante = us.idUsuario
    ORDER BY s.idSolicitud DESC
");
$solicitudes_arr = [];
while ($row = $res_sol_rpt->fetch_assoc()) $solicitudes_arr[] = $row;

// ── MENSAJE FLASH ─────────────────────────────────────────────────────────
$flash = '';
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
$notif_enviada = '';
if (isset($_SESSION['notif_enviada'])) {
    $notif_enviada = $_SESSION['notif_enviada'];
    unset($_SESSION['notif_enviada']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donapp — Panel de Moderación</title>
    <link rel="icon" type="image/png" href="../assets/uploads/Icon.png">
    <link rel="stylesheet" href="../assets/css/asis_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script></head>
<body>

<?php if ($flash): ?>
<div class="flash-msg" id="flashMsg"><?php echo htmlspecialchars($flash); ?></div>
<?php endif; ?>
<?php if ($notif_enviada): ?>
<div class="flash-msg flash-info" id="flashNotif"><?php echo htmlspecialchars($notif_enviada); ?></div>
<?php endif; ?>

<div class="admin-wrapper">

    <!-- ═══════════════ SIDEBAR ═══════════════ -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <a href="../index.php"><img src="../assets/uploads/Red Logo.png" alt="Donapp" onerror="this.style.display='none'"></a>
            <p class="sidebar-title">Panel de Moderación</p>
        </div>
        <ul class="nav-menu">
            <li><a href="asis_dashboard.php#dashboard"  class="nav-link" onclick="irTab('dashboard',  event)"><i class="fa-solid fa-house"></i><span> Dashboard</span></a></li>
            <li><a href="asis_dashboard.php#clientes"   class="nav-link" onclick="irTab('clientes',   event)"><i class="fa-solid fa-users"></i><span> Donantes / Solicitantes</span></a></li>
            <li><a href="asis_dashboard.php#donapp"     class="nav-link" onclick="irTab('donapp',     event)"><i class="fa-solid fa-hand-holding-heart"></i><span> Donaciones/Sol.</span></a></li>
            <li><a href="asis_dashboard.php#eventos"    class="nav-link" onclick="irTab('eventos',    event)"><i class="fa-solid fa-calendar-days"></i><span> Eventos</span></a></li>
            <li><a href="asis_dashboard.php#categorias" class="nav-link" onclick="irTab('categorias', event)"><i class="fa-solid fa-tags"></i><span> Categorías</span></a></li>
            <li><a href="asis_dashboard.php#reportes"   class="nav-link" onclick="irTab('reportes',   event)"><i class="fa-solid fa-file-pdf"></i><span> Reportes</span></a></li>
            <li><a href="asis_dashboard.php#perfil"     class="nav-link" onclick="irTab('perfil',     event)"><i class="fa-solid fa-user-gear"></i><span> Mi Perfil</span></a></li>
            <li><hr></li>
            <li><a href="../controller/logout.php" class="nav-link logout"><i class="fa-solid fa-power-off"></i><span> Cerrar Sesión</span></a></li>
        </ul>
    </aside>

    <!-- ═══════════════ MAIN ═══════════════ -->
    <main class="main-content">

        <!-- ────────── DASHBOARD ────────── -->
        <div id="dashboard" class="tab-pane active">
            <h1 class="page-title">Bienvenid@, <?php echo htmlspecialchars($asis_data['nombre']); ?> 👋</h1>
            <p class="text-muted" class="page-subtitle">
                <i class="fa-solid fa-shield-halved"></i> Módulo de Moderación — Revisa y gestiona donaciones, solicitudes y eventos.
            </p>
            <div class="stats-grid">
                <div class="stat-card alert-pending">
                    <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
                    <div><h3><?php echo $total_pendientes; ?></h3><p>Pendientes por revisar</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fa-solid fa-box-open"></i></div>
                    <div><h3><?php echo $total_donaciones; ?></h3><p>Donaciones</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div><h3><?php echo $total_solicitudes; ?></h3><p>Solicitudes</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
                    <div><h3><?php echo $total_aprobadas; ?></h3><p>Donaciones aprobadas</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-calendar-check"></i></div>
                    <div><h3><?php echo $total_eventos; ?></h3><p>Eventos</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                    <div><h3><?php echo $total_clientes; ?></h3><p>Donantes / Solicitantes</p></div>
                </div>
            </div>

            <!-- Accesos rápidos -->
            <h2 class="page-title" class="subtitle-section">Accesos rápidos</h2>
            <div class="stats-grid" class="stats-grid-sm">
                <a href="#donapp" class="stat-card" 
                   onclick="activarTab('#donapp')">
                    <div class="stat-icon orange"><i class="fa-solid fa-box-open"></i></div>
                    <div><p >Ver Donaciones</p></div>
                </a>
                <a href="#donapp" class="stat-card" 
                   onclick="activarTab('#donapp'); setTimeout(()=>{ const b=document.querySelector('.tab-btn[onclick*=sol-panel]'); if(b) switchInner(b,'sol-panel'); },100);">
                    <div class="stat-icon blue"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div><p >Ver Solicitudes</p></div>
                </a>
                <a href="#eventos" class="stat-card" 
                   onclick="activarTab('#eventos')">
                    <div class="stat-icon green"><i class="fa-solid fa-calendar-plus"></i></div>
                    <div><p >Nuevo Evento</p></div>
                </a>
                <a href="#reportes" class="stat-card" 
                   onclick="activarTab('#reportes')">
                    <div class="stat-icon"><i class="fa-solid fa-file-pdf"></i></div>
                    <div><p >Generar Reporte</p></div>
                </a>
            </div>
        </div>

        <!-- ────────── CLIENTES ────────── -->
        <div id="clientes" class="tab-pane">
            <div class="section-header">
                <h2 class="page-title">Gestión de Donantes y Solicitantes</h2>
                <button class="btn btn-primary" onclick="abrirModal('modalCrearDonante')">
                    <i class="fa-solid fa-user-plus"></i> Nuevo Donante / Solicitante
                </button>
            </div>
            <div class="card">
                <form method="GET" class="filter-bar">
                    <input type="hidden" name="tab" value="clientes">
                    <input type="text" name="cli_search" placeholder="🔍 Buscar donante/solicitante por nombre o email..."
                           value="<?php echo htmlspecialchars($cli_search); ?>" class="form-input search-input" maxlength="200">
                    <select name="cli_prioridad" class="form-input sel-small">
                        <option value="">Todas las prioridades</option>
                        <option value="alta"   <?php echo $cli_prioridad==='alta'  ?'selected':''; ?>>🔴 Alta</option>
                        <option value="media"  <?php echo $cli_prioridad==='media' ?'selected':''; ?>>🟡 Media</option>
                        <option value="baja"   <?php echo $cli_prioridad==='baja'  ?'selected':''; ?>>🟢 Baja</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
                    <?php if ($cli_search || $cli_prioridad): ?>
                    <a href="asis_dashboard.php?tab=clientes#clientes" class="btn btn-secondary btn-sm">Limpiar</a>
                    <?php endif; ?>
                </form>
                <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><a href="<?php echo sort_toggle_url('cli_sort','cli_dir','idUsuario','clientes'); ?>#clientes" style="color:inherit;text-decoration:none;">#<?php echo sort_icon('cli_sort','cli_dir','idUsuario'); ?></a></th>
                            <th><a href="<?php echo sort_toggle_url('cli_sort','cli_dir','nombre','clientes'); ?>#clientes" style="color:inherit;text-decoration:none;">Nombre<?php echo sort_icon('cli_sort','cli_dir','nombre'); ?></a></th>
                            <th>Documento</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Necesidad</th>
                            <th><a href="<?php echo sort_toggle_url('cli_sort','cli_dir','prioridad','clientes'); ?>#clientes" style="color:inherit;text-decoration:none;">Prioridad<?php echo sort_icon('cli_sort','cli_dir','prioridad'); ?></a></th>
                            <th><a href="<?php echo sort_toggle_url('cli_sort','cli_dir','estado','clientes'); ?>#clientes" style="color:inherit;text-decoration:none;">Estado<?php echo sort_icon('cli_sort','cli_dir','estado'); ?></a></th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_clientes->num_rows === 0): ?>
                        <tr><td colspan="9" class="empty-row">No se encontraron donantes/solicitantes.</td></tr>
                        <?php endif; ?>
                        <?php while ($cli = $res_clientes->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $cli['idUsuario']; ?></td>
                            <td><?php echo htmlspecialchars($cli['nombre']); ?></td>
                            <td><small><?php echo $cli['tipoDocumento'].': '.htmlspecialchars($cli['numDocumento']); ?></small></td>
                            <td><?php echo htmlspecialchars($cli['email']); ?></td>
                            <td><?php echo htmlspecialchars($cli['telefono']); ?></td>
                            <td class="necesidad-cell"><?php echo htmlspecialchars($cli['necesidad'] ?? '—'); ?></td>
                            <td><?php
                                $p = $cli['prioridad'] ?? '';
                                $badge = ['alta'=>'<span style="color:#c0392b;font-weight:700;">🔴 Alta</span>',
                                          'media'=>'<span style="color:#d68910;font-weight:700;">🟡 Media</span>',
                                          'baja'=>'<span style="color:#1e8449;font-weight:700;">🟢 Baja</span>'];
                                echo $badge[$p] ?? '—';
                            ?></td>
                            <td><span class="badge estado-<?php echo $cli['estado']; ?>"><?php echo $cli['estado']; ?></span></td>
                            <td class="td-actions">
                                <button onclick='abrirModalVerDonante(<?php echo json_encode($cli); ?>)'
                                        class="btn btn-sm btn-primary" title="Ver detalles">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button onclick='abrirModalEditarDonante(<?php echo json_encode($cli); ?>)'
                                        class="btn btn-sm btn-warning" title="Editar donante/solicitante">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <a href="#donapp?sol_search=<?php echo urlencode($cli['nombre']); ?>"
                                   onclick="filtrarSolicitudesPorCliente('<?php echo htmlspecialchars(addslashes($cli['nombre'])); ?>')"
                                   class="btn btn-sm btn-secondary" title="Ver solicitudes del donante/solicitante">
                                    <i class="fa-solid fa-clipboard-list"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- ────────── DONACIONES / SOLICITUDES ────────── -->
        <div id="donapp" class="tab-pane">
            <h2 class="page-title">Donaciones y Solicitudes</h2>

            <div class="tabs-inner">
                <button class="tab-btn <?php echo (!$sol_search && !$sol_estado && !$sol_cat) ? 'active' : ''; ?>"
                        onclick="switchInner(this,'don-panel')">Donaciones</button>
                <button class="tab-btn <?php echo ($sol_search || $sol_estado || $sol_cat) ? 'active' : ''; ?>"
                        onclick="switchInner(this,'sol-panel')">Solicitudes</button>
            </div>

            <!-- DONACIONES -->
            <div id="don-panel" class="inner-panel" <?php echo ($sol_search || $sol_estado || $sol_cat) ? 'style="display:none;"' : ''; ?>>
                <form method="GET" class="filter-bar" style="margin-bottom:16px;">
                    <input type="hidden" name="tab" value="donapp">
                    <input type="text" name="don_search" placeholder="🔍 Buscar por descripción o donante..."
                           value="<?php echo htmlspecialchars($don_search); ?>" class="form-input search-input" maxlength="200">
                    <select name="don_estado" class="form-input sel-small" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <option value="pendiente"  <?php echo $don_estado==='pendiente' ?'selected':''; ?>>Pendiente</option>
                        <option value="aprobada"   <?php echo $don_estado==='aprobada'  ?'selected':''; ?>>Aprobada</option>
                        <option value="rechazada"  <?php echo $don_estado==='rechazada' ?'selected':''; ?>>Rechazada</option>
                    </select>
                    <select name="don_cat" class="form-input sel-small" onchange="this.form.submit()">
                        <option value="0">Todas las categorías</option>
                        <?php
                        $res_cat_don = $conn->query("SELECT idCategoria, nombre FROM categoria ORDER BY nombre");
                        while ($rc = $res_cat_don->fetch_assoc()):
                        ?>
                        <option value="<?php echo $rc['idCategoria']; ?>" <?php echo $don_cat==$rc['idCategoria']?'selected':''; ?>>
                            <?php echo htmlspecialchars($rc['nombre']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                    <?php if ($don_search || $don_estado || $don_cat): ?>
                    <a href="asis_dashboard.php?tab=donapp#donapp" class="btn btn-secondary btn-sm">Limpiar</a>
                    <?php endif; ?>
                </form>
                <div class="card">
                    <div class="table-wrap">
                    <table>
                        <thead><tr>
                            <th><a href="<?php echo sort_toggle_url('don_sort','don_dir','idDonacion','donapp'); ?>#donapp" style="color:inherit;text-decoration:none;">#<?php echo sort_icon('don_sort','don_dir','idDonacion'); ?></a></th>
                            <th><a href="<?php echo sort_toggle_url('don_sort','don_dir','descripcion','donapp'); ?>#donapp" style="color:inherit;text-decoration:none;">Descripción<?php echo sort_icon('don_sort','don_dir','descripcion'); ?></a></th>
                            <th>Categoría</th><th>Stock</th>
                            <th><a href="<?php echo sort_toggle_url('don_sort','don_dir','estado','donapp'); ?>#donapp" style="color:inherit;text-decoration:none;">Estado<?php echo sort_icon('don_sort','don_dir','estado'); ?></a></th>
                            <th><a href="<?php echo sort_toggle_url('don_sort','don_dir','fechaCreacion','donapp'); ?>#donapp" style="color:inherit;text-decoration:none;">Fecha<?php echo sort_icon('don_sort','don_dir','fechaCreacion'); ?></a></th>
                            <th>Donante</th><th>Observación</th><th>Acción</th></tr></thead>
                        <tbody>
    <?php if (empty($donaciones_procesadas)): ?>
    <tr><td colspan="9" class="empty-row">No se encontraron donaciones con esos criterios.</td></tr>
    <?php endif; ?>
    <?php foreach ($donaciones_procesadas as $d): ?>
    <tr>
        <td><?php echo $d['idDonacion']; ?></td>
        <td><?php echo htmlspecialchars($d['descripcion']); ?></td>
        <td><?php echo htmlspecialchars($d['categoria'] ?? '—'); ?></td>
        <td><?php echo $d['stock']; ?></td>
        <td><span class="badge estado-<?php echo $d['estado']; ?>"><?php echo $d['estado']; ?></span></td>
        <td><?php echo date('d/m/Y', strtotime($d['fechaCreacion'])); ?></td>
        <td><?php echo htmlspecialchars($d['donante'] ?? '—'); ?></td>
        <td><?php echo htmlspecialchars($d['observacion'] ?? '—'); ?></td>
        <td>
            <button onclick='abrirModalDonacion(<?php echo json_encode($d); ?>)'
                    class="btn btn-sm btn-primary"><i class="fa-solid fa-pen-to-square"></i></button>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
                    </table>
                    </div>
                </div>
            </div>

            <!-- SOLICITUDES -->
            <div id="sol-panel" class="inner-panel" <?php echo (!$sol_search && !$sol_estado && !$sol_cat) ? 'style="display:none;"' : ''; ?>>
                <form method="GET" class="filter-bar" style="margin-bottom:16px;">
                    <input type="hidden" name="tab" value="donapp">
                    <input type="text" name="sol_search" placeholder="🔍 Buscar por descripción o solicitante..."
                           value="<?php echo htmlspecialchars($sol_search); ?>" class="form-input search-input" maxlength="200">
                    <select name="sol_estado" class="form-input sel-small" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <option value="pendiente"  <?php echo $sol_estado==='pendiente' ?'selected':''; ?>>Pendiente</option>
                        <option value="aprobada"   <?php echo $sol_estado==='aprobada'  ?'selected':''; ?>>Aprobada</option>
                        <option value="rechazada"  <?php echo $sol_estado==='rechazada' ?'selected':''; ?>>Rechazada</option>
                    </select>
                    <select name="sol_cat" class="form-input sel-small" onchange="this.form.submit()">
                        <option value="0">Todas las categorías</option>
                        <?php
                        $res_cat_sol = $conn->query("SELECT idCategoria, nombre FROM categoria ORDER BY nombre");
                        while ($rc = $res_cat_sol->fetch_assoc()):
                        ?>
                        <option value="<?php echo $rc['idCategoria']; ?>" <?php echo $sol_cat==$rc['idCategoria']?'selected':''; ?>>
                            <?php echo htmlspecialchars($rc['nombre']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                    <?php if ($sol_search || $sol_estado || $sol_cat): ?>
                    <a href="asis_dashboard.php?tab=donapp#donapp" class="btn btn-secondary btn-sm">Limpiar</a>
                    <?php endif; ?>
                </form>
                <div class="card">
                    <div class="table-wrap">
                    <table>
                        <thead><tr>
                            <th><a href="<?php echo sort_toggle_url('sol_sort','sol_dir','idSolicitud','donapp'); ?>#donapp" style="color:inherit;text-decoration:none;">#<?php echo sort_icon('sol_sort','sol_dir','idSolicitud'); ?></a></th>
                            <th><a href="<?php echo sort_toggle_url('sol_sort','sol_dir','descripcion','donapp'); ?>#donapp" style="color:inherit;text-decoration:none;">Descripción<?php echo sort_icon('sol_sort','sol_dir','descripcion'); ?></a></th>
                            <th>Categoría</th>
                            <th><a href="<?php echo sort_toggle_url('sol_sort','sol_dir','estado','donapp'); ?>#donapp" style="color:inherit;text-decoration:none;">Estado<?php echo sort_icon('sol_sort','sol_dir','estado'); ?></a></th>
                            <th><a href="<?php echo sort_toggle_url('sol_sort','sol_dir','fechaCreacion','donapp'); ?>#donapp" style="color:inherit;text-decoration:none;">Fecha<?php echo sort_icon('sol_sort','sol_dir','fechaCreacion'); ?></a></th>
                            <th>Solicitante</th><th>Gestor</th><th>Observación</th><th>Acción</th></tr></thead>
                        <tbody>
    <?php if (empty($solicitudes_procesadas)): ?>
    <tr><td colspan="9" class="empty-row">No se encontraron solicitudes con esos criterios.</td></tr>
    <?php endif; ?>
    <?php foreach ($solicitudes_procesadas as $s): ?>
    <tr>
        <td><?php echo $s['idSolicitud']; ?></td>
        <td><?php echo htmlspecialchars($s['descripcion']); ?></td>
        <td><?php echo htmlspecialchars($s['categoria'] ?? '—'); ?></td>
        <td><span class="badge estado-<?php echo $s['estado']; ?>"><?php echo $s['estado']; ?></span></td>
        <td><?php echo date('d/m/Y', strtotime($s['fechaCreacion'])); ?></td>
        <td><?php echo htmlspecialchars($s['solicitante'] ?? '—'); ?></td>
        <td><?php echo htmlspecialchars($s['gestor'] ?? '—'); ?></td>
        <td><?php echo htmlspecialchars($s['observacion'] ?? '—'); ?></td>
        <td>
            <button onclick='abrirModalSolicitud(<?php echo json_encode($s); ?>)'
                    class="btn btn-sm btn-primary"><i class="fa-solid fa-pen-to-square"></i></button>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ────────── EVENTOS ────────── -->
        <div id="eventos" class="tab-pane">
            <div class="section-header">
                <h2 class="page-title">Gestión de Eventos</h2>
                <button class="btn btn-primary" onclick="abrirModalCrearEvento()">
                    <i class="fa-solid fa-plus"></i> Nuevo Evento
                </button>
            </div>
            <div class="card">
                <form method="GET" class="filter-bar" style="margin-bottom:16px;">
                    <input type="hidden" name="tab" value="eventos">
                    <input type="text" name="ev_search" placeholder="🔍 Buscar evento por nombre..."
                           value="<?php echo htmlspecialchars($ev_search); ?>" class="form-input search-input" maxlength="200">
                    <select name="ev_estado" class="form-input sel-small" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <option value="activo"   <?php echo $ev_estado==='activo'   ?'selected':''; ?>>Activo</option>
                        <option value="inactivo" <?php echo $ev_estado==='inactivo' ?'selected':''; ?>>Inactivo</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                    <?php if ($ev_search || $ev_estado): ?>
                    <a href="asis_dashboard.php?tab=eventos#eventos" class="btn btn-secondary btn-sm">Limpiar</a>
                    <?php endif; ?>
                </form>
                <div class="table-wrap">
                <table>
                    <thead><tr>
                        <th><a href="<?php echo sort_toggle_url('ev_sort','ev_dir','idEvento','eventos'); ?>#eventos" style="color:inherit;text-decoration:none;">#<?php echo sort_icon('ev_sort','ev_dir','idEvento'); ?></a></th>
                        <th><a href="<?php echo sort_toggle_url('ev_sort','ev_dir','Nombre','eventos'); ?>#eventos" style="color:inherit;text-decoration:none;">Nombre<?php echo sort_icon('ev_sort','ev_dir','Nombre'); ?></a></th>
                        <th><a href="<?php echo sort_toggle_url('ev_sort','ev_dir','estado','eventos'); ?>#eventos" style="color:inherit;text-decoration:none;">Estado<?php echo sort_icon('ev_sort','ev_dir','estado'); ?></a></th>
                        <th>Fecha Entrega</th><th>Lugar</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php
                        $res_eventos->data_seek(0);
                        $ev_count = 0;
                        while ($ev = $res_eventos->fetch_assoc()):
                            $ev_count++;
                            if (!empty($ev['imagen'])) {
                                $ev['imagen'] = 'data:image/png;base64,' . base64_encode($ev['imagen']);
                            } else {
                                $ev['imagen'] = '';
                            }
                        ?>
                        <tr>
                            <td><?php echo $ev['idEvento']; ?></td>
                            <td><?php echo htmlspecialchars($ev['Nombre']); ?></td>
                            <td><span class="badge estado-<?php echo $ev['estado']; ?>"><?php echo $ev['estado']; ?></span></td>
                            <td><?php echo $ev['FechaEntrega'] ? date('d/m/Y', strtotime($ev['FechaEntrega'])) : '—'; ?></td>
                            <td class="lugar-cell"><?php echo htmlspecialchars($ev['Lugar'] ?? '—'); ?></td>
                            <td class="td-actions">
                                <button onclick='abrirModalEditarEvento(<?php echo json_encode($ev); ?>)'
                                        class="btn btn-sm btn-primary"><i class="fa-solid fa-pen"></i></button>
                                <a href="../controller/acciones_asis_eventos.php?toggle=<?php echo $ev['idEvento']; ?>"
                                   class="btn btn-sm btn-warning">
                                    <i class="fa-solid fa-arrows-rotate"></i> Toggle
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if ($ev_count === 0): ?>
                        <tr><td colspan="6" class="empty-row">No se encontraron eventos con esos criterios.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- ────────── CATEGORÍAS ────────── -->
        <div id="categorias" class="tab-pane">
            <div class="section-header">
                <h2 class="page-title">Gestión de Categorías</h2>
                <button class="btn btn-primary" onclick="abrirModal('modalCrearCategoria')">
                    <i class="fa-solid fa-plus"></i> Nueva Categoría
                </button>
            </div>
            <div class="card">
                <form method="GET" class="filter-bar" style="margin-bottom:16px;">
                    <input type="hidden" name="tab" value="categorias">
                    <input type="text" name="cat_search" placeholder="🔍 Buscar categoría..."
                           value="<?php echo htmlspecialchars($cat_search); ?>" class="form-input search-input" maxlength="200">
                    <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
                    <?php if ($cat_search): ?>
                    <a href="asis_dashboard.php?tab=categorias#categorias" class="btn btn-secondary btn-sm">Limpiar</a>
                    <?php endif; ?>
                </form>
                <div class="table-wrap">
                <table>
                    <thead><tr>
                        <th><a href="<?php echo sort_toggle_url('cat_sort','cat_dir','idCategoria','categorias'); ?>#categorias" style="color:inherit;text-decoration:none;">#<?php echo sort_icon('cat_sort','cat_dir','idCategoria'); ?></a></th>
                        <th><a href="<?php echo sort_toggle_url('cat_sort','cat_dir','nombre','categorias'); ?>#categorias" style="color:inherit;text-decoration:none;">Nombre<?php echo sort_icon('cat_sort','cat_dir','nombre'); ?></a></th>
                        <th>Acciones</th></tr></thead>
                    <tbody>
                    <?php
                    if ($res_categorias->num_rows === 0): ?>
                        <tr><td colspan="3" class="empty-row">No hay categorías registradas.</td></tr>
                    <?php endif;
                    while ($cat = $res_categorias->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $cat['idCategoria']; ?></td>
                        <td><?php echo htmlspecialchars($cat['nombre']); ?></td>
                        <td class="td-actions">
                            <button onclick='abrirModalEditarCategoria(<?php echo json_encode($cat); ?>)'
                                    class="btn btn-sm btn-warning"><i class="fa-solid fa-pen"></i></button>
                            <a href="../controller/acciones_asis_categorias.php?eliminar=<?php echo $cat['idCategoria']; ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('¿Eliminar esta categoría?')">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- ────────── REPORTES ────────── -->
        <div id="reportes" class="tab-pane">
            <h2 class="page-title">Generador de Reportes PDF</h2>
            <div class="reportes-grid">

                <div class="card reporte-card">
                    <div class="reporte-icon"><i class="fa-solid fa-box-open"></i></div>
                    <h3>Reporte de Donaciones</h3>
                    <p>Genera un PDF con el listado detallado de donaciones según los criterios que elijas.</p>
                    <div class="reporte-filters">
                        <label>Estado:</label>
                        <select id="rpt_don_estado" class="form-input">
                            <option value="todos">Todos</option>
                            <option value="aprobada">Aprobadas</option>
                            <option value="rechazada">Rechazadas</option>
                            <option value="pendiente">Pendientes</option>
                        </select>
                        <label>Fecha desde:</label>
                        <input type="date" id="rpt_don_desde" class="form-input">
                        <label>Fecha hasta:</label>
                        <input type="date" id="rpt_don_hasta" class="form-input">
                    </div>
                    <button class="btn btn-primary" onclick="generarReporteDonaciones()">
                        <i class="fa-solid fa-file-pdf"></i> Generar PDF
                    </button>
                </div>

                <div class="card reporte-card">
                    <div class="reporte-icon blue"><i class="fa-solid fa-clipboard-list"></i></div>
                    <h3>Reporte de Solicitudes</h3>
                    <p>Genera un PDF con el detalle de solicitudes completadas o según el estado que selecciones.</p>
                    <div class="reporte-filters">
                        <label>Estado:</label>
                        <select id="rpt_sol_estado" class="form-input">
                            <option value="todos">Todos</option>
                            <option value="aprobada">Aprobadas</option>
                            <option value="rechazada">Rechazadas</option>
                            <option value="pendiente">Pendientes</option>
                        </select>
                        <label>Fecha desde:</label>
                        <input type="date" id="rpt_sol_desde" class="form-input">
                        <label>Fecha hasta:</label>
                        <input type="date" id="rpt_sol_hasta" class="form-input">
                    </div>
                    <button class="btn btn-primary" onclick="generarReporteSolicitudes()">
                        <i class="fa-solid fa-file-pdf"></i> Generar PDF
                    </button>
                </div>

            </div>

            <!-- Datos ocultos para JS -->
            <script id="donacionesData" type="application/json">
            <?php echo json_encode($donaciones_arr, JSON_UNESCAPED_UNICODE); ?>
            </script>
            <script id="solicitudesData" type="application/json">
            <?php echo json_encode($solicitudes_arr, JSON_UNESCAPED_UNICODE); ?>
            </script>
        </div>

        <!-- ────────── PERFIL ────────── -->
<div id="perfil" class="tab-pane">
            <h2 class="page-title">Mi Perfil</h2>
            <div class="card card-perfil">
                <form action="../controller/acciones_asis_usuarios.php" method="POST" id="formPerfil"
                      onsubmit="return validarFormularioCompleto('formPerfil')">
                    <input type="hidden" name="idUsuario" value="<?php echo $asis_data['idUsuario']; ?>">

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Nombre completo</label>
                            <input type="text" name="nombre" class="form-input"
                                   value="<?php echo htmlspecialchars($asis_data['nombre']); ?>"
                                   required minlength="3" maxlength="100"
                                   pattern="[A-Za-záéíóúÁÉÍÓÚñÑüÜ\s]+"
                                   oninput="this.value = this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚñÑüÜ\s]/g, '')"
                                   title="Solo se permiten letras y espacios"
                                   placeholder="Digita tus nombres y apellidos completos">
                        </div>
                        <div class="form-group">
                            <label>Tipo de documento</label>
                            <select name="tipoDocumento" class="form-input" required>
                                <option value="" disabled>Selecciona tu tipo de documento</option>
                                <?php foreach(['CC','TI','CE','PEP'] as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo $asis_data['tipoDocumento']==$t?'selected':''; ?>><?php echo $t; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Número de documento</label>
                            <input type="text" name="numDocumento" class="form-input"
                                   value="<?php echo htmlspecialchars($asis_data['numDocumento']); ?>"
                                   required pattern="[0-9]{4,15}" maxlength="15"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                   title="Solo se permiten números"
                                   placeholder="Ingresa los dígitos de tu documento de identidad">
                        </div>
                        <div class="form-group">
                            <label>Fecha de nacimiento</label>
                            <input type="date" name="fechaNacimiento" class="form-input"
                                   value="<?php echo $asis_data['fechaNacimiento']; ?>"
                                   required max="<?php echo date('Y-m-d', strtotime('-5 years')); ?>">
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="tel" name="telefono" class="form-input"
                                   value="<?php echo htmlspecialchars($asis_data['telefono']); ?>"
                                   required pattern="[0-9]{10}" maxlength="10"
                                   oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                   title="Solo se permiten 10 números"
                                   placeholder="Digita tu número de teléfono celular">
                        </div>
                        <div class="form-group">
                            <label>Dirección</label>
                            <input type="text" name="direccion" class="form-input"
                                   value="<?php echo htmlspecialchars($asis_data['direccion']); ?>"
                                   required minlength="5" maxlength="255"
                                   placeholder="Escribe tu dirección de residencia actual">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-input"
                                   value="<?php echo htmlspecialchars($asis_data['email']); ?>" required
                                   placeholder="Ingresa tu correo electrónico institucional o personal"
                                   maxlength="100">
                        </div>
                    </div>

                    <hr class="section-divider">
                    <p class="text-muted"><i class="fa-solid fa-lock"></i> Cambiar contraseña (dejar en blanco para no cambiar)</p>

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Nueva contraseña</label>
                            <div class="pass-wrap">
                                <input type="password" name="password" id="perfil_pass" class="form-input"
                                       autocomplete="new-password" minlength="6" maxlength="20"
                                       title="Mínimo 6 caracteres"
                                       placeholder="Crea una nueva clave de seguridad">
                                <button type="button" class="eye-btn" onclick="togglePass('perfil_pass','perfil_eye')">
                                    <i class="fa-solid fa-eye" id="perfil_eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Confirmar contraseña</label>
                            <div class="pass-wrap">
                                <input type="password" name="password_confirm" id="perfil_pass2" class="form-input"
                                       autocomplete="new-password" minlength="6" maxlength="20"
                                       placeholder="Repite la nueva clave para confirmar">
                                <button type="button" class="eye-btn" onclick="togglePass('perfil_pass2','perfil_eye2')">
                                    <i class="fa-solid fa-eye" id="perfil_eye2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="perfil_pass_err" class="field-error" style="display:none;">Las contraseñas no coinciden.</div>

                    <button type="submit" name="update_perfil" class="btn btn-primary"
                            onclick="return validarPassPerfil()">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
                    </button>
                </form>
            </div>
        </div>

    </main>
</div><!-- /admin-wrapper -->

<!-- ═══════════════════════════════════════════
     MODALES
═══════════════════════════════════════════ -->

<!-- VER CLIENTE -->
<div id="modalVerDonante" class="modal">
<div class="modal-content modal-sm">
    <div class="modal-header">
        <h3><i class="fa-solid fa-user"></i> Detalle del Donante / Solicitante</h3>
        <button class="modal-close" onclick="cerrarModal('modalVerDonante')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="donante_detalle" class="donante-detalle"></div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalVerDonante')">Cerrar</button>
    </div>
</div>
</div>

<!-- GESTIONAR DONACIÓN -->
<div id="modalDonacion" class="modal">
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fa-solid fa-box-open"></i> Gestionar Donación</h3>
        <button class="modal-close" onclick="cerrarModal('modalDonacion')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="don_detalle" class="detalle-box"></div>
    <form action="../controller/acciones_asis_donapp.php" method="POST"
          onsubmit="return validarObservacionRequerida('don_estado','don_obs','don_obs_err')">
        <input type="hidden" name="id"   id="don_id">
        <input type="hidden" name="tipo" value="donacion">
        <div class="form-group">
            <label>Estado</label>
            <select name="nuevo_estado" id="don_estado" class="form-input"
                    onchange="actualizarHintObservacion('don_estado','don_obs_hint','don_obs','don_obs_err')">
                <option value="pendiente">Pendiente</option>
                <option value="aprobada">Aprobada</option>
                <option value="rechazada">Rechazada</option>
            </select>
        </div>
        <div class="form-group">
            <label>Observación <span id="don_obs_hint">(opcional)</span></label>
            <textarea name="observacion" id="don_obs" class="form-input" rows="3"
                      placeholder="Añade una observación o motivo..." maxlength="250"></textarea>
            <small id="don_obs_err" class="field-error" style="display:none;">
                <i class="fa-solid fa-triangle-exclamation"></i> La observación es obligatoria al rechazar.
            </small>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
            <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalDonacion')">Cancelar</button>
        </div>
    </form>
</div>
</div>

<!-- GESTIONAR SOLICITUD -->
<div id="modalSolicitud" class="modal">
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fa-solid fa-clipboard-list"></i> Gestionar Solicitud</h3>
        <button class="modal-close" onclick="cerrarModal('modalSolicitud')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="sol_detalle" class="detalle-box"></div>
    <form action="../controller/acciones_asis_donapp.php" method="POST"
          onsubmit="return validarObservacionRequerida('sol_estado','sol_obs','sol_obs_err')">
        <input type="hidden" name="id"   id="sol_id">
        <input type="hidden" name="tipo" value="solicitud">
        <div class="form-group">
            <label>Estado</label>
            <select name="nuevo_estado" id="sol_estado" class="form-input"
                    onchange="actualizarHintObservacion('sol_estado','sol_obs_hint','sol_obs','sol_obs_err')">
                <option value="pendiente">Pendiente</option>
                <option value="aprobada">Aprobada</option>
                <option value="rechazada">Rechazada</option>
            </select>
        </div>
        <div class="form-group">
            <label>Observación <span id="sol_obs_hint">(opcional)</span></label>
            <textarea name="observacion" id="sol_obs" class="form-input" rows="3"
                      placeholder="Añade una observación o motivo de rechazo..."maxlength="250"></textarea>
            <small id="sol_obs_err" class="field-error" style="display:none;">
                <i class="fa-solid fa-triangle-exclamation"></i> La observación es obligatoria al rechazar.
            </small>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
            <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalSolicitud')">Cancelar</button>
        </div>
    </form>
</div>
</div>

<!-- CREAR EVENTO -->
<div id="modalCrearEvento" class="modal">
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fa-solid fa-calendar-plus"></i> Publicar Nuevo Evento</h3>
        <button class="modal-close" onclick="cerrarModal('modalCrearEvento')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form action="../controller/acciones_asis_eventos.php" method="POST" enctype="multipart/form-data">
        <div class="form-grid" class="form-grid-2">
            <div class="form-group">
                <label>Nombre del Evento *</label>
                <input type="text" name="nombre_evento" class="form-input" required
                       placeholder="Ingresa el nombre del evento" maxlength="150">
            </div>
            <div class="form-group">
                <label>Estado</label>
                <select name="estado_evento" class="form-input">
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
        </div>

        <div class="form-grid" class="form-grid-2">
            <div class="form-group">
                <label>Fecha de la Entrega *</label>
                <input type="date" name="fecha_entrega" class="form-input" required>
            </div>
            <div class="form-group">
                <label>Lugar de Entrega *</label>
                <input type="text" name="lugar_entrega" class="form-input" required
                       placeholder="Ingresa la dirección del lugar de entrega"
                       maxlength="255"
                       value="Transversal 73 H Bis #75B 46 SUR Barrio Sierra Morena V Sector">
            </div>
        </div>

        <hr >

        <div class="form-group">
            <label>Título de la Publicación *</label>
            <input type="text" name="titulo_pub" class="form-input" required
                   placeholder="Ingresa el título de la publicación" maxlength="200">
        </div>
        <div class="form-group">
            <label>Contenido / Detalles *</label>
            <textarea name="contenido_pub" class="form-input" rows="3" required
                      placeholder="Describe los detalles del evento para los usuarios" 
                      maxlength="500"></textarea>
        </div>
        <div class="form-group">
            <label>Imagen (Opcional)</label>
            <input type="file" name="imagen_pub" class="form-input" accept="image/*">
        </div>

        <div class="modal-footer">
            <button type="submit" name="crear_evento_completo" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Crear y Publicar
            </button>
            <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalCrearEvento')">Cancelar</button>
        </div>
    </form>
</div>
</div>

<div id="modalEditarEvento" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><i class="fa-solid fa-calendar-check"></i> Editar Evento y Publicación</h3>
            <button type="button" class="modal-close" onclick="cerrarModal('modalEditarEvento')">&times;</button>
        </div>
        <form action="../controller/acciones_asis_eventos.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="idEvento" id="edit_idEvento">

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Nombre del Evento</label>
                    <input type="text" name="nombre" id="edit_nombre" class="form-input" required
                           placeholder="Ingresa el nombre del evento" maxlength="150">
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" id="edit_estado" class="form-input">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group">
                    <label>Fecha de Entrega</label>
                    <input type="date" name="fecha_entrega" id="edit_fecha_entrega" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Lugar</label>
                    <input type="text" name="lugar" id="edit_lugar_entrega" class="form-input" required
                           placeholder="Ingresa la dirección del lugar de entrega" maxlength="255">
                </div>
            </div>

            <hr >

            <div class="form-group">
                <label>Título de la Publicación</label>
                <input type="text" name="titulo_p" id="edit_titulo_pub" class="form-input" required
                       placeholder="Ingresa el título de la publicación" maxlength="200">
            </div>

            <div class="form-group" >
                <label>Contenido</label>
                <textarea name="contenido_p" id="edit_contenido_pub" class="form-input" rows="4" required
                          placeholder="Describe los detalles del evento para los usuarios" maxlength="500"></textarea>
            </div>

            <div class="form-group" >
                <label>Imagen de la Publicación</label>
                <div id="edit_img_preview_wrap" class="img-preview-wrap">
                    <p class="img-preview-label">
                        <i class="fa-solid fa-image"></i> Imagen actual:
                    </p>
                    <img id="edit_img_preview" src="" alt="Imagen actual"
                         class="img-current-preview">
                </div>
                <input type="file" name="imagen_pub" id="edit_imagen_pub" class="form-input" accept="image/*"
                       onchange="previewNuevaImagen(this)">
                <small class="text-muted">Deja vacío para mantener la imagen actual.</small>
                
                <img id="edit_nueva_img_preview" src="" alt="Nueva imagen"
                     class="img-new-preview" style="display:none;">
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditarEvento')">Cancelar</button>
                <button type="submit" name="editar_evento_completo" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- CREAR CLIENTE -->
<div id="modalCrearDonante" class="modal">
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fa-solid fa-user-plus"></i> Nuevo Donante / Solicitante</h3>
        <button class="modal-close" onclick="cerrarModal('modalCrearDonante')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form action="../controller/acciones_asis_usuarios.php" method="POST">
        <div class="form-grid-2">
            <div class="form-group">
                <label>Nombre completo *</label>
                <input type="text" name="nombre" class="form-input" required 
                       minlength="3" maxlength="100"
                       pattern="[A-Za-záéíóúÁÉÍÓÚñÑüÜ\s]+" 
                       oninput="this.value = this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚñÑüÜ\s]/g, '')"
                       title="Solo se permiten letras y espacios" 
                       placeholder="Digita los nombres y apellidos del cliente">
            </div>
            <div class="form-group">
                <label>Tipo de documento *</label>
                <select name="tipoDocumento" class="form-input" required>
                    <option value="" disabled selected>Selecciona el tipo de documento</option>
                    <?php foreach(['CC','TI','CE','PEP'] as $t): ?>
                    <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Número de documento *</label>
                <input type="text" name="numDocumento" class="form-input" required 
                       pattern="[0-9]{4,15}" maxlength="15"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                       title="Solo se permiten números (4 a 15 dígitos)"
                       placeholder="Ingresa los dígitos del documento de identidad">
            </div>
            <div class="form-group">
                <label>Fecha de nacimiento *</label>
                <input type="date" name="fechaNacimiento" class="form-input" required max="<?php echo date('Y-m-d', strtotime('-5 years')); ?>">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" class="form-input" required maxlength="150"
                       placeholder="Escribe el correo electrónico de contacto">
            </div>
            <div class="form-group">
                <label>Teléfono *</label>
                <input type="tel" name="telefono" class="form-input" required 
                       pattern="[0-9]{10}" maxlength="10"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                       title="Solo se permiten 10 números"
                       placeholder="Digita el número celular (10 dígitos)">
            </div>
            <div class="form-group">
                <label>Dirección *</label>
                <input type="text" name="direccion" class="form-input" required minlength="5" maxlength="255"
                       placeholder="Ingresa la dirección de residencia del cliente">
            </div>
            <div class="form-group">
                <label>Necesidad</label>
                <input type="text" name="necesidad" class="form-input" maxlength="255" 
                       placeholder="Describe la necesidad principal (Opcional)">
            </div>
            <div class="form-group">
                <label class="form-label">Prioridad</label>
                <select name="prioridad" class="form-input">
                    <option value="">Sin prioridad</option>
                    <option value="alta">Alta</option>
                    <option value="media">Media</option>
                    <option value="baja">Baja</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Observación de visita</label>
            <textarea name="observacion_visita" class="form-input" rows="3" maxlength="500"
                      placeholder="Anota observaciones relevantes de la visita (Opcional)"></textarea>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Contraseña *</label>
                <div class="pass-wrap">
                    <input type="password" name="password" id="crear_cli_pass" class="form-input" required minlength="6" maxlength="20"
                           placeholder="Asigna una clave de acceso">
                    <button type="button" class="eye-btn" onclick="togglePass('crear_cli_pass','crear_cli_eye')"><i class="fa-solid fa-eye" id="crear_cli_eye"></i></button>
                </div>
            </div>
            <div class="form-group">
                <label>Confirmar contraseña *</label>
                <div class="pass-wrap">
                    <input type="password" name="password_confirm" id="crear_cli_pass2" class="form-input" required minlength="6" maxlength="20"
                           placeholder="Repite la clave de acceso">
                    <button type="button" class="eye-btn" onclick="togglePass('crear_cli_pass2','crear_cli_eye2')"><i class="fa-solid fa-eye" id="crear_cli_eye2"></i></button>
                </div>
            </div>
        </div>
        <div id="crear_cli_pass_err" class="field-error" style="display:none;">Las contraseñas no coinciden.</div>
        <div class="modal-footer">
            <button type="submit" name="crear_cliente" class="btn btn-primary"
                    onclick="return validarPassModal('crear_cli_pass','crear_cli_pass2','crear_cli_pass_err')">
                <i class="fa-solid fa-floppy-disk"></i> Crear Cliente
            </button>
            <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalCrearDonante')">Cancelar</button>
        </div>
    </form>
</div>
</div>

<!-- EDITAR CLIENTE -->
<div id="modalEditarDonante" class="modal">
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fa-solid fa-user-pen"></i> Editar Cliente</h3>
        <button class="modal-close" onclick="cerrarModal('modalEditarDonante')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form action="../controller/acciones_asis_usuarios.php" method="POST">
        <input type="hidden" name="idUsuario" id="edit_cli_id">
        <div class="form-grid-2">
            <div class="form-group">
                <label>Nombre completo *</label>
                <input type="text" name="nombre" id="edit_cli_nombre" class="form-input" required 
                       minlength="3" maxlength="100"
                       pattern="[A-Za-záéíóúÁÉÍÓÚñÑüÜ\s]+" 
                       oninput="this.value = this.value.replace(/[^A-Za-záéíóúÁÉÍÓÚñÑüÜ\s]/g, '')"
                       title="Solo se permiten letras y espacios"
                       placeholder="Actualiza los nombres y apellidos">
            </div>
            <div class="form-group">
                <label>Tipo de documento *</label>
                <select name="tipoDocumento" id="edit_cli_tipoDoc" class="form-input" required>
                    <option value="" disabled>Selecciona el tipo de documento</option>
                    <?php foreach(['CC','TI','CE','PEP'] as $t): ?>
                    <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Número de documento *</label>
                <input type="text" name="numDocumento" id="edit_cli_numDoc" class="form-input" required 
                       pattern="[0-9]{4,15}" maxlength="15"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                       placeholder="Modifica los dígitos del documento">
            </div>
            <div class="form-group">
                <label>Fecha de nacimiento *</label>
                <input type="date" name="fechaNacimiento" id="edit_cli_fechaNac" class="form-input" required max="<?php echo date('Y-m-d', strtotime('-5 years')); ?>">
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" id="edit_cli_email" class="form-input" required maxlength="150"
                       placeholder="Actualiza el correo electrónico">
            </div>
            <div class="form-group">
                <label>Teléfono *</label>
                <input type="tel" name="telefono" id="edit_cli_telefono" class="form-input" required 
                       pattern="[0-9]{10}" maxlength="10"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                       placeholder="Ingresa el nuevo número telefónico">
            </div>
            <div class="form-group">
                <label>Dirección *</label>
                <input type="text" name="direccion" id="edit_cli_direccion" class="form-input" required minlength="5" maxlength="255"
                       placeholder="Escribe la nueva dirección de residencia">
            </div>
            <div class="form-group">
                <label>Necesidad</label>
                <input type="text" name="necesidad" id="edit_cli_necesidad" class="form-input" maxlength="255"
                       placeholder="Actualiza la descripción de la necesidad">
            </div>
            <div class="form-group">
                <label>Prioridad</label>
                <select name="prioridad" id="edit_cli_prioridad" class="form-input">
                    <option value="">Sin prioridad</option>
                    <option value="alta">Alta</option>
                    <option value="media">Media</option>
                    <option value="baja">Baja</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nueva contraseña <small>(dejar en blanco para no cambiar)</small></label>
                <div class="pass-wrap">
                    <input type="password" name="password" id="edit_cli_pass" class="form-input" minlength="6" maxlength="20" autocomplete="new-password"
                           placeholder="Escribe una nueva clave de seguridad">
                    <button type="button" class="eye-btn" onclick="togglePass('edit_cli_pass','edit_cli_eye')"><i class="fa-solid fa-eye" id="edit_cli_eye"></i></button>
                </div>
            </div>
            <div class="form-group">
                <label>Confirmar contraseña</label>
                <div class="pass-wrap">
                    <input type="password" name="password_confirm" id="edit_cli_pass2" class="form-input" minlength="6" maxlength="20" autocomplete="new-password"
                           placeholder="Repite la nueva clave para confirmar">
                    <button type="button" class="eye-btn" onclick="togglePass('edit_cli_pass2','edit_cli_eye2')"><i class="fa-solid fa-eye" id="edit_cli_eye2"></i></button>
                </div>
            </div>
        </div>
        <div class="form-group">
            <label>Observación de visita</label>
            <textarea name="observacion_visita" id="edit_cli_obs" class="form-input" rows="3" maxlength="500"
                      placeholder="Anota observaciones relevantes de la visita (Opcional)"></textarea>
        </div>
        <div id="edit_cli_pass_err" class="field-error" style="display:none;">Las contraseñas no coinciden.</div>
        <div class="modal-footer">
            <button type="submit" name="editar_cliente" class="btn btn-primary"
                    onclick="return validarPassModal('edit_cli_pass','edit_cli_pass2','edit_cli_pass_err')">
                <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
            </button>
            <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditarDonante')">Cancelar</button>
        </div>
    </form>
</div>
</div>

<!-- CREAR CATEGORÍA -->
<div id="modalCrearCategoria" class="modal">
    <div class="modal-content modal-xs">
        <div class="modal-header">
            <h3><i class="fa-solid fa-tag"></i> Nueva Categoría</h3>
            <button class="modal-close" onclick="cerrarModal('modalCrearCategoria')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="../controller/acciones_asis_categorias.php" method="POST">
            <div class="form-group">
                <label>Nombre de la categoría *</label>
                <input type="text" 
                       name="nombre_categoria" 
                       id="asis_cat_nombre" 
                       class="form-input" 
                       required 
                       minlength="3" 
                       maxlength="100"
                       placeholder="Ingresa el nombre de la categoría"
                       oninput="validarCategoriaAsistente(this)"> <small id="asis_cat_err" class="field-error" style="display:none;"></small>
            </div>
            <div class="modal-footer">
                <button type="submit" name="crear_categoria" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Crear</button>
                <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalCrearCategoria')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- EDITAR CATEGORÍA -->
<div id="modalEditarCategoria" class="modal">
<div class="modal-content modal-xs">
    <div class="modal-header">
        <h3><i class="fa-solid fa-tag"></i> Editar Categoría</h3>
        <button class="modal-close" onclick="cerrarModal('modalEditarCategoria')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form action="../controller/acciones_asis_categorias.php" method="POST">
        <input type="hidden" name="idCategoria" id="edit_cat_id">
        <div class="form-group">
    <label>Nombre de la categoría *</label>
    <input type="text" 
           name="nombre_categoria" 
           id="edit_cat_nombre" 
           class="form-input" 
           required 
           minlength="3" 
           maxlength="100"
           oninput="validarCategoriaAsistente(this)"> <small id="edit_cat_err" class="field-error" style="display:none;"></small>
</div>
        <div class="modal-footer">
            <button type="submit" name="editar_categoria" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
            <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditarCategoria')">Cancelar</button>
        </div>
    </form>
</div>
</div>

<!-- ═══════════════════════════════════════════
     SCRIPTS
═══════════════════════════════════════════ -->
<script src="../assets/js/asistente.js"></script>
</body>
</html>