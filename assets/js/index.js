/**
 * DONAPP - Sistema de Gestión de Donaciones
 * JS Principal optimizado para integración con PHP
 */

document.addEventListener('DOMContentLoaded', () => {
    console.log('✅ DONAPP cargado correctamente');

    // Partículas animadas para el hero (si el contenedor existe)
    initHeroParticles();
});

// ========== MODALES ==========

function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Evita scroll de fondo
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

// Cerrar modales al hacer clic fuera del contenido
window.addEventListener('click', (event) => {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
});

// ========== VER DETALLE DE PUBLICACIÓN ==========

function verDetallePublicacion(data) {
    // 1. Normalizar estado
    const estadoLimpio = data.estado.trim().toLowerCase();
    const textoEstado = estadoLimpio === 'activo' ? 'Activo' : 'Finalizado';
    const claseEstado = estadoLimpio === 'activo' ? 'badge-activo' : 'badge-inactivo';

    // 2. Llenar elementos de texto básicos
    document.getElementById('detalle-titulo').textContent = data.titulo;
    document.getElementById('detalle-contenido').textContent = data.contenido;
    document.getElementById('detalle-evento').innerHTML = `<i class="fa-solid fa-tag"></i> ${data.evento}`;
    document.getElementById('detalle-entrega').textContent = data.entrega;
    document.getElementById('detalle-lugar').textContent = data.lugar;
    document.getElementById('detalle-autor').innerHTML = `<i class="fa-regular fa-user"></i> Publicado por ${data.autor}`;

    // 3. Construir el Header (Estado y Fecha) con margen superior
    const detalleHeader = document.getElementById('detalle-header');
    let htmlHeader = `
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top: 10px; margin-bottom: 20px; width: 100%; padding-right: 50px;">
            <span class="publicacion-badge ${claseEstado}">${textoEstado}</span>
            <span class="publicacion-fecha" style="color: var(--color-text-muted); font-size: 0.9rem; font-weight: 500;">
                <i class="fa-regular fa-calendar"></i> ${data.fecha}
            </span>
        </div>`;

    detalleHeader.innerHTML = htmlHeader;

    // 4. Gestión de la Imagen (TIPO PÓSTER - SIN CORTAR)
    const imgPrevia = document.getElementById('detalle-imagen-bloque');
    if (imgPrevia) imgPrevia.remove();

    if (data.imagen && data.imagen.trim() !== '') {
        const infoBox = document.querySelector('.detalle-info-box');
        const pImagenBloque = document.createElement('div');
        pImagenBloque.id = 'detalle-imagen-bloque';

        // Contenedor con fondo neutro por si la imagen es muy delgada
        pImagenBloque.style.cssText = `
            margin-top: 15px; 
            margin-bottom: 25px; 
            width: 100%; 
            background: #f8f9fa; 
            border-radius: 12px; 
            overflow: hidden;
            display: flex;
            justify-content: center;
        `;

        const pImagen = document.createElement('img');
        pImagen.src = data.imagen;

        /* CAMBIO CLAVE: 
           - max-height: 500px (ajusta este valor según prefieras el alto máximo)
           - object-fit: contain (Muestra la imagen completa sin recortes)
        */
        pImagen.style.cssText = `
            width: 100%; 
            max-height: 500px; 
            object-fit: contain; 
            display: block;
            background: #f8f9fa;
        `;

        pImagenBloque.appendChild(pImagen);

        if (infoBox) {
            infoBox.parentNode.insertBefore(pImagenBloque, infoBox);
        }
    }

    // 5. Ajustes de estructura del Modal
    const modalContent = document.querySelector('.detalle-publicacion-content');
    if (modalContent) {
        modalContent.style.display = 'block';
        modalContent.style.position = 'relative';
        modalContent.style.padding = '20px 25px';
    }

    // 6. Botón Cerrar (X) - Estilizado y sobre la imagen si es necesario
    const closeBtn = document.querySelector('#modal-detalle-publicacion .modal-close');
    if (closeBtn) {
        closeBtn.style.cssText = `
            position: absolute !important;
            top: 15px !important;
            right: 15px !important;
            background: white;
            color: #333;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            border: 1px solid #eee;
            cursor: pointer;
        `;
    }

    showModal('modal-detalle-publicacion');
}

// ========== UTILIDADES VISUALES ==========

function initHeroParticles() {
    const container = document.getElementById('particles-container');
    if (!container) return;

    const count = 30;
    for (let i = 0; i < count; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.cssText = `
            position: absolute;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
            width: ${2 + Math.random() * 6}px;
            height: ${2 + Math.random() * 6}px;
            animation: float ${6 + Math.random() * 10}s linear infinite;
            animation-delay: ${Math.random() * 5}s;
            pointer-events: none;
        `;
        container.appendChild(p);
    }
}

// Estilo para la animación de las partículas (inyectado)
const style = document.createElement('style');
style.innerHTML = `
    @keyframes float {
        0% { transform: translateY(0) translateX(0); opacity: 0; }
        50% { opacity: 0.5; }
        100% { transform: translateY(-100vh) translateX(20px); opacity: 0; }
    }
`;
document.head.appendChild(style);