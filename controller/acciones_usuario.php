<?php
session_start();
include '../config/conexion.php';

// ── Protección de rol ──────────────────────────────────────────────────────
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'donante') {
    header("Location: ../index.php");
    exit();
}

$idUsuario = $_SESSION['idUsuario'];
$idCliente = $idUsuario; // Puede actuar como donante y/o solicitante

function redir($url, $msg = '') {
    if ($msg) $_SESSION['flash'] = $msg;
    header("Location: $url");
    exit();
}

// ════════════════════════════════════════════════════════════════════════════
// CREAR DONACIÓN
// ════════════════════════════════════════════════════════════════════════════
if (isset($_POST['crear_donacion'])) {
    $descripcion = trim($_POST['descripcion']);
    $idCategoria = (int) $_POST['idCategoria'];
    $stock       = (int) $_POST['stock'];

    // Validaciones
    if (empty($descripcion) || strlen($descripcion) < 5) {
        redir('../view/user_dashboard.php?tab=donaciones', 'La descripción debe tener al menos 5 caracteres.');
    }
    if ($idCategoria <= 0) {
        redir('../view/user_dashboard.php?tab=donaciones', 'Debes seleccionar una categoría.');
    }
    if ($stock < 1 || $stock > 9999) {
        redir('../view/user_dashboard.php?tab=donaciones', 'La cantidad debe estar entre 1 y 9999.');
    }

    // Verificar que la categoría existe
    $chkCat = $conn->prepare("SELECT idCategoria FROM categoria WHERE idCategoria = ?");
    $chkCat->bind_param("i", $idCategoria);
    $chkCat->execute();
    if ($chkCat->get_result()->num_rows === 0) {
        redir('../view/user_dashboard.php?tab=donaciones', 'La categoría seleccionada no es válida.');
    }

    // Procesar imagen
    $imgData = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $check = getimagesize($_FILES['imagen']['tmp_name']);
        if ($check !== false) {
            $imgData = file_get_contents($_FILES['imagen']['tmp_name']);
        } else {
            redir('../view/user_dashboard.php?tab=donaciones', 'El archivo de imagen no es válido.');
        }
    }

    $conn->begin_transaction();
    try {
        // 1. Insertar donación
        $stmtD = $conn->prepare("
            INSERT INTO donacion (descripcion, imagen, estado, stock, idCategoria)
            VALUES (?, ?, 'pendiente', ?, ?)
        ");
        $null = null;
        $stmtD->bind_param("sbii", $descripcion, $null, $stock, $idCategoria);
        if ($imgData) $stmtD->send_long_data(1, $imgData);
        $stmtD->execute();
        $idDonacion = $conn->insert_id;

        // 2. Vincular donación al cliente en DonacionUsuario
        $fechaHoy = date('Y-m-d');
        $stmtDU = $conn->prepare("
            INSERT INTO DonacionUsuario (FechaCreacion, idDonante, idDonacion)
            VALUES (?, ?, ?)
        ");
        $stmtDU->bind_param("sii", $fechaHoy, $idCliente, $idDonacion);
        $stmtDU->execute();

        $conn->commit();
        redir('../view/user_dashboard.php?tab=donaciones', '¡Donación registrada correctamente! Queda pendiente de revisión.');
    } catch (Exception $e) {
        $conn->rollback();
        redir('../view/user_dashboard.php?tab=donaciones', 'Error al registrar la donación: ' . $e->getMessage());
    }
}

// ════════════════════════════════════════════════════════════════════════════
// EDITAR DONACIÓN (solo si está pendiente y pertenece al cliente)
// ════════════════════════════════════════════════════════════════════════════
if (isset($_POST['editar_donacion'])) {
    $idDonacion  = (int) $_POST['idDonacion'];
    $descripcion = trim($_POST['descripcion']);
    $idCategoria = (int) $_POST['idCategoria'];
    $stock       = (int) $_POST['stock'];

    // Verificar pertenencia y estado
    $chk = $conn->query("
        SELECT d.idDonacion, d.estado
        FROM donacion d
        INNER JOIN DonacionUsuario du ON d.idDonacion = du.idDonacion
        WHERE d.idDonacion = $idDonacion
          AND du.idDonante = $idCliente
          AND d.estado     = 'pendiente'
    ");
    if (!$chk || $chk->num_rows === 0) {
        redir('../view/user_dashboard.php?tab=donaciones', 'No puedes editar esta donación. Solo se pueden editar donaciones pendientes propias.');
    }

    if (empty($descripcion) || strlen($descripcion) < 5) {
        redir('../view/user_dashboard.php?tab=donaciones', 'La descripción debe tener al menos 5 caracteres.');
    }
    if ($idCategoria <= 0) {
        redir('../view/user_dashboard.php?tab=donaciones', 'Debes seleccionar una categoría.');
    }
    if ($stock < 1 || $stock > 9999) {
        redir('../view/user_dashboard.php?tab=donaciones', 'La cantidad debe estar entre 1 y 9999.');
    }

    // Procesar nueva imagen si se subió
    $imgData = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $check = getimagesize($_FILES['imagen']['tmp_name']);
        if ($check !== false) {
            $imgData = file_get_contents($_FILES['imagen']['tmp_name']);
        }
    }

    if ($imgData) {
        $stmt = $conn->prepare("UPDATE donacion SET descripcion=?, imagen=?, stock=?, idCategoria=? WHERE idDonacion=?");
        $null = null;
        $stmt->bind_param("sbiii", $descripcion, $null, $stock, $idCategoria, $idDonacion);
        $stmt->send_long_data(1, $imgData);
    } else {
        $stmt = $conn->prepare("UPDATE donacion SET descripcion=?, stock=?, idCategoria=? WHERE idDonacion=?");
        $stmt->bind_param("siii", $descripcion, $stock, $idCategoria, $idDonacion);
    }

    if ($stmt->execute()) {
        redir('../view/user_dashboard.php?tab=donaciones', 'Donación actualizada correctamente.');
    } else {
        redir('../view/user_dashboard.php?tab=donaciones', 'Error al actualizar la donación.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// CANCELAR DONACIÓN (solo pendientes propias)
// ════════════════════════════════════════════════════════════════════════════
if (isset($_GET['cancelar_donacion'])) {
    $idDonacion = (int) $_GET['cancelar_donacion'];

    // Verificar que pertenece al cliente y está pendiente
    $chk = $conn->query("
        SELECT d.idDonacion
        FROM donacion d
        INNER JOIN DonacionUsuario du ON d.idDonacion = du.idDonacion
        WHERE d.idDonacion = $idDonacion
          AND du.idDonante = $idCliente
          AND d.estado     = 'pendiente'
    ");
    if (!$chk || $chk->num_rows === 0) {
        redir('../view/user_dashboard.php?tab=donaciones', 'No puedes cancelar esta donación.');
    }

    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM DonacionUsuario WHERE idDonacion = $idDonacion");
        $conn->query("DELETE FROM donacion WHERE idDonacion = $idDonacion");
        $conn->commit();
        redir('../view/user_dashboard.php?tab=donaciones', 'Donación cancelada y eliminada correctamente.');
    } catch (Exception $e) {
        $conn->rollback();
        redir('../view/user_dashboard.php?tab=donaciones', 'Error al cancelar la donación.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// CREAR SOLICITUD
// ════════════════════════════════════════════════════════════════════════════
if (isset($_POST['crear_solicitud'])) {
    $descripcion = trim($_POST['descripcion']);
    $idCategoria = (int) $_POST['idCategoria'];

    if (empty($descripcion) || strlen($descripcion) < 5) {
        redir('../view/user_dashboard.php?tab=solicitudes', 'La descripción debe tener al menos 5 caracteres.');
    }
    if ($idCategoria <= 0) {
        redir('../view/user_dashboard.php?tab=solicitudes', 'Debes seleccionar una categoría.');
    }

    // Verificar que la categoría existe
    $chkCat = $conn->prepare("SELECT idCategoria FROM categoria WHERE idCategoria = ?");
    $chkCat->bind_param("i", $idCategoria);
    $chkCat->execute();
    if ($chkCat->get_result()->num_rows === 0) {
        redir('../view/user_dashboard.php?tab=solicitudes', 'La categoría seleccionada no es válida.');
    }

    // Procesar imagen
    $imgData = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $check = getimagesize($_FILES['imagen']['tmp_name']);
        if ($check !== false) {
            $imgData = file_get_contents($_FILES['imagen']['tmp_name']);
        } else {
            redir('../view/user_dashboard.php?tab=solicitudes', 'El archivo de imagen no es válido.');
        }
    }

// CÓDIGO CORREGIDO
    $stmt = $conn->prepare("
        INSERT INTO solicitud (descripcion, imagen, estado, idSolicitante, idCategoria, idGestor)
        VALUES (?, ?, 'pendiente', ?, ?, ?)
    ");
    
    // Ahora 'idGestor' coincide con el nombre en la base de datos.
    // Pasamos $idCliente al final para que el creador sea el primer gestor por defecto.
    $null = null;
    $stmt->bind_param("sbiii", $descripcion, $null, $idCliente, $idCategoria, $idCliente);
    
    if ($imgData) {
        $stmt->send_long_data(1, $imgData);
    }

    if ($stmt->execute()) {
        redir('../view/user_dashboard.php?tab=solicitudes', '¡Solicitud registrada correctamente! Queda pendiente de revisión.');
    } else {
        redir('../view/user_dashboard.php?tab=solicitudes', 'Error al registrar la solicitud: ' . $conn->error);
    }
}

// ════════════════════════════════════════════════════════════════════════════
// EDITAR SOLICITUD (solo pendientes propias)
// ════════════════════════════════════════════════════════════════════════════
if (isset($_POST['editar_solicitud'])) {
    $idSolicitud = (int) $_POST['idSolicitud'];
    $descripcion = trim($_POST['descripcion']);
    $idCategoria = (int) $_POST['idCategoria'];

    // Verificar pertenencia y estado pendiente
    $chk = $conn->query("
        SELECT idSolicitud FROM solicitud
        WHERE idSolicitud = $idSolicitud
          AND idSolicitante = $idCliente
          AND estado = 'pendiente'
    ");
    if (!$chk || $chk->num_rows === 0) {
        redir('../view/user_dashboard.php?tab=solicitudes', 'No puedes editar esta solicitud. Solo se pueden editar solicitudes pendientes propias.');
    }

    if (empty($descripcion) || strlen($descripcion) < 5) {
        redir('../view/user_dashboard.php?tab=solicitudes', 'La descripción debe tener al menos 5 caracteres.');
    }
    if ($idCategoria <= 0) {
        redir('../view/user_dashboard.php?tab=solicitudes', 'Debes seleccionar una categoría.');
    }

    // Procesar nueva imagen si se subió
    $imgData = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $check = getimagesize($_FILES['imagen']['tmp_name']);
        if ($check !== false) {
            $imgData = file_get_contents($_FILES['imagen']['tmp_name']);
        }
    }

    if ($imgData) {
        $stmt = $conn->prepare("UPDATE solicitud SET descripcion=?, imagen=?, idCategoria=? WHERE idSolicitud=?");
        $null = null;
        $stmt->bind_param("sbii", $descripcion, $null, $idCategoria, $idSolicitud);
        $stmt->send_long_data(1, $imgData);
    } else {
        $stmt = $conn->prepare("UPDATE solicitud SET descripcion=?, idCategoria=? WHERE idSolicitud=?");
        $stmt->bind_param("sii", $descripcion, $idCategoria, $idSolicitud);
    }

    if ($stmt->execute()) {
        redir('../view/user_dashboard.php?tab=solicitudes', 'Solicitud actualizada correctamente.');
    } else {
        redir('../view/user_dashboard.php?tab=solicitudes', 'Error al actualizar la solicitud.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// CANCELAR SOLICITUD (solo pendientes propias)
// ════════════════════════════════════════════════════════════════════════════
if (isset($_GET['cancelar_solicitud'])) {
    $idSolicitud = (int) $_GET['cancelar_solicitud'];

    $chk = $conn->query("
        SELECT idSolicitud FROM solicitud
        WHERE idSolicitud = $idSolicitud
          AND idSolicitante = $idCliente
          AND estado = 'pendiente'
    ");
    if (!$chk || $chk->num_rows === 0) {
        redir('../view/user_dashboard.php?tab=solicitudes', 'No puedes cancelar esta solicitud.');
    }

    $stmt = $conn->prepare("DELETE FROM solicitud WHERE idSolicitud = ?");
    $stmt->bind_param("i", $idSolicitud);
    if ($stmt->execute()) {
        redir('../view/user_dashboard.php?tab=solicitudes', 'Solicitud cancelada y eliminada correctamente.');
    } else {
        redir('../view/user_dashboard.php?tab=solicitudes', 'Error al cancelar la solicitud.');
    }
}

// ════════════════════════════════════════════════════════════════════════════
// ACTUALIZAR PERFIL PROPIO DEL CLIENTE
// ════════════════════════════════════════════════════════════════════════════
if (isset($_POST['update_perfil'])) {
    $id        = (int) $_POST['idUsuario'];
    $idCliente = (int) $idCliente;
    // Seguridad: solo puede editar su propio perfil
    if ($id !== $idCliente) redir('../view/user_dashboard.php?tab=perfil', 'Acción no permitida.');

    $nombre    = trim($_POST['nombre']);
    // Validar nombre: solo letras, tildes y espacios
    if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]+$/', $nombre)) {
        redir('../view/user_dashboard.php?tab=perfil', 'El nombre solo puede contener letras y espacios, sin números ni símbolos.');
    }
    $tipoDoc   = $_POST['tipoDocumento'];
    $numDoc    = (int) $_POST['numDocumento'];
    $fechaNac  = $_POST['fechaNacimiento'];
    $direccion = trim($_POST['direccion']);
    $email     = trim($_POST['email']);
    $telefono  = trim($_POST['telefono']);
    $necesidad = trim($_POST['necesidad'] ?? '');
    $passActual = $_POST['password_actual'] ?? '';
    $pass      = $_POST['password'] ?? '';
    $passConf  = $_POST['password_confirm'] ?? '';

    if (empty($necesidad)) $necesidad = null;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        redir('../view/user_dashboard.php?tab=perfil', 'Email inválido.');

    $chk = $conn->prepare("SELECT idUsuario FROM usuario WHERE (email=? OR numDocumento=?) AND idUsuario != ?");
    $chk->bind_param("sii", $email, $numDoc, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0)
        redir('../view/user_dashboard.php?tab=perfil', 'El email o número de documento ya está en uso por otro usuario.');

    if (!empty($pass)) {
        // Verificar contraseña actual
        if (empty($passActual)) {
            redir('../view/user_dashboard.php?tab=perfil', 'Debes ingresar tu contraseña actual para cambiarla.');
        }
        $stmtHash = $conn->prepare("SELECT contrasena FROM usuario WHERE idUsuario = ?");
        $stmtHash->bind_param("i", $id);
        $stmtHash->execute();
        $rowHash = $stmtHash->get_result()->fetch_assoc();
        if (!$rowHash || !password_verify($passActual, $rowHash['contrasena'])) {
            redir('../view/user_dashboard.php?tab=perfil', 'La contraseña actual es incorrecta.');
        }
        if ($pass !== $passConf) redir('../view/user_dashboard.php?tab=perfil', 'Las contraseñas no coinciden.');
        if (strlen($pass) < 6)  redir('../view/user_dashboard.php?tab=perfil', 'La contraseña debe tener al menos 6 caracteres.');
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            UPDATE usuario SET nombre=?, tipoDocumento=?, numDocumento=?, fechaNacimiento=?,
                               direccion=?, email=?, contrasena=?, telefono=?, necesidad=?
            WHERE idUsuario=? AND rol='donante'
        ");
        $stmt->bind_param("ssissssssi",
            $nombre, $tipoDoc, $numDoc, $fechaNac,
            $direccion, $email, $hash, $telefono, $necesidad, $id
        );
    } else {
        $stmt = $conn->prepare("
            UPDATE usuario SET nombre=?, tipoDocumento=?, numDocumento=?, fechaNacimiento=?,
                               direccion=?, email=?, telefono=?, necesidad=?
            WHERE idUsuario=? AND rol='donante'
        ");
        $stmt->bind_param("ssisssssi",
            $nombre, $tipoDoc, $numDoc, $fechaNac,
            $direccion, $email, $telefono, $necesidad, $id
        );
    }

    if ($stmt->execute()) {
        $_SESSION['nombre'] = $nombre;
        redir('../view/user_dashboard.php?tab=perfil', 'Perfil actualizado correctamente.');
    } else {
        redir('../view/user_dashboard.php?tab=perfil', 'Error al actualizar: ' . $conn->error);
    }
}

// Si no entra en ningún caso, redirigir al panel
redir('../view/user_dashboard.php');