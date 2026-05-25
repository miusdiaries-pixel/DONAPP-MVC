function soloLetras(e) {
    let key   = e.keyCode || e.which;
    let tecla = String.fromCharCode(key).toLowerCase();
    let letras = ' áéíóúabcdefghijklmnñopqrstuvwxyz';
    return letras.indexOf(tecla) !== -1;
}

function soloNumeros(e) {
    let key = e.keyCode || e.which;
    return (key >= 48 && key <= 57);
}

function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function abrirAsistido() {
    document.getElementById('modalAsistido').style.display = 'flex';
}

function cerrarAsistido() {
    document.getElementById('modalAsistido').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('modalAsistido');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
};

const registrationForm = document.getElementById('registerForm');

const fechaNacInput = document.getElementById('fecha_nac');
if (fechaNacInput) {
    fechaNacInput.max = new Date().toISOString().split('T')[0];
}

registrationForm.onsubmit = function(e) {
    const pass    = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    if (pass !== confirm) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: '¡Ups!',
            text: 'Las contraseñas no coinciden.',
            confirmButtonColor: '#df0b0b'
        });
        return false;
    }
};
