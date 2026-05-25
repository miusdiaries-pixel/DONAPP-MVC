<?php
session_start();
include '../config/conexion.php'; 

// Función mejorada con estilos personalizados de Donapp
function mostrarAlerta($icono, $titulo, $texto, $url) {
    echo "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <link href='https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap' rel='stylesheet'>
        <style>
            body { 
                font-family: 'DM Sans', sans-serif; 
                background-color: #f8f9fa; 
            }
            .donapp-popup {
                border-radius: 28px !important;
                padding: 2rem !important;
            }
            .donapp-title {
                color: #2d2d2d !important;
                font-weight: 700 !important;
            }
            .donapp-confirm-btn {
                border-radius: 12px !important;
                padding: 12px 30px !important;
                font-weight: 700 !important;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
        </style>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: '$icono',
                title: '$titulo',
                text: '$texto',
                confirmButtonColor: '#df0b0b',
                confirmButtonText: 'ENTENDIDO',
                customClass: {
                    popup: 'donapp-popup',
                    title: 'donapp-title',
                    confirmButton: 'donapp-confirm-btn'
                },
                showClass: {
                    popup: 'animate__animated animate__fadeInUp'
                }
            }).then((result) => {
                window.location.href = '$url';
            });
        </script>
    </body>
    </html>";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['contrasena']; 

    // Consultamos la columna 'contrasena' tal como está en tu DB
    $sql = "SELECT idUsuario, nombre, contrasena, rol, estado FROM usuario WHERE email = '$email'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // 1. Verificación de estado
        if ($user['estado'] !== 'activo') {
            mostrarAlerta('error', '¡Cuenta inactiva!', 'Tu acceso ha sido restringido por el administrador.', '../view/IniciarSesion.html');
            exit();
        }

        // 2. Verificación de contraseña
        if (password_verify($password, $user['contrasena'])) {
            
            // ── VARIABLES DE SESIÓN CRÍTICAS ─────────────────────────────────
            $_SESSION['idUsuario']     = $user['idUsuario'];
            $_SESSION['nombre']        = $user['nombre'];
            $_SESSION['rol']           = $user['rol'];
            
            // ESTA LÍNEA ES LA QUE CORRIGE EL ERROR EN LOS PANELES:
            // Guardamos el hash actual para la validación en tiempo real
            $_SESSION['password_hash'] = $user['contrasena']; 
            // ────────────────────────────────────────────────────────────────

            // Redirección según rol
            $ruta = "../view/user_dashboard.php"; // Para donantes/solicitantes
            if ($user['rol'] == 'administrador') {
                $ruta = "../view/admin_dashboard.php";
            } elseif ($user['rol'] == 'asistente') {
                $ruta = "../view/asis_dashboard.php";
            }

            mostrarAlerta('success', '¡Bienvenido!', 'Hola ' . $user['nombre'] . ', redirigiéndote a tu panel...', $ruta);
            exit();
        } else {
            mostrarAlerta('error', 'Contraseña incorrecta', 'Verifica tus credenciales e intenta nuevamente.', '../view/IniciarSesion.html');
        }
    } else {
        mostrarAlerta('warning', 'Usuario no registrado', 'No encontramos una cuenta con ese correo electrónico.', '../view/IniciarSesion.html');
    }
}
?>