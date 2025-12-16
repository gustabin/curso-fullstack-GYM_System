// dashboard.js
$(document).ready(function () {
    // 🔒 Verificación de sesión al cargar la página
    $.ajax({
        url: '../php/dashboard.php?action=check-session',
        method: 'GET',
        dataType: 'json',
        success: function (res) {
            // alert(res.status);
            if (res.status !== 'active') {
                window.location.href = '../auth/index.html';
            }
        },
        error: function () {
            window.location.href = '../auth/index.html';
        }
    });



    // Cargar métricas del dashboard
    loadDashboardMetrics();
    loadRecentPayments();
    // Cargar y mostrar gráfico de asistencias
    loadAttendanceChart();



    // Manejar logout
    $('#logoutBtn').click(function () {
        Swal.fire({
            title: '¿Seguro que deseas cerrar sesión?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, cerrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../php/auth.php?action=logout',
                    method: 'POST',
                    success: function (res) {
                        const data = typeof res === 'string' ? JSON.parse(res) : res;
                        if (data.status === 'success') {
                            // Redirigir al login
                            window.location.href = '../auth/index.html';
                        } else {
                            Swal.fire('Error', 'No se pudo cerrar sesión', 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Error de conexión', 'error');
                    }
                });
            }
        });
    });
});

function loadAttendanceChart() {
    $.ajax({
        url: '../php/dashboard.php?action=get-attendance-trend',
        method: 'GET',
        dataType: 'json',
        success: function (res) {
            if (res.status !== 'success') return;

            const ctx = document.getElementById('attendanceChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: res.labels,
                    datasets: [{
                        label: 'Asistencias por día',
                        data: res.data,
                        backgroundColor: 'rgba(40, 167, 69, 0.6)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    });
}

function loadDashboardMetrics() {
    $.ajax({
        url: '../php/dashboard.php?action=get-metrics',
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            if (data.status === 'success') {
                $('#activeMembers').text(data.metrics.active_members);
                $('#todayAttendance').text(data.metrics.today_attendance);
                $('#expiringMemberships').text(data.metrics.expiring_memberships);
                $('#recentPayments').text(data.metrics.recent_payments_count);
            } else {
                Swal.fire('Error', 'No se pudieron cargar las métricas', 'error');
            }
        },
        error: function () {
            Swal.fire('Error', 'Error de conexión al cargar métricas', 'error');
        }
    });
}


function loadRecentPayments() {
    $.ajax({
        url: '../php/dashboard.php?action=get-recent-payments',
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            if (data.status === 'success') {
                if ($.fn.DataTable.isDataTable('#paymentsTable')) {
                    $('#paymentsTable').DataTable().destroy();
                }

                $('#paymentsTable').DataTable({
                    data: data.payments,
                    columns: [
                        {
                            className: 'dtr-control',
                            orderable: false,
                            data: null,
                            defaultContent: ''
                        },
                        { data: 'payment_id', title: 'ID Pago' },
                        { data: 'member_name', title: 'Socio' },
                        {
                            data: 'amount',
                            title: 'Monto',
                            render: (data) => '$' + parseFloat(data).toFixed(2)
                        },
                        { data: 'paid_at', title: 'Fecha' }
                    ],
                    responsive: {
                        details: {
                            type: 'column',
                            target: 'tr'
                        }
                    },
                    order: [[1, 'desc']],
                    language: {
                        "sProcessing": "Procesando...",
                        "sLengthMenu": "Mostrar _MENU_ registros",
                        "sZeroRecords": "No hay pagos registrados",
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
                    pageLength: 5
                });
            }
        },
        error: function () {
            Swal.fire('Error', 'Error al cargar los pagos', 'error');
        }
    });
}