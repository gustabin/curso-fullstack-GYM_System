// attendances.js
$(document).ready(function () {
    // Protección de sesión
    $.ajax({
        url: '../php/dashboard.php?action=check-session',
        method: 'GET',
        dataType: 'json',
        success: function (res) {
            if (res.status !== 'active') window.location.href = '../auth/index.html';
        },
        error: function () {
            window.location.href = '../auth/index.html';
        }
    });

    loadMembersForSelect();
    loadAttendances();

    // Cargar socios activos en el select
    function loadMembersForSelect() {
        $.ajax({
            url: '../php/attendances.php?action=get-active-members',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                console.log('Respuesta del servidor:', res); // Debug
                if (res.status === 'success') {
                    const select = $('#memberSelect');
                    select.empty().append('<option value="">-- Seleccione un socio --</option>');

                    if (res.members && res.members.length > 0) {
                        res.members.forEach(m => {
                            select.append(`<option value="${m.member_id}">${m.full_name} (DNI: ${m.dni || '—'})</option>`);
                        });
                        console.log('Socios cargados:', res.members.length); // Debug
                    } else {
                        select.append('<option value="">No hay socios activos</option>');
                        console.log('No hay socios disponibles'); // Debug
                    }
                } else {
                    console.error('Error en la respuesta:', res.message);
                    Swal.fire('Error', res.message || 'No se pudieron cargar los socios', 'error');
                }
            },
            error: function (xhr, status, error) {
                console.error('Error AJAX:', error);
                console.error('Respuesta:', xhr.responseText);
                Swal.fire('Error', 'Error de conexión al cargar socios', 'error');
            }
        });
    }

    // Registrar asistencia
    $('#registrarAsistenciaBtn').click(function () {
        const memberId = $('#memberSelect').val();
        if (!memberId) {
            Swal.fire('Error', 'Seleccione un socio', 'error');
            return;
        }

        $.ajax({
            url: '../php/attendances.php?action=register',
            method: 'POST',
            data: { member_id: memberId },
            success: function (res) {
                const data = typeof res === 'string' ? JSON.parse(res) : res;
                const msgDiv = $('#mensajeAsistencia');
                if (data.status === 'success') {
                    msgDiv.html('<div class="alert alert-success">✅ ' + data.message + '</div>');
                    loadAttendances();
                    $('#memberSelect').val(''); // Limpiar selección
                } else {
                    msgDiv.html('<div class="alert alert-danger">❌ ' + data.message + '</div>');
                }
                setTimeout(() => msgDiv.empty(), 5000);
            },
            error: function () {
                $('#mensajeAsistencia').html('<div class="alert alert-danger">Error de conexión</div>');
            }
        });
    });

    // Cargar historial de asistencias
    function loadAttendances() {
        $.ajax({
            url: '../php/attendances.php?action=get-all',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.status !== 'success') return;

                if ($.fn.DataTable.isDataTable('#attendancesTable')) {
                    $('#attendancesTable').DataTable().destroy();
                }

                $('#attendancesTable').DataTable({
                    data: res.data,
                    columns: [
                        {
                            className: 'dtr-control',
                            orderable: false,
                            data: null,
                            defaultContent: ''
                        },
                        { data: 'attendance_id', title: 'ID' },
                        { data: 'member_name', title: 'Socio' },
                        { data: 'date', title: 'Fecha' },
                        { data: 'checked_in_at', title: 'Hora' }
                    ],
                    responsive: {
                        details: {
                            type: 'column',
                            target: 'tr'
                        }
                    },
                    language: {
                        "sProcessing": "Procesando...",
                        "sLengthMenu": "Mostrar _MENU_ registros",
                        "sZeroRecords": "No hay asistencias registradas",
                        "sEmptyTable": "No hay datos disponibles",
                        "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                        "sSearch": "Buscar:",
                        "oPaginate": {
                            "sFirst": "Primero",
                            "sLast": "Último",
                            "sNext": "Siguiente",
                            "sPrevious": "Anterior"
                        }
                    },
                    order: [[1, 'desc']],
                    pageLength: 10
                });
            },
            error: function (xhr, status, error) {
                console.error('Error al cargar asistencias:', error);
                Swal.fire('Error', 'No se pudieron cargar las asistencias', 'error');
            }
        });
    }
});