<?php
session_start();
require_once '../config/database.php';

// Mensajes de estado
$message = '';
$messageType = ''; // 'success' o 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_repeat = $_POST['password_repeat'] ?? '';

    if (!$token || !$password || !$password_repeat) {
        $message = "Todos los campos son obligatorios.";
        $messageType = "error";
    } elseif ($password !== $password_repeat) {
        $message = "Las contraseñas no coinciden.";
        $messageType = "error";
    } elseif (strlen($password) < 6) {
        $message = "La contraseña debe tener al menos 6 caracteres.";
        $messageType = "error";
    } else {
        // Validar token y obtener user_id
        $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $reset = $result->fetch_assoc();

        if (!$reset) {
            $message = "Token inválido.";
            $messageType = "error";
        } elseif (strtotime($reset['expires_at']) < time()) {
            $message = "El enlace ha expirado. Solicita uno nuevo.";
            $messageType = "error";
        } else {
            // Actualizar contraseña
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $update->bind_param("si", $hashedPassword, $reset['user_id']);

            if ($update->execute()) {
                // Eliminar token usado
                $del = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                $del->bind_param("s", $token);
                $del->execute();

                $message = "¡Contraseña actualizada con éxito! Ya puedes iniciar sesión.";
                $messageType = "success";
            } else {
                $message = "Error al actualizar la contraseña. Inténtalo de nuevo.";
                $messageType = "error";
            }
        }
    }
} else {
    // Método GET: mostrar formulario con token
    $token = $_GET['token'] ?? '';
    if (!$token) {
        header("Location: ../auth/index.html");
        exit;
    }
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Restablecer Contraseña - GYM Management</title>
    <link rel="icon" href="images/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="d-flex align-items-center min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card p-4">
                    <div class="text-center mb-4">
                        <img src="../images/logotipo.png" alt="Logo" width="60">
                        <h3 class="mt-2">🔑 Restablecer Contraseña</h3>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    <?php endif; ?>

                    <?php if ($_SERVER['REQUEST_METHOD'] === 'GET' && $token): ?>
                        <form method="POST">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            <div class="form-group">
                                <label>Nueva Contraseña</label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label>Repetir Contraseña</label>
                                <input type="password" name="password_repeat" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-key"></i> Actualizar Contraseña
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center">
                            <a href="../auth/index.html" class="btn btn-outline-primary">
                                ← Volver al login
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>

</html>