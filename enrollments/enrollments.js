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

    loadMembers();
    loadMemberships();
    loadEnrollments();

    function loadMembers() {
        $.ajax({
            url: '../php/enrollments.php?action=get-members',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    const $sel = $('#memberSelect');
                    $sel.empty().append('<option value="">-- Seleccione --</option>');
                    res.members.forEach(m => {
                        $sel.append(`<option value="${m.member_id}">${m.full_name} (DNI: ${m.dni || '—'})</option>`);
                    });
                }
            },
            error: function () { console.error('Error cargando miembros'); }
        });
    }

    function loadMemberships() {
        $.ajax({
            url: '../php/enrollments.php?action=get-memberships',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    const $sel = $('#membershipSelect');
                    $sel.empty().append('<option value="">-- Seleccione --</option>');
                    res.memberships.forEach(m => {
                        $sel.append(`<option value="${m.membership_id}">${m.name} - $${parseFloat(m.price).toFixed(2)} (${m.duration_days} días)</option>`);
                    });
                }
            },
            error: function () { console.error('Error cargando membresías'); }
        });
    }

    $('#asignarBtn').off('click').on('click', function () {
        const memberId = $('#memberSelect').val();
        const membershipId = $('#membershipSelect').val();

        if (!memberId || !membershipId) {
            Swal.fire('Error', 'Seleccione socio y membresía', 'error');
            return;
        }

        $.ajax({
            url: '../php/enrollments.php?action=assign',
            method: 'POST',
            dataType: 'json',
            data: { member_id: memberId, membership_id: membershipId },
            success: function (res) {
                const data = typeof res === 'string' ? JSON.parse(res) : res;
                const msgDiv = $('#mensajeAsignacion');
                if (data.status === 'success') {
                    msgDiv.html('<div class="alert alert-success">✅ ' + data.message + '</div>');
                    loadEnrollments();
                    $('#memberSelect').val('');
                    $('#membershipSelect').val('');
                } else {
                    msgDiv.html('<div class="alert alert-danger">❌ ' + data.message + '</div>');
                }
                setTimeout(() => msgDiv.empty(), 5000);
            },
            error: function () {
                $('#mensajeAsignacion').html('<div class="alert alert-danger">Error de conexión</div>');
            }
        });
    });

    function loadEnrollments() {
        $.ajax({
            url: '../php/enrollments.php?action=get-all',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.status !== 'success') return;

                if ($.fn.DataTable.isDataTable('#enrollmentsTable')) {
                    $('#enrollmentsTable').DataTable().destroy();
                }

                $('#enrollmentsTable').DataTable({
                    data: res.data,
                    columns: [
                        { className: 'dtr-control', orderable: false, data: null, defaultContent: '' },
                        { data: 'mm_id', title: 'ID' },
                        { data: 'member_name', title: 'Socio' },
                        { data: 'membership_name', title: 'Membresía' },
                        { data: 'start_date', title: 'Inicio' },
                        { data: 'end_date', title: 'Fin' },
                        {
                            data: 'status',
                            title: 'Estado',
                            render: (d) => {
                                const badge = d === 'active' ? 'success' : 'secondary';
                                return `<span class="badge badge-${badge}">${d}</span>`;
                            }
                        }
                    ],
                    responsive: { details: { type: 'column', target: 'tr' } },
                    language: {
                        "sProcessing": "Procesando...",
                        "sLengthMenu": "Mostrar _MENU_ registros",
                        "sZeroRecords": "No hay inscripciones",
                        "sEmptyTable": "No hay datos",
                        "sInfo": "Mostrando _START_ a _END_ de _TOTAL_",
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
            error: function () { console.error('Error cargando inscripciones'); }
        });
    }
});