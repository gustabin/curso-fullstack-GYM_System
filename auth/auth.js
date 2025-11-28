// auth.js
$(document).ready(function () {
    // Mostrar login por defecto
    $('#loginForm').removeClass('d-none');

    // Cambiar entre formularios
    $('#showRecoveryLink').click(e => {
        e.preventDefault();
        $('#loginForm').addClass('d-none');
        $('#recoveryForm').removeClass('d-none');
    });

    $('#showLoginLink').click(e => {
        e.preventDefault();
        $('#recoveryForm').addClass('d-none');
        $('#loginForm').removeClass('d-none');
    });

    // Login
    $('#loginForm').submit(function (e) {
        e.preventDefault();
        const username = $('#username').val().trim();
        const password = $('#password').val();

        if (!username || !password) {
            Swal.fire('Error', 'Completa todos los campos', 'error');
            return;
        }

        $.ajax({
            url: '../php/auth.php?action=login',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ username, password }),
            success: function (res) {
                const data = typeof res === 'string' ? JSON.parse(res) : res;
                if (data.status === 'success') {
                    Swal.fire('¡Bienvenido!', 'Iniciando sesión...', 'success').then(() => {
                        window.location.href = data.redirect;
                    });
                } else {
                    Swal.fire('Error', data.message || 'Credenciales inválidas', 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        });
    });

    // Recuperación de contraseña
    $('#recoveryForm').submit(function (e) {
        e.preventDefault();
        const email = $('#recoveryEmail').val().trim();

        if (!email || !validateEmail(email)) {
            Swal.fire('Error', 'Ingresa un email válido', 'error');
            return;
        }

        $.ajax({
            url: '../php/auth.php?action=request-reset',
            method: 'POST',
            data: { email: email },
            success: function (res) {
                const data = typeof res === 'string' ? JSON.parse(res) : res;
                if (data.status === 'success') {
                    Swal.fire('¡Listo!', data.message, 'success');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'No se pudo enviar el correo', 'error');
            }
        });
    });

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});