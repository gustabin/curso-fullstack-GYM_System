<?php // Endpoint: payments.php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

require_once '../config/database.php';

$action = $_GET['action'] ?? null;

switch ($action) {
    case 'get-active-members':
        getActiveMembers();
        break;
    case 'get-active-memberships':
        getActiveMemberships();
        break;
    case 'register':
        registerPayment();
        break;
    case 'get-report':
        getPaymentsReport();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}

function getActiveMembers()
{
    global $conn;
    $sql = "
        SELECT DISTINCT m.member_id, m.first_name, m.last_name, m.dni
        FROM members m
        INNER JOIN member_memberships mm ON m.member_id = mm.member_id
        WHERE mm.status = 'active' AND mm.end_date >= CURDATE()
        ORDER BY m.first_name
    ";
    $result = $conn->query($sql);
    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = [
            'member_id' => (int)$row['member_id'],
            'full_name' => htmlspecialchars($row['first_name'] . ' ' . $row['last_name']),
            'dni' => htmlspecialchars($row['dni'] ?? '')
        ];
    }
    echo json_encode(['status' => 'success', 'members' => $members]);
}

function getActiveMemberships()
{
    global $conn;
    $memberId = (int)($_GET['member_id'] ?? 0);
    if (!$memberId) {
        echo json_encode(['status' => 'error', 'message' => 'ID de socio inválido']);
        return;
    }

    $sql = "
        SELECT mm.mm_id, ms.name as membership_name, mm.end_date
        FROM member_memberships mm
        INNER JOIN memberships ms ON mm.membership_id = ms.membership_id
        WHERE mm.member_id = ? AND mm.status = 'active' AND mm.end_date >= CURDATE()
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    $result = $stmt->get_result();
    $memberships = [];
    while ($row = $result->fetch_assoc()) {
        $memberships[] = [
            'mm_id' => (int)$row['mm_id'],
            'membership_name' => htmlspecialchars($row['membership_name']),
            'end_date' => htmlspecialchars($row['end_date'])
        ];
    }
    echo json_encode(['status' => 'success', 'memberships' => $memberships]);
}

function registerPayment()
{
    global $conn;
    $mmId = (int)($_POST['mm_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $method = trim($_POST['payment_method'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!$mmId || $amount <= 0 || $method === '') {
        echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
        return;
    }

    // Verificar que la inscripción exista
    $check = $conn->prepare("SELECT mm_id FROM member_memberships WHERE mm_id = ?");
    if (!$check) {
        echo json_encode(['status' => 'error', 'message' => 'Error en la consulta de verificación']);
        return;
    }
    $check->bind_param("i", $mmId);
    $check->execute();
    $checkResult = $check->get_result();
    if (!$checkResult || $checkResult->num_rows === 0) {
        $check->close();
        echo json_encode(['status' => 'error', 'message' => 'Inscripción no válida']);
        return;
    }
    $check->close();

    // Insertar pago (tipos: i = integer, d = double, s = string, s = string)
    $stmt = $conn->prepare("INSERT INTO payments (mm_id, amount, payment_method, notes) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Error preparando inserción de pago']);
        return;
    }

    $stmt->bind_param("idss", $mmId, $amount, $method, $notes);

    if ($stmt->execute()) {
        $insertId = $stmt->insert_id;
        $stmt->close();
        echo json_encode(['status' => 'success', 'message' => 'Pago registrado correctamente.', 'payment_id' => (int)$insertId]);
    } else {
        $err = $stmt->error;
        $stmt->close();
        echo json_encode(['status' => 'error', 'message' => 'Error al registrar pago: ' . $err]);
    }
}


function getPaymentsReport()
{
    global $conn;
    $from = $_GET['from'] ?? '2000-01-01';
    $to = $_GET['to'] ?? date('Y-m-d');
    $memberId = (int)($_GET['member_id'] ?? 0);
    $method = $_GET['method'] ?? '';

    $sql = "
        SELECT p.payment_id, 
               CONCAT(m.first_name, ' ', m.last_name) as member_name,
               ms.name as membership_name,
               p.amount,
               p.payment_method,
               p.notes,
               DATE(p.paid_at) as paid_at
        FROM payments p
        INNER JOIN member_memberships mm ON p.mm_id = mm.mm_id
        INNER JOIN members m ON mm.member_id = m.member_id
        INNER JOIN memberships ms ON mm.membership_id = ms.membership_id
        WHERE DATE(p.paid_at) BETWEEN ? AND ?
    ";
    $params = [$from, $to];
    $types = "ss";

    if ($memberId > 0) {
        $sql .= " AND m.member_id = ?";
        $params[] = $memberId;
        $types .= "i";
    }
    if ($method) {
        $sql .= " AND p.payment_method = ?";
        $params[] = $method;
        $types .= "s";
    }

    $sql .= " ORDER BY p.payment_id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'payment_id' => (int)$row['payment_id'],
            'member_name' => htmlspecialchars($row['member_name']),
            'membership_name' => htmlspecialchars($row['membership_name']),
            'amount' => floatval($row['amount']),
            'payment_method' => htmlspecialchars($row['payment_method']),
            'notes' => htmlspecialchars($row['notes'] ?? ''),
            'paid_at' => htmlspecialchars($row['paid_at'])
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $data]);
}
