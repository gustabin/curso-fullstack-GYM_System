<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/index.html');
    exit;
}

// Redirige según el rol
$role = $_SESSION['role'];
switch ($role) {
    case 'admin':
        header('Location: dashboard/index.html');
        break;
    case 'trainer':
        header('Location: attendances/index.html');
        break;
    case 'reception':
        header('Location: members/index.html');
        break;
    default:
        header('Location: auth/index.html');
        break;
}
exit;
