<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
include '../config/conexion.php';

require '../libs/PHPMailer/src/Exception.php';
require '../libs/PHPMailer/src/PHPMailer.php';
require '../libs/PHPMailer/src/SMTP.php';

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
    header("Location: ../index.php");
    exit();
}

function redir($url, $msg = '') {
    if ($msg) $_SESSION['flash'] = $msg;
    header("Location: $url");
    exit();
}

/**
 * Envía notificación al cliente por correo (PHPMailer + SMTP Gmail).
 */
function notificarCliente($conn, $tipo, $idRegistro, $nuevoEstado, $observacion) {
    if ($tipo === 'donacion') {
        $res = $conn->query("
            SELECT u.email, u.nombre
            FROM donacion d
            LEFT JOIN DonacionUsuario du ON d.idDonacion = du.idDonacion
            LEFT JOIN usuario u ON du.idDonante = u.idUsuario
            WHERE d.idDonacion = $idRegistro
            LIMIT 1
        ");
    } else {
        $res = $conn->query("
            SELECT u.email, u.nombre
            FROM solicitud s
            LEFT JOIN usuario u ON s.idSolicitante = u.idUsuario
            WHERE s.idSolicitud = $idRegistro
            LIMIT 1
        ");
    }

    if (!$res) return;
    $cliente = $res->fetch_assoc();
    if (!$cliente || empty($cliente['email'])) return;

    $tipoLabel   = ($tipo === 'donacion') ? 'donación' : 'solicitud';
    $estadoLabel = ucfirst($nuevoEstado);

    $colorEstado = '#f57c00'; // naranja = pendiente
    if ($nuevoEstado === 'aprobada')  $colorEstado = '#2e7d32';
    if ($nuevoEstado === 'rechazada') $colorEstado = '#c62828';

    $obsHtml = '';
    if (!empty($observacion)) {
        $obsHtml = "<p style='margin-top:12px; padding:10px 14px; background:#f5f5f5; border-left:4px solid {$colorEstado}; border-radius:4px;'>
                        <strong>Motivo / Observación:</strong><br>" . htmlspecialchars($observacion) . "
                    </p>";
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username = 'donapp.co@gmail.com';
        $mail->Password = 'jceq kxjs rrsh uwav';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('no-reply@donapp.com', 'DONAPP Equipo');
        $mail->addAddress($cliente['email'], $cliente['nombre']);

        $mail->isHTML(true);
        $mail->Subject = "DONAPP — Tu $tipoLabel ha sido $estadoLabel";
        $mail->Body    = "
        <div style='font-family: sans-serif; padding: 24px; border: 1px solid #e0e0e0; border-radius: 10px; max-width: 520px; margin: auto;'>
            <h2 style='color: #d32f2f; margin-bottom: 4px;'>DONAPP</h2>
            <p style='color:#555; margin-top:0; font-size:0.9rem;'>Notificación de cambio de estado</p>
            <hr style='border:none; border-top:1px solid #eee; margin: 12px 0;'>
            <p>Hola <strong>{$cliente['nombre']}</strong>,</p>
            <p>El estado de tu <strong>$tipoLabel</strong> ha sido actualizado a:</p>
            <div style='text-align:center; margin: 16px 0;'>
                <span style='background:{$colorEstado}; color:#fff; padding:8px 22px; border-radius:20px; font-size:1rem; font-weight:bold; display:inline-block;'>
                    $estadoLabel
                </span>
            </div>
            {$obsHtml}
            <p style='margin-top:20px; color:#555;'>Ingresa a la plataforma para ver más detalles.</p>
            <p style='color:#aaa; font-size:0.8rem; margin-top:24px;'>— Equipo DONAPP</p>
        </div>";

        $mail->send();
        $_SESSION['notif_enviada'] = "Notificación enviada a {$cliente['nombre']} ({$cliente['email']}).";
    } catch (Exception $e) {
        $_SESSION['notif_enviada'] = "Advertencia: no se pudo enviar el correo a {$cliente['email']}.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo        = $_POST['tipo'] ?? '';
    $id          = (int) ($_POST['id'] ?? 0);
    $nuevoEstado = $_POST['nuevo_estado'] ?? '';
    $observacion = trim($_POST['observacion'] ?? '');

    $estadosValidos = ['pendiente', 'aprobada', 'rechazada'];
    if (!in_array($nuevoEstado, $estadosValidos)) {
        redir('../view/admin_dashboard.php#donapp', 'Estado no válido.');
    }

    if ($nuevoEstado === 'rechazada' && trim($observacion) === '') {
        redir('../view/admin_dashboard.php#donapp', 'La observación es obligatoria al rechazar.');
    }

    // Registrar al administrador actual como gestor
    $idGestorActual = (int) $_SESSION['idUsuario'];

    if ($tipo === 'donacion') {
        $stmt = $conn->prepare("UPDATE donacion SET estado=?, observacion=? WHERE idDonacion=?");
        $stmt->bind_param("ssi", $nuevoEstado, $observacion, $id);
        $stmt->execute();
        notificarCliente($conn, 'donacion', $id, $nuevoEstado, $observacion);
        redir('../view/admin_dashboard.php#donapp', 'Donación actualizada correctamente.');

    } elseif ($tipo === 'solicitud') {
        // CORRECCIÓN: idGestor y actualización del responsable actual
        $stmt = $conn->prepare("UPDATE solicitud SET estado=?, observacion=?, idGestor=? WHERE idSolicitud=?");
        $stmt->bind_param("ssii", $nuevoEstado, $observacion, $idGestorActual, $id);
        $stmt->execute();
        notificarCliente($conn, 'solicitud', $id, $nuevoEstado, $observacion);
        redir('../view/admin_dashboard.php#donapp', 'Solicitud actualizada correctamente.');

    } else {
        redir('../view/admin_dashboard.php#donapp', 'Tipo de acción no reconocido.');
    }
}

redir('../view/admin_dashboard.php#donapp');