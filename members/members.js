// members.js
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
    $('#crearSocioBtn').click(function () {
        $('#socioForm')[0].reset();
        $('#photoPreview').hide();
        $('#memberId').val('');
        $('#socioModal').modal('show');
    });

    // Guardar socio
    $('#guardarSocio').click(function () {
        const id = $('#memberId').val();
        const firstName = $('#firstName').val().trim();
        const lastName = $('#lastName').val().trim();
        const dni = $('#dni').val().trim();
        const email = $('#email').val().trim();
        const phone = $('#phone').val().trim();
        const photo = $('#photo')[0].files[0];

        if (!firstName || !lastName) {
            Swal.fire('Error', 'Nombre y apellido son obligatorios', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('id', id);
        formData.append('firstName', firstName);
        formData.append('lastName', lastName);
        formData.append('dni', dni);
        formData.append('email', email);
        formData.append('phone', phone);
        if (photo) formData.append('photo', photo);

        const action = id ? 'update' : 'create';
        $.ajax({
            url: '../php/members.php?action=' + action,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                const data = typeof res === 'string' ? JSON.parse(res) : res;
                if (data.status === 'success') {
                    Swal.fire('Éxito', data.message, 'success');
                    $('#socioModal').modal('hide');
                    loadMembers();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Error de conexión', 'error');
            }
        });
    });

    // Cargar socios
    function loadMembers() {
        $.ajax({
            url: '../php/members.php?action=getAll',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.status !== 'success') return;

                if ($.fn.DataTable.isDataTable('#membersTable')) {
                    $('#membersTable').DataTable().destroy();
                }

                $('#membersTable').DataTable({
                    data: res.data,
                    columns: [
                        {
                            className: 'dtr-control',
                            orderable: false,
                            data: null,
                            defaultContent: ''
                        },
                        { data: 'member_id', title: 'ID' },
                        { data: 'full_name', title: 'Nombre Completo' },
                        { data: 'dni', title: 'DNI' },
                        { data: 'email', title: 'Email' },
                        { data: 'phone', title: 'Teléfono' },
                        {
                            data: 'photo',
                            title: 'Foto',
                            render: (data) => data ? `<img src="../images/members/${data}" width="40" class="rounded">` : '—'
                        },
                        {
                            data: 'member_id',
                            title: 'Acciones',
                            className: 'text-right',
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
                    order: [[1, 'asc']],
                    language: {
                        "sProcessing": "Procesando...",
                        "sLengthMenu": "Mostrar _MENU_ registros",
                        "sZeroRecords": "No se encontraron resultados",
                        "sEmptyTable": "Ningún dato disponible",
                        "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                        "sSearch": "Buscar:",
                        "oPaginate": {
                            "sFirst": "Primero",
                            "sLast": "Último",
                            "sNext": "Siguiente",
                            "sPrevious": "Anterior"
                        }
                    }
                });
            }
        });
    }

    // Editar
    $('#membersTable tbody').on('click', '.edit-btn', function () {
        const id = $(this).data('id');
        $.ajax({
            url: '../php/members.php?action=getById',
            data: { id: id },
            success: function (res) {
                const data = typeof res === 'string' ? JSON.parse(res) : res;
                if (data.status !== 'success') return;

                const m = data.member;
                $('#memberId').val(m.member_id);
                $('#firstName').val(m.first_name);
                $('#lastName').val(m.last_name);
                $('#dni').val(m.dni);
                $('#email').val(m.email);
                $('#phone').val(m.phone);
                if (m.photo) {
                    $('#photoPreview').attr('src', '../images/members/' + m.photo).show();
                } else {
                    $('#photoPreview').hide();
                }
                $('#socioModal').modal('show');
            }
        });
    });

    // Eliminar
    $('#membersTable tbody').on('click', '.delete-btn', function () {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar socio?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../php/members.php?action=delete',
                    method: 'POST',
                    data: { id: id },
                    success: function (res) {
                        const data = typeof res === 'string' ? JSON.parse(res) : res;
                        if (data.status === 'success') {
                            Swal.fire('Eliminado', data.message, 'success');
                            loadMembers();
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    }
                });
            }
        });
    });
});