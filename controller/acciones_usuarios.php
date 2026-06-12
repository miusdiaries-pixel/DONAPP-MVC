<?php
session_start();
include '../config/conexion.php';

// ── Protección de rol ──────────────────────────────────────────────────────
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../index.php");
    exit();
}

function redir($url, $msg = '') {
    if ($msg) $_SESSION['flash'] = $msg;
    header("Location: $url");
    exit();
}

// ════════════════════════════════════════════════════════════════════════════
// TOGGLE ESTADO USUARIO (activo <-> inactivo)
// ════════════════════════════════════════════════════════════════════════════
if (isset($_GET['toggle_estado'])) {
    $id = (int) $_GET['toggle_estado'];
    if ($id === (int)$_SESSION['idUsuario']) {
        redir('../view/admin_dashboard.php#usuarios', 'No puedes cambiar tu propio estado.');
    }
    $res = $conn->query("SELECT estado FROM usuario WHERE idUsuario=$id");
    if ($res && $row = $res->fetch_assoc()) {
        $nuevoEstado = ($row['estado'] === 'activo') ? 'inactivo' : 'activo';
        $stmt = $conn->prepare("UPDATE usuario SET estado=? WHERE idUsuario=?");
        $stmt->bind_param("si", $nuevoEstado, $id);
        $stmt->execute();
        $msg = ($nuevoEstado === 'activo') ? 'Usuario activado correctamente.' : 'Usuario inactivado correctamente.';
        redir('../view/admin_dashboard.php#usuarios', $msg);
    }
    redir('../view/admin_dashboard.php#usuarios', 'Error al cambiar estado.');
}

// ════════════════════════════════════════════════════════════════════════════
// CREAR USUARIO
// ════════════════════════════════════════════════════════════════════════════
if (isset($_POST['crear_usuario'])) {
    $nombre      = trim($_POST['nombre']);
    $tipoDoc     = $_POST['tipoDocumento'];
    $numDoc      = (int) $_POST['numDocumento'];
    $fechaNac    = $_POST['fechaNacimiento'];
    $direccion   = trim($_POST['direccion']);
    $email       = trim($_POST['email']);
    $telefono    = trim($_POST['telefono']);
    $rol         = $_POST['rol'];
    $estado      = $_POST['estado'];
    $pass        = $_POST['password'];
    $passConfirm = $_POST['password_confirm'];

    // REGLA DE NEGOCIO: Solo el donante/solicitante tiene necesidad y prioridad
    $necesidad = ($rol === 'donante') ? trim($_POST['necesidad'] ?? '') : null;
    if (empty($necesidad)) $necesidad = null;

    if ($pass !== $passConfirm) redir('../view/admin_dashboard.php#usuarios', 'Las contraseñas no coinciden.');
    if (strlen($pass) < 6) redir('../view/admin_dashboard.php#usuarios', 'La contraseña debe tener al menos 6 caracteres.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) redir('../view/admin_dashboard.php#usuarios', 'Email inválido.');

    $chk = $conn->prepare("SELECT idUsuario FROM usuario WHERE email=? OR numDocumento=?");
    $chk->bind_param("si", $email, $numDoc);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) redir('../view/admin_dashboard.php#usuarios', 'El email o número de documento ya está registrado.');

    $hash = password_hash($pass, PASSWORD_DEFAULT);

    // 11 params: ssissssssss
    $stmt = $conn->prepare("
        INSERT INTO usuario (nombre, tipoDocumento, numDocumento, fechaNacimiento,
                             direccion, email, contrasena, telefono, necesidad, prioridad, observacion_visita, estado, rol)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssissssssssss",
        $nombre, $tipoDoc, $numDoc, $fechaNac,
        $direccion, $email, $hash, $telefono, $necesidad, $prioridad, $obs_visita, $estado, $rol
    );
    
    if ($stmt->execute()) {
        redir('../view/admin_dashboard.php#usuarios', 'Usuario creado correctamente.');
    } else {
        redir('../view/admin_dashboard.php#usuarios', 'Error al crear usuario: ' . $conn->error);
    }
}

// ════════════════════════════════════════════════════════════════════════════
// EDITAR USUARIO (Desde el Panel de Administración)
// ════════════════════════════════════════════════════════════════════════════
if (isset($_POST['update_user'])) {
    $id        = (int) $_POST['idUsuario'];
    $nombre    = trim($_POST['nombre']);
    $tipoDoc   = $_POST['tipoDocumento'];
    $numDoc    = (int) $_POST['numDocumento'];
    $fechaNac  = $_POST['fechaNacimiento'];
    $direccion = trim($_POST['direccion']);
    $email     = trim($_POST['email']);
    $telefono  = trim($_POST['telefono']);
    $rol       = $_POST['rol'];
    $estado    = $_POST['estado'];
    $pass      = $_POST['password'];
    $passConf  = $_POST['password_confirm'];

    // --- NUEVA VALIDACIÓN DE SEGURIDAD ---
    // Si el ID que se está editando es el mismo que el del Admin logueado
    if ($id === (int)$_SESSION['idUsuario']) {
        // Consultamos el rol actual en la sesión
        if ($rol !== $_SESSION['rol']) {
            redir('../view/admin_dashboard.php#usuarios', 'No puedes cambiar tu propio rol desde este módulo.');
        }
        if ($estado !== 'activo') {
            redir('../view/admin_dashboard.php#usuarios', 'No puedes desactivar tu propia cuenta.');
        }
    }
    // -------------------------------------

    // REGLA DE NEGOCIO: Solo el donante/solicitante tiene necesidad y prioridad
    $necesidad = ($rol === 'donante') ? trim($_POST['necesidad'] ?? '') : null;
    if (empty($necesidad)) $necesidad = null;
    $prioridad  = ($rol === 'donante' && !empty($_POST['prioridad']))         ? trim($_POST['prioridad'])          : null;
    $obs_visita = ($rol === 'donante' && !empty($_POST['observacion_visita'])) ? trim($_POST['observacion_visita']) : null;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) redir('../view/admin_dashboard.php#usuarios', 'Email inválido.');

    $chk = $conn->prepare("SELECT idUsuario FROM usuario WHERE (email=? OR numDocumento=?) AND idUsuario != ?");
    $chk->bind_param("sii", $email, $numDoc, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) redir('../view/admin_dashboard.php#usuarios', 'El email o número de documento ya pertenece a otro usuario.');

    if (!empty($pass)) {
        if ($pass !== $passConf) redir('../view/admin_dashboard.php#usuarios', 'Las contraseñas no coinciden.');
        if (strlen($pass) < 6) redir('../view/admin_dashboard.php#usuarios', 'La contraseña debe tener al menos 6 caracteres.');
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            UPDATE usuario SET nombre=?, tipoDocumento=?, numDocumento=?, fechaNacimiento=?,
                               direccion=?, email=?, contrasena=?, telefono=?, necesidad=?, prioridad=?, observacion_visita=?, rol=?, estado=?
            WHERE idUsuario=?
        ");
        $stmt->bind_param("ssissssssssssi",
            $nombre, $tipoDoc, $numDoc, $fechaNac,
            $direccion, $email, $hash, $telefono, $necesidad, $prioridad, $obs_visita, $rol, $estado, $id
        );
    } else {
        $stmt = $conn->prepare("
            UPDATE usuario SET nombre=?, tipoDocumento=?, numDocumento=?, fechaNacimiento=?,
                               direccion=?, email=?, telefono=?, necesidad=?, prioridad=?, observacion_visita=?, rol=?, estado=?
            WHERE idUsuario=?
        ");
        $stmt->bind_param("ssisssssssssi",
            $nombre, $tipoDoc, $numDoc, $fechaNac,
            $direccion, $email, $telefono, $necesidad, $prioridad, $obs_visita, $rol, $estado, $id
        );
    }

    if ($stmt->execute()) {
        redir('../view/admin_dashboard.php#usuarios', 'Usuario actualizado correctamente.');
    } else {
        redir('../view/admin_dashboard.php#usuarios', 'Error al actualizar: ' . $conn->error);
    }
}

// ════════════════════════════════════════════════════════════════════════════
// ACTUALIZAR PERFIL PROPIO
// ════════════════════════════════════════════════════════════════════════════
if (isset($_POST['update_perfil'])) {
    $id        = (int) $_POST['idUsuario'];
    $nombre    = trim($_POST['nombre']);
    $tipoDoc   = $_POST['tipoDocumento'];
    $numDoc    = (int) $_POST['numDocumento'];
    $fechaNac  = $_POST['fechaNacimiento'];
    $direccion = trim($_POST['direccion']);
    $email     = trim($_POST['email']);
    $telefono  = trim($_POST['telefono']);
    
    // Para el perfil propio, mantenemos el rol de la sesión para la lógica de necesidad
    $rolActual = $_SESSION['rol'];
    $necesidad = ($rolActual === 'donante') ? trim($_POST['necesidad'] ?? '') : null;
    if (empty($necesidad)) $necesidad = null;

    $pass      = $_POST['password'];
    $passConf  = $_POST['password_confirm'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) redir('../view/admin_dashboard.php#perfil', 'Email inválido.');

    $chk = $conn->prepare("SELECT idUsuario FROM usuario WHERE (email=? OR numDocumento=?) AND idUsuario != ?");
    $chk->bind_param("sii", $email, $numDoc, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) redir('../view/admin_dashboard.php#perfil', 'El email o documento ya está en uso.');

    if (!empty($pass)) {
        if ($pass !== $passConf) redir('../view/admin_dashboard.php#perfil', 'Las contraseñas no coinciden.');
        if (strlen($pass) < 6) redir('../view/admin_dashboard.php#perfil', 'La contraseña debe tener al menos 6 caracteres.');
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        
        // 10 params: ssissssssi
        $stmt = $conn->prepare("
            UPDATE usuario SET nombre=?, tipoDocumento=?, numDocumento=?, fechaNacimiento=?,
                               direccion=?, email=?, contrasena=?, telefono=?, necesidad=?, prioridad=?
            WHERE idUsuario=?
        ");
        $stmt->bind_param("ssisssssssi",
            $nombre, $tipoDoc, $numDoc, $fechaNac,
            $direccion, $email, $hash, $telefono, $necesidad, $prioridad, $id
        );
    } else {
        // 9 params: ssisssssi
        $stmt = $conn->prepare("
            UPDATE usuario SET nombre=?, tipoDocumento=?, numDocumento=?, fechaNacimiento=?,
                               direccion=?, email=?, telefono=?, necesidad=?, prioridad=?
            WHERE idUsuario=?
        ");
        $stmt->bind_param("ssissssssi",
            $nombre, $tipoDoc, $numDoc, $fechaNac,
            $direccion, $email, $telefono, $necesidad, $prioridad, $id
        );
    }

    if ($stmt->execute()) {
        $_SESSION['nombre'] = $nombre;
        redir('../view/admin_dashboard.php#perfil', 'Perfil actualizado correctamente.');
    } else {
        redir('../view/admin_dashboard.php#perfil', 'Error al actualizar: ' . $conn->error);
    }
}
?>