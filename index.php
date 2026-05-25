<?php
// Configuración de conexión
$host = "localhost";
$user = "root";
$pass = "";
$db   = "donapp";

$conn = new mysqli($host, $user, $pass, $db);

// Verificar conexión
if ($conn->connect_error) {
    $stats_usuarios   = 0;
    $stats_donaciones = 0;
    $stats_eventos    = 0;
    $publicaciones    = [];
} else {
    // Estadísticas del hero
    $res_c = $conn->query("SELECT COUNT(*) as total FROM usuario WHERE rol = 'donante'");
    $stats_usuarios = $res_c ? $res_c->fetch_assoc()['total'] : 0;

    $res_d = $conn->query("SELECT COUNT(*) as total FROM donacion");
$stats_donaciones = $res_d ? $res_d->fetch_assoc()['total'] : 0;

    $res_e = $conn->query("SELECT COUNT(*) as total FROM evento WHERE estado = 'activo'");
    $stats_eventos = $res_e ? $res_e->fetch_assoc()['total'] : 0;

    // CONSULTA CORREGIDA: Trae datos de ProgramadorEventos explícitamente
    $res_p = $conn->query("
    SELECT 
        p.titulo,
        p.contenido,
        p.imagen, -- <--- FUNDAMENTAL
        p.fechaPublicacion,
        u.nombre AS subidoPor,
        e.Nombre AS evento,
        e.estado AS evento_estado,
        pe.FechaEntrega,
        pe.Lugar
    FROM publicacion p
    INNER JOIN usuario u ON p.idUsuario = u.idUsuario
    LEFT JOIN evento e ON p.idEvento = e.idEvento
    LEFT JOIN ProgramadorEventos pe ON e.idEvento = pe.idEvento
    ORDER BY p.fechaPublicacion DESC
");
    
    $publicaciones = [];
    if ($res_p) {
        while ($row = $res_p->fetch_assoc()) {
            $publicaciones[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donapp</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="icon" type="image/png" href="assets/uploads/Icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <!-- LANDING PAGE -->
    <div id="landing-page" class="page active">

        <!-- ===== HERO CON VIDEO DE FONDO ===== -->
        <section class="hero-video-section">
            <!-- Partículas animadas (fallback visual si no hay video) -->
            <div class="hero-particles" id="particles-container"></div>

            <!-- Overlay con gradiente -->
            <div class="hero-overlay"></div>

            <div class="hero-video-content">
                <a href="index.php"><img src="assets/uploads/White Logo.png" alt="Donapp Logo" class="logo-site"></a>
                <h1 class="hero-title">
                    <span class="title-gradient">Dona</span> lo que no usas,<br>
                    <span class="title-gradient">Recibe</span> lo que necesitas
                </h1>
                <p class="hero-subtitle">
                    Conectamos a la comunidad de Ciudad Bolívar a través de donaciones seguras y verificadas para la fundación CES Waldorf.
                </p>
                <div class="hero-buttons">
                    <a class="btn btn-primary" href="view/IniciarSesion.html">Iniciar Sesión</a>
                    <a class="btn btn-secondary" href="view/Registrar.html">Registrarse</a>
                </div>

                <!-- Estadísticas flotantes -->
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="stat-icon">🎁</span>
                        <div>
                            <strong>+<?php echo $stats_donaciones; ?></strong>
                            <span>Donaciones</span>
                        </div>
                    </div>
                    <div class="hero-stat">
                        <span class="stat-icon">👥</span>
                        <div>
                            <strong>+<?php echo $stats_usuarios; ?></strong>
                            <span>Usuarios</span>
                        </div>
                    </div>
                    <div class="hero-stat">
                        <span class="stat-icon">📅</span>
                        <div>
                            <strong><?php echo $stats_eventos; ?></strong>
                            <span>Eventos activos</span>
                        </div>
                    </div>
                    <div class="hero-stat">
                        <span class="stat-icon">📍</span>
                        <div>
                            <strong>1</strong>
                            <span>Punto de entrega</span>
                        </div>
                    </div>
                </div>
            </div>

        </section>

        <div class="landing-container">

            <!-- ===== CÓMO FUNCIONA ===== -->
            <div class="features-section">
                <h2 class="section-title">¿Cómo funciona?</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-number">01</div>
                        <h3>Regístrate</h3>
                        <p>Crea tu cuenta en Donapp y forma parte de la comunidad solidaria de Ciudad Bolívar</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-number">02</div>
                        <h3>Publica</h3>
                        <p>Ofrece productos que ya no usas o solicita lo que necesitas a través de la plataforma</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-number">03</div>
                        <h3>Acércate</h3>
                        <p>La fundación CES Waldorf gestiona la entrega. Todo se hace en Sierra Morena, Ciudad Bolívar</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-number">04</div>
                        <h3>Impacta</h3>
                        <p>Cada donación ayuda a reutilizar y darle una segunda oportunidad a los productos</p>
                    </div>
                </div>
            </div>

            <!-- ===== SOBRE NOSOTROS ===== -->
            <section class="sobre-nosotros-section">
                <div class="sobre-content">
                    <div class="sobre-text">
                        <span class="sobre-tag">¿Quiénes somos?</span>
                        <h2 class="sobre-title">Fundación CES Waldorf</h2>
                        <p class="sobre-description">
                            Somos una organización ubicada en <strong>Sierra Morena, Ciudad Bolívar</strong>, dedicada a promover el desarrollo integral del ser humano a través del arte.
                            Trabajamos con niños, jóvenes y adultos en la transformación de sus condiciones de vida.
                        </p>
                        <div class="sobre-mision-vision">
                            <div class="mv-card">
                                <div class="mv-icon"><i class="fa-solid fa-bullseye"></i></div>
                                <div>
                                    <h4>Misión</h4>
                                    <p>Corporación sin ánimo de lucro que contribuye a la formación de niños, jóvenes y familias en condición de vulnerabilidad, mediante programas educativos, artísticos y culturales que rescaten su individualidad y promuevan el desarrollo solidario y comunitario.</p>
                                </div>
                            </div>
                            <div class="mv-card">
                                <div class="mv-icon"><i class="fa-solid fa-eye"></i></div>
                                <div>
                                    <h4>Visión</h4>
                                    <p>Ser una entidad reconocida por su proyección social y estrategias de desarrollo autosostenible, por la dignificación de las condiciones de vida y la protección de los derechos humanos de las comunidades marginales.</p>
                                </div>
                            </div>
                        </div>
                        <a href="https://www.ceswaldorf.org/" target="_blank" class="btn btn-primary sobre-btn">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            Conoce más sobre nosotros
                        </a>
                    </div>
                    <div class="sobre-visual">
                        <div class="sobre-badge-grid">
                            <div class="sobre-badge">
                                <i class="fa-solid fa-child-reaching"></i>
                                <span>Niños y jóvenes</span>
                            </div>
                            <div class="sobre-badge">
                                <i class="fa-solid fa-palette"></i>
                                <span>Arte y cultura</span>
                            </div>
                            <div class="sobre-badge">
                                <i class="fa-solid fa-heart"></i>
                                <span>Comunidad</span>
                            </div>
                            <div class="sobre-badge">
                                <i class="fa-solid fa-graduation-cap"></i>
                                <span>Educación</span>
                            </div>
                            <div class="sobre-badge sobre-badge-main">
                                <i class="fa-solid fa-location-dot"></i>
                                <a href="https://maps.app.goo.gl/vnfgQmNTdWB2v4Yg7" class="link-sin-subrayado"><span>Sierra Morena<br>Ciudad Bolívar</span></a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===== PUBLICACIONES / EVENTOS ===== -->
<div class="publicaciones-section">
    <h2 class="section-title">Próximos Eventos</h2>
    <p class="section-subtitle">Entérate de las próximas actividades de la fundación</p>

    <?php if (empty($publicaciones)): ?>
        <div class="publicaciones-empty">
            <i class="fa-regular fa-calendar-xmark"></i>
            <p>No hay publicaciones disponibles por el momento.</p>
        </div>
    <?php else: ?>
        <div class="publicaciones-grid">
            <?php foreach ($publicaciones as $pub): 
                // Convertir imagen a Base64
                $imagenBase64 = '';
                if (!empty($pub['imagen'])) {
                    $imagenBase64 = 'data:image/png;base64,' . base64_encode($pub['imagen']);
                }

                // Preparamos un objeto limpio para enviarlo a JS sin errores de comillas
                $datosJson = json_encode([
                    'titulo' => $pub['titulo'],
                    'contenido' => $pub['contenido'],
                    'evento' => $pub['evento'],
                    'fecha' => date('d/m/Y', strtotime($pub['fechaPublicacion'])),
                    'entrega' => !empty($pub['FechaEntrega']) ? date('d/m/Y', strtotime($pub['FechaEntrega'])) : 'Pendiente',
                    'lugar' => $pub['Lugar'] ?? 'No especificado',
                    'autor' => $pub['subidoPor'],
                    'estado' => $pub['evento_estado'],
                    'imagen' => $imagenBase64
                ], JSON_HEX_QUOT | JSON_HEX_APOS);
            ?>
                <div class="publicacion-card <?php echo strtolower($pub['evento_estado']) === 'activo' ? 'publicacion-activa' : 'publicacion-inactiva'; ?>" 
                     onclick='verDetallePublicacion(<?php echo $datosJson; ?>)'>

                    <div class="publicacion-header">
                        <span class="publicacion-badge <?php echo strtolower($pub['evento_estado']) === 'activo' ? 'badge-activo' : 'badge-inactivo'; ?>">
                            <?php echo strtolower($pub['evento_estado']) === 'activo' ? 'Activo' : 'Finalizado'; ?>
                        </span>
                        <span class="publicacion-fecha">
                            <i class="fa-regular fa-calendar"></i>
                            <?php echo date('d/m/Y', strtotime($pub['fechaPublicacion'])); ?>
                        </span>
                    </div>

                    <div class="publicacion-body">
                        <h3 class="publicacion-titulo"><?php echo htmlspecialchars($pub['titulo']); ?></h3>
                        <p class="publicacion-evento">
                            <i class="fa-solid fa-tag"></i>
                            <?php echo htmlspecialchars($pub['evento']); ?>
                        </p>
                        <p class="publicacion-contenido"><?php echo mb_strimwidth(htmlspecialchars($pub['contenido']), 0, 100, "..."); ?></p>
                    </div>

                    <div class="publicacion-footer">
                        <p class="publicacion-autor">Ver detalles <i class="fa-solid fa-plus"></i></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

        </div><!-- /.landing-container -->
    </div><!-- /#landing-page -->

    <!-- ===== MODAL DETALLE PUBLICACIÓN ===== -->
    <div id="modal-detalle-publicacion" class="modal">
        <div class="modal-content detalle-publicacion-content">
            <button class="modal-close" onclick="closeModal('modal-detalle-publicacion')">&times;</button>
            
            <div id="detalle-header"></div>

            <div class="detalle-body">
                <h2 id="detalle-titulo" class="hero-title"></h2>
                <p id="detalle-evento" class="publicacion-evento"></p>
                
                <div class="detalle-info-box">
                    <h3><i class="fa-solid fa-circle-info"></i> Descripción</h3>
                    <p id="detalle-contenido"></p>
                </div>

                <div class="detalle-grid">
                    <div class="detalle-item">
                        <i class="fa-solid fa-truck"></i>
                        <div>
                            <strong>Fecha Programada</strong>
                            <p id="detalle-entrega"></p>
                        </div>
                    </div>
                    <div class="detalle-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <div>
                            <strong>Lugar</strong>
                            <p id="detalle-lugar"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detalle-footer">
                <p id="detalle-autor"></p>
                <button class="btn btn-primary btn-full" onclick="closeModal('modal-detalle-publicacion')">Entendido</button>
            </div>
        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer class="main-footer">
        <div class="footer-content">
            <div class="footer-brand">
                <a href="index.php">
   <img src="assets/uploads/Red Logo.png" alt="Donapp" class="footer-logo">
</a>
                <p>Plataforma de donaciones para la comunidad de Ciudad Bolívar.</p>
            </div>
            <div class="footer-info">
                <p><i class="fa-solid fa-location-dot"></i><a href="https://maps.app.goo.gl/vnfgQmNTdWB2v4Yg7">Transversal 73 H Bis # 75B - 46 Sur, Sierra Morena V Sector, Ciudad Bolívar, Bogotá</a></p>
                <p><i class="fa-solid fa-building"></i> Corporación Educativa y Social WALDORF</p>
                <a href="https://www.ceswaldorf.org/" target="_blank">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> ceswaldorf.org
                </a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Donapp – Corporación Educatica y Social WALDORF. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="assets/js/index.js"></script>
    <script>
        // Partículas animadas para el hero
        (function() {
            const container = document.getElementById('particles-container');
            if (!container) return;
            const count = 40;
            for (let i = 0; i < count; i++) {
                const p = document.createElement('div');
                p.className = 'particle';
                p.style.cssText = `
                    left: ${Math.random() * 100}%;
                    top: ${Math.random() * 100}%;
                    width: ${4 + Math.random() * 8}px;
                    height: ${4 + Math.random() * 8}px;
                    animation-delay: ${Math.random() * 8}s;
                    animation-duration: ${6 + Math.random() * 10}s;
                    opacity: ${0.1 + Math.random() * 0.4};
                `;
                container.appendChild(p);
            }
        })();

        // Scroll suave al bajar
        document.querySelector('.scroll-indicator')?.addEventListener('click', () => {
            document.querySelector('.features-section')?.scrollIntoView({ behavior: 'smooth' });
        });
    </script>
</body>
</html>