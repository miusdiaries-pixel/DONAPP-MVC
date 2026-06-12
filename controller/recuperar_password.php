<?php
session_start();
include '../config/conexion.php';

// Importar PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../libs/PHPMailer/src/Exception.php';
require '../libs/PHPMailer/src/PHPMailer.php';
require '../libs/PHPMailer/src/SMTP.php';

$msg    = '';
$error  = '';
$paso   = $_GET['paso'] ?? 'solicitar'; // 'solicitar' | 'restablecer'
$token  = $_GET['token'] ?? '';

// ════════════════════════════════════════════════════════════════════════════
// PASO 1 — SOLICITAR CORREO
// ════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar_reset'])) {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ingresa un email válido.';
    } else {
        $stmt = $conn->prepare("SELECT idUsuario, nombre, rol FROM usuario WHERE email=? AND estado='activo'");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || $user['rol'] !== 'administrador') {
            // No revelar si existe por seguridad, pero dar mensaje de éxito
            $msg = 'Si el correo pertenece a una cuenta activa, recibirás las instrucciones.';
        } else {
            $token_raw = bin2hex(random_bytes(32));
            $expira    = date('Y-m-d H:i:s', time() + 3600); // 1 hora
            $token_hash = hash('sha256', $token_raw);

            // Guardar token en BD
            $stmt_upd = $conn->prepare("UPDATE usuario SET reset_token=?, reset_expira=? WHERE idUsuario=?");
            $stmt_upd->bind_param("ssi", $token_hash, $expira, $user['idUsuario']);
            $stmt_upd->execute();

            // Configuración de Envío con PHPMailer
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
                $mail->addAddress($email, $user['nombre']);

                $link = "http://{$_SERVER['HTTP_HOST']}/DONAPP MVC/controller/recuperar_password.php?paso=restablecer&token=$token_raw";
                
                $mail->isHTML(true);
                $mail->Subject = 'DONAPP — Recuperar contraseña';
                $mail->Body    = "
                <div style='font-family: sans-serif; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <h2 style='color: #d32f2f;'>Hola {$user['nombre']},</h2>
                    <p>Recibimos una solicitud para restablecer tu contraseña en <strong>DONAPP</strong>.</p>
                    <p>Haz clic en el botón de abajo para continuar. Este enlace es válido por 1 hora:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$link' style='background: #d32f2f; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Restablecer Contraseña</a>
                    </div>
                    <p style='color: #666; font-size: 0.9rem;'>Si no solicitaste este cambio, puedes ignorar este correo de forma segura.</p>
                </div>";

                $mail->send();
                $msg = 'Si el correo pertenece a una cuenta activa, recibirás las instrucciones.';
            } catch (Exception $e) {
                $error = "No se pudo enviar el correo. Error: {$mail->ErrorInfo}";
            }
        }
    }
}

// ════════════════════════════════════════════════════════════════════════════
// PASO 2 — VERIFICAR TOKEN
// ════════════════════════════════════════════════════════════════════════════
if ($paso === 'restablecer' && $token) {
    $token_hash = hash('sha256', $token);
    $ahora      = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("SELECT idUsuario FROM usuario WHERE reset_token=? AND reset_expira > ? AND rol='administrador'");
    $stmt->bind_param("ss", $token_hash, $ahora);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        $error = 'El enlace es inválido o ya expiró. Solicita uno nuevo.';
        $paso  = 'solicitar';
    }
}

// ════════════════════════════════════════════════════════════════════════════
// PASO 3 — PROCESAR NUEVA CONTRASEÑA
// ════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_pass'])) {
    $token_post = trim($_POST['token']);
    $pass1      = $_POST['password'];
    $pass2      = $_POST['password_confirm'];
    $token_hash = hash('sha256', $token_post);
    $ahora      = date('Y-m-d H:i:s');

    // Mantener el token visible para el GET en caso de error
    $token = $token_post;

    $stmt = $conn->prepare("SELECT idUsuario FROM usuario WHERE reset_token=? AND reset_expira > ?");
    $stmt->bind_param("ss", $token_hash, $ahora);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        $error = 'Token inválido o expirado.';
        $paso  = 'solicitar';
    } elseif ($pass1 !== $pass2) {
        $error = 'Las contraseñas no coinciden.';
        $paso  = 'restablecer';
    } elseif (strlen($pass1) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
        $paso  = 'restablecer';
    } else {
        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        $stmt2 = $conn->prepare("UPDATE usuario SET contrasena=?, reset_token=NULL, reset_expira=NULL WHERE idUsuario=?");
        $stmt2->bind_param("si", $hash, $user['idUsuario']);
        $stmt2->execute();
        $msg  = 'Contraseña restablecida correctamente. Ya puedes iniciar sesión.';
        $paso = 'listo';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donapp — Recuperar contraseña</title>
    <link rel="icon" type="image/png" href="../assets/uploads/Icon.png">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="../assets/css/token.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>

<div class="recover-card">

    <a href="../index.php" class="logo-container">
            <img src="../assets/uploads/Red-Logo.png" alt="Logo Donapp" class="form-logo">
        </a>

    <h2><i class="fa-solid fa-lock-open"></i> Recuperar Contraseña</h2>

    <?php if ($msg):   ?><div class="msg-ok"><?php echo htmlspecialchars($msg);   ?></div><?php endif; ?>
    <?php if ($error): ?><div class="msg-err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <?php if ($paso === 'solicitar'): ?>
    <form method="POST">
        <div class="form-group">
            <label>Correo electrónico</label>
            <input type="email" name="email" class="form-input" required placeholder="Ingresa tu correo registrado">
        </div>
        <button type="submit" name="solicitar_reset" class="btn-submit">
            <i class="fa-solid fa-paper-plane"></i> Enviar instrucciones
        </button>
        <a href="../view/IniciarSesion.html" class="back-link" style="text-decoration: none;">← Volver al inicio de sesión</a>
    </form>

    <?php elseif ($paso === 'restablecer'): ?>
    <form method="POST" onsubmit="return checkPass(this)">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <div class="form-group">
            <label>Nueva contraseña</label>
            <input type="password" name="password" id="rp_pass1" class="form-input" required minlength="6" placeholder="Ingresa mínimo 6 caracteres">
        </div>
        <div class="form-group">
            <label>Confirmar contraseña</label>
            <input type="password" name="password_confirm" id="rp_pass2" class="form-input" required minlength="6" placeholder="Repite tu contraseña">
            <small id="rp_match_err" style="color:#c62828;display:none;">Las contraseñas no coinciden.</small>
        </div>
        <button type="submit" name="nueva_pass" class="btn-submit">
            <i class="fa-solid fa-floppy-disk"></i> Restablecer contraseña
        </button>
    </form>
    <script src="../assets/js/token.js"></script>

    <?php elseif ($paso === 'listo'): ?>
    <div style="text-align: center;">
        <p style="margin-bottom: 20px;">Tu contraseña ha sido actualizada.</p>
        <a href="../view/IniciarSesion.html" class="btn-submit" style="text-decoration: none;">
            <i class="fa-solid fa-right-to-bracket"></i> Ir al inicio de sesión
        </a>
    </div>
    <?php endif; ?>
</div>

</body>
</html>