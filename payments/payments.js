$(document).ready(function () {
    // 🔒 Protección de sesión
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

    // Cargar datos para filtros y selección
    loadMembersForSelect();
    loadMembersForReport();

    // ==== REGISTRO DE PAGO ====
    function loadMembersForSelect() {
        $.ajax({
            url: '../php/payments.php?action=get-active-members',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    $('#memberSelect').empty().append('<option value="">-- Seleccione --</option>');
                    res.members.forEach(m => {
                        $('#memberSelect').append(`<option value="${m.member_id}">${m.full_name} (DNI: ${m.dni || '—'})</option>`);
                    });
                }
            },
            error: function () { console.error('Error cargando socios'); }
        });
    }

    $('#memberSelect').change(function () {
        const memberId = $(this).val();
        const $membership = $('#membershipSelect');
        $membership.prop('disabled', !memberId).empty();
        if (!memberId) {
            $membership.append('<option value="">-- Seleccione socio primero --</option>');
            return;
        }

        $.ajax({
            url: '../php/payments.php?action=get-active-memberships',
            method: 'GET',
            dataType: 'json',
            data: { member_id: memberId },
            success: function (res) {
                if (res.status === 'success') {
                    $membership.empty();
                    if (res.memberships && res.memberships.length > 0) {
                        res.memberships.forEach(m => {
                            $membership.append(`<option value="${m.mm_id}">${m.membership_name} (Vence: ${m.end_date})</option>`);
                        });
                    } else {
                        $membership.append('<option value="">Sin membresías activas</option>');
                    }
                } else {
                    $membership.append('<option value="">Error al cargar membresías</option>');
                }
            },
            error: function () {
                $membership.append('<option value="">Error de conexión</option>');
            }
        });
    });

    $('#registrarPagoBtn').click(function () {
        const mmId = $('#membershipSelect').val();
        const amount = $('#amount').val();
        const method = $('#paymentMethod').val();
        const notes = $('#notes').val().trim();

        if (!mmId || !amount || parseFloat(amount) <= 0) {
            Swal.fire('Error', 'Seleccione membresía y monto válido', 'error');
            return;
        }

        $.ajax({
            url: '../php/payments.php?action=register',
            method: 'POST',
            dataType: 'json',
            data: { mm_id: mmId, amount: amount, payment_method: method, notes: notes },
            success: function (res) {
                const msgDiv = $('#mensajePago');
                if (res.status === 'success') {
                    msgDiv.html('<div class="alert alert-success">✅ ' + res.message + '</div>');
                    loadPaymentsReport();
                    $('#memberSelect').val('');
                    $('#membershipSelect').empty().prop('disabled', true);
                    $('#amount, #notes').val('');
                } else {
                    msgDiv.html('<div class="alert alert-danger">❌ ' + (res.message || 'Error') + '</div>');
                }
                setTimeout(() => msgDiv.empty(), 5000);
            },
            error: function () {
                $('#mensajePago').html('<div class="alert alert-danger">Error de conexión</div>');
            }
        });
    });

    // ==== REPORTE DE PAGOS ====
    function loadMembersForReport() {
        $.ajax({
            url: '../php/payments.php?action=get-active-members',
            method: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    $('#reportMemberFilter').empty().append('<option value="">Todos</option>');
                    res.members.forEach(m => {
                        $('#reportMemberFilter').append(`<option value="${m.member_id}">${m.full_name}</option>`);
                    });
                }
            },
            error: function () { console.error('Error cargando socios para reporte'); }
        });
    }

    // Función central para cargar el reporte
    function loadPaymentsReport() {
        const from = $('#dateFrom').val() || '2000-01-01';
        const to = $('#dateTo').val() || '2099-12-31';
        const memberId = $('#reportMemberFilter').val() || '';
        const method = $('#reportMethodFilter').val() || '';

        $.ajax({
            url: '../php/payments.php?action=get-report',
            method: 'GET',
            dataType: 'json',
            data: { from, to, member_id: memberId, method },
            success: function (res) {
                if (res.status !== 'success') return;

                if ($.fn.DataTable.isDataTable('#paymentsTable')) {
                    $('#paymentsTable').DataTable().destroy();
                }

                // Guardar datos para exportar
                window.currentPaymentsData = res.data;

                $('#paymentsTable').DataTable({
                    data: res.data,
                    columns: [
                        { className: 'dtr-control', orderable: false, data: null, defaultContent: '' },
                        { data: 'payment_id', title: 'ID' },
                        { data: 'member_name', title: 'Socio' },
                        { data: 'membership_name', title: 'Membresía' },
                        {
                            data: 'amount',
                            title: 'Monto',
                            render: (d) => '$' + parseFloat(d).toFixed(2)
                        },
                        { data: 'payment_method', title: 'Método' },
                        { data: 'paid_at', title: 'Fecha' },
                        { data: 'notes', title: 'Notas' }
                    ],
                    responsive: { details: { type: 'column', target: 'tr' } },
                    language: {
                        "sProcessing": "Procesando...",
                        "sLengthMenu": "Mostrar _MENU_ registros",
                        "sZeroRecords": "No hay pagos",
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
            error: function () { console.error('Error cargando reporte'); }
        });
    }

    // Aplicar filtros
    $('#applyFilters').click(loadPaymentsReport);

    // Exportar a CSV
    $('#exportCSV').click(function () {
        if (!window.currentPaymentsData || window.currentPaymentsData.length === 0) {
            Swal.fire('Info', 'No hay datos para exportar', 'info');
            return;
        }

        let csv = 'ID,Socio,Membresía,Monto,Método,Fecha,Notas\n';
        csv += window.currentPaymentsData.map(p =>
            `"${p.payment_id}","${p.member_name}","${p.membership_name}","${p.amount}","${p.payment_method}","${p.paid_at}","${(p.notes || '').replace(/"/g, '""')}"`
        ).join('\n');

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'reporte_pagos.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });

    // Fechas por defecto: últimos 30 días
    const today = new Date().toISOString().split('T')[0];
    const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    $('#dateFrom').val(thirtyDaysAgo);
    $('#dateTo').val(today);

    // Cargar reporte inicial
    loadPaymentsReport();
});