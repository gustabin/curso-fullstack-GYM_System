<?php // Endpoint: trainers.php
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
        getAllTrainers();
        break;
    case 'getById':
        getTrainerById();
        break;
    case 'create':
        createTrainer();
        break;
    case 'update':
        updateTrainer();
        break;
    case 'delete':
        deleteTrainer();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}

function getAllTrainers()
{
    global $conn;
    $stmt = $conn->prepare("SELECT trainer_id, first_name, last_name, email, phone, specialization, photo FROM trainers ORDER BY trainer_id DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    $trainers = [];
    while ($row = $result->fetch_assoc()) {
        $trainers[] = [
            'trainer_id' => (int)$row['trainer_id'],
            'full_name' => htmlspecialchars($row['first_name'] . ' ' . $row['last_name']),
            'first_name' => htmlspecialchars($row['first_name']),
            'last_name' => htmlspecialchars($row['last_name']),
            'email' => htmlspecialchars($row['email'] ?? ''),
            'phone' => htmlspecialchars($row['phone'] ?? ''),
            'specialization' => htmlspecialchars($row['specialization'] ?? ''),
            'photo' => $row['photo'] ?? null
        ];
    }
    echo json_encode(['status' => 'success', 'data' => $trainers]);
}

function getTrainerById()
{
    global $conn;
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        return;
    }
    $stmt = $conn->prepare("SELECT * FROM trainers WHERE trainer_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $trainer = $result->fetch_assoc();
    if ($trainer) {
        echo json_encode(['status' => 'success', 'trainer' => $trainer]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Entrenador no encontrado']);
    }
}

function createTrainer()
{
    global $conn;
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');

    if (!$firstName || !$lastName) {
        echo json_encode(['status' => 'error', 'message' => 'Nombre y apellido son obligatorios']);
        return;
    }

    $photoName = null;
    if (!empty($_FILES['photo']['name'])) {
        $photoName = uploadPhoto($_FILES['photo']);
        if (!$photoName) {
            echo json_encode(['status' => 'error', 'message' => 'Error al subir la foto']);
            return;
        }
    }

    $stmt = $conn->prepare("INSERT INTO trainers (first_name, last_name, email, phone, specialization, photo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $firstName, $lastName, $email, $phone, $specialization, $photoName);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Entrenador creado correctamente']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al crear entrenador']);
    }
}

function updateTrainer()
{
    global $conn;
    $id = (int)($_POST['id'] ?? 0);
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');

    if (!$id || !$firstName || !$lastName) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
        return;
    }

    $photoName = null;
    if (!empty($_FILES['photo']['name'])) {
        $photoName = uploadPhoto($_FILES['photo']);
        if (!$photoName) {
            echo json_encode(['status' => 'error', 'message' => 'Error al subir la foto']);
            return;
        }
    }

    if ($photoName) {
        $stmt = $conn->prepare("UPDATE trainers SET first_name = ?, last_name = ?, email = ?, phone = ?, specialization = ?, photo = ? WHERE trainer_id = ?");
        $stmt->bind_param("ssssssi", $firstName, $lastName, $email, $phone, $specialization, $photoName, $id);
    } else {
        $stmt = $conn->prepare("UPDATE trainers SET first_name = ?, last_name = ?, email = ?, phone = ?, specialization = ? WHERE trainer_id = ?");
        $stmt->bind_param("sssssi", $firstName, $lastName, $email, $phone, $specialization, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Entrenador actualizado']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar']);
    }
}

function deleteTrainer()
{
    global $conn;
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        return;
    }

    $stmt = $conn->prepare("SELECT photo FROM trainers WHERE trainer_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row && $row['photo']) {
        $photoPath = '../images/trainers/' . $row['photo'];
        if (file_exists($photoPath)) unlink($photoPath);
    }

    $del = $conn->prepare("DELETE FROM trainers WHERE trainer_id = ?");
    $del->bind_param("i", $id);
    if ($del->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Entrenador eliminado']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar']);
    }
}

function uploadPhoto($file)
{
    $uploadDir = '../images/trainers/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) return null;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxSize = 2 * 1024 * 1024;

    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > $maxSize) return null;
    if (!in_array($file['type'], $allowedTypes)) return null;

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = uniqid('trainer_', true) . '.' . strtolower($ext);
    $target = $uploadDir . $newName;

    return move_uploaded_file($file['tmp_name'], $target) ? $newName : null;
}
