<?php
require_once __DIR__ . '/../controller/user.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DONAPP — Mi Panel</title>
    <link rel="icon" type="image/png" href="../assets/uploads/Icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/usuario_style.css">
</head>
<body>

<?php if ($flash): ?>
<div class="flash-msg" id="flashMsg"><?php echo htmlspecialchars($flash); ?></div>
<?php endif; ?>

<div class="sidebar">
    <div class="sidebar-logo">
        <a href="../index.php"><img src="../assets/uploads/Red Logo.png" alt="Donapp" onerror="this.style.display='none'"></a>
        <span class="sidebar-role">Donante / Solicitante</span>
        <div class="sidebar-username"><?php echo htmlspecialchars($cliente['nombre']); ?></div>
    </div>
    <ul class="nav-menu">
        <li><a href="?tab=inicio"      class="nav-link <?php echo $tab_activo==='inicio'      ?'active':''; ?>"><i class="fa-solid fa-house"></i><span> Inicio</span></a></li>
        <li><a href="?tab=donaciones"  class="nav-link <?php echo $tab_activo==='donaciones'  ?'active':''; ?>"><i class="fa-solid fa-box-open"></i><span> Mis Donaciones</span></a></li>
        <li><a href="?tab=solicitudes" class="nav-link <?php echo $tab_activo==='solicitudes' ?'active':''; ?>"><i class="fa-solid fa-clipboard-list"></i><span> Mis Solicitudes</span></a></li>
        <li><a href="?tab=eventos"     class="nav-link <?php echo $tab_activo==='eventos'     ?'active':''; ?>"><i class="fa-solid fa-calendar-days"></i><span> Eventos</span></a></li>
        <li><a href="?tab=perfil"      class="nav-link <?php echo $tab_activo==='perfil'      ?'active':''; ?>"><i class="fa-solid fa-user-gear"></i><span> Mi Perfil</span></a></li>
        <li><hr></li>
        <li><a href="../controller/logout.php" class="nav-link logout"><i class="fa-solid fa-power-off"></i><span> Cerrar Sesión</span></a></li>
    </ul>
</div>

<main class="main-content">

    <!-- ══════════════ INICIO ══════════════ -->
    <div id="inicio" class="tab-pane <?php echo $tab_activo==='inicio' ? 'active' : ''; ?>">

        <div class="welcome-hero">
            <h1>Hola, <?php echo htmlspecialchars(explode(' ', $cliente['nombre'])[0]); ?> 👋</h1>
            <p>Bienvenido a tu panel personal de DONAPP — aquí puedes gestionar tus donaciones y solicitudes.</p>
            <div class="hero-actions">
                <button class="btn-hero btn-hero-primary" onclick="abrirModal('modalNuevaDonacion')">
                    <i class="fa-solid fa-box-open"></i> Nueva Donación
                </button>
                <button class="btn-hero btn-hero-outline" onclick="abrirModal('modalNuevaSolicitud')">
                    <i class="fa-solid fa-clipboard-list"></i> Nueva Solicitud
                </button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fa-solid fa-box-open"></i></div>
                <div><h3><?php echo $stats['total_don']; ?></h3><p>Total donaciones</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-clock"></i></div>
                <div><h3><?php echo $stats['don_pendientes']; ?></h3><p>Donaciones pendientes</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
                <div><h3><?php echo $stats['don_aprobadas']; ?></h3><p>Donaciones aprobadas</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fa-solid fa-clipboard-list"></i></div>
                <div><h3><?php echo $stats['total_sol']; ?></h3><p>Total solicitudes</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
                <div><h3><?php echo $stats['sol_pendientes']; ?></h3><p>Solicitudes pendientes</p></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-handshake-angle"></i></div>
                <div><h3><?php echo $stats['sol_aprobadas']; ?></h3><p>Solicitudes aprobadas</p></div>
            </div>
        </div>

        <?php if (!empty($eventos)): ?>
        <div class="card">
            <div class="card-title"><i class="fa-solid fa-calendar-star"></i> Próximos Eventos Activos</div>
            <div class="eventos-grid">
            <?php foreach (array_slice($eventos, 0, 3) as $ev): ?>
                <div class="event-card">
                    <?php if ($ev['imagen']): ?>
                    <img src="<?php echo $ev['imagen']; ?>" alt="Evento" class="event-card-img">
                    <?php else: ?>
                    <div class="event-card-noimg"><i class="fa-solid fa-calendar-days"></i></div>
                    <?php endif; ?>
                    <div class="event-card-body">
                        <h3><?php echo htmlspecialchars($ev['Nombre']); ?></h3>
                        <?php if ($ev['titulo']): ?><p><?php echo htmlspecialchars(mb_substr($ev['contenido'], 0, 100)).'...'; ?></p><?php endif; ?>
                        <div class="event-meta">
                            <?php if ($ev['FechaEntrega']): ?>
                            <span><i class="fa-solid fa-calendar"></i> <?php echo date('d/m/Y', strtotime($ev['FechaEntrega'])); ?></span>
                            <?php endif; ?>
                            <?php if ($ev['Lugar']): ?>
                            <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars(mb_substr($ev['Lugar'], 0, 40)); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php if (count($eventos) > 3): ?>
            <div class="eventos-more">
                <a href="?tab=eventos" class="btn btn-secondary btn-sm">
                    Ver todos los eventos <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- ══════════════ DONACIONES ══════════════ -->
    <div id="donaciones" class="tab-pane <?php echo $tab_activo==='donaciones' ? 'active' : ''; ?>">
        <div class="section-header">
            <div>
                <h2 class="page-title">Mis Donaciones</h2>
                <p class="page-subtitle">Consulta y gestiona las donaciones que has registrado.</p>
            </div>
            <button class="btn btn-primary" onclick="abrirModal('modalNuevaDonacion')">
                <i class="fa-solid fa-plus"></i> Nueva Donación
            </button>
        </div>

        <div class="card">
            <form method="GET" class="filter-bar" id="formFiltrosDon">
                <input type="hidden" name="tab" value="donaciones">
                <input type="text" name="don_buscar" class="form-input search-input"
                       placeholder="🔍 Buscar por descripción..."
                       value="<?php echo htmlspecialchars($don_buscar_f); ?>"
                       maxlength="200"
                       onchange="submitFiltroTab(this.form,'donaciones')">
                <select name="don_estado" class="form-input" onchange="submitFiltroTab(this.form,'donaciones')">
                    <option value="">Todos los estados</option>
                    <option value="pendiente"  <?php echo $don_estado_f==='pendiente' ?'selected':''; ?>>Pendiente</option>
                    <option value="aprobada"   <?php echo $don_estado_f==='aprobada'  ?'selected':''; ?>>Aprobada</option>
                    <option value="rechazada"  <?php echo $don_estado_f==='rechazada' ?'selected':''; ?>>Rechazada</option>
                </select>
                <select name="don_cat" class="form-input" onchange="submitFiltroTab(this.form,'donaciones')">
                    <option value="0">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat['idCategoria']; ?>" <?php echo $don_cat_f==$cat['idCategoria']?'selected':''; ?>>
                        <?php echo htmlspecialchars($cat['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <a href="?tab=donaciones" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-xmark"></i> Limpiar filtros
                </a>
            </form>

            <?php if (empty($mis_donaciones)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fa-solid fa-box-open"></i></div>
                <h3>Sin donaciones registradas</h3>
                <p>Aún no has realizado ninguna donación<?php echo ($don_estado_f || $don_cat_f) ? ' con estos filtros' : ''; ?>.</p>
                <?php if (!$don_estado_f && !$don_cat_f): ?>
                <button class="btn btn-primary" onclick="abrirModal('modalNuevaDonacion')">
                    <i class="fa-solid fa-plus"></i> Hacer mi primera donación
                </button>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><a href="<?php echo sort_toggle_url('don_sort','don_dir','idDonacion'); ?>&tab=donaciones#donaciones" style="color:inherit;text-decoration:none;">#<?php echo sort_icon('don_sort','don_dir','idDonacion'); ?></a></th>
                        <th><a href="<?php echo sort_toggle_url('don_sort','don_dir','descripcion'); ?>&tab=donaciones#donaciones" style="color:inherit;text-decoration:none;">Descripción<?php echo sort_icon('don_sort','don_dir','descripcion'); ?></a></th>
                        <th>Categoría</th>
                        <th><a href="<?php echo sort_toggle_url('don_sort','don_dir','stock'); ?>&tab=donaciones#donaciones" style="color:inherit;text-decoration:none;">Stock<?php echo sort_icon('don_sort','don_dir','stock'); ?></a></th>
                        <th><a href="<?php echo sort_toggle_url('don_sort','don_dir','estado'); ?>&tab=donaciones#donaciones" style="color:inherit;text-decoration:none;">Estado<?php echo sort_icon('don_sort','don_dir','estado'); ?></a></th>
                        <th><a href="<?php echo sort_toggle_url('don_sort','don_dir','fechaCreacion'); ?>&tab=donaciones#donaciones" style="color:inherit;text-decoration:none;">Fecha<?php echo sort_icon('don_sort','don_dir','fechaCreacion'); ?></a></th>
                        <th>Observación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($mis_donaciones as $d): ?>
                <tr>
                    <td><?php echo $d['idDonacion']; ?></td>
                    <td class="td-desc" title="<?php echo htmlspecialchars($d['descripcion']); ?>"><?php echo htmlspecialchars($d['descripcion']); ?></td>
                    <td class="td-cat"  title="<?php echo htmlspecialchars($d['categoria'] ?? '—'); ?>"><?php echo htmlspecialchars($d['categoria'] ?? '—'); ?></td>
                    <td><?php echo $d['stock']; ?></td>
                    <td><span class="badge estado-<?php echo $d['estado']; ?>"><?php echo $d['estado']; ?></span></td>
                    <td><?php echo date('d/m/Y', strtotime($d['fechaCreacion'])); ?></td>
                    <td class="td-obs" title="<?php echo htmlspecialchars($d['observacion'] ?? ''); ?>">
                        <?php echo $d['observacion'] ? htmlspecialchars($d['observacion']) : '—'; ?>
                    </td>
                    <td class="td-actions">
                        <button onclick='verDetalleDonacion(<?php echo json_encode($d); ?>)'
                                class="btn btn-sm btn-secondary" title="Ver detalle">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <?php if ($d['estado'] === 'pendiente'): ?>
                        <button onclick='abrirModalEditarDonacion(<?php echo json_encode($d); ?>)'
                                class="btn btn-sm btn-primary" title="Editar">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <a href="../controller/acciones_usuario.php?cancelar_donacion=<?php echo $d['idDonacion']; ?>"
                           onclick="return confirm('¿Cancelar esta donación? Esta acción no se puede deshacer.')"
                           class="btn btn-sm btn-danger" title="Cancelar">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══════════════ SOLICITUDES ══════════════ -->
    <div id="solicitudes" class="tab-pane <?php echo $tab_activo==='solicitudes' ? 'active' : ''; ?>">
        <div class="section-header">
            <div>
                <h2 class="page-title">Mis Solicitudes</h2>
                <p class="page-subtitle">Revisa el estado de tus solicitudes de ayuda.</p>
            </div>
            <button class="btn btn-primary" onclick="abrirModal('modalNuevaSolicitud')">
                <i class="fa-solid fa-plus"></i> Nueva Solicitud
            </button>
        </div>

        <div class="card">
            <form method="GET" class="filter-bar" id="formFiltrosSol">
                <input type="hidden" name="tab" value="solicitudes">
                <input type="text" name="sol_buscar" class="form-input search-input"
                       placeholder="🔍 Buscar por descripción..."
                       value="<?php echo htmlspecialchars($sol_buscar_f); ?>"
                       maxlength="200"
                       onchange="submitFiltroTab(this.form,'solicitudes')">
                <select name="sol_estado" class="form-input" onchange="submitFiltroTab(this.form,'solicitudes')">
                    <option value="">Todos los estados</option>
                    <option value="pendiente"  <?php echo $sol_estado_f==='pendiente' ?'selected':''; ?>>Pendiente</option>
                    <option value="aprobada"   <?php echo $sol_estado_f==='aprobada'  ?'selected':''; ?>>Aprobada</option>
                    <option value="rechazada"  <?php echo $sol_estado_f==='rechazada' ?'selected':''; ?>>Rechazada</option>
                </select>
                <select name="sol_cat" class="form-input" onchange="submitFiltroTab(this.form,'solicitudes')">
                    <option value="0">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat['idCategoria']; ?>" <?php echo $sol_cat_f==$cat['idCategoria']?'selected':''; ?>>
                        <?php echo htmlspecialchars($cat['nombre']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <a href="?tab=solicitudes" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-xmark"></i> Limpiar filtros
                </a>
            </form>

            <?php if (empty($mis_solicitudes)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                <h3>Sin solicitudes registradas</h3>
                <p>No tienes solicitudes<?php echo ($sol_estado_f || $sol_cat_f) ? ' con estos filtros' : ' registradas aún'; ?>.</p>
                <?php if (!$sol_estado_f && !$sol_cat_f): ?>
                <button class="btn btn-primary" onclick="abrirModal('modalNuevaSolicitud')">
                    <i class="fa-solid fa-plus"></i> Hacer mi primera solicitud
                </button>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th><a href="<?php echo sort_toggle_url('sol_sort','sol_dir','idSolicitud'); ?>&tab=solicitudes#solicitudes" style="color:inherit;text-decoration:none;">#<?php echo sort_icon('sol_sort','sol_dir','idSolicitud'); ?></a></th>
                        <th><a href="<?php echo sort_toggle_url('sol_sort','sol_dir','descripcion'); ?>&tab=solicitudes#solicitudes" style="color:inherit;text-decoration:none;">Descripción<?php echo sort_icon('sol_sort','sol_dir','descripcion'); ?></a></th>
                        <th>Categoría</th>
                        <th><a href="<?php echo sort_toggle_url('sol_sort','sol_dir','estado'); ?>&tab=solicitudes#solicitudes" style="color:inherit;text-decoration:none;">Estado<?php echo sort_icon('sol_sort','sol_dir','estado'); ?></a></th>
                        <th><a href="<?php echo sort_toggle_url('sol_sort','sol_dir','fechaCreacion'); ?>&tab=solicitudes#solicitudes" style="color:inherit;text-decoration:none;">Fecha<?php echo sort_icon('sol_sort','sol_dir','fechaCreacion'); ?></a></th>
                        <th>Observación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($mis_solicitudes as $s): ?>
                <tr>
                    <td><?php echo $s['idSolicitud']; ?></td>
                    <td class="td-desc" title="<?php echo htmlspecialchars($s['descripcion']); ?>"><?php echo htmlspecialchars($s['descripcion']); ?></td>
                    <td class="td-cat"  title="<?php echo htmlspecialchars($s['categoria'] ?? '—'); ?>"><?php echo htmlspecialchars($s['categoria'] ?? '—'); ?></td>
                    <td><span class="badge estado-<?php echo $s['estado']; ?>"><?php echo $s['estado']; ?></span></td>
                    <td><?php echo date('d/m/Y', strtotime($s['fechaCreacion'])); ?></td>
                    <td class="td-obs" title="<?php echo htmlspecialchars($s['observacion'] ?? ''); ?>">
                        <?php echo $s['observacion'] ? htmlspecialchars($s['observacion']) : '—'; ?>
                    </td>
                    <td class="td-actions">
                        <button onclick='verDetalleSolicitud(<?php echo json_encode($s); ?>)'
                                class="btn btn-sm btn-secondary" title="Ver detalle">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                        <?php if ($s['estado'] === 'pendiente'): ?>
                        <button onclick='abrirModalEditarSolicitud(<?php echo json_encode($s); ?>)'
                                class="btn btn-sm btn-primary" title="Editar">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <a href="../controller/acciones_usuario.php?cancelar_solicitud=<?php echo $s['idSolicitud']; ?>"
                           onclick="return confirm('¿Cancelar esta solicitud? Esta acción no se puede deshacer.')"
                           class="btn btn-sm btn-danger" title="Cancelar">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══════════════ EVENTOS ══════════════ -->
    <div id="eventos" class="tab-pane <?php echo $tab_activo==='eventos' ? 'active' : ''; ?>">
        <div>
            <h2 class="page-title">Eventos de la Fundación</h2>
            <p class="page-subtitle">Conoce las jornadas de entrega y actividades publicadas.</p>
        </div>

        <?php if (empty($eventos)): ?>
        <div class="card">
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fa-solid fa-calendar-xmark"></i></div>
                <h3>No hay eventos activos</h3>
                <p>Por el momento no hay eventos publicados. ¡Vuelve pronto!</p>
            </div>
        </div>
        <?php else: ?>
        <div class="eventos-grid">
        <?php foreach ($eventos as $ev): ?>
            <div class="event-card">
                <?php if ($ev['imagen']): ?>
                <img src="<?php echo $ev['imagen']; ?>" alt="Evento" class="event-card-img">
                <?php else: ?>
                <div class="event-card-noimg"><i class="fa-solid fa-calendar-days"></i></div>
                <?php endif; ?>
                <div class="event-card-body">
                    <h3><?php echo htmlspecialchars($ev['Nombre']); ?></h3>
                    <?php if ($ev['titulo']): ?>
                    <p class="event-pub-title">
                        <?php echo htmlspecialchars($ev['titulo']); ?>
                    </p>
                    <?php endif; ?>
                    <?php if ($ev['contenido']): ?>
                    <p><?php echo htmlspecialchars($ev['contenido']); ?></p>
                    <?php endif; ?>
                    <div class="event-meta">
                        <?php if ($ev['FechaEntrega']): ?>
                        <span><i class="fa-solid fa-calendar-check"></i> <?php echo date('d \d\e F \d\e Y', strtotime($ev['FechaEntrega'])); ?></span>
                        <?php endif; ?>
                        <?php if ($ev['Lugar']): ?>
                        <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($ev['Lugar']); ?></span>
                        <?php endif; ?>
                        <?php if ($ev['fechaPublicacion']): ?>
                        <span><i class="fa-solid fa-clock"></i> Publicado: <?php echo date('d/m/Y', strtotime($ev['fechaPublicacion'])); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ══════════════ PERFIL ══════════════ -->
    <div id="perfil" class="tab-pane <?php echo $tab_activo==='perfil' ? 'active' : ''; ?>">
        <h2 class="page-title">Mi Perfil</h2>
        <p class="page-subtitle">Actualiza tu información personal y contraseña.</p>

        <div class="card card-perfil">
            <div class="perfil-header">
                <div class="perfil-avatar">
                    <?php echo mb_strtoupper(mb_substr($cliente['nombre'], 0, 1)); ?>
                </div>
                <div class="perfil-header-info">
                    <h2><?php echo htmlspecialchars($cliente['nombre']); ?></h2>
                    <p><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($cliente['email']); ?></p>
                    <p class="perfil-estado"><span class="badge estado-<?php echo $cliente['estado']; ?>"><?php echo $cliente['estado']; ?></span></p>
                </div>
            </div>

            <form action="../controller/acciones_usuario.php" method="POST" id="formPerfil">
                <input type="hidden" name="idUsuario" value="<?php echo $cliente['idUsuario']; ?>">

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Nombre completo *</label>
                        <input type="text" name="nombre" class="form-input"
                               value="<?php echo htmlspecialchars($cliente['nombre']); ?>"
                               required minlength="3" maxlength="100"
                               placeholder="Digita tus nombres y apellidos completos"
                               oninput="this.value=this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚüÜñÑ\s]/g,'')">
                    </div>
                    <div class="form-group">
                        <label>Tipo de documento *</label>
                        <select name="tipoDocumento" class="form-input">
                            <?php foreach(['CC','TI','CE','PEP'] as $t): ?>
                            <option value="<?php echo $t; ?>" <?php echo $cliente['tipoDocumento']==$t?'selected':''; ?>><?php echo $t; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Número de documento *</label>
                        <input type="text" name="numDocumento" class="form-input"
                               value="<?php echo htmlspecialchars($cliente['numDocumento']); ?>"
                               required maxlength="15" pattern="[0-9]{4,15}"
                               placeholder="Ingresa los dígitos de tu documento de identidad"
                               oninput="this.value=this.value.replace(/\D/g,'').slice(0,15)">
                    </div>
                    <div class="form-group">
                        <label>Fecha de nacimiento *</label>
                        <input type="date" name="fechaNacimiento" class="form-input"
                               value="<?php echo $cliente['fechaNacimiento']; ?>"
                               required max="<?php echo date('Y-m-d', strtotime('-5 years')); ?>">
                    </div>
                    <div class="form-group">
                        <label>Teléfono *</label>
                        <input type="tel" name="telefono" class="form-input"
                               value="<?php echo htmlspecialchars($cliente['telefono']); ?>"
                               required pattern="[0-9]{10}" maxlength="10"
                               placeholder="Digita tu número de teléfono celular"
                               oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)">
                    </div>
                    <div class="form-group">
                        <label>Dirección *</label>
                        <input type="text" name="direccion" class="form-input"
                               value="<?php echo htmlspecialchars($cliente['direccion']); ?>"
                               required minlength="5" maxlength="255"
                               placeholder="Escribe tu dirección de residencia actual">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-input"
                               value="<?php echo htmlspecialchars($cliente['email']); ?>"
                               required maxlength="150"
                               placeholder="Ingresa tu correo electrónico">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Necesidad <small class="text-muted">(describe tu situación)</small></label>
                        <textarea name="necesidad" class="form-input" maxlength="255" rows="3"
                                  placeholder="Describe brevemente tu necesidad o situación actual..."><?php echo htmlspecialchars($cliente['necesidad'] ?? ''); ?></textarea>
                    </div>
                </div>

                <hr class="section-divider">
                <p class="hint-text">
                    <i class="fa-solid fa-lock"></i> Cambiar contraseña — deja vacío para no cambiar
                </p>
                <div class="form-group">
                    <label>Contraseña actual <small class="text-muted">(requerida para cambiar)</small></label>
                    <div class="pass-wrap">
                        <input type="password" name="password_actual" id="perfil_pass_actual" class="form-input"
                               autocomplete="current-password" maxlength="30"
                               placeholder="Ingresa tu contraseña actual para confirmar el cambio">
                        <button type="button" class="eye-btn" onclick="togglePass('perfil_pass_actual','eye_actual')">
                            <i class="fa-solid fa-eye" id="eye_actual"></i>
                        </button>
                    </div>
                    <p class="hint-text">
                        ¿No recuerdas tu contraseña?
                        <a href="../controller/recuperar_password.php" class="link-primary">Recupérala aquí</a>
                    </p>
                </div>
                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Nueva contraseña</label>
                        <div class="pass-wrap">
                            <input type="password" name="password" id="perfil_pass" class="form-input"
                                   autocomplete="new-password" minlength="6" maxlength="30"
                                   placeholder="Crea una nueva clave de seguridad">
                            <button type="button" class="eye-btn" onclick="togglePass('perfil_pass','perfil_eye')">
                                <i class="fa-solid fa-eye" id="perfil_eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirmar contraseña</label>
                        <div class="pass-wrap">
                            <input type="password" name="password_confirm" id="perfil_pass2" class="form-input"
                                   autocomplete="new-password" minlength="6" maxlength="30"
                                   placeholder="Repite la nueva clave para confirmar">
                            <button type="button" class="eye-btn" onclick="togglePass('perfil_pass2','perfil_eye2')">
                                <i class="fa-solid fa-eye" id="perfil_eye2"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <p id="perfil_pass_err" class="field-error" style="display:none;">Las contraseñas no coinciden.</p>

                <button type="submit" name="update_perfil" class="btn btn-primary"
                        onclick="return validarPassPerfil()">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
                </button>
            </form>
        </div>
    </div>

</main>

<!-- ═══════════════════════════════════════════════════
     MODALES
═══════════════════════════════════════════════════ -->

<!-- NUEVA DONACIÓN -->
<div id="modalNuevaDonacion" class="modal">
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fa-solid fa-box-open"></i> Registrar Donación</h3>
        <button class="modal-close" onclick="cerrarModal('modalNuevaDonacion')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form action="../controller/acciones_usuario.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Descripción del artículo *</label>
            <textarea name="descripcion" class="form-input" required maxlength="200" rows="3"
                      placeholder="Describe el artículo que deseas donar (tipo, estado, características)..."></textarea>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Categoría *</label>
                <select name="idCategoria" class="form-input" required>
                    <option value="">Selecciona una categoría</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat['idCategoria']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Cantidad / Stock *</label>
                <input type="number" name="stock" class="form-input" required min="1" max="9999" value="1"
                       placeholder="¿Cuántas unidades? (máx. 9999)">
            </div>
        </div>
        <div class="form-group">
            <label>Imagen del artículo <small class="text-muted">(opcional)</small></label>
            <input type="file" name="imagen" class="form-input" accept="image/*" onchange="previewImg(this,'prev_don')">
            <img id="prev_don" src="" alt="" class="img-file-preview" style="display:none;">
            <p class="form-hint"><i class="fa-solid fa-circle-info"></i> La imagen ayuda al equipo a validar tu donación.</p>
        </div>
        <div class="modal-footer">
            <button type="submit" name="crear_donacion" class="btn btn-primary">
                <i class="fa-solid fa-paper-plane"></i> Enviar Donación
            </button>
            <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalNuevaDonacion')">Cancelar</button>
        </div>
    </form>
</div>
</div>

<!-- EDITAR DONACIÓN -->
<div id="modalEditarDonacion" class="modal">
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fa-solid fa-pen"></i> Editar Donación</h3>
        <button class="modal-close" onclick="cerrarModal('modalEditarDonacion')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form action="../controller/acciones_usuario.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="idDonacion" id="ed_id">
        <div class="form-group">
            <label>Descripción *</label>
            <textarea name="descripcion" id="ed_desc" class="form-input" required maxlength="200" rows="3"></textarea>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Categoría *</label>
                <select name="idCategoria" id="ed_cat" class="form-input" required>
                    <option value="">Selecciona una categoría</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat['idCategoria']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Stock *</label>
                <input type="number" name="stock" id="ed_stock" class="form-input" required min="1" max="9999">
            </div>
        </div>
        <div class="form-group">
            <label>Nueva imagen <small class="text-muted">(deja vacío para mantener la actual)</small></label>
            <input type="file" name="imagen" class="form-input" accept="image/*" onchange="previewImg(this,'prev_ed_don')">
            <img id="prev_ed_don" src="" alt="" class="img-file-preview" style="display:none;">
        </div>
        <div class="modal-footer">
            <button type="submit" name="editar_donacion" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
            </button>
            <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditarDonacion')">Cancelar</button>
        </div>
    </form>
</div>
</div>

<!-- VER DETALLE DONACIÓN -->
<div id="modalDetalleDonacion" class="modal">
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fa-solid fa-box-open"></i> Detalle de Donación</h3>
        <button class="modal-close" onclick="cerrarModal('modalDetalleDonacion')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="detalle_don_body" class="modal-body-pad"></div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalDetalleDonacion')">Cerrar</button>
    </div>
</div>
</div>

<!-- NUEVA SOLICITUD -->
<div id="modalNuevaSolicitud" class="modal">
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fa-solid fa-clipboard-list"></i> Registrar Solicitud</h3>
        <button class="modal-close" onclick="cerrarModal('modalNuevaSolicitud')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form action="../controller/acciones_usuario.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>Descripción de la solicitud *</label>
            <textarea name="descripcion" class="form-input" required maxlength="300" rows="3"
                      placeholder="Describe qué necesitas, para qué y cualquier detalle relevante..."></textarea>
        </div>
        <div class="form-group">
            <label>Categoría *</label>
            <select name="idCategoria" class="form-input" required>
                <option value="">Selecciona la categoría de tu necesidad</option>
                <?php foreach ($categorias as $cat): ?>
                <option value="<?php echo $cat['idCategoria']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Imagen de soporte <small class="text-muted">(opcional)</small></label>
            <input type="file" name="imagen" class="form-input" accept="image/*" onchange="previewImg(this,'prev_sol')">
            <img id="prev_sol" src="" alt="" class="img-file-preview" style="display:none;">
            <p class="form-hint"><i class="fa-solid fa-circle-info"></i> Puedes adjuntar una foto que respalde tu solicitud.</p>
        </div>
        <div class="modal-footer">
            <button type="submit" name="crear_solicitud" class="btn btn-primary">
                <i class="fa-solid fa-paper-plane"></i> Enviar Solicitud
            </button>
            <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalNuevaSolicitud')">Cancelar</button>
        </div>
    </form>
</div>
</div>

<!-- EDITAR SOLICITUD -->
<div id="modalEditarSolicitud" class="modal">
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fa-solid fa-pen"></i> Editar Solicitud</h3>
        <button class="modal-close" onclick="cerrarModal('modalEditarSolicitud')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form action="../controller/acciones_usuario.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="idSolicitud" id="es_id">
        <div class="form-group">
            <label>Descripción *</label>
            <textarea name="descripcion" id="es_desc" class="form-input" required maxlength="300" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label>Categoría *</label>
            <select name="idCategoria" id="es_cat" class="form-input" required>
                <option value="">Selecciona una categoría</option>
                <?php foreach ($categorias as $cat): ?>
                <option value="<?php echo $cat['idCategoria']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Nueva imagen <small class="text-muted">(deja vacío para mantener la actual)</small></label>
            <input type="file" name="imagen" class="form-input" accept="image/*" onchange="previewImg(this,'prev_ed_sol')">
            <img id="prev_ed_sol" src="" alt="" class="img-file-preview" style="display:none;">
        </div>
        <div class="modal-footer">
            <button type="submit" name="editar_solicitud" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
            </button>
            <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditarSolicitud')">Cancelar</button>
        </div>
    </form>
</div>
</div>

<!-- VER DETALLE SOLICITUD -->
<div id="modalDetalleSolicitud" class="modal">
<div class="modal-content">
    <div class="modal-header">
        <h3><i class="fa-solid fa-clipboard-list"></i> Detalle de Solicitud</h3>
        <button class="modal-close" onclick="cerrarModal('modalDetalleSolicitud')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="detalle_sol_body" class="modal-body-pad"></div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalDetalleSolicitud')">Cerrar</button>
    </div>
</div>
</div>

<script src="../assets/js/usuario.js"></script>

</body>
</html>