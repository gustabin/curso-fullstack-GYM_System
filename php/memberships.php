<?php // Endpoint: memberships.php
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
    case 'getAll':
        getAllMemberships();
        break;
    case 'getById':
        getMembershipById();
        break;
    case 'create':
        createMembership();
        break;
    case 'update':
        updateMembership();
        break;
    case 'delete':
        deleteMembership();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}

function getAllMemberships()
{
    global $conn;
    $stmt = $conn->prepare("SELECT membership_id, name, duration_days, price, benefits FROM memberships ORDER BY membership_id DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    $memberships = [];
    while ($row = $result->fetch_assoc()) {
        $memberships[] = [
            'membership_id' => (int)$row['membership_id'],
            'name' => htmlspecialchars($row['name']),
            'duration_days' => (int)$row['duration_days'],
            'price' => floatval($row['price']),
            'benefits' => htmlspecialchars($row['benefits'] ?? '')
        ];
    }
    echo json_encode(['status' => 'success', 'data' => $memberships]);
}

function getMembershipById()
{
    global $conn;
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        return;
    }
    $stmt = $conn->prepare("SELECT * FROM memberships WHERE membership_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $membership = $result->fetch_assoc();
    if ($membership) {
        echo json_encode(['status' => 'success', 'membership' => $membership]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Membresía no encontrada']);
    }
}

function createMembership()
{
    global $conn;
    $name = trim($_POST['name'] ?? '');
    $durationDays = (int)($_POST['durationDays'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $benefits = trim($_POST['benefits'] ?? '');

    if (!$name || $durationDays <= 0 || $price < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
        return;
    }

    $stmt = $conn->prepare("INSERT INTO memberships (name, duration_days, price, benefits) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sids", $name, $durationDays, $price, $benefits);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Membresía creada correctamente']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al crear membresía']);
    }
}

function updateMembership()
{
    global $conn;
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $durationDays = (int)($_POST['durationDays'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $benefits = trim($_POST['benefits'] ?? '');

    if (!$id || !$name || $durationDays <= 0 || $price < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
        return;
    }

    $stmt = $conn->prepare("UPDATE memberships SET name = ?, duration_days = ?, price = ?, benefits = ? WHERE membership_id = ?");
    $stmt->bind_param("sidsi", $name, $durationDays, $price, $benefits, $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Membresía actualizada']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar']);
    }
}

function deleteMembership()
{
    global $conn;
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        return;
    }

    // Verificar si está en uso
    $check = $conn->prepare("SELECT COUNT(*) as count FROM member_memberships WHERE membership_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'No se puede eliminar: hay socios con esta membresía asignada.']);
        return;
    }

    $del = $conn->prepare("DELETE FROM memberships WHERE membership_id = ?");
    $del->bind_param("i", $id);
    if ($del->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Membresía eliminada']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar']);
    }
}
