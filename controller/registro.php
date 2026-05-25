<?php
include '../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitización de datos
    $nombre    = mysqli_real_escape_string($conn, trim($_POST['nombre']));
    $tipoDoc   = $_POST['tipo_doc'];
    $numDoc    = filter_var($_POST['numero_doc'], FILTER_SANITIZE_NUMBER_INT);
    $fechaNac  = $_POST['fecha_nac'];
    $direccion = mysqli_real_escape_string($conn, trim($_POST['direccion']));
    $email     = mysqli_real_escape_string($conn, trim($_POST['email']));
    $telefono  = mysqli_real_escape_string($conn, trim($_POST['telefono']));
    $necesidad = !empty($_POST['necesidad']) ? mysqli_real_escape_string($conn, trim($_POST['necesidad'])) : NULL;
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Inicio de la estructura HTML para inyectar SweetAlert con estilo Donapp
    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
        <style>
            body { 
                font-family: "DM Sans", sans-serif; 
                background-color: #f8f9fa; 
            }
            /* Estilos unificados Donapp */
            .donapp-popup {
                border-radius: 28px !important;
                padding: 2.5rem !important;
            }
            .donapp-title {
                color: #2d2d2d !important;
                font-weight: 700 !important;
            }
            .donapp-confirm-btn {
                border-radius: 12px !important;
                padding: 12px 35px !important;
                font-weight: 700 !important;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
        </style>
    </head>
    <body>';

    // 1. Validar duplicados (Correo o Documento)
    $checkQuery = "SELECT email, numDocumento FROM usuario WHERE email = '$email' OR numDocumento = '$numDoc'";
    $resultCheck = $conn->query($checkQuery);

    if ($resultCheck && $resultCheck->num_rows > 0) {
        echo "<script>
            Swal.fire({
                icon: 'warning',
                title: '¡Ya registrado!',
                text: 'El correo electrónico o número de documento ya existen en nuestro sistema.',
                confirmButtonColor: '#df0b0b',
                confirmButtonText: 'CORREGIR DATOS',
                customClass: {
                    popup: 'donapp-popup',
                    title: 'donapp-title',
                    confirmButton: 'donapp-confirm-btn'
                }
            }).then(() => {
                window.history.back();
            });
        </script>";
    } else {
        // 2. Insertar nuevo usuario (por defecto rol cliente y estado activo)
        $sql = "INSERT INTO usuario (nombre, tipoDocumento, numDocumento, fechaNacimiento, direccion, email, contrasena, telefono, necesidad, rol, estado) 
                VALUES ('$nombre', '$tipoDoc', '$numDoc', '$fechaNac', '$direccion', '$email', '$password', '$telefono', " . ($necesidad ? "'$necesidad'" : "NULL") . ", 'donante', 'activo')";

        if ($conn->query($sql) === TRUE) {
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: '¡Bienvenido a Donapp!',
                    text: 'Tu cuenta ha sido creada exitosamente. Ahora puedes iniciar sesión.',
                    confirmButtonColor: '#df0b0b',
                    confirmButtonText: 'IR AL LOGIN',
                    customClass: {
                        popup: 'donapp-popup',
                        title: 'donapp-title',
                        confirmButton: 'donapp-confirm-btn'
                    }
                }).then(() => {
                    window.location.href = '../view/IniciarSesion.html';
                });
            </script>";
        } else {
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error de registro',
                    text: 'No pudimos procesar tu solicitud en este momento.',
                    confirmButtonColor: '#811212',
                    confirmButtonText: 'REINTENTAR',
                    customClass: {
                        popup: 'donapp-popup',
                        title: 'donapp-title',
                        confirmButton: 'donapp-confirm-btn'
                    }
                }).then(() => {
                    window.history.back();
                });
            </script>";
        }
    }

    echo '</body></html>';
    $conn->close();
}
?>