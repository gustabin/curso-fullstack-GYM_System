<?php // Endpoint: dashboard.php
header('Content-Type: application/json');
session_start();

// Autorización
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// Cargar configuración de BD
require_once __DIR__ . '/../config/database.php';

// Aceptar varias posibles variables de conexión establecidas en database.php
if (!isset($conn) || $conn === null) {
    if (isset($mysqli)) $conn = $mysqli;
    elseif (isset($db)) $conn = $db;
    elseif (isset($conexion)) $conn = $conexion;
}

// Si no hay conexión, devolver error JSON (evita fatal)
if (!isset($conn) || $conn === null) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection not initialized']);
    exit;
}

$action = $_GET['action'] ?? null;
switch ($action) {
    case 'check-session':
        echo json_encode(['status' => 'active']);
        break;
    case 'get-metrics':
        getMetrics();
        break;
    case 'get-recent-payments':
        getRecentPayments();
        break;
    case 'get-attendance-trend':
        getAttendanceTrend();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no encontrada']);
        break;
}

function getMetrics()
{
    global $conn;

    // 1. Socios activos
    $stmt1 = $conn->prepare("
        SELECT COUNT(DISTINCT member_id) as count 
        FROM member_memberships 
        WHERE status = 'active' AND end_date >= CURDATE()
    ");
    $stmt1->execute();
    $activeMembers = $stmt1->get_result()->fetch_assoc()['count'] ?? 0;

    // 2. Asistencias hoy
    $stmt2 = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM attendances 
        WHERE date = CURDATE()
    ");
    $stmt2->execute();
    $todayAttendance = $stmt2->get_result()->fetch_assoc()['count'] ?? 0;

    // 3. Membresías por vencer (en los próximos 3 días)
    $stmt3 = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM member_memberships 
        WHERE status = 'active' 
          AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
    ");
    $stmt3->execute();
    $expiringMemberships = $stmt3->get_result()->fetch_assoc()['count'] ?? 0;

    // 4. Últimos pagos (en los últimos 7 días)
    $stmt4 = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM payments 
        WHERE paid_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt4->execute();
    $recentPayments = $stmt4->get_result()->fetch_assoc()['count'] ?? 0;

    echo json_encode([
        'status' => 'success',
        'metrics' => [
            'active_members' => (int)$activeMembers,
            'today_attendance' => (int)$todayAttendance,
            'expiring_memberships' => (int)$expiringMemberships,
            'recent_payments_count' => (int)$recentPayments
        ]
    ]);
}

function getRecentPayments()
{
    global $conn;
    // Obtener últimos 5 pagos con datos del socio y membresía
    $sql = "
        SELECT p.payment_id, 
               CONCAT(m.first_name, ' ', m.last_name) as member_name,
               p.amount,
               DATE(p.paid_at) as paid_at
        FROM payments p
        INNER JOIN member_memberships mm ON p.mm_id = mm.mm_id
        INNER JOIN members m ON mm.member_id = m.member_id
        ORDER BY p.payment_id DESC
        LIMIT 5
    ";
    $result = $conn->query($sql);
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = [
            'payment_id' => (int)$row['payment_id'],
            'member_name' => htmlspecialchars($row['member_name']),
            'amount' => floatval($row['amount']),
            'paid_at' => htmlspecialchars($row['paid_at'])
        ];
    }
    echo json_encode([
        'status' => 'success',
        'payments' => $payments
    ]);
}

function getAttendanceTrend()
{
    global $conn;
    // Últimos 7 días, incluyendo hoy
    $sql = "
        SELECT DATE(date) as day, COUNT(*) as count
        FROM attendances
        WHERE date >= CURDATE() - INTERVAL 6 DAY
          AND date <= CURDATE()
        GROUP BY day
        ORDER BY day ASC
    ";
    $result = $conn->query($sql);

    // Generar array con todos los días (incluso con 0 asistencias)
    $labels = [];
    $data = [];
    $current = new DateTime('now - 6 days');
    $end = new DateTime('now');

    // Crear mapa de datos reales
    $map = [];
    while ($row = $result->fetch_assoc()) {
        $map[$row['day']] = (int)$row['count'];
    }

    // Llenar todos los días
    while ($current <= $end) {
        $dayStr = $current->format('Y-m-d');
        $labels[] = $current->format('M j'); // Ej: "Abr 5"
        $data[] = $map[$dayStr] ?? 0;
        $current->modify('+1 day');
    }

    echo json_encode([
        'status' => 'success',
        'labels' => $labels,
        'data' => $data
    ]);
}
