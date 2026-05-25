// Enviar formulario de filtros conservando el tab activo
function submitFiltroTab(form, tabName) {
    form.querySelector('[name="tab"]').value = tabName;
    form.submit();
}

// ── MODALES ───────────────────────────────────────────────────────────────
function abrirModal(id)  { document.getElementById(id).style.display = 'flex'; }
function cerrarModal(id) { document.getElementById(id).style.display = 'none'; }

document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) cerrarModal(m.id); });
});

// ── PREVIEW IMAGEN ────────────────────────────────────────────────────────
function previewImg(input, imgId) {
    const img = document.getElementById(imgId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { img.src = e.target.result; img.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    } else {
        img.style.display = 'none';
        img.src = '';
    }
}

// ── EDITAR DONACIÓN ───────────────────────────────────────────────────────
function abrirModalEditarDonacion(d) {
    document.getElementById('ed_id').value    = d.idDonacion;
    document.getElementById('ed_desc').value  = d.descripcion;
    document.getElementById('ed_cat').value   = d.idCategoria;
    document.getElementById('ed_stock').value = d.stock;
    const prev = document.getElementById('prev_ed_don');
    prev.style.display = 'none';
    prev.src = '';
    abrirModal('modalEditarDonacion');
}

// ── VER DETALLE DONACIÓN ──────────────────────────────────────────────────
function verDetalleDonacion(d) {
    const estadoClass = {
        pendiente: 'estado-pendiente',
        aprobada:  'estado-aprobada',
        rechazada: 'estado-rechazada'
    };
    const imgHtml = d.imagen ? `<img src="${d.imagen}" alt="" class="img-preview">` : '';
    const obsHtml = d.observacion
        ? `<div class="obs-box ${d.estado === 'aprobada' ? 'green-obs' : ''}">
               <strong><i class="fa-solid fa-comment-dots"></i> Observación:</strong><br>${d.observacion}
           </div>`
        : '';

    document.getElementById('detalle_don_body').innerHTML = `
        ${imgHtml}
        <div class="detalle-box">
            <p><strong>Descripción:</strong> ${d.descripcion}</p>
            <p><strong>Categoría:</strong> ${d.categoria || '—'}</p>
            <p><strong>Stock:</strong> ${d.stock}</p>
            <p><strong>Fecha:</strong> ${d.fechaCreacion ? d.fechaCreacion.substring(0,10) : '—'}</p>
            <p><strong>Estado:</strong> <span class="badge ${estadoClass[d.estado] || ''}">${d.estado}</span></p>
        </div>
        ${obsHtml}`;
    abrirModal('modalDetalleDonacion');
}

// ── EDITAR SOLICITUD ──────────────────────────────────────────────────────
function abrirModalEditarSolicitud(s) {
    document.getElementById('es_id').value   = s.idSolicitud;
    document.getElementById('es_desc').value = s.descripcion;
    document.getElementById('es_cat').value  = s.idCategoria;
    const prev = document.getElementById('prev_ed_sol');
    prev.style.display = 'none';
    prev.src = '';
    abrirModal('modalEditarSolicitud');
}

// ── VER DETALLE SOLICITUD ─────────────────────────────────────────────────
function verDetalleSolicitud(s) {
    const estadoClass = {
        pendiente: 'estado-pendiente',
        aprobada:  'estado-aprobada',
        rechazada: 'estado-rechazada'
    };
    const imgHtml = s.imagen ? `<img src="${s.imagen}" alt="" class="img-preview">` : '';
    const obsHtml = s.observacion
        ? `<div class="obs-box ${s.estado === 'aprobada' ? 'green-obs' : ''}">
               <strong><i class="fa-solid fa-comment-dots"></i> Observación / Motivo:</strong><br>${s.observacion}
           </div>`
        : '';

    document.getElementById('detalle_sol_body').innerHTML = `
        ${imgHtml}
        <div class="detalle-box">
            <p><strong>Descripción:</strong> ${s.descripcion}</p>
            <p><strong>Categoría:</strong> ${s.categoria || '—'}</p>
            <p><strong>Fecha:</strong> ${s.fechaCreacion ? s.fechaCreacion.substring(0,10) : '—'}</p>
            <p><strong>Estado:</strong> <span class="badge ${estadoClass[s.estado] || ''}">${s.estado}</span></p>
        </div>
        ${obsHtml}`;
    abrirModal('modalDetalleSolicitud');
}

// ── CONTRASEÑA ────────────────────────────────────────────────────────────
function togglePass(inputId, iconId) {
    const inp = document.getElementById(inputId);
    const ico = document.getElementById(iconId);
    if (inp.type === 'password') {
        inp.type       = 'text';
        ico.className  = 'fa-solid fa-eye-slash';
    } else {
        inp.type       = 'password';
        ico.className  = 'fa-solid fa-eye';
    }
}

function validarPassPerfil() {
    const pActual = document.getElementById('perfil_pass_actual').value;
    const p1      = document.getElementById('perfil_pass').value;
    const p2      = document.getElementById('perfil_pass2').value;
    const err     = document.getElementById('perfil_pass_err');
    err.style.display = 'none';

    if ((p1 || p2) && !pActual) {
        err.textContent   = 'Debes ingresar tu contraseña actual para cambiarla.';
        err.style.display = 'block';
        return false;
    }
    if (p1 && p1.length < 6) {
        err.textContent   = 'La nueva contraseña debe tener mínimo 6 caracteres.';
        err.style.display = 'block';
        return false;
    }
    if (p1 !== p2) {
        err.textContent   = 'Las contraseñas no coinciden.';
        err.style.display = 'block';
        return false;
    }
    return true;
}

// ── FLASH ─────────────────────────────────────────────────────────────────
const flash = document.getElementById('flashMsg');
if (flash) setTimeout(() => {
    flash.style.opacity = '0';
    setTimeout(() => flash.remove(), 500);
}, 3500);
