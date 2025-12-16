<?php // Endpoint: auth.php
header('Content-Type: application/json');
session_start();
// Cargar librería PHPMailer (asegúrate de tener estos archivos)
require '../vendor/PHPMailer/Exception.php';
require '../vendor/PHPMailer/PHPMailer.php';
require '../vendor/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once '../config/database.php';

$action = $_GET['action'] ?? null;

switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'request-reset':
        handlePasswordResetRequest();
        break;
    default:
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
        exit;
}

function handleLogin()
{
    global $conn;
    $input = json_decode(file_get_contents('php://input'), true);
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (!$username || !$password) {
        echo json_encode(['status' => 'error', 'message' => 'Usuario y contraseña requeridos']);
        return;
    }

    $stmt = $conn->prepare("SELECT user_id, username, password_hash, role, is_active FROM users WHERE (username = ? OR email = ?) AND is_active = 1");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['status' => 'error', 'message' => 'Credenciales inválidas']);
        return;
    }

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    session_regenerate_id(true); // Prevención de fijación de sesión

    // Redirección según rol
    $redirect = match ($user['role']) {
        'admin' => '../dashboard/index.html',
        'trainer' => '../attendances/index.html',
        default => '../members/index.html'
    };

    echo json_encode(['status' => 'success', 'redirect' => $redirect]);
}

function handleLogout()
{
    session_destroy();
    echo json_encode(['status' => 'success']);
}

function handlePasswordResetRequest()
{
    global $conn;
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Email inválido']);
        return;
    }

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND is_active = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result->num_rows) {
        // No revelar si el email existe o no (seguridad)
        echo json_encode(['status' => 'success', 'message' => 'Si el email está registrado, recibirás un enlace de recuperación.']);
        return;
    }

    // Generar token seguro
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Guardar token en BD
    $user_id = $result->fetch_assoc()['user_id'];
    $del = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $del->bind_param("i", $user_id);
    $del->execute();

    $ins = $conn->prepare("INSERT INTO password_resets (token, user_id, expires_at) VALUES (?, ?, ?)");
    $ins->bind_param("sis", $token, $user_id, $expires);
    $ins->execute();

    // ⚠️ En producción, usa PHPMailer para enviar el email
    // Configuración de PHPMailer
    // Modo de depuración (true para desarrollo, false para producción)
    define('DEBUG', true);
    define('SMTP_AUTH', true);

    // ✉️ ENVIAR EMAIL CON PHPMAILER
    try {
        // Crear una nueva instancia de PHPMailer
        $mail = new PHPMailer(DEBUG);

        // Configuración del servidor
        $mail->isSMTP();                                      // Usar SMTP
        $mail->Host       = SMTP_HOST;                        // Servidor SMTP
        $mail->SMTPAuth   = SMTP_AUTH;                        // Habilitar autenticación SMTP
        $mail->Username   = SMTP_USER;                        // Usuario SMTP
        $mail->Password   = SMTP_PASS;                        // Contraseña SMTP
        $mail->SMTPSecure = SMTP_SECURE;                      // Habilitar encriptación TLS o SSL
        $mail->Port       = SMTP_PORT;                        // Puerto TCP
        $mail->CharSet    = 'UTF-8';                          // Codificación de caracteres

        // Si estamos en modo debug, habilitar el debug de SMTP
        if (DEBUG) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;            // Nivel de depuración
            $mail->Debugoutput = 'error_log';                 // Salida de depuración
        }

        // Remitentes y destinatarios
        $mail->setFrom('soporte@stackcodelab.com', 'Soporte Stackcodelab');
        $mail->addAddress('tabindev@gmail.com', 'Equipo de soporte'); // reemplazar 'Nombre Real'
        $mail->addReplyTo('tabindev@gmail.com', 'Tabindev in Gmail');                   // Agregar remitente como dirección de respuesta

        // Contenido del mensaje
        $mail->isHTML(true);                                  // Formato HTML
        $mail->Subject = 'Recuperación de contraseña';
        $mail->Body    = "
            <h3>¿Olvidaste tu contraseña?</h3>
            <p>Haz clic en el enlace para restablecerla:</p>
            <a href='https://stackcodelab.com/gym_system/php/reset-password.php?token=$token' 
               style='padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px;'>
               Restablecer contraseña
            </a>
            <p>El enlace expira en 1 hora.</p>
        ";

        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'Se ha enviado un enlace de recuperación a tu correo.']);
    } catch (Exception $e) {
        error_log("Error al enviar email: " . $mail->ErrorInfo);
        // error_log("Token de recuperación para $email: $token (válido 1 hora)");
        echo json_encode(['status' => 'success', 'message' => 'Si el email está registrado, recibirás un enlace de recuperación.']);
        // ⚠️ No revelamos errores de email por seguridad
    }
}
