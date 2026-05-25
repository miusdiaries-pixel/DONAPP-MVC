<?php
session_start();
include '../config/conexion.php';

// ── Protección: solo asistente ────────────────────────────────────────────
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'asistente') {
    header("Location: ../index.php");
    exit();
}

function redir($url, $msg = '') {
    if ($msg) $_SESSION['flash'] = $msg;
    header("Location: $url");
    exit();
}

// ════════════════════════════════════════════════════════════════════════════
// CREAR DONANTE/SOLICITANTE
// ════════════════════════════════════════════════════════════════════════════
if (isset($_POST['crear_donante'])) {
    $nombre      = trim($_POST['nombre']);
    $tipoDoc     = $_POST['tipoDocumento'];
    $numDoc      = (int) $_POST['numDocumento'];
    $fechaNac    = $_POST['fechaNacimiento'];
    $direccion   = trim($_POST['direccion']);
    $email       = trim($_POST['email']);
    $telefono    = trim($_POST['telefono']);
    $necesidad   = trim($_POST['necesidad'] ?? '');
    $pass        = $_POST['password'];
    $passConfirm = $_POST['password_confirm'];
    $prioridad   = !empty($_POST['prioridad'])          ? trim($_POST['prioridad'])          : null;
    $obs_visita  = !empty($_POST['observacion_visita']) ? trim($_POST['observacion_visita']) : null;

    if (empty($necesidad)) $necesidad = null;

    if ($pass !== $passConfirm) redir('../view/asis_dashboard.php#clientes', 'Las contraseñas no coinciden.');
    if (strlen($pass) < 6)      redir('../view/asis_dashboard.php#clientes', 'La contraseña debe tener al menos 6 caracteres.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) redir('../view/asis_dashboard.php#clientes', 'Email inválido.');

    $chk = $conn->prepare("SELECT idUsuario FROM usuario WHERE email=? OR numDocumento=?");
    $chk->bind_param("si", $email, $numDoc);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) redir('../view/asis_dashboard.php#clientes', 'El email o número de documento ya está registrado.');

    $hash   = password_hash($pass, PASSWORD_DEFAULT);
    $rol    = 'donante';
    $estado = 'activo';

    // 13 params: s s i s s s s s s s s s s
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
        redir('../view/asis_dashboard.php#clientes', 'Donante/Solicitante creado correctamente.');
    } else {
        redir('../view/asis_dashboard.php#clientes', 'Error al crear donante/solicitante: ' . $conn->error);
    }
}

// ════════════════════════════════════════════════════════════════════════════
// EDITAR DONANTE/SOLICITANTE
// ════════════════════════════════════════════════════════════════════════════
if (isset($_POST['editar_donante'])) {
    $id         = (int) $_POST['idUsuario'];
    $nombre     = trim($_POST['nombre']);
    $tipoDoc    = $_POST['tipoDocumento'];
    $numDoc     = (int) $_POST['numDocumento'];
    $fechaNac   = $_POST['fechaNacimiento'];
    $direccion  = trim($_POST['direccion']);
    $email      = trim($_POST['email']);
    $telefono   = trim($_POST['telefono']);
    $necesidad  = trim($_POST['necesidad'] ?? '');
    $pass       = $_POST['password'];
    $passConf   = $_POST['password_confirm'];
    $prioridad  = !empty($_POST['prioridad'])          ? trim($_POST['prioridad'])          : null;
    $obs_visita = !empty($_POST['observacion_visita']) ? trim($_POST['observacion_visita']) : null;

    if (empty($necesidad)) $necesidad = null;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) redir('../view/asis_dashboard.php#clientes', 'Email inválido.');

    $chk = $conn->prepare("SELECT idUsuario FROM usuario WHERE (email=? OR numDocumento=?) AND idUsuario != ?");
    $chk->bind_param("sii", $email, $numDoc, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) redir('../view/asis_dashboard.php#clientes', 'El email o número de documento ya pertenece a otro usuario.');

    if (!empty($pass)) {
        if ($pass !== $passConf) redir('../view/asis_dashboard.php#clientes', 'Las contraseñas no coinciden.');
        if (strlen($pass) < 6)  redir('../view/asis_dashboard.php#clientes', 'La contraseña debe tener al menos 6 caracteres.');
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        // 12 params: s s i s s s s s s s s i
        $stmt = $conn->prepare("
            UPDATE usuario SET nombre=?, tipoDocumento=?, numDocumento=?, fechaNacimiento=?,
                               direccion=?, email=?, contrasena=?, telefono=?, necesidad=?, prioridad=?, observacion_visita=?
            WHERE idUsuario=? AND rol='donante'
        ");
        $stmt->bind_param("ssissssssssi",
            $nombre, $tipoDoc, $numDoc, $fechaNac,
            $direccion, $email, $hash, $telefono, $necesidad, $prioridad, $obs_visita, $id
        );
    } else {
        // 11 params: s s i s s s s s s s i
        $stmt = $conn->prepare("
            UPDATE usuario SET nombre=?, tipoDocumento=?, numDocumento=?, fechaNacimiento=?,
                               direccion=?, email=?, telefono=?, necesidad=?, prioridad=?, observacion_visita=?
            WHERE idUsuario=? AND rol='donante'
        ");
        $stmt->bind_param("ssisssssssi",
            $nombre, $tipoDoc, $numDoc, $fechaNac,
            $direccion, $email, $telefono, $necesidad, $prioridad, $obs_visita, $id
        );
    }

    if ($stmt->execute()) {
        redir('../view/asis_dashboard.php#clientes', 'Donante/Solicitante actualizado correctamente.');
    } else {
        redir('../view/asis_dashboard.php#clientes', 'Error al actualizar: ' . $conn->error);
    }
}

// ════════════════════════════════════════════════════════════════════════════
// ACTUALIZAR PERFIL PROPIO DEL ASISTENTE
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
    $pass      = $_POST['password'];
    $passConf  = $_POST['password_confirm'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) redir('../view/asis_dashboard.php#perfil', 'Email inválido.');

    $chk = $conn->prepare("SELECT idUsuario FROM usuario WHERE (email=? OR numDocumento=?) AND idUsuario != ?");
    $chk->bind_param("sii", $email, $numDoc, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) redir('../view/asis_dashboard.php#perfil', 'El email o documento ya está en uso.');

    if (!empty($pass)) {
        if ($pass !== $passConf) redir('../view/asis_dashboard.php#perfil', 'Las contraseñas no coinciden.');
        if (strlen($pass) < 6)  redir('../view/asis_dashboard.php#perfil', 'La contraseña debe tener al menos 6 caracteres.');
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            UPDATE usuario SET nombre=?, tipoDocumento=?, numDocumento=?, fechaNacimiento=?,
                               direccion=?, email=?, contrasena=?, telefono=?
            WHERE idUsuario=?
        ");
        $stmt->bind_param("ssisssssi",
            $nombre, $tipoDoc, $numDoc, $fechaNac,
            $direccion, $email, $hash, $telefono, $id
        );
    } else {
        $stmt = $conn->prepare("
            UPDATE usuario SET nombre=?, tipoDocumento=?, numDocumento=?, fechaNacimiento=?,
                               direccion=?, email=?, telefono=?
            WHERE idUsuario=?
        ");
        $stmt->bind_param("ssissssi",
            $nombre, $tipoDoc, $numDoc, $fechaNac,
            $direccion, $email, $telefono, $id
        );
    }

    if ($stmt->execute()) {
        $_SESSION['nombre'] = $nombre;
        redir('../view/asis_dashboard.php#perfil', 'Perfil actualizado correctamente.');
    } else {
        redir('../view/asis_dashboard.php#perfil', 'Error al actualizar: ' . $conn->error);
    }
}
