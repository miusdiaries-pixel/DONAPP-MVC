<?php
session_start();
include '../config/conexion.php';

// 1. Verificación básica de sesión
if (!isset($_SESSION['idUsuario'])) {
    header("Location: ../index.php");
    exit();
}

$idUsuarioActual = $_SESSION['idUsuario'];

// 2. VALIDACIÓN EN TIEMPO REAL Y OBTENCIÓN DE DATOS
// Usamos SELECT * porque el cliente suele necesitar sus datos para el perfil
$stmt = $conn->prepare("SELECT * FROM usuario WHERE idUsuario = ?");
$stmt->bind_param("i", $idUsuarioActual);
$stmt->execute();
$res = $stmt->get_result();
$usuario = $res->fetch_assoc();
$cliente = $usuario; // alias para retrocompatibilidad

// 3. LÓGICA DE EXPULSIÓN INTEGRAL
if (
    !$usuario || 
    $usuario['rol'] !== 'donante' || 
    $usuario['estado'] !== 'activo' ||
        (isset($_SESSION['password_hash']) && $_SESSION['password_hash'] !== $usuario['contrasena'])
) {
    session_unset();
    session_destroy();
    header("Location: ../index.php?error=acceso_denegado");
    exit();
}

$idCliente = $idUsuarioActual; 
// ── SORT HELPERS ─────────────────────────────────────────────────────────
function get_sort($param, $allowed, $default) {
    $col = isset($_GET[$param]) ? $_GET[$param] : $default;
    return in_array($col, $allowed) ? $col : $default;
}
function get_dir($param) {
    return (isset($_GET[$param]) && $_GET[$param] === 'ASC') ? 'ASC' : 'DESC';
}
function sort_toggle_url($col_param, $dir_param, $col) {
    $params = $_GET;
        if (!isset($params['tab'])) {
        if (isset($_GET['don_sort']) || isset($_GET['don_estado']) || isset($_GET['don_cat']) || isset($_GET['don_buscar'])) {
            $params['tab'] = 'donaciones';
        } elseif (isset($_GET['sol_sort']) || isset($_GET['sol_estado']) || isset($_GET['sol_cat']) || isset($_GET['sol_buscar'])) {
            $params['tab'] = 'solicitudes';
        }
    }
    $cur_col = $params[$col_param] ?? '';
    $cur_dir = (isset($params[$dir_param]) && $params[$dir_param] === 'ASC') ? 'ASC' : 'DESC';
    $params[$col_param] = $col;
    $params[$dir_param] = ($cur_col === $col && $cur_dir === 'ASC') ? 'DESC' : 'ASC';
    return '?' . http_build_query($params);
}
function sort_icon($col_param, $dir_param, $col) {
    $cur_col = $_GET[$col_param] ?? '';
    $cur_dir = (isset($_GET[$dir_param]) && $_GET[$dir_param] === 'ASC') ? 'ASC' : 'DESC';
    $active  = ($cur_col === $col);
    if (!$active) return '<i class="fa-solid fa-sort" style="opacity:.35;font-size:.75rem;margin-left:3px;"></i>';
    return $cur_dir === 'ASC'
        ? '<i class="fa-solid fa-sort-up" style="font-size:.75rem;margin-left:3px;color:#d32f2f;"></i>'
        : '<i class="fa-solid fa-sort-down" style="font-size:.75rem;margin-left:3px;color:#d32f2f;"></i>';
}

// ── MIS DONACIONES ───────────────────────────────────────────────────────
$don_estado_f = isset($_GET['don_estado']) ? $conn->real_escape_string($_GET['don_estado']) : '';
$don_cat_f    = isset($_GET['don_cat'])    ? (int)$_GET['don_cat']                          : 0;
$don_buscar_f = isset($_GET['don_buscar']) ? $conn->real_escape_string(trim($_GET['don_buscar'])) : '';

$allowed_don_sort = ['idDonacion','descripcion','estado','fechaCreacion','stock'];
$don_sort = get_sort('don_sort', $allowed_don_sort, 'fechaCreacion');
$don_dir  = get_dir('don_dir');

$don_where = "WHERE du.idDonante = $idCliente";
if ($don_estado_f)  $don_where .= " AND d.estado = '$don_estado_f'";
if ($don_cat_f > 0) $don_where .= " AND d.idCategoria = $don_cat_f";
if ($don_buscar_f)  $don_where .= " AND d.descripcion LIKE '%$don_buscar_f%'";

$res_mis_donaciones = $conn->query("
    SELECT d.*, c.nombre AS categoria, du.FechaCreacion AS fechaDonante
    FROM DonacionUsuario du
    INNER JOIN donacion d  ON du.idDonacion  = d.idDonacion
    LEFT  JOIN categoria c ON d.idCategoria  = c.idCategoria
    $don_where
    ORDER BY d.$don_sort $don_dir
");
$mis_donaciones = [];
while ($row = $res_mis_donaciones->fetch_assoc()) {
    if (!empty($row['imagen'])) {
        $row['imagen'] = 'data:image/jpeg;base64,' . base64_encode($row['imagen']);
    }
    $mis_donaciones[] = $row;
}

// ── MIS SOLICITUDES ──────────────────────────────────────────────────────
$sol_estado_f = isset($_GET['sol_estado']) ? $conn->real_escape_string($_GET['sol_estado']) : '';
$sol_cat_f    = isset($_GET['sol_cat'])    ? (int)$_GET['sol_cat']                           : 0;
$sol_buscar_f = isset($_GET['sol_buscar']) ? $conn->real_escape_string(trim($_GET['sol_buscar'])) : '';

$allowed_sol_sort = ['idSolicitud','descripcion','estado','fechaCreacion'];
$sol_sort = get_sort('sol_sort', $allowed_sol_sort, 'fechaCreacion');
$sol_dir  = get_dir('sol_dir');

$sol_where = "WHERE s.idSolicitante = $idCliente";
if ($sol_estado_f)  $sol_where .= " AND s.estado = '$sol_estado_f'";
if ($sol_cat_f > 0) $sol_where .= " AND s.idCategoria = $sol_cat_f";
if ($sol_buscar_f)  $sol_where .= " AND s.descripcion LIKE '%$sol_buscar_f%'";

$res_mis_solicitudes = $conn->query("
    SELECT s.*, c.nombre AS categoria
    FROM solicitud s
    LEFT JOIN categoria c ON s.idCategoria = c.idCategoria
    $sol_where
    ORDER BY s.$sol_sort $sol_dir
");
$mis_solicitudes = [];
while ($row = $res_mis_solicitudes->fetch_assoc()) {
    if (!empty($row['imagen'])) {
        $row['imagen'] = 'data:image/jpeg;base64,' . base64_encode($row['imagen']);
    }
    $mis_solicitudes[] = $row;
}

// ── EVENTOS ACTIVOS ──────────────────────────────────────────────────────
$res_eventos = $conn->query("
    SELECT e.idEvento, e.Nombre, e.estado,
           pe.FechaEntrega, pe.Lugar,
           p.titulo, p.contenido, p.imagen, p.fechaPublicacion
    FROM evento e
    LEFT JOIN ProgramadorEventos pe ON pe.idProgramadorEventos = (
        SELECT idProgramadorEventos FROM ProgramadorEventos
        WHERE idEvento = e.idEvento ORDER BY idProgramadorEventos DESC LIMIT 1
    )
    LEFT JOIN publicacion p ON p.idPublicacion = (
        SELECT idPublicacion FROM publicacion
        WHERE idEvento = e.idEvento ORDER BY idPublicacion DESC LIMIT 1
    )
    WHERE e.estado = 'activo'
    ORDER BY pe.FechaEntrega ASC
");
$eventos = [];
while ($row = $res_eventos->fetch_assoc()) {
    if (!empty($row['imagen'])) {
        $row['imagen'] = 'data:image/jpeg;base64,' . base64_encode($row['imagen']);
    }
    $eventos[] = $row;
}

// ── CATEGORÍAS ────────────────────────────────────────────────────────────
$res_cats = $conn->query("SELECT idCategoria, nombre FROM categoria ORDER BY nombre");
$categorias = [];
while ($c = $res_cats->fetch_assoc()) $categorias[] = $c;

// ── STATS DEL CLIENTE ────────────────────────────────────────────────────
$stats = $conn->query("
    SELECT
        (SELECT COUNT(*) FROM DonacionUsuario du WHERE du.idDonante = $idCliente) AS total_don,
        (SELECT COUNT(*) FROM DonacionUsuario du
         INNER JOIN donacion d ON du.idDonacion = d.idDonacion
         WHERE du.idDonante = $idCliente AND d.estado = 'pendiente') AS don_pendientes,
        (SELECT COUNT(*) FROM DonacionUsuario du
         INNER JOIN donacion d ON du.idDonacion = d.idDonacion
         WHERE du.idDonante = $idCliente AND d.estado = 'aprobada') AS don_aprobadas,
        (SELECT COUNT(*) FROM solicitud WHERE idSolicitante = $idCliente) AS total_sol,
        (SELECT COUNT(*) FROM solicitud WHERE idSolicitante = $idCliente AND estado = 'pendiente') AS sol_pendientes,
        (SELECT COUNT(*) FROM solicitud WHERE idSolicitante = $idCliente AND estado = 'aprobada') AS sol_aprobadas
")->fetch_assoc();

// ── TAB ACTIVO (detectado por PHP) ───────────────────────────────────────
$tabs_validos = ['inicio','donaciones','solicitudes','eventos','perfil'];
$tab_activo = 'inicio';
if (isset($_GET['tab']) && in_array($_GET['tab'], $tabs_validos)) {
    $tab_activo = $_GET['tab'];
} elseif (isset($_SESSION['flash_tab']) && in_array($_SESSION['flash_tab'], $tabs_validos)) {
    $tab_activo = $_SESSION['flash_tab'];
    unset($_SESSION['flash_tab']);
}

// ── FLASH MSG ─────────────────────────────────────────────────────────────
$flash = '';
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>