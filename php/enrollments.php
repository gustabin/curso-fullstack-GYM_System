<?php
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
    case 'get-members':
        getMembers();
        break;
    case 'get-memberships':
        getMemberships();
        break;
    case 'assign':
        assignMembership();
        break;
    case 'get-all':
        getAllEnrollments();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}

function getMembers()
{
    global $conn;
    $sql = "SELECT member_id, first_name, last_name, dni FROM members ORDER BY first_name";
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

function getMemberships()
{
    global $conn;
    $sql = "SELECT membership_id, name, duration_days, price FROM memberships ORDER BY name";
    $result = $conn->query($sql);
    $memberships = [];
    while ($row = $result->fetch_assoc()) {
        $memberships[] = [
            'membership_id' => (int)$row['membership_id'],
            'name' => htmlspecialchars($row['name']),
            'duration_days' => (int)$row['duration_days'],
            'price' => floatval($row['price'])
        ];
    }
    echo json_encode(['status' => 'success', 'memberships' => $memberships]);
}

function assignMembership()
{
    global $conn;
    $memberId = (int)($_POST['member_id'] ?? 0);
    $membershipId = (int)($_POST['membership_id'] ?? 0);

    if (!$memberId || !$membershipId) {
        echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
        return;
    }

    // Verificar que la membresía exista y obtener duración
    $stmt = $conn->prepare("SELECT duration_days FROM memberships WHERE membership_id = ?");
    $stmt->bind_param("i", $membershipId);
    $stmt->execute();
    $result = $stmt->get_result();
    $memb = $result->fetch_assoc();

    if (!$memb) {
        echo json_encode(['status' => 'error', 'message' => 'Membresía no válida']);
        return;
    }

    // Verificar si ya tiene una membresía activa
    $check = $conn->prepare("
        SELECT mm_id FROM member_memberships 
        WHERE member_id = ? AND status = 'active' AND end_date >= CURDATE()
    ");
    $check->bind_param("i", $memberId);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'El socio ya tiene una membresía activa.']);
        return;
    }

    // Calcular fechas
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime("+$memb[duration_days] days"));

    // Insertar
    $insert = $conn->prepare("
        INSERT INTO member_memberships (member_id, membership_id, start_date, end_date, status)
        VALUES (?, ?, ?, ?, 'active')
    ");
    $insert->bind_param("iiss", $memberId, $membershipId, $startDate, $endDate);

    if ($insert->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Membresía asignada correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al asignar membresía.']);
    }
}

function getAllEnrollments()
{
    global $conn;
    $sql = "
        SELECT mm.mm_id, 
               CONCAT(m.first_name, ' ', m.last_name) as member_name,
               ms.name as membership_name,
               mm.start_date,
               mm.end_date,
               mm.status
        FROM member_memberships mm
        INNER JOIN members m ON mm.member_id = m.member_id
        INNER JOIN memberships ms ON mm.membership_id = ms.membership_id
        ORDER BY mm.mm_id DESC
    ";
    $result = $conn->query($sql);
    $enrollments = [];
    while ($row = $result->fetch_assoc()) {
        $enrollments[] = [
            'mm_id' => (int)$row['mm_id'],
            'member_name' => htmlspecialchars($row['member_name']),
            'membership_name' => htmlspecialchars($row['membership_name']),
            'start_date' => htmlspecialchars($row['start_date']),
            'end_date' => htmlspecialchars($row['end_date']),
            'status' => htmlspecialchars($row['status'])
        ];
    }
    echo json_encode(['status' => 'success', 'data' => $enrollments]);
}
