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
                $mail->Username = MAIL_USERNAME;
                $mail->Password = MAIL_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom('no-reply@donapp.com', 'DONAPP Equipo');
                $mail->addAddress($email, $user['nombre']);

                $link = "http://{$_SERVER['HTTP_HOST']}/donapp/controller/recuperar_password.php?paso=restablecer&token=$token_raw";
                
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --color-primary: #df0b0b;
            --color-primary-dark: #811212;
        }

        body { 
            display:flex; 
            align-items:center; 
            justify-content:center; 
            min-height:100vh; 
            background-color: #f8f9fa;
            margin:0; 
            font-family: 'DM Sans', sans-serif; 
            overflow: hidden;
            position: relative;
        }

        /* ═══════════════════════════════════════════
           FONDO ANIMADO EN PATRÓN (LOOP)
           ═══════════════════════════════════════════ */
        body::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background-image: url('../assets/uploads/Red Logo.png'); 
            background-repeat: repeat;
            background-size: 110px;
            opacity: 0.06;
            transform: rotate(-15deg);
            animation: moveBackground 20s linear infinite;
            z-index: -1;
        }

        @keyframes moveBackground {
            from { transform: rotate(-15deg) translateY(0); }
            to { transform: rotate(-15deg) translateY(110px); }
        }

        .recover-card { 
            background: #ffffff; 
            border-radius: 16px; 
            padding: 40px; 
            max-width: 420px; 
            width: 90%; 
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1); 
            z-index: 1;
        }

        .recover-card h2 { margin-bottom: 24px; color: var(--color-primary); text-align: center; }
        
        .msg-ok  { color: #2e7d32; background: #e8f5e9; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; text-align: center; font-size: 0.9rem; border-left: 4px solid #2e7d32; }
        .msg-err { color: #c62828; background: #ffebee; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; text-align: center; font-size: 0.9rem; border-left: 4px solid #c62828; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-input { 
            width: 100%; 
            padding: 12px; 
            border: 1.5px solid #e5e7eb; 
            border-radius: 10px; 
            outline: none; 
            box-sizing: border-box;
            transition: 0.3s;
        }
        .form-input:focus { border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(223, 11, 11, 0.1); }

        .btn-submit {
            background: var(--color-primary);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 10px;
            width: 100%;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover { background: var(--color-primary-dark); transform: translateY(-2px); }

        .back-link { 
            margin-top: 20px; 
            display: block; 
            text-align: center; 
            color: var(--color-primary); 
            text-decoration: none; 
            font-size: 0.95rem;
            font-weight: 500;
        }
        .back-link:hover { text-decoration: underline; }

    .logo-container {
    display: block;
    text-align: center;
    margin-bottom: 0;
}

.form-logo {
    width: 150px;
    margin-bottom: 0px;
    transition: transform 0.3s ease;
}

.form-logo:hover {
    transform: scale(1.05);
}

    </style>
</head>
<body>

<div class="recover-card">

    <a href="../index.php" class="logo-container">
            <img src="../assets/uploads/Red Logo.png" alt="Logo Donapp" class="form-logo">
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
    <script>
    function checkPass(form) {
        const p1 = document.getElementById('rp_pass1').value;
        const p2 = document.getElementById('rp_pass2').value;
        const err = document.getElementById('rp_match_err');
        if (p1 !== p2) {
            err.style.display = 'block';
            document.getElementById('rp_pass2').focus();
            return false;
        }
        err.style.display = 'none';
        return true;
    }
    document.getElementById('rp_pass2').addEventListener('input', function() {
        const p1 = document.getElementById('rp_pass1').value;
        const err = document.getElementById('rp_match_err');
        err.style.display = (this.value && this.value !== p1) ? 'block' : 'none';
    });
    </script>

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