<?php // Endpoint: attendances.php
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
    case 'register':
        registerAttendance();
        break;
    case 'get-all':
        getAllAttendances();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}

function getActiveMembers()
{
    global $conn;
    // Obtener socios con membresía activa hoy
    $sql = "
        SELECT m.member_id, m.first_name, m.last_name, m.dni
        FROM members m
        INNER JOIN member_memberships mm ON m.member_id = mm.member_id
        WHERE mm.status = 'active' AND mm.end_date >= CURDATE()
        ORDER BY m.first_name, m.last_name
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



function registerAttendance()
{
    global $conn;
    $memberId = (int)($_POST['member_id'] ?? 0);
    if (!$memberId) {
        echo json_encode(['status' => 'error', 'message' => 'ID de socio inválido']);
        return;
    }

    // Verificar que el socio tenga membresía activa HOY
    $check = $conn->prepare("
        SELECT COUNT(*) as active
        FROM member_memberships
        WHERE member_id = ? AND status = 'active' AND end_date >= CURDATE()
    ");
    $check->bind_param("i", $memberId);
    $check->execute();
    $result = $check->get_result();
    $row = $result->fetch_assoc();

    if ($row['active'] == 0) {
        echo json_encode(['status' => 'error', 'message' => 'El socio no tiene una membresía activa hoy.']);
        return;
    }

    // Verificar si ya registró asistencia HOY
    $stmt = $conn->prepare("SELECT attendance_id FROM attendances WHERE member_id = ? AND date = CURDATE()");
    $stmt->bind_param("i", $memberId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'El socio ya registró su asistencia hoy.']);
        return;
    }

    // Registrar asistencia
    $insert = $conn->prepare("INSERT INTO attendances (member_id, date, checked_in_at) VALUES (?, CURDATE(), CURTIME())");
    $insert->bind_param("i", $memberId);
    if ($insert->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Asistencia registrada correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al registrar asistencia.']);
    }
}

function getAllAttendances()
{
    global $conn;
    $sql = "
        SELECT a.attendance_id, 
               CONCAT(m.first_name, ' ', m.last_name) as member_name,
               a.date,
               a.checked_in_at
        FROM attendances a
        INNER JOIN members m ON a.member_id = m.member_id
        ORDER BY a.attendance_id DESC
        LIMIT 100
    ";
    $result = $conn->query($sql);
    $attendances = [];
    while ($row = $result->fetch_assoc()) {
        $attendances[] = [
            'attendance_id' => (int)$row['attendance_id'],
            'member_name' => htmlspecialchars($row['member_name']),
            'date' => htmlspecialchars($row['date']),
            'checked_in_at' => htmlspecialchars($row['checked_in_at'])
        ];
    }
    echo json_encode(['status' => 'success', 'data' => $attendances]);
}
