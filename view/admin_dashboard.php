<?php
require_once __DIR__ . '/../controller/admin.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donapp — Panel Administrativo</title>
    <link rel="icon" type="image/png" href="../assets/uploads/Icon.png">
    <link rel="stylesheet" href="../assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
</head>
<body>

<?php if ($flash): ?>
    <div class="flash-msg" id="flashMsg"><?php echo htmlspecialchars($flash); ?></div>
<?php endif; ?>
<?php if ($notif_enviada): ?>
    <div class="flash-msg flash-info" id="flashNotif"><?php echo htmlspecialchars($notif_enviada); ?></div>
<?php endif; ?>

<div class="admin-wrapper">

    <!-- ═══════════════ SIDEBAR ═══════════════ -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <a href="../index.php">
                <img src="../assets/uploads/Red-Logo.png" alt="Donapp" onerror="this.style.display='none'">
            </a>
            <p class="sidebar-title">Panel Administrativo</p>
        </div>
        <ul class="nav-menu">
            <li><a href="#dashboard"  class="nav-link active"><i class="fa-solid fa-house"></i><span> Dashboard</span></a></li>
            <li><a href="#usuarios"   class="nav-link"><i class="fa-solid fa-users"></i><span> Usuarios</span></a></li>
            <li><a href="#categorias" class="nav-link"><i class="fa-solid fa-tags"></i><span> Categorías</span></a></li>
            <li><a href="#donapp"     class="nav-link"><i class="fa-solid fa-hand-holding-heart"></i><span> Donaciones/Sol.</span></a></li>
            <li><a href="#eventos"    class="nav-link"><i class="fa-solid fa-calendar-days"></i><span> Eventos</span></a></li>
            <li><a href="#reportes"   class="nav-link"><i class="fa-solid fa-file-pdf"></i><span> Reportes</span></a></li>
            <li><a href="#perfil"     class="nav-link"><i class="fa-solid fa-user-gear"></i><span> Mi Perfil</span></a></li>
            <li><hr></li>
            <li><a href="../controller/logout.php" class="nav-link logout"><i class="fa-solid fa-power-off"></i><span> Cerrar Sesión</span></a></li>
        </ul>
    </aside>

    <!-- ═══════════════ MAIN ═══════════════ -->
    <main class="main-content">

        <!-- ────────── DASHBOARD ────────── -->
        <div id="dashboard" class="tab-pane active">
            <h1 class="page-title">Bienvenid@, <?php echo htmlspecialchars($admin_data['nombre']); ?> 👋</h1>
            <p class="text-muted">
                <i class="fa-solid fa-shield-halved"></i>
                Módulo de Administrador — Revisa y gestiona usuarios, categorías, donaciones, solicitudes y eventos.
            </p>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                    <div><h3><?php echo $total_usuarios; ?></h3><p>Usuarios totales</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fa-solid fa-box-open"></i></div>
                    <div><h3><?php echo $total_donaciones; ?></h3><p>Donaciones</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div><h3><?php echo $total_solicitudes; ?></h3><p>Solicitudes</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-calendar-check"></i></div>
                    <div><h3><?php echo $total_eventos; ?></h3><p>Eventos</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
                    <div><h3><?php echo $total_aprobadas; ?></h3><p>Donaciones aprobadas</p></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-tags"></i></div>
                    <div><h3><?php echo $total_categorias; ?></h3><p>Categorías</p></div>
                </div>
            </div>

            <h2 class="page-title">Accesos rápidos</h2>
            <div class="stats-grid stats-grid-sm">
                <a href="#usuarios"   class="stat-card" onclick="activarTab('#usuarios')">
                    <div class="stat-icon"><i class="fa-solid fa-user-plus"></i></div>
                    <div><p>Gestionar Usuarios</p></div>
                </a>
                <a href="#categorias" class="stat-card" onclick="activarTab('#categorias')">
                    <div class="stat-icon"><i class="fa-solid fa-tags"></i></div>
                    <div><p>Ver Categorías</p></div>
                </a>
                <a href="#donapp"     class="stat-card" onclick="activarTab('#donapp')">
                    <div class="stat-icon orange"><i class="fa-solid fa-hand-holding-heart"></i></div>
                    <div><p>Ver Donaciones y Solicitudes</p></div>
                </a>
                <a href="#eventos"    class="stat-card" onclick="activarTab('#eventos')">
                    <div class="stat-icon blue"><i class="fa-solid fa-calendar-days"></i></div>
                    <div><p>Gestionar Eventos</p></div>
                </a>
                <a href="#reportes"   class="stat-card" onclick="activarTab('#reportes')">
                    <div class="stat-icon"><i class="fa-solid fa-file-pdf"></i></div>
                    <div><p>Generar Reportes</p></div>
                </a>
            </div>
        </div>

        <!-- ────────── USUARIOS ────────── -->
        <div id="usuarios" class="tab-pane">
            <div class="section-header">
                <h2 class="page-title">Gestión de Usuarios</h2>
                <button class="btn btn-primary" onclick="abrirModalCrearUsuario()">
                    <i class="fa-solid fa-user-plus"></i> Nuevo Usuario
                </button>
            </div>
            <div class="card">
                <form method="GET" class="filter-bar">
                    <input type="hidden" name="tab" value="usuarios">
                    <input type="text" name="search" placeholder="🔍 Buscar por nombre o email..."
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="form-input search-input" maxlength="200">
                    <select name="rol" id="filtro_rol_select"
                            onchange="togglePrioridadFiltro(); this.form.submit();"
                            class="form-input sel-small">
                        <option value="">Todos los roles</option>
                        <option value="donante"       <?php echo $filtro_rol === 'donante'       ? 'selected' : ''; ?>>Donante / Solicitante</option>
                        <option value="asistente"     <?php echo $filtro_rol === 'asistente'     ? 'selected' : ''; ?>>Asistente</option>
                        <option value="administrador" <?php echo $filtro_rol === 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                    <select name="prioridad" id="filtro_prioridad_select"
                            class="form-input sel-small"
                            onchange="this.form.submit()"
                            style="display:<?php echo $mostrar_prioridad ? 'inline-block' : 'none'; ?>;">
                        <option value="">Todas las prioridades</option>
                        <option value="alta"  <?php echo $filtro_prioridad === 'alta'  ? 'selected' : ''; ?>>🔴 Alta</option>
                        <option value="media" <?php echo $filtro_prioridad === 'media' ? 'selected' : ''; ?>>🟡 Media</option>
                        <option value="baja"  <?php echo $filtro_prioridad === 'baja'  ? 'selected' : ''; ?>>🟢 Baja</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
                    <?php if ($search || $filtro_rol || $filtro_prioridad): ?>
                        <a href="admin_dashboard.php#usuarios" class="btn btn-secondary btn-sm">Limpiar</a>
                    <?php endif; ?>
                </form>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th><a href="<?php echo sort_toggle_url('usr_sort','usr_dir','idUsuario'); ?>#usuarios">ID<?php echo sort_icon('usr_sort','usr_dir','idUsuario'); ?></a></th>
                                <th><a href="<?php echo sort_toggle_url('usr_sort','usr_dir','nombre'); ?>#usuarios">Nombre<?php echo sort_icon('usr_sort','usr_dir','nombre'); ?></a></th>
                                <th>Documento</th>
                                <th>Email</th>
                                <th>Teléfono</th>
                                <th><a href="<?php echo sort_toggle_url('usr_sort','usr_dir','rol'); ?>#usuarios">Rol<?php echo sort_icon('usr_sort','usr_dir','rol'); ?></a></th>
                                <th><a href="<?php echo sort_toggle_url('usr_sort','usr_dir','estado'); ?>#usuarios">Estado<?php echo sort_icon('usr_sort','usr_dir','estado'); ?></a></th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($res_usuarios->num_rows === 0): ?>
                                <tr><td colspan="8" class="empty-row">No se encontraron usuarios.</td></tr>
                            <?php endif; ?>
                            <?php while ($u = $res_usuarios->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $u['idUsuario']; ?></td>
                                    <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                                    <td><small><?php echo $u['tipoDocumento'] . ': ' . htmlspecialchars($u['numDocumento']); ?></small></td>
                                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                                    <td><?php echo htmlspecialchars($u['telefono']); ?></td>
                                    <td><span class="badge <?php echo $u['rol']; ?>"><?php echo $u['rol']; ?></span></td>
                                    <td><span class="badge estado-<?php echo $u['estado']; ?>"><?php echo $u['estado']; ?></span></td>
                                    <td class="td-actions">
                                        <button onclick='abrirModalEditarUsuario(<?php echo json_encode($u); ?>)'
                                                class="btn btn-sm btn-primary" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <a href="../controller/acciones_usuarios.php?toggle_estado=<?php echo $u['idUsuario']; ?>"
                                           onclick="return confirm('<?php echo $u['estado'] === 'activo' ? '¿Inactivar este usuario?' : '¿Activar este usuario?'; ?>')"
                                           class="btn btn-sm <?php echo $u['estado'] === 'activo' ? 'btn-warning' : 'btn-success'; ?>"
                                           title="<?php echo $u['estado'] === 'activo' ? 'Inactivar' : 'Activar'; ?>">
                                            <i class="fa-solid <?php echo $u['estado'] === 'activo' ? 'fa-ban' : 'fa-circle-check'; ?>"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ────────── CATEGORÍAS ────────── -->
        <div id="categorias" class="tab-pane">
            <div class="section-header">
                <h2 class="page-title">Gestión de Categorías</h2>
                <button class="btn btn-primary" onclick="abrirModal('modalCrearCategoria')">
                    <i class="fa-solid fa-plus"></i> Nueva Categoría
                </button>
            </div>
            <div class="card">
                <form method="GET" class="filter-bar" style="margin-bottom:16px;">
                    <input type="hidden" name="tab" value="categorias">
                    <input type="text" name="cat_search" placeholder="🔍 Buscar categoría por nombre..."
                           value="<?php echo htmlspecialchars($cat_search); ?>"
                           class="form-input search-input" maxlength="200">
                    <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
                    <?php if ($cat_search): ?>
                        <a href="admin_dashboard.php#categorias" class="btn btn-secondary btn-sm">Limpiar</a>
                    <?php endif; ?>
                </form>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th><a href="<?php echo sort_toggle_url('cat_sort','cat_dir','idCategoria'); ?>#categorias">#<?php echo sort_icon('cat_sort','cat_dir','idCategoria'); ?></a></th>
                                <th><a href="<?php echo sort_toggle_url('cat_sort','cat_dir','nombre'); ?>#categorias">Nombre<?php echo sort_icon('cat_sort','cat_dir','nombre'); ?></a></th>
                                <th>Creador</th><th>Donaciones</th><th>Solicitudes</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res_categorias->data_seek(0);
                            while ($cat = $res_categorias->fetch_assoc()):
                                $id_cat = $cat['idCategoria'];
                                $n_don  = $conn->query("SELECT COUNT(*) AS c FROM donacion WHERE idCategoria=$id_cat")->fetch_assoc()['c'];
                                $n_sol  = $conn->query("SELECT COUNT(*) AS c FROM solicitud WHERE idCategoria=$id_cat")->fetch_assoc()['c'];
                            ?>
                                <tr>
                                    <td><?php echo $cat['idCategoria']; ?></td>
                                    <td><?php echo htmlspecialchars($cat['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($cat['creador'] ?? '—'); ?></td>
                                    <td>
                                        <?php if ($n_don > 0): ?>
                                            <a href="admin_dashboard.php?don_cat=<?php echo $id_cat; ?>#donapp" class="badge estado-aprobada"><?php echo $n_don; ?></a>
                                        <?php else: echo '0'; endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($n_sol > 0): ?>
                                            <a href="admin_dashboard.php?sol_cat=<?php echo $id_cat; ?>#donapp" class="badge estado-aprobada"><?php echo $n_sol; ?></a>
                                        <?php else: echo '0'; endif; ?>
                                    </td>
                                    <td class="td-actions">
                                        <button onclick='abrirModalEditarCategoria(<?php echo json_encode(['idCategoria' => $cat['idCategoria'], 'nombre' => $cat['nombre']]); ?>)'
                                                class="btn btn-sm btn-primary" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <?php if ($n_don == 0 && $n_sol == 0): ?>
                                            <a href="../controller/acciones_categorias.php?eliminar=<?php echo $cat['idCategoria']; ?>"
                                               onclick="return confirm('¿Eliminar esta categoría?')"
                                               class="btn btn-sm btn-danger" title="Eliminar">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary" disabled title="Tiene registros asociados">
                                                <i class="fa-solid fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ────────── DONACIONES / SOLICITUDES ────────── -->
        <div id="donapp" class="tab-pane">
            <h2 class="page-title">Donaciones y Solicitudes</h2>
            <div class="tabs-inner">
                <button class="tab-btn <?php echo (!$sol_search && !$sol_estado && !$sol_cat) ? 'active' : ''; ?>"
                        onclick="switchInner(this,'don-panel')">Donaciones</button>
                <button class="tab-btn <?php echo ($sol_search || $sol_estado || $sol_cat) ? 'active' : ''; ?>"
                        onclick="switchInner(this,'sol-panel')">Solicitudes</button>
            </div>

            <!-- Panel Donaciones -->
            <div id="don-panel" class="inner-panel" <?php echo ($sol_search || $sol_estado || $sol_cat) ? 'style="display:none;"' : ''; ?>>
                <form method="GET" class="filter-bar" style="margin-bottom:16px;">
                    <input type="hidden" name="tab" value="donapp">
                    <input type="text" name="don_search" placeholder="🔍 Buscar por descripción o donante..."
                           value="<?php echo htmlspecialchars($don_search); ?>"
                           class="form-input search-input" maxlength="200">
                    <select name="don_estado" class="form-input sel-small" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <option value="pendiente" <?php echo $don_estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="aprobada"  <?php echo $don_estado === 'aprobada'  ? 'selected' : ''; ?>>Aprobada</option>
                        <option value="rechazada" <?php echo $don_estado === 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
                    </select>
                    <select name="don_cat" class="form-input sel-small" onchange="this.form.submit()">
                        <option value="0">Todas las categorías</option>
                        <?php
                        $res_cat_don = $conn->query("SELECT idCategoria, nombre FROM categoria ORDER BY nombre");
                        while ($rc = $res_cat_don->fetch_assoc()):
                        ?>
                            <option value="<?php echo $rc['idCategoria']; ?>" <?php echo $don_cat == $rc['idCategoria'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rc['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                    <?php if ($don_search || $don_estado || $don_cat): ?>
                        <a href="admin_dashboard.php#donapp" class="btn btn-secondary btn-sm">Limpiar</a>
                    <?php endif; ?>
                </form>
                <div class="card">
                    <div class="table-wrap">
                        <table>
                            <thead><tr>
                                <th><a href="<?php echo sort_toggle_url('don_sort','don_dir','idDonacion'); ?>#donapp">#<?php echo sort_icon('don_sort','don_dir','idDonacion'); ?></a></th>
                                <th><a href="<?php echo sort_toggle_url('don_sort','don_dir','descripcion'); ?>#donapp">Descripción<?php echo sort_icon('don_sort','don_dir','descripcion'); ?></a></th>
                                <th>Categoría</th><th>Stock</th>
                                <th><a href="<?php echo sort_toggle_url('don_sort','don_dir','estado'); ?>#donapp">Estado<?php echo sort_icon('don_sort','don_dir','estado'); ?></a></th>
                                <th><a href="<?php echo sort_toggle_url('don_sort','don_dir','fechaCreacion'); ?>#donapp">Fecha<?php echo sort_icon('don_sort','don_dir','fechaCreacion'); ?></a></th>
                                <th>Donante</th><th>Observación</th><th>Acción</th>
                            </tr></thead>
                            <tbody>
                                <?php if (empty($donaciones_procesadas)): ?>
                                    <tr><td colspan="9" class="empty-row">No se encontraron donaciones con esos criterios.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($donaciones_procesadas as $d): ?>
                                    <tr>
                                        <td><?php echo $d['idDonacion']; ?></td>
                                        <td><?php echo htmlspecialchars($d['descripcion']); ?></td>
                                        <td><?php echo htmlspecialchars($d['categoria'] ?? '—'); ?></td>
                                        <td><?php echo $d['stock']; ?></td>
                                        <td><span class="badge estado-<?php echo $d['estado']; ?>"><?php echo $d['estado']; ?></span></td>
                                        <td><?php echo date('d/m/Y', strtotime($d['fechaCreacion'])); ?></td>
                                        <td><?php echo htmlspecialchars($d['donante'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($d['observacion'] ?? '—'); ?></td>
                                        <td>
                                            <button onclick='abrirModalDonacion(<?php echo json_encode($d); ?>)'
                                                    class="btn btn-sm btn-primary">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Panel Solicitudes -->
            <div id="sol-panel" class="inner-panel" <?php echo (!$sol_search && !$sol_estado && !$sol_cat) ? 'style="display:none;"' : ''; ?>>
                <form method="GET" class="filter-bar" style="margin-bottom:16px;">
                    <input type="hidden" name="tab" value="donapp">
                    <input type="text" name="sol_search" placeholder="🔍 Buscar por descripción o solicitante..."
                           value="<?php echo htmlspecialchars($sol_search); ?>"
                           class="form-input search-input" maxlength="200">
                    <select name="sol_estado" class="form-input sel-small" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <option value="pendiente" <?php echo $sol_estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                        <option value="aprobada"  <?php echo $sol_estado === 'aprobada'  ? 'selected' : ''; ?>>Aprobada</option>
                        <option value="rechazada" <?php echo $sol_estado === 'rechazada' ? 'selected' : ''; ?>>Rechazada</option>
                    </select>
                    <select name="sol_cat" class="form-input sel-small" onchange="this.form.submit()">
                        <option value="0">Todas las categorías</option>
                        <?php
                        $res_cat_sol = $conn->query("SELECT idCategoria, nombre FROM categoria ORDER BY nombre");
                        while ($rc = $res_cat_sol->fetch_assoc()):
                        ?>
                            <option value="<?php echo $rc['idCategoria']; ?>" <?php echo $sol_cat == $rc['idCategoria'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rc['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                    <?php if ($sol_search || $sol_estado || $sol_cat): ?>
                        <a href="admin_dashboard.php#donapp" class="btn btn-secondary btn-sm">Limpiar</a>
                    <?php endif; ?>
                </form>
                <div class="card">
                    <div class="table-wrap">
                        <table>
                            <thead><tr>
                                <th><a href="<?php echo sort_toggle_url('sol_sort','sol_dir','idSolicitud'); ?>#donapp">#<?php echo sort_icon('sol_sort','sol_dir','idSolicitud'); ?></a></th>
                                <th><a href="<?php echo sort_toggle_url('sol_sort','sol_dir','descripcion'); ?>#donapp">Descripción<?php echo sort_icon('sol_sort','sol_dir','descripcion'); ?></a></th>
                                <th>Categoría</th>
                                <th><a href="<?php echo sort_toggle_url('sol_sort','sol_dir','estado'); ?>#donapp">Estado<?php echo sort_icon('sol_sort','sol_dir','estado'); ?></a></th>
                                <th><a href="<?php echo sort_toggle_url('sol_sort','sol_dir','fechaCreacion'); ?>#donapp">Fecha<?php echo sort_icon('sol_sort','sol_dir','fechaCreacion'); ?></a></th>
                                <th>Solicitante</th><th>Gestor</th><th>Observación</th><th>Acción</th>
                            </tr></thead>
                            <tbody>
                                <?php if (empty($solicitudes_procesadas)): ?>
                                    <tr><td colspan="9" class="empty-row">No se encontraron solicitudes con esos criterios.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($solicitudes_procesadas as $s): ?>
                                    <tr>
                                        <td><?php echo $s['idSolicitud']; ?></td>
                                        <td><?php echo htmlspecialchars($s['descripcion']); ?></td>
                                        <td><?php echo htmlspecialchars($s['categoria'] ?? '—'); ?></td>
                                        <td><span class="badge estado-<?php echo $s['estado']; ?>"><?php echo $s['estado']; ?></span></td>
                                        <td><?php echo date('d/m/Y', strtotime($s['fechaCreacion'])); ?></td>
                                        <td><?php echo htmlspecialchars($s['nombre_solicitante'] ?? '—'); ?></td>
                                        <td>
                                            <?php if (!empty($s['nombre_gestor'])): ?>
                                                <span class="badge-staff">
                                                    <i class="fa-solid fa-user-shield"></i>
                                                    <?php echo htmlspecialchars($s['nombre_gestor']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted"><i>Esperando revisión...</i></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($s['observacion'] ?? '—'); ?></td>
                                        <td>
                                            <button onclick='abrirModalSolicitud(<?php echo json_encode($s); ?>)'
                                                    class="btn btn-sm btn-primary">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ────────── EVENTOS ────────── -->
        <div id="eventos" class="tab-pane">
            <div class="section-header">
                <h2 class="page-title">Gestión de Eventos</h2>
                <button class="btn btn-primary" onclick="abrirModalCrearEvento()">
                    <i class="fa-solid fa-plus"></i> Nuevo Evento
                </button>
            </div>
            <div class="card">
                <form method="GET" class="filter-bar" style="margin-bottom:16px;">
                    <input type="hidden" name="tab" value="eventos">
                    <input type="text" name="ev_search" placeholder="🔍 Buscar evento por nombre..."
                           value="<?php echo htmlspecialchars($ev_search); ?>"
                           class="form-input search-input" maxlength="200">
                    <select name="ev_estado" class="form-input sel-small" onchange="this.form.submit()">
                        <option value="">Todos los estados</option>
                        <option value="activo"   <?php echo $ev_estado === 'activo'   ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo $ev_estado === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                    <?php if ($ev_search || $ev_estado): ?>
                        <a href="admin_dashboard.php#eventos" class="btn btn-secondary btn-sm">Limpiar</a>
                    <?php endif; ?>
                </form>
                <div class="table-wrap">
                    <table>
                        <thead><tr>
                            <th><a href="<?php echo sort_toggle_url('ev_sort','ev_dir','idEvento'); ?>#eventos">ID<?php echo sort_icon('ev_sort','ev_dir','idEvento'); ?></a></th>
                            <th><a href="<?php echo sort_toggle_url('ev_sort','ev_dir','Nombre'); ?>#eventos">Nombre<?php echo sort_icon('ev_sort','ev_dir','Nombre'); ?></a></th>
                            <th><a href="<?php echo sort_toggle_url('ev_sort','ev_dir','estado'); ?>#eventos">Estado<?php echo sort_icon('ev_sort','ev_dir','estado'); ?></a></th>
                            <th>Acciones</th>
                        </tr></thead>
                        <tbody>
                            <?php
                            $res_eventos->data_seek(0);
                            $ev_count = 0;
                            while ($ev = $res_eventos->fetch_assoc()):
                                $ev_count++;
                                $ev['imagen'] = !empty($ev['imagen'])
                                    ? 'data:image/png;base64,' . base64_encode($ev['imagen'])
                                    : '';
                            ?>
                                <tr>
                                    <td><?php echo $ev['idEvento']; ?></td>
                                    <td><?php echo htmlspecialchars($ev['Nombre']); ?></td>
                                    <td><span class="badge estado-<?php echo $ev['estado']; ?>"><?php echo $ev['estado']; ?></span></td>
                                    <td class="td-actions">
                                        <button onclick='abrirModalEditarEvento(<?php echo json_encode($ev); ?>)'
                                                class="btn btn-sm btn-primary"><i class="fa-solid fa-pen"></i></button>
                                        <a href="../controller/acciones_eventos.php?toggle=<?php echo $ev['idEvento']; ?>"
                                           class="btn btn-sm btn-warning">
                                            <i class="fa-solid fa-arrows-rotate"></i> Toggle
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <?php if ($ev_count === 0): ?>
                                <tr><td colspan="4" class="empty-row">No se encontraron eventos con esos criterios.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ────────── REPORTES ────────── -->
        <div id="reportes" class="tab-pane">
            <h2 class="page-title">Generador de Reportes PDF</h2>
            <div class="reportes-grid">

                <div class="card reporte-card">
                    <div class="reporte-icon"><i class="fa-solid fa-box-open"></i></div>
                    <h3>Reporte de Donaciones</h3>
                    <p>Genera un PDF con el listado detallado de donaciones según los criterios que elijas.</p>
                    <div class="reporte-filters">
                        <label>Estado:</label>
                        <select id="rpt_don_estado" class="form-input">
                            <option value="todos">Todos</option>
                            <option value="aprobada">Aprobadas</option>
                            <option value="rechazada">Rechazadas</option>
                            <option value="pendiente">Pendientes</option>
                        </select>
                        <label>Fecha desde:</label>
                        <input type="date" id="rpt_don_desde" class="form-input">
                        <label>Fecha hasta:</label>
                        <input type="date" id="rpt_don_hasta" class="form-input">
                    </div>
                    <button class="btn btn-primary" onclick="generarReporteDonaciones()">
                        <i class="fa-solid fa-file-pdf"></i> Generar PDF
                    </button>
                </div>

                <div class="card reporte-card">
                    <div class="reporte-icon blue"><i class="fa-solid fa-clipboard-list"></i></div>
                    <h3>Reporte de Solicitudes</h3>
                    <p>Genera un PDF con el detalle de solicitudes completadas o según el estado que selecciones.</p>
                    <div class="reporte-filters">
                        <label>Estado:</label>
                        <select id="rpt_sol_estado" class="form-input">
                            <option value="todos">Todos</option>
                            <option value="aprobada">Aprobadas</option>
                            <option value="rechazada">Rechazadas</option>
                            <option value="pendiente">Pendientes</option>
                        </select>
                        <label>Fecha desde:</label>
                        <input type="date" id="rpt_sol_desde" class="form-input">
                        <label>Fecha hasta:</label>
                        <input type="date" id="rpt_sol_hasta" class="form-input">
                    </div>
                    <button class="btn btn-primary" onclick="generarReporteSolicitudes()">
                        <i class="fa-solid fa-file-pdf"></i> Generar PDF
                    </button>
                </div>

            </div>

            <!-- Datos para el generador de PDF (JSON inline, sin lógica de negocio) -->
            <script id="donacionesData" type="application/json">
                <?php echo json_encode($donaciones_rpt, JSON_UNESCAPED_UNICODE); ?>
            </script>
            <script id="solicitudesData" type="application/json">
                <?php echo json_encode($solicitudes_rpt, JSON_UNESCAPED_UNICODE); ?>
            </script>
        </div>

        <!-- ────────── PERFIL ────────── -->
        <div id="perfil" class="tab-pane">
            <h2 class="page-title">Mi Perfil</h2>
            <div class="card card-perfil">
                <form action="../controller/acciones_usuarios.php" method="POST" id="formPerfil"
                      onsubmit="return validarFormularioCompleto()">
                    <input type="hidden" name="idUsuario" value="<?php echo $admin_data['idUsuario']; ?>">

                    <div class="form-grid-2">
                        <div class="form-group">
                            <label>Nombre completo</label>
                            <input type="text" name="nombre" class="form-input"
                                   value="<?php echo htmlspecialchars($admin_data['nombre']); ?>"
                                   required minlength="3" maxlength="100"
                                   pattern="[A-Za-záéíóúÁÉÍÓÚñÑüÜ\s\.]+"
                                   title="Solo letras y espacios"
                                   placeholder="Digita tus nombres y apellidos completos">
                        </div>
                        <div class="form-group">
                            <label>Tipo de documento</label>
                            <select name="tipoDocumento" class="form-input" required>
                                <option value="" disabled <?php echo empty($admin_data['tipoDocumento']) ? 'selected' : ''; ?>>Selecciona tu tipo de documento</option>
                                <?php foreach (['CC','TI','CE','PEP'] as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo $admin_data['tipoDocumento'] == $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Número de documento</label>
                            <input type="text" name="numDocumento" class="form-input"
                                   value="<?php echo htmlspecialchars($admin_data['numDocumento']); ?>"
                                   required maxlength="15" pattern="[0-9]{4,15}"
                                   title="Solo números, máximo 15 dígitos"
                                   placeholder="Ingresa los dígitos de tu documento de identidad">
                        </div>
                        <div class="form-group">
                            <label>Fecha de nacimiento</label>
                            <input type="date" name="fechaNacimiento" class="form-input"
                                   value="<?php echo $admin_data['fechaNacimiento']; ?>"
                                   required max="<?php echo date('Y-m-d', strtotime('-5 years')); ?>">
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="tel" name="telefono" class="form-input"
                                   value="<?php echo htmlspecialchars($admin_data['telefono']); ?>"
                                   required pattern="[0-9]{10}" maxlength="10"
                                   title="Solo números, máximo 10 dígitos"
                                   placeholder="Digita tu número de teléfono celular">
                        </div>
                        <div class="form-group">
                            <label>Dirección</label>
                            <input type="text" name="direccion" class="form-input"
                                   value="<?php echo htmlspecialchars($admin_data['direccion']); ?>"
                                   required minlength="5" maxlength="255"
                                   placeholder="Escribe tu dirección de residencia actual">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-input"
                                   value="<?php echo htmlspecialchars($admin_data['email']); ?>"
                                   required maxlength="150"
                                   placeholder="Ingresa tu correo electrónico">
                        </div>
                    </div>

                    <hr>
                    <p class="text-muted"><i class="fa-solid fa-lock"></i> Cambiar contraseña (dejar en blanco para no cambiar)</p>

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
                    <div id="perfil_pass_err" class="field-error" style="display:none;">Las contraseñas no coinciden.</div>

                    <button type="submit" name="update_perfil" class="btn btn-primary"
                            onclick="return validarPassPerfil()">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
                    </button>
                </form>
            </div>
        </div>

    </main>
</div><!-- /admin-wrapper -->


<!-- ═══════════════════════════════════════════
     MODALES
═══════════════════════════════════════════ -->

<!-- CREAR USUARIO -->
<div id="modalCrearUsuario" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><i class="fa-solid fa-user-plus"></i> Nuevo Usuario</h3>
            <button class="modal-close" onclick="cerrarModal('modalCrearUsuario')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="../controller/acciones_usuarios.php" method="POST" id="formCrearUsuario"
              onsubmit="return validarPassModal('cu_pass','cu_pass2','cu_pass_err')">
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Nombre completo *</label>
                    <input type="text" name="nombre" class="form-input" required minlength="3" maxlength="100"
                           oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '')"
                           placeholder="Ingrese los nombres y apellidos del usuario">
                </div>
                <div class="form-group">
                    <label>Tipo de documento *</label>
                    <select name="tipoDocumento" class="form-input" required>
                        <option value="">Seleccione el tipo de documento de identidad</option>
                        <?php foreach (['CC','TI','CE','PEP'] as $t): ?>
                            <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Número de documento *</label>
                    <input type="text" name="numDocumento" class="form-input" required
                           maxlength="15" pattern="[0-9]{4,15}"
                           placeholder="Ingrese el número de identificación">
                </div>
                <div class="form-group">
                    <label>Fecha de nacimiento *</label>
                    <input type="date" name="fechaNacimiento" class="form-input" required
                           max="<?php echo date('Y-m-d', strtotime('-5 years')); ?>">
                </div>
                <div class="form-group">
                    <label>Dirección *</label>
                    <input type="text" name="direccion" class="form-input" required minlength="5" maxlength="255"
                           placeholder="Ingrese la dirección de residencia actual">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" class="form-input" required maxlength="150"
                           placeholder="Ingrese el correo electrónico">
                </div>
                <div class="form-group">
                    <label>Teléfono *</label>
                    <input type="tel" name="telefono" class="form-input" required
                           pattern="[0-9]{10}" maxlength="10"
                           placeholder="Ingrese el número de contacto telefónico">
                </div>
                <div class="form-group">
                    <label>Necesidad <small>(opcional)</small></label>
                    <input type="text" name="necesidad" id="nuevo_necesidad" class="form-input" maxlength="300"
                           placeholder="Describa la necesidad específica del usuario">
                </div>
                <div class="form-group" id="grp_nuevo_prioridad" style="display:none;">
                    <label>Prioridad</label>
                    <select name="prioridad" id="nuevo_prioridad" class="form-input">
                        <option value="">Sin prioridad</option>
                        <option value="alta">Alta</option>
                        <option value="media">Media</option>
                        <option value="baja">Baja</option>
                    </select>
                </div>
                <div class="form-group" id="grp_nuevo_obs" style="display:none;">
                    <label>Observación</label>
                    <textarea name="observacion_visita" id="nuevo_obs_visita" class="form-input"
                              rows="3" maxlength="500"
                              placeholder="Describa la observación del usuario"></textarea>
                </div>
                <div class="form-group">
                    <label>Rol *</label>
                    <select name="rol" id="nuevo_rol" class="form-input" required
                            onchange="toggleCamposDonante('nuevo_rol','grp_nuevo_prioridad','grp_nuevo_obs')">
                        <option value="donante">Donante / Solicitante</option>
                        <option value="asistente">Asistente</option>
                        <option value="administrador">Administrador</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado *</label>
                    <select name="estado" class="form-input" required>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="suspendido">Suspendido</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Contraseña *</label>
                    <div class="pass-wrap">
                        <input type="password" name="password" id="cu_pass" class="form-input"
                               required minlength="6" maxlength="30"
                               placeholder="Asigne una clave de acceso al sistema">
                        <button type="button" class="eye-btn" onclick="togglePass('cu_pass','cu_eye')">
                            <i class="fa-solid fa-eye" id="cu_eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirmar contraseña *</label>
                    <div class="pass-wrap">
                        <input type="password" name="password_confirm" id="cu_pass2" class="form-input"
                               required minlength="6" maxlength="30"
                               placeholder="Repita la clave de acceso para verificar">
                        <button type="button" class="eye-btn" onclick="togglePass('cu_pass2','cu_eye2')">
                            <i class="fa-solid fa-eye" id="cu_eye2"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div id="cu_pass_err" class="field-error" style="display:none;">Las contraseñas no coinciden.</div>
            <div class="modal-footer">
                <button type="submit" name="crear_usuario" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Crear Usuario
                </button>
                <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalCrearUsuario')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- EDITAR USUARIO -->
<div id="modalEditarUsuario" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><i class="fa-solid fa-user-pen"></i> Editar Usuario</h3>
            <button class="modal-close" onclick="cerrarModal('modalEditarUsuario')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="../controller/acciones_usuarios.php" method="POST"
              onsubmit="return validarPassModal('eu_pass','eu_pass2','eu_pass_err')">
            <input type="hidden" name="idUsuario" id="eu_id">
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Nombre completo *</label>
                    <input type="text" name="nombre" id="eu_nombre" class="form-input"
                           required minlength="3" maxlength="100"
                           oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '')"
                           placeholder="Modifique los nombres y apellidos del usuario">
                </div>
                <div class="form-group">
                    <label>Tipo de documento *</label>
                    <select name="tipoDocumento" id="eu_tipo" class="form-input" required>
                        <?php foreach (['CC','TI','CE','PEP'] as $t): ?>
                            <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Número de documento *</label>
                    <input type="text" name="numDocumento" id="eu_doc" class="form-input"
                           required maxlength="15" pattern="[0-9]{4,15}"
                           placeholder="Modifique el número de identificación">
                </div>
                <div class="form-group">
                    <label>Fecha de nacimiento *</label>
                    <input type="date" name="fechaNacimiento" id="eu_fnac" class="form-input"
                           required max="<?php echo date('Y-m-d', strtotime('-5 years')); ?>">
                </div>
                <div class="form-group">
                    <label>Dirección *</label>
                    <input type="text" name="direccion" id="eu_dir" class="form-input"
                           required minlength="5" maxlength="255"
                           placeholder="Modifique la dirección de residencia actual">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" id="eu_email" class="form-input" required
                           maxlength="150" placeholder="Modifique el correo electrónico del usuario">
                </div>
                <div class="form-group">
                    <label>Teléfono *</label>
                    <input type="tel" name="telefono" id="eu_tel" class="form-input"
                           required pattern="[0-9]{10}" maxlength="10"
                           placeholder="Modifique el número de contacto telefónico">
                </div>
                <div class="form-group">
                    <label>Necesidad</label>
                    <input type="text" name="necesidad" id="eu_nec" class="form-input" maxlength="300"
                           placeholder="Modifique la necesidad registrada del usuario">
                </div>
                <div class="form-group" id="grp_eu_prioridad" style="display:none;">
                    <label>Prioridad</label>
                    <select name="prioridad" id="eu_prioridad" class="form-input">
                        <option value="">Sin prioridad</option>
                        <option value="alta">Alta</option>
                        <option value="media">Media</option>
                        <option value="baja">Baja</option>
                    </select>
                </div>
                <div class="form-group" id="grp_eu_obs" style="display:none;">
                    <label>Observación</label>
                    <textarea name="observacion_visita" id="eu_obs_visita" class="form-input"
                              rows="3" maxlength="500"
                              placeholder="Modifique la observación registrada del usuario"></textarea>
                </div>
                <div class="form-group">
                    <label>Rol *</label>
                    <select name="rol" id="eu_rol" class="form-input" required
                            onchange="toggleCamposDonante('eu_rol','grp_eu_prioridad','grp_eu_obs')">
                        <option value="donante">Donante / Solicitante</option>
                        <option value="asistente">Asistente</option>
                        <option value="administrador">Administrador</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado *</label>
                    <select name="estado" id="eu_estado" class="form-input" required>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="suspendido">Suspendido</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nueva contraseña <small>(vacío = no cambiar)</small></label>
                    <div class="pass-wrap">
                        <input type="password" name="password" id="eu_pass" class="form-input"
                               autocomplete="new-password" minlength="6" maxlength="30"
                               placeholder="Ingrese una nueva clave si desea cambiarla">
                        <button type="button" class="eye-btn" onclick="togglePass('eu_pass','eu_eye')">
                            <i class="fa-solid fa-eye" id="eu_eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirmar contraseña</label>
                    <div class="pass-wrap">
                        <input type="password" name="password_confirm" id="eu_pass2" class="form-input"
                               autocomplete="new-password" minlength="6" maxlength="30"
                               placeholder="Repita la nueva clave para verificar">
                        <button type="button" class="eye-btn" onclick="togglePass('eu_pass2','eu_eye2')">
                            <i class="fa-solid fa-eye" id="eu_eye2"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div id="eu_pass_err" class="field-error" style="display:none;">Las contraseñas no coinciden.</div>
            <div class="modal-footer">
                <button type="submit" name="update_user" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar cambios
                </button>
                <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditarUsuario')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- GESTIONAR DONACIÓN -->
<div id="modalDonacion" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-box-open"></i> Gestionar Donación</h3>
            <button class="modal-close" onclick="cerrarModal('modalDonacion')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="don_detalle" class="detalle-box"></div>
        <form action="../controller/acciones_donapp.php" method="POST"
              onsubmit="return validarObservacionRequerida('don_estado','don_obs','don_obs_err')">
            <input type="hidden" name="id"   id="don_id">
            <input type="hidden" name="tipo" value="donacion">
            <div class="form-group">
                <label>Estado</label>
                <select name="nuevo_estado" id="don_estado" class="form-input"
                        onchange="actualizarHintObservacion('don_estado','don_obs_hint','don_obs','don_obs_err')">
                    <option value="pendiente">Pendiente</option>
                    <option value="aprobada">Aprobada</option>
                    <option value="rechazada">Rechazada</option>
                </select>
            </div>
            <div class="form-group">
                <label>Observación <span id="don_obs_hint">(opcional)</span></label>
                <textarea name="observacion" id="don_obs" class="form-input" rows="3"
                          placeholder="Añade una observación..." maxlength="250"></textarea>
                <small id="don_obs_err" class="field-error" style="display:none;">
                    <i class="fa-solid fa-triangle-exclamation"></i> La observación es obligatoria al rechazar.
                </small>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
                <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalDonacion')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- GESTIONAR SOLICITUD -->
<div id="modalSolicitud" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-clipboard-list"></i> Gestionar Solicitud</h3>
            <button class="modal-close" onclick="cerrarModal('modalSolicitud')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="sol_detalle" class="detalle-box"></div>
        <form action="../controller/acciones_donapp.php" method="POST"
              onsubmit="return validarObservacionRequerida('sol_estado','sol_obs','sol_obs_err')">
            <input type="hidden" name="id"   id="sol_id">
            <input type="hidden" name="tipo" value="solicitud">
            <div class="form-group">
                <label>Estado</label>
                <select name="nuevo_estado" id="sol_estado" class="form-input"
                        onchange="actualizarHintObservacion('sol_estado','sol_obs_hint','sol_obs','sol_obs_err')">
                    <option value="pendiente">Pendiente</option>
                    <option value="aprobada">Aprobada</option>
                    <option value="rechazada">Rechazada</option>
                </select>
            </div>
            <div class="form-group">
                <label>Observación <span id="sol_obs_hint">(opcional)</span></label>
                <textarea name="observacion" id="sol_obs" class="form-input" rows="3"
                          placeholder="Añade una observación..." maxlength="250"></textarea>
                <small id="sol_obs_err" class="field-error" style="display:none;">
                    <i class="fa-solid fa-triangle-exclamation"></i> La observación es obligatoria al rechazar.
                </small>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar</button>
                <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalSolicitud')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- CREAR EVENTO -->
<div id="modalCrearEvento" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-calendar-plus"></i> Publicar Nuevo Evento</h3>
            <button class="modal-close" onclick="cerrarModal('modalCrearEvento')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="../controller/acciones_eventos.php" method="POST" enctype="multipart/form-data">
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Nombre del Evento *</label>
                    <input type="text" name="nombre_evento" class="form-input" required
                           placeholder="Ingresa el nombre del evento" maxlength="150">
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado_evento" class="form-input">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Fecha de la Entrega *</label>
                    <input type="date" name="fecha_entrega" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Lugar de Entrega *</label>
                    <input type="text" name="lugar_entrega" class="form-input" required
                           placeholder="Ingresa la dirección del lugar de entrega"
                           value="Transversal 73 H Bis #75B 46 SUR Barrio Sierra Morena V Sector"
                           maxlength="255">
                </div>
            </div>
            <hr>
            <div class="form-group">
                <label>Título de la Publicación *</label>
                <input type="text" name="titulo_pub" class="form-input" required
                       placeholder="Ingresa el título de la publicación" maxlength="200">
            </div>
            <div class="form-group">
                <label>Contenido / Detalles *</label>
                <textarea name="contenido_pub" class="form-input" rows="3" required
                          placeholder="Describe los detalles del evento para los usuarios" maxlength="500"></textarea>
            </div>
            <div class="form-group">
                <label>Imagen (Opcional)</label>
                <input type="file" name="imagen_pub" class="form-input" accept="image/*">
            </div>
            <div class="modal-footer">
                <button type="submit" name="crear_evento_completo" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Crear y Publicar
                </button>
                <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalCrearEvento')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- EDITAR EVENTO -->
<div id="modalEditarEvento" class="modal">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><i class="fa-solid fa-calendar-check"></i> Editar Evento y Publicación</h3>
            <button type="button" class="modal-close" onclick="cerrarModal('modalEditarEvento')">&times;</button>
        </div>
        <form action="../controller/acciones_eventos.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="idEvento" id="edit_idEvento">
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Nombre del Evento</label>
                    <input type="text" name="nombre" id="edit_nombre" class="form-input" required
                           placeholder="Ingresa el nombre del evento" maxlength="150">
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado" id="edit_estado" class="form-input">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group">
                    <label>Fecha de Entrega</label>
                    <input type="date" name="fecha_entrega" id="edit_fecha_entrega" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Lugar</label>
                    <input type="text" name="lugar" id="edit_lugar_entrega" class="form-input" required
                           placeholder="Ingresa la dirección del lugar de entrega" maxlength="255">
                </div>
            </div>
            <hr>
            <div class="form-group">
                <label>Título de la Publicación</label>
                <input type="text" name="titulo_p" id="edit_titulo_pub" class="form-input" required
                       placeholder="Ingresa el título de la publicación" maxlength="200">
            </div>
            <div class="form-group">
                <label>Contenido</label>
                <textarea name="contenido_p" id="edit_contenido_pub" class="form-input" rows="4" required
                          placeholder="Describe los detalles del evento para los usuarios" maxlength="500"></textarea>
            </div>
            <div class="form-group">
                <label>Imagen de la Publicación</label>
                <div id="edit_img_preview_wrap" class="img-preview-wrap">
                    <p class="img-preview-label"><i class="fa-solid fa-image"></i> Imagen actual:</p>
                    <img id="edit_img_preview" src="" alt="Imagen actual" class="img-current-preview">
                </div>
                <input type="file" name="imagen_pub" id="edit_imagen_pub" class="form-input" accept="image/*"
                       onchange="previewNuevaImagen(this)">
                <small class="text-muted">Deja vacío para mantener la imagen actual.</small>
                <img id="edit_nueva_img_preview" src="" alt="Nueva imagen"
                     class="img-new-preview" style="display:none;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditarEvento')">Cancelar</button>
                <button type="submit" name="editar_evento_completo" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- CREAR CATEGORÍA -->
<div id="modalCrearCategoria" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-tags"></i> Nueva Categoría</h3>
            <button class="modal-close" onclick="cerrarModal('modalCrearCategoria')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="../controller/acciones_categorias.php" method="POST"
              onsubmit="return validarCategoria('crear')">
            <div class="form-group">
                <label>Nombre de la categoría *</label>
                <input type="text" name="nombre_categoria" id="cat_nombre" class="form-input"
                       required minlength="3" maxlength="100"
                       placeholder="Ingrese la nueva categoría"
                       oninput="validarEntrada(this)">
                <small id="cat_err" class="field-error" style="display:none;"></small>
            </div>
            <div class="modal-footer">
                <button type="submit" name="crear_categoria" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Crear
                </button>
                <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalCrearCategoria')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- EDITAR CATEGORÍA -->
<div id="modalEditarCategoria" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fa-solid fa-tag"></i> Editar Categoría</h3>
            <button class="modal-close" onclick="cerrarModal('modalEditarCategoria')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="../controller/acciones_categorias.php" method="POST"
              onsubmit="return validarCategoria('editar')">
            <input type="hidden" name="idCategoria" id="ecat_id">
            <div class="form-group">
                <label>Nombre de la categoría *</label>
                <input type="text" name="nombre_categoria" id="ecat_nombre" class="form-input"
                       required minlength="3" maxlength="100"
                       oninput="validarEntrada(this)">
                <small id="ecat_err" class="field-error" style="display:none;"></small>
            </div>
            <div class="modal-footer">
                <button type="submit" name="editar_categoria" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar
                </button>
                <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEditarCategoria')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- JS al final del body -->
<script src="../assets/js/admin.js"></script>
<script src="../assets/js/admin_dashboard.js"></script>

</body>
</html>+

