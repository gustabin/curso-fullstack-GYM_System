// memberships.js
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

    loadMemberships();

    // Abrir modal para crear
    $('#crearMembresiaBtn').click(function () {
        $('#membresiaForm')[0].reset();
        $('#membershipId').val('');
        $('#membresiaModal').modal('show');
    });

    // Guardar membresía
    $('#guardarMembresia').click(function () {
        const id = $('#membershipId').val();
        const name = $('#name').val().trim();
        const durationDays = $('#durationDays').val();
        const price = $('#price').val();
        const benefits = $('#benefits').val().trim();

        if (!name || !durationDays || !price) {
            Swal.fire('Error', 'Nombre, duración y precio son obligatorios', 'error');
            return;
        }

        $.ajax({
            url: '../php/memberships.php?action=' + (id ? 'update' : 'create'),
            method: 'POST',
            data: {
                id: id,
                name: name,
                durationDays: durationDays,
                price: price,
                benefits: benefits
            },
            success: function (res) {
                const data = typeof res === 'string' ? JSON.parse(res) : res;
                if (data.status === 'success') {
                    Swal.fire('Éxito', data.message, 'success');
                    $('#membresiaModal').modal('hide');
                    loadMemberships();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        });
    });

    // Cargar membresías
    function loadMemberships() {
        $.ajax({
            url: '../php/memberships.php?action=getAll',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.status !== 'success') return;

                if ($.fn.DataTable.isDataTable('#membershipsTable')) {
                    $('#membershipsTable').DataTable().destroy();
                }

                $('#membershipsTable').DataTable({
                    data: res.data,
                    columns: [
                        {
                            className: 'dtr-control',
                            orderable: false,
                            data: null,
                            defaultContent: ''
                        },
                        { data: 'membership_id', title: 'ID' },
                        { data: 'name', title: 'Nombre' },
                        { data: 'duration_days', title: 'Duración (días)' },
                        {
                            data: 'price',
                            title: 'Precio ($)',
                            render: (data) => '$' + parseFloat(data).toFixed(2)
                        },
                        {
                            data: 'benefits',
                            title: 'Beneficios',
                            render: (data) => data ? data.replace(/\n/g, '<br>') : '—'
                        },
                        {
                            data: 'membership_id',
                            title: 'Acciones',
                            render: (id) => `
                                <button class="btn btn-warning btn-sm edit-btn" data-id="${id}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger btn-sm delete-btn" data-id="${id}">
                                    <i class="fas fa-trash"></i>
                                </button>`
                        }
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
                        "sZeroRecords": "No hay membresías registradas",
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
                    pageLength: 10
                });
            }
        });
    }

    // Editar
    $(document).on('click', '#membershipsTable tbody .edit-btn', function () {
        const id = $(this).data('id');
        $.ajax({
            url: '../php/memberships.php?action=getById',
            method: 'GET',
            data: { id: id },
            success: function (res) {
                const data = typeof res === 'string' ? JSON.parse(res) : res;
                if (data.status !== 'success') return;

                const m = data.membership;
                $('#membershipId').val(m.membership_id);
                $('#name').val(m.name);
                $('#durationDays').val(m.duration_days);
                $('#price').val(m.price);
                $('#benefits').val(m.benefits || '');
                $('#membresiaModal').modal('show');
            }
        });
    });

    // Eliminar
    $(document).on('click', '#membershipsTable tbody .delete-btn', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar membresía?',
            text: "Esta acción afectará a socios que la tengan asignada.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../php/memberships.php?action=delete',
                    method: 'POST',
                    data: { id: id },
                    success: function (res) {
                        const data = typeof res === 'string' ? JSON.parse(res) : res;
                        if (data.status === 'success') {
                            Swal.fire('Eliminado', data.message, 'success');
                            loadMemberships();
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    }
                });
            }
        });
    });
});