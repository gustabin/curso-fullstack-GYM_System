// trainers.js
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

    loadTrainers();

    // Vista previa de foto
    $('#photo').change(function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => {
                $('#photoPreview').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(file);
        }
    });

    // Abrir modal para crear
    $('#crearEntrenadorBtn').click(function () {
        $('#entrenadorForm')[0].reset();
        $('#photoPreview').hide();
        $('#trainerId').val('');
        $('#entrenadorModal').modal('show');
    });

    // Guardar entrenador
    $('#guardarEntrenador').click(function () {
        const id = $('#trainerId').val();
        const firstName = $('#firstName').val().trim();
        const lastName = $('#lastName').val().trim();
        const email = $('#email').val().trim();
        const phone = $('#phone').val().trim();
        const specialization = $('#specialization').val().trim();
        const photo = $('#photo')[0].files[0];

        if (!firstName || !lastName) {
            Swal.fire('Error', 'Nombre y apellido son obligatorios', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('id', id);
        formData.append('firstName', firstName);
        formData.append('lastName', lastName);
        formData.append('email', email);
        formData.append('phone', phone);
        formData.append('specialization', specialization);
        if (photo) formData.append('photo', photo);

        const action = id ? 'update' : 'create';
        $.ajax({
            url: '../php/trainers.php?action=' + action,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                const data = typeof res === 'string' ? JSON.parse(res) : res;
                if (data.status === 'success') {
                    Swal.fire('Éxito', data.message, 'success');
                    $('#entrenadorModal').modal('hide');
                    loadTrainers();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        });
    });

    // Cargar entrenadores
    function loadTrainers() {
        $.ajax({
            url: '../php/trainers.php?action=getAll',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.status !== 'success') return;

                if ($.fn.DataTable.isDataTable('#trainersTable')) {
                    $('#trainersTable').DataTable().destroy();
                }

                $('#trainersTable').DataTable({
                    data: res.data,
                    columns: [
                        { className: 'dtr-control', orderable: false, data: null, defaultContent: '' },
                        { data: 'trainer_id', title: 'ID' },
                        { data: 'full_name', title: 'Nombre Completo' },
                        { data: 'email', title: 'Email' },
                        { data: 'phone', title: 'Teléfono' },
                        { data: 'specialization', title: 'Especialidad' },
                        {
                            data: 'photo',
                            title: 'Foto',
                            render: (data) => data ? `<img src="../images/trainers/${data}" width="40" class="rounded">` : '—'
                        },
                        {
                            data: 'trainer_id',
                            title: 'Acciones',
                            orderable: false,
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
                        "sZeroRecords": "No hay entrenadores registrados",
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
            },
            error: function () { console.error('Error cargando entrenadores'); }
        });
    }

    // Editar (delegated)
    $(document).on('click', '#trainersTable tbody .edit-btn', function () {
        const id = $(this).data('id');
        $.ajax({
            url: '../php/trainers.php?action=getById',
            method: 'GET',
            dataType: 'json',
            data: { id: id },
            success: function (res) {
                const data = typeof res === 'string' ? JSON.parse(res) : res;
                if (data.status !== 'success') return;

                const t = data.trainer;
                $('#trainerId').val(t.trainer_id);
                $('#firstName').val(t.first_name);
                $('#lastName').val(t.last_name);
                $('#email').val(t.email);
                $('#phone').val(t.phone);
                $('#specialization').val(t.specialization);
                if (t.photo) {
                    $('#photoPreview').attr('src', '../images/trainers/' + t.photo).show();
                } else {
                    $('#photoPreview').hide();
                }
                $('#entrenadorModal').modal('show');
            },
            error: function () { Swal.fire('Error', 'Error al cargar entrenador', 'error'); }
        });
    });

    // Eliminar (delegated)
    $(document).on('click', '#trainersTable tbody .delete-btn', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar entrenador?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../php/trainers.php?action=delete',
                    method: 'POST',
                    dataType: 'json',
                    data: { id: id },
                    success: function (res) {
                        const data = typeof res === 'string' ? JSON.parse(res) : res;
                        if (data.status === 'success') {
                            Swal.fire('Eliminado', data.message, 'success');
                            loadTrainers();
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    },
                    error: function () { Swal.fire('Error', 'Error de conexión', 'error'); }
                });
            }
        });
    });
});