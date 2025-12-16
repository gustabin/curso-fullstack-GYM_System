<?php // Endpoint: members.php
header('Content-Type: application/json');
session_start();

// Proteger acceso
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

require_once '../config/database.php';

$action = $_GET['action'] ?? null;

switch ($action) {
    case 'getAll':
        getAllMembers();
        break;
    case 'getById':
        getMemberById();
        break;
    case 'create':
        createMember();
        break;
    case 'update':
        updateMember();
        break;
    case 'delete':
        deleteMember();
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        break;
}

function getAllMembers()
{
    global $conn;
    $stmt = $conn->prepare("SELECT member_id, first_name, last_name, dni, email, phone, photo FROM members ORDER BY member_id DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = [
            'member_id' => (int)$row['member_id'],
            'full_name' => htmlspecialchars($row['first_name'] . ' ' . $row['last_name']),
            'first_name' => htmlspecialchars($row['first_name']),
            'last_name' => htmlspecialchars($row['last_name']),
            'dni' => htmlspecialchars($row['dni'] ?? ''),
            'email' => htmlspecialchars($row['email'] ?? ''),
            'phone' => htmlspecialchars($row['phone'] ?? ''),
            'photo' => $row['photo'] ?? null
        ];
    }
    echo json_encode(['status' => 'success', 'data' => $members]);
}

function getMemberById()
{
    global $conn;
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        return;
    }
    $stmt = $conn->prepare("SELECT * FROM members WHERE member_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();
    if ($member) {
        echo json_encode(['status' => 'success', 'member' => $member]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Socio no encontrado']);
    }
}

function createMember()
{
    global $conn;
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

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

    $stmt = $conn->prepare("INSERT INTO members (first_name, last_name, dni, email, phone, photo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $firstName, $lastName, $dni, $email, $phone, $photoName);
    try {
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Socio creado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al crear socio']);
        }
    } catch (mysqli_sql_exception $e) {
        // Verificar si el error es por DNI duplicado
        if ($e->getCode() === 1062) { // 1062 = Duplicate entry
            echo json_encode(['status' => 'error', 'message' => 'El DNI ya está registrado.']);
        } else {
            // Otro error de base de datos
            error_log("Error DB en createMember: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error al crear socio. Intente nuevamente.']);
        }
    }
}

function updateMember()
{
    global $conn;
    $id = (int)($_POST['id'] ?? 0);
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $dni = trim($_POST['dni'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$id || !$firstName || !$lastName) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos']);
        return;
    }

    // Si se sube una nueva foto, reemplazar
    $photoName = null;
    if (!empty($_FILES['photo']['name'])) {
        $photoName = uploadPhoto($_FILES['photo']);
        if (!$photoName) {
            echo json_encode(['status' => 'error', 'message' => 'Error al subir la foto']);
            return;
        }
    }

    if ($photoName) {
        // Actualizar con nueva foto
        $stmt = $conn->prepare("UPDATE members SET first_name = ?, last_name = ?, dni = ?, email = ?, phone = ?, photo = ? WHERE member_id = ?");
        $stmt->bind_param("ssssssi", $firstName, $lastName, $dni, $email, $phone, $photoName, $id);
    } else {
        // Sin cambiar foto
        $stmt = $conn->prepare("UPDATE members SET first_name = ?, last_name = ?, dni = ?, email = ?, phone = ? WHERE member_id = ?");
        $stmt->bind_param("sssssi", $firstName, $lastName, $dni, $email, $phone, $id);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Socio actualizado correctamente']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar']);
    }
}

function deleteMember()
{
    global $conn;
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        return;
    }

    // ✅ Verificar si el socio existe
    $check = $conn->prepare("SELECT member_id FROM members WHERE member_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Socio no encontrado.']);
        return;
    }

    // Opcional: eliminar foto del disco
    $stmt = $conn->prepare("SELECT photo FROM members WHERE member_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row && $row['photo']) {
        $photoPath = '../images/members/' . $row['photo'];
        if (file_exists($photoPath)) unlink($photoPath);
    }

    // Eliminar el registro
    $del = $conn->prepare("DELETE FROM members WHERE member_id = ?");
    $del->bind_param("i", $id);
    if ($del->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Socio eliminado']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar']);
    }
}

function uploadPhoto($file)
{
    $uploadDir = '../images/members/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return null;
        }
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > $maxSize) return null;
    if (!in_array($file['type'], $allowedTypes)) return null;

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = uniqid('member_', true) . '.' . strtolower($ext);
    $target = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $newName;
    }
    return null;
}
