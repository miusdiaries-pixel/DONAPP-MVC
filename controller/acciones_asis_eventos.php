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

// ── TOGGLE estado (activo ↔ inactivo) ────────────────────────────────────
if (isset($_GET['toggle'])) {
    $id  = (int) $_GET['toggle'];
    $res = $conn->query("SELECT estado FROM evento WHERE idEvento=$id");
    if ($res && $row = $res->fetch_assoc()) {
        $nuevo = ($row['estado'] === 'activo') ? 'inactivo' : 'activo';
        $stmt  = $conn->prepare("UPDATE evento SET estado=? WHERE idEvento=?");
        $stmt->bind_param("si", $nuevo, $id);
        $stmt->execute();
        redir('../view/asis_dashboard.php#eventos', "El evento ahora está $nuevo.");
    }
    redir('../view/asis_dashboard.php#eventos', 'Error al cambiar estado.');
}

// ── CREAR EVENTO Y PUBLICACIÓN ────────────────────────────────────────────
if (isset($_POST['crear_evento_completo'])) {
    $nombre_evento = trim($_POST['nombre_evento']);
    $estado_evento = $_POST['estado_evento'];
    $fecha_entrega = $_POST['fecha_entrega'];
    $lugar_entrega = trim($_POST['lugar_entrega']);
    $titulo_pub    = trim($_POST['titulo_pub']);
    $contenido_pub = trim($_POST['contenido_pub']);
    $idAsistente   = $_SESSION['idUsuario'];
    $fechaHoy      = date('Y-m-d');

    $imgData = null;
    if (isset($_FILES['imagen_pub']) && $_FILES['imagen_pub']['error'] == UPLOAD_ERR_OK) {
        if (getimagesize($_FILES['imagen_pub']['tmp_name']) !== false) {
            $imgData = file_get_contents($_FILES['imagen_pub']['tmp_name']);
        }
    }

    $conn->begin_transaction();
    try {
        $stmtE = $conn->prepare("INSERT INTO evento (Nombre, estado) VALUES (?, ?)");
        $stmtE->bind_param("ss", $nombre_evento, $estado_evento);
        $stmtE->execute();
        $idEvento = $conn->insert_id;

        $stmtP = $conn->prepare("INSERT INTO ProgramadorEventos (FechaEntrega, Lugar, idUsuario, idEvento) VALUES (?, ?, ?, ?)");
        $stmtP->bind_param("ssii", $fecha_entrega, $lugar_entrega, $idAsistente, $idEvento);
        $stmtP->execute();

        $stmtPub = $conn->prepare("INSERT INTO publicacion (titulo, contenido, imagen, fechaPublicacion, idUsuario, idEvento) VALUES (?, ?, ?, ?, ?, ?)");
        $null = NULL;
        $stmtPub->bind_param("ssbsii", $titulo_pub, $contenido_pub, $null, $fechaHoy, $idAsistente, $idEvento);
        if ($imgData) $stmtPub->send_long_data(2, $imgData);
        $stmtPub->execute();

        $conn->commit();
        redir('../view/asis_dashboard.php#eventos', 'Evento y Publicación publicados con éxito.');
    } catch (Exception $e) {
        $conn->rollback();
        redir('../view/asis_dashboard.php#eventos', 'Error: ' . $e->getMessage());
    }
}

// ── EDITAR EVENTO Y PUBLICACIÓN ───────────────────────────────────────────
if (isset($_POST['editar_evento_completo'])) {
    $idEvento    = (int) $_POST['idEvento'];
    $nombre      = trim($_POST['nombre']);
    $estado      = $_POST['estado'];
    $fecha_ent   = $_POST['fecha_entrega'];
    $lugar_ent   = trim($_POST['lugar']);
    $titulo_p    = trim($_POST['titulo_p']);
    $contenido_p = trim($_POST['contenido_p']);

    $imgData = null;
    if (isset($_FILES['imagen_pub']) && $_FILES['imagen_pub']['tmp_name'] != '') {
        $imgData = file_get_contents($_FILES['imagen_pub']['tmp_name']);
    }

    $conn->begin_transaction();
    try {
        $stmt1 = $conn->prepare("UPDATE evento SET Nombre=?, estado=? WHERE idEvento=?");
        $stmt1->bind_param("ssi", $nombre, $estado, $idEvento);
        $stmt1->execute();

        $stmt2 = $conn->prepare("UPDATE ProgramadorEventos SET FechaEntrega=?, Lugar=? WHERE idEvento=?");
        $stmt2->bind_param("ssi", $fecha_ent, $lugar_ent, $idEvento);
        $stmt2->execute();

        if ($imgData) {
            $stmt3 = $conn->prepare("UPDATE publicacion SET titulo=?, contenido=?, imagen=? WHERE idEvento=?");
            $null = NULL;
            $stmt3->bind_param("ssbi", $titulo_p, $contenido_p, $null, $idEvento);
            $stmt3->send_long_data(2, $imgData);
        } else {
            $stmt3 = $conn->prepare("UPDATE publicacion SET titulo=?, contenido=? WHERE idEvento=?");
            $stmt3->bind_param("ssi", $titulo_p, $contenido_p, $idEvento);
        }
        $stmt3->execute();

        $conn->commit();
        redir('../view/asis_dashboard.php#eventos', 'Cambios guardados correctamente.');
    } catch (Exception $e) {
        $conn->rollback();
        redir('../view/asis_dashboard.php#eventos', 'Error al actualizar: ' . $e->getMessage());
    }
}

redir('../view/asis_dashboard.php#eventos');