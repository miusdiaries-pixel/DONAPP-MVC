<?php
session_start();
include '../config/conexion.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../index.php");
    exit();
}

function redir($url, $msg = '') {
    if ($msg) $_SESSION['flash'] = $msg;
    header("Location: $url");
    exit();
}

// ── CREAR CATEGORÍA ───────────────────────────────────────────────────────
if (isset($_POST['crear_categoria'])) {
    $nombre = trim($_POST['nombre_categoria']);
    if (empty($nombre) || strlen($nombre) < 3) {
        redir('../view/admin_dashboard.php#categorias', 'El nombre debe tener al menos 3 caracteres.');
    }
    if (!preg_match('/^[A-Za-záéíóúÁÉÍÓÚñÑüÜ\s\(\)\-]+$/u', $nombre)) {
        redir('../view/admin_dashboard.php#categorias', 'El nombre solo puede contener letras y espacios.');
    }
    $idUsuario = $_SESSION['idUsuario'];

    $chk = $conn->prepare("SELECT idCategoria FROM categoria WHERE nombre = ?");
    $chk->bind_param("s", $nombre);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        redir('../view/admin_dashboard.php#categorias', 'Ya existe una categoría con ese nombre.');
    }

    $stmt = $conn->prepare("INSERT INTO categoria (nombre, idUsuario) VALUES (?, ?)");
    $stmt->bind_param("si", $nombre, $idUsuario);
    if ($stmt->execute()) {
        redir('../view/admin_dashboard.php#categorias', 'Categoría creada correctamente.');
    } else {
        redir('../view/admin_dashboard.php#categorias', 'Error al crear: ' . $conn->error);
    }
}

// ── EDITAR CATEGORÍA ──────────────────────────────────────────────────────
if (isset($_POST['editar_categoria'])) {
    $id     = (int) $_POST['idCategoria'];
    $nombre = trim($_POST['nombre_categoria']);
    if (empty($nombre) || strlen($nombre) < 3) {
        redir('../view/admin_dashboard.php#categorias', 'El nombre debe tener al menos 3 caracteres.');
    }
    if (!preg_match('/^[A-Za-záéíóúÁÉÍÓÚñÑüÜ\s\(\)\-]+$/u', $nombre)) {
        redir('../view/admin_dashboard.php#categorias', 'El nombre solo puede contener letras y espacios.');
    }

    $chk = $conn->prepare("SELECT idCategoria FROM categoria WHERE nombre = ? AND idCategoria != ?");
    $chk->bind_param("si", $nombre, $id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        redir('../view/admin_dashboard.php#categorias', 'Ya existe otra categoría con ese nombre.');
    }

    $stmt = $conn->prepare("UPDATE categoria SET nombre=? WHERE idCategoria=?");
    $stmt->bind_param("si", $nombre, $id);
    if ($stmt->execute()) {
        redir('../view/admin_dashboard.php#categorias', 'Categoría actualizada correctamente.');
    } else {
        redir('../view/admin_dashboard.php#categorias', 'Error al actualizar: ' . $conn->error);
    }
}

// ── ELIMINAR CATEGORÍA ────────────────────────────────────────────────────
if (isset($_GET['eliminar'])) {
    $id = (int) $_GET['eliminar'];
    // Verificar que no tenga donaciones o solicitudes activas
    $uso = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM donacion WHERE idCategoria=$id) +
            (SELECT COUNT(*) FROM solicitud WHERE idCategoria=$id) AS total
    ")->fetch_assoc()['total'];

    if ($uso > 0) {
        redir('../view/admin_dashboard.php#categorias', "No se puede eliminar: la categoría tiene $uso registros asociados.");
    }

    $stmt = $conn->prepare("DELETE FROM categoria WHERE idCategoria=?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        redir('../view/admin_dashboard.php#categorias', 'Categoría eliminada correctamente.');
    } else {
        redir('../view/admin_dashboard.php#categorias', 'Error al eliminar: ' . $conn->error);
    }
}

redir('../view/admin_dashboard.php#categorias');