# crear_gym_system.ps1
$root = "gym_system"

# Carpetas principales
$modulos = @("auth", "members", "memberships", "attendances", "trainers", "payments", "dashboard")
$config = "config"
$images = "images"
$php_dir = "php"

# Crear raíz
New-Item -ItemType Directory -Path $root -Force | Out-Null

# Crear módulos
foreach ($modulo in $modulos) {
    $path = Join-Path $root $modulo
    New-Item -ItemType Directory -Path $path -Force | Out-Null
    Set-Content -Path "$path/index.html" -Value "<!-- $modulo index -->"
    Set-Content -Path "$path/$modulo.js" -Value "// $modulo.js"
}

# Crear /config
New-Item -ItemType Directory -Path "$root/$config" -Force | Out-Null
Set-Content -Path "$root/$config/database.php" -Value "<?php // Conexión a base de datos"
Set-Content -Path "$root/$config/schema.sql" -Value "-- Script SQL del GYM"

# Crear /images
New-Item -ItemType Directory -Path "$root/$images" -Force | Out-Null
Set-Content -Path "$root/$images/barra.gif" -Value ""
Set-Content -Path "$root/$images/favicon.png" -Value ""
Set-Content -Path "$root/$images/logotipo.png" -Value ""

# Crear /php
New-Item -ItemType Directory -Path "$root/$php_dir" -Force | Out-Null
$php_files = @("auth.php", "members.php", "memberships.php", "attendances.php", "trainers.php", "payments.php", "dashboard.php")
foreach ($file in $php_files) {
    Set-Content -Path "$root/$php_dir/$file" -Value "<?php // Endpoint: $file"
}

# README.md
Set-Content -Path "$root/README.md" -Value "# Sistema de Gestión para GYM`n`nCurso Full-Stack StackCodeLab"

Write-Host "✅ Estructura creada en: $root" -ForegroundColor Green