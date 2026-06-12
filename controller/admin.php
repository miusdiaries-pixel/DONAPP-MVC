<?php
/**
 * CONTROLADOR: admin_dashboard_controller.php
 * Responsabilidad: autenticación, filtros, consultas a BD y preparación de datos.
 * No contiene HTML, CSS ni JS.
 */

session_start();
include '../config/conexion.php';

// ─────────────────────────────────────────────
// 1. VERIFICACIÓN BÁSICA DE SESIÓN
// ─────────────────────────────────────────────
if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../index.php");
    exit();
}

$idUsuarioActual = $_SESSION['idUsuario'];

// ─────────────────────────────────────────────
// 2. VALIDACIÓN EN TIEMPO REAL
// ─────────────────────────────────────────────
$check = $conn->prepare("SELECT rol, estado, contrasena FROM usuario WHERE idUsuario = ?");
if (!$check) {
    die("Error en SQL: " . $conn->error);
}
$check->bind_param("i", $idUsuarioActual);
$check->execute();
$resCheck = $check->get_result();
$dataCheck = $resCheck->fetch_assoc();

// ─────────────────────────────────────────────
// 3. LÓGICA DE EXPULSIÓN
// ─────────────────────────────────────────────
if (
    !$dataCheck ||
    $dataCheck['rol'] !== 'administrador' ||
    $dataCheck['estado'] !== 'activo' ||
    (isset($_SESSION['password_hash']) && $_SESSION['password_hash'] !== $dataCheck['contrasena'])
) {
    session_unset();
    session_destroy();
    header("Location: ../index.php?error=acceso_denegado");
    exit();
}

$idAdmin = $idUsuarioActual;

// ─────────────────────────────────────────────
// 4. FILTROS DE BÚSQUEDA
// ─────────────────────────────────────────────

// Usuarios
$search           = isset($_GET['search'])     ? $conn->real_escape_string(trim($_GET['search'])) : '';
$filtro_rol       = isset($_GET['rol'])         ? $conn->real_escape_string($_GET['rol'])           : '';
$filtro_prioridad = isset($_GET['prioridad'])   ? $conn->real_escape_string($_GET['prioridad'])     : '';

// Categorías
$cat_search = isset($_GET['cat_search']) ? $conn->real_escape_string(trim($_GET['cat_search'])) : '';

// Donaciones
$don_search = isset($_GET['don_search']) ? $conn->real_escape_string(trim($_GET['don_search'])) : '';
$don_estado = isset($_GET['don_estado']) ? $conn->real_escape_string($_GET['don_estado'])        : '';
$don_cat    = isset($_GET['don_cat'])    ? (int)$_GET['don_cat']                                 : 0;

// Solicitudes
$sol_search = isset($_GET['sol_search']) ? $conn->real_escape_string(trim($_GET['sol_search'])) : '';
$sol_estado = isset($_GET['sol_estado']) ? $conn->real_escape_string($_GET['sol_estado'])        : '';
$sol_cat    = isset($_GET['sol_cat'])    ? (int)$_GET['sol_cat']                                 : 0;

// Eventos
$ev_search = isset($_GET['ev_search']) ? $conn->real_escape_string(trim($_GET['ev_search'])) : '';
$ev_estado = isset($_GET['ev_estado']) ? $conn->real_escape_string($_GET['ev_estado'])        : '';

// ─────────────────────────────────────────────
// 5. HELPERS DE ORDENAMIENTO
// ─────────────────────────────────────────────

$allowed_usr_sort = ['idUsuario', 'nombre', 'rol', 'estado'];
$allowed_cat_sort = ['nombre', 'idCategoria'];
$allowed_don_sort = ['idDonacion', 'descripcion', 'estado', 'fechaCreacion'];
$allowed_sol_sort = ['idSolicitud', 'descripcion', 'estado', 'fechaCreacion'];
$allowed_ev_sort  = ['idEvento', 'Nombre', 'estado'];

function get_sort($param, $allowed, $default) {
    $col = isset($_GET[$param]) ? $_GET[$param] : $default;
    return in_array($col, $allowed) ? $col : $default;
}

function get_dir($param) {
    return (isset($_GET[$param]) && $_GET[$param] === 'ASC') ? 'ASC' : 'DESC';
}

function sort_toggle_url($col_param, $dir_param, $col) {
    $params   = $_GET;
    $cur_col  = $params[$col_param] ?? '';
    $cur_dir  = (isset($params[$dir_param]) && $params[$dir_param] === 'ASC') ? 'ASC' : 'DESC';
    $new_dir  = ($cur_col === $col && $cur_dir === 'ASC') ? 'DESC' : 'ASC';
    $params[$col_param] = $col;
    $params[$dir_param] = $new_dir;
    return '?' . http_build_query($params);
}

function sort_icon($col_param, $dir_param, $col) {
    $active = (isset($_GET[$col_param]) && $_GET[$col_param] === $col);
    $dir    = (isset($_GET[$dir_param]) && $_GET[$dir_param] === 'ASC') ? 'ASC' : 'DESC';
    if (!$active) {
        return '<i class="fa-solid fa-sort" style="opacity:.35;font-size:.75rem;margin-left:3px;"></i>';
    }
    return $dir === 'ASC'
        ? '<i class="fa-solid fa-sort-up"   style="font-size:.75rem;margin-left:3px;color:#d32f2f;"></i>'
        : '<i class="fa-solid fa-sort-down" style="font-size:.75rem;margin-left:3px;color:#d32f2f;"></i>';
}

$usr_sort = get_sort('usr_sort', $allowed_usr_sort, 'idUsuario');
$usr_dir  = get_dir('usr_dir');
$cat_sort = get_sort('cat_sort', $allowed_cat_sort, 'nombre');
$cat_dir  = get_dir('cat_dir');
$don_sort = get_sort('don_sort', $allowed_don_sort, 'idDonacion');
$don_dir  = get_dir('don_dir');
$sol_sort = get_sort('sol_sort', $allowed_sol_sort, 'idSolicitud');
$sol_dir  = get_dir('sol_dir');
$ev_sort  = get_sort('ev_sort',  $allowed_ev_sort,  'idEvento');
$ev_dir   = get_dir('ev_dir');

// ─────────────────────────────────────────────
// 6. STATS GLOBALES
// ─────────────────────────────────────────────
$total_usuarios    = $conn->query("SELECT COUNT(*) AS c FROM usuario")->fetch_assoc()['c'];
$total_donaciones  = $conn->query("SELECT COUNT(*) AS c FROM donacion")->fetch_assoc()['c'];
$total_solicitudes = $conn->query("SELECT COUNT(*) AS c FROM solicitud")->fetch_assoc()['c'];
$total_eventos     = $conn->query("SELECT COUNT(*) AS c FROM evento")->fetch_assoc()['c'];
$total_aprobadas   = $conn->query("SELECT COUNT(*) AS c FROM donacion WHERE estado='aprobada'")->fetch_assoc()['c'];

// ─────────────────────────────────────────────
// 7. LISTADO USUARIOS
// ─────────────────────────────────────────────
$mostrar_prioridad = ($filtro_rol === '' || $filtro_rol === 'donante');

$sql_users = "SELECT * FROM usuario WHERE (nombre LIKE '%$search%' OR email LIKE '%$search%')";
if ($filtro_rol !== '')                            $sql_users .= " AND rol = '$filtro_rol'";
if ($mostrar_prioridad && $filtro_prioridad !== '') $sql_users .= " AND prioridad = '$filtro_prioridad'";
$sql_users .= " ORDER BY $usr_sort $usr_dir";
$res_usuarios = $conn->query($sql_users);

// ─────────────────────────────────────────────
// 8. DONACIONES
// ─────────────────────────────────────────────
$don_where = "WHERE 1=1";
if ($don_search)  $don_where .= " AND (d.descripcion LIKE '%$don_search%' OR u.nombre LIKE '%$don_search%')";
if ($don_estado)  $don_where .= " AND d.estado = '$don_estado'";
if ($don_cat > 0) $don_where .= " AND d.idCategoria = $don_cat";

$res_donaciones = $conn->query("
    SELECT d.*, c.nombre AS categoria, u.nombre AS donante
    FROM donacion d
    LEFT JOIN categoria c         ON d.idCategoria   = c.idCategoria
    LEFT JOIN DonacionUsuario du  ON d.idDonacion    = du.idDonacion
    LEFT JOIN usuario u           ON du.idDonante    = u.idUsuario
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

// ─────────────────────────────────────────────
// 9. SOLICITUDES
// ─────────────────────────────────────────────
$sol_where = "WHERE 1=1";
if ($sol_search) {
    $sol_where .= " AND (s.descripcion LIKE '%$sol_search%'
                     OR us.nombre LIKE '%$sol_search%'
                     OR ug.nombre LIKE '%$sol_search%')";
}
if ($sol_estado)  $sol_where .= " AND s.estado = '$sol_estado'";
if ($sol_cat > 0) $sol_where .= " AND s.idCategoria = $sol_cat";

$res_solicitudes = $conn->query("
    SELECT s.*, c.nombre AS categoria,
           us.nombre AS nombre_solicitante,
           ug.nombre AS nombre_gestor
    FROM solicitud s
    LEFT JOIN categoria c ON s.idCategoria   = c.idCategoria
    LEFT JOIN usuario us  ON s.idSolicitante = us.idUsuario
    LEFT JOIN usuario ug  ON s.idGestor      = ug.idUsuario
    $sol_where
    ORDER BY s.$sol_sort $sol_dir
");

$solicitudes_procesadas = [];
while ($row = $res_solicitudes->fetch_assoc()) {
    if (!empty($row['imagen'])) {
        $row['imagen'] = 'data:image/jpeg;base64,' . base64_encode($row['imagen']);
    }
    $solicitudes_procesadas[] = $row;
}

// ─────────────────────────────────────────────
// 10. EVENTOS
// ─────────────────────────────────────────────
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

// ─────────────────────────────────────────────
// 11. CATEGORÍAS
// ─────────────────────────────────────────────
$cat_sql = "SELECT c.*, u.nombre AS creador FROM categoria c LEFT JOIN usuario u ON c.idUsuario = u.idUsuario";
if ($cat_search) $cat_sql .= " WHERE c.nombre LIKE '%$cat_search%'";
$cat_sql .= " ORDER BY c.$cat_sort $cat_dir";

$res_categorias     = $conn->query($cat_sql);
$res_categorias_sel = $conn->query("SELECT * FROM categoria ORDER BY nombre");
$total_categorias   = $conn->query("SELECT COUNT(*) AS c FROM categoria")->fetch_assoc()['c'];

// ─────────────────────────────────────────────
// 12. DATOS REPORTES PDF (usados en vista como JSON inline)
// ─────────────────────────────────────────────
$res_don_rpt = $conn->query("
    SELECT d.idDonacion, d.descripcion, d.estado, d.stock,
           d.observacion, d.fechaCreacion, c.nombre AS categoria,
           u.nombre AS donante
    FROM donacion d
    LEFT JOIN categoria c        ON d.idCategoria = c.idCategoria
    LEFT JOIN DonacionUsuario du ON d.idDonacion  = du.idDonacion
    LEFT JOIN usuario u          ON du.idDonante  = u.idUsuario
    ORDER BY d.idDonacion DESC
");
$donaciones_rpt = [];
while ($row = $res_don_rpt->fetch_assoc()) $donaciones_rpt[] = $row;

$res_sol_rpt = $conn->query("
    SELECT s.idSolicitud, s.descripcion, s.estado,
           s.observacion, s.fechaCreacion, c.nombre AS categoria,
           us.nombre AS solicitante
    FROM solicitud s
    LEFT JOIN categoria c  ON s.idCategoria  = c.idCategoria
    LEFT JOIN usuario us   ON s.idSolicitante = us.idUsuario
    ORDER BY s.idSolicitud DESC
");
$solicitudes_rpt = [];
while ($row = $res_sol_rpt->fetch_assoc()) $solicitudes_rpt[] = $row;

// ─────────────────────────────────────────────
// 13. PERFIL ADMIN
// ─────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM usuario WHERE idUsuario = ?");
$stmt->bind_param("i", $idAdmin);
$stmt->execute();
$admin_data = $stmt->get_result()->fetch_assoc();

// ─────────────────────────────────────────────
// 14. MENSAJES FLASH
// ─────────────────────────────────────────────
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