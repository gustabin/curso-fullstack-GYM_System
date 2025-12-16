<?php
// Configuración del servidor de base de datos(BD) de acuerdo al entorno

// Cargar variables de entorno desde .env
function loadEnv($filePath)
{
    if (!file_exists($filePath)) {
        throw new Exception(".env file  not found at: $filePath");
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Ignorar comentarios y líneas vacías
        if (empty($line) || strpos(trim($line), '#') === 0) continue;

        // Parsear KEY=VALUE
        if (strpos($line, '=') === false) continue;

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        // Remover comillas
        $value = trim($value, '"\'');

        // Solo establecer si no existe
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

// Cargar .env
loadEnv(__DIR__ . '/../.env');

// AHORA definir constantes SMTP (después de cargar .env)
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'localhost');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_SECURE', getenv('SMTP_SECURE') ?: 'tls');
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');

// Localhost
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    $servername = getenv('DB_HOST_DEV');
    $username = getenv('DB_USER_DEV');
    $password = getenv('DB_PASSWORD_DEV');
    $dbname = getenv('DB_NAME_DEV');
}

// Produccion (gymsystem.shop)
if (($_SERVER['HTTP_HOST'] == 'gymsystem.shop') or ($_SERVER['HTTP_HOST'] == 'www.gymsystem.shop')) {
    $servername = getenv('DB_HOST_PROD');
    $username = getenv('DB_USER_PROD');
    $password = getenv('DB_PASSWORD_PROD');
    $dbname = getenv('DB_NAME_PROD');
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception('Error de conexión: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8');
} catch (Exception $e) {
    header('Content-Type: application/json');
    error_log($e->getMessage());
    echo json_encode(["error" => "No se pudo conectar a la base de datos. Por favor, intente nuevamente más tarde."]);
    exit;
}
