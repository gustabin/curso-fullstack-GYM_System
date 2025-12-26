-- =============================================================
-- SEED GYM DATA — Dinámico, seguro y compatible con el dashboard
-- Fecha base: se calcula automáticamente con CURDATE()
-- Autor: Gustavo Arias / StackCodeLab.com
-- =============================================================

USE gym_management;

-- =============================================================
-- 1. LIMPIEZA SEGURA (respetando restricciones de clave foránea)
-- =============================================================
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM payments;
DELETE FROM attendances;
DELETE FROM member_memberships;
DELETE FROM members;
DELETE FROM trainers;
DELETE FROM memberships;
DELETE FROM users;

-- Reiniciar contadores
ALTER TABLE payments AUTO_INCREMENT = 1;
ALTER TABLE attendances AUTO_INCREMENT = 1;
ALTER TABLE member_memberships AUTO_INCREMENT = 1;
ALTER TABLE members AUTO_INCREMENT = 1;
ALTER TABLE trainers AUTO_INCREMENT = 1;
ALTER TABLE memberships AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- 2. USUARIOS
-- =============================================================
INSERT INTO users (username, email, password_hash, role, is_active) VALUES
('admin', 'gustabin@yahoo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1),
('recep', 'recep@gym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'reception', 1),
('trainer1', 'trainer1@gym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer', 1);

-- =============================================================
-- 3. PLANES DE MEMBRESÍA
-- =============================================================
INSERT INTO memberships (name, duration_days, price, benefits) VALUES
('Mensual Básico', 30, 45.00, 'Acceso ilimitado a máquinas'),
('Trimestral Premium', 90, 120.00, 'Acceso total + 1 sesión personalizada/mes'),
('Anual VIP', 365, 450.00, 'Acceso 24/7 + 4 sesiones personalizadas/mes + toallas');

-- =============================================================
-- 4. SOCIOS (20 registros)
-- =============================================================
INSERT INTO members (first_name, last_name, dni, phone, email, photo) VALUES
('Carlos', 'Mendoza', '12345678', '987654321', 'carlos.m@gmail.com', 'carlos.jpg'),
('Ana', 'Rojas', '87654321', '912345678', 'ana.r@gmail.com', 'ana.jpg'),
('Luis', 'García', '23456789', '923456789', 'luis.g@gmail.com', 'luis.jpg'),
('María', 'López', '34567890', '934567890', 'maria.l@gmail.com', 'maria.jpg'),
('Javier', 'Fernández', '45678901', '945678901', 'javier.f@gmail.com', 'javier.jpg'),
('Sofía', 'Martínez', '56789012', '956789012', 'sofia.m@gmail.com', 'sofia.jpg'),
('Diego', 'Hernández', '67890123', '967890123', 'diego.h@gmail.com', 'diego.jpg'),
('Valeria', 'Gómez', '78901234', '978901234', 'valeria.g@gmail.com', 'valeria.jpg'),
('Andrés', 'Sánchez', '89012345', '989012345', 'andres.s@gmail.com', 'andres.jpg'),
('Camila', 'Díaz', '90123456', '990123456', 'camila.d@gmail.com', 'camila.jpg'),
('Felipe', 'Torres', '01234567', '901234567', 'felipe.t@gmail.com', 'felipe.jpg'),
('Lucía', 'Vargas', '10293847', '910293847', 'lucia.v@gmail.com', 'lucia.jpg'),
('Tomás', 'Morales', '20394857', '920394857', 'tomas.m@gmail.com', 'tomas.jpg'),
('Isabella', 'Castro', '30495867', '930495867', 'isabella.c@gmail.com', 'isabella.jpg'),
('Sebastián', 'Ortiz', '40596877', '940596877', 'sebastian.o@gmail.com', 'sebastian.jpg'),
('Martina', 'Ramos', '50697887', '950697887', 'martina.r@gmail.com', 'martina.jpg'),
('Emilio', 'Silva', '60798897', '960798897', 'emilio.s@gmail.com', 'emilio.jpg'),
('Renata', 'Aguilar', '70899907', '970899907', 'renata.a@gmail.com', 'renata.jpg'),
('Nicolás', 'Pérez', '80900017', '980900017', 'nicolas.p@gmail.com', 'nicolas.jpg'),
('Antonella', 'Reyes', '91011127', '991011127', 'antonella.r@gmail.com', 'antonella.jpg');

-- =============================================================
-- 5. MEMBRESÍAS DE SOCIOS (20 registros)
-- =============================================================
-- 15 membresías activas (vencen en el futuro)
INSERT INTO member_memberships (member_id, membership_id, start_date, end_date, status) VALUES
(1, 1, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'active'),
(2, 2, DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'active'),
(3, 1, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'active'),
(4, 3, DATE_SUB(CURDATE(), INTERVAL 100 DAY), DATE_ADD(CURDATE(), INTERVAL 265 DAY), 'active'),
(5, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 28 DAY), 'active'),
(6, 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 89 DAY), 'active'),
(7, 1, DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'active'),
(8, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 27 DAY), 'active'),
(9, 3, DATE_SUB(CURDATE(), INTERVAL 200 DAY), DATE_ADD(CURDATE(), INTERVAL 165 DAY), 'active'),
(10, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'active'),
(11, 2, DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'active'),
(12, 1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_ADD(CURDATE(), INTERVAL 26 DAY), 'active'),
(13, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 29 DAY), 'active'),
(14, 2, DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 83 DAY), 'active'),
(15, 1, DATE_SUB(CURDATE(), INTERVAL 11 DAY), DATE_ADD(CURDATE(), INTERVAL 19 DAY), 'active');

-- 3 membresías por vencer en los próximos 3 días
INSERT INTO member_memberships (member_id, membership_id, start_date, end_date, status) VALUES
(16, 1, DATE_SUB(CURDATE(), INTERVAL 30 DAY), CURDATE(), 'active'),                -- HOY
(17, 1, DATE_SUB(CURDATE(), INTERVAL 31 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'active'), -- mañana
(18, 1, DATE_SUB(CURDATE(), INTERVAL 32 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'active'); -- en 2 días

-- 2 membresías expiradas
INSERT INTO member_memberships (member_id, membership_id, start_date, end_date, status) VALUES
(19, 1, DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'expired'),
(20, 2, DATE_SUB(CURDATE(), INTERVAL 120 DAY), DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'expired');

-- =============================================================
-- 6. ASISTENCIAS (últimos 7 días, con énfasis en HOY)
-- =============================================================
INSERT INTO attendances (member_id, date) VALUES
-- Hoy (2025-12-26)
(1, CURDATE()), (2, CURDATE()), (3, CURDATE()), (4, CURDATE()), (5, CURDATE()),
(6, CURDATE()), (7, CURDATE()), (8, CURDATE()), (9, CURDATE()), (10, CURDATE()),
-- Ayer
(1, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(3, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(5, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(7, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(9, DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
-- Hace 2 días
(2, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(4, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(6, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(8, DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
-- Hace 3-6 días (menos frecuencia)
(1, DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(3, DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
(5, DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
(7, DATE_SUB(CURDATE(), INTERVAL 6 DAY)),
(9, DATE_SUB(CURDATE(), INTERVAL 7 DAY));

-- =============================================================
-- 7. PAGOS (últimos 7 días)
-- =============================================================
INSERT INTO payments (mm_id, amount, payment_method, notes, paid_at) VALUES
(1, 45.00, 'Efectivo', 'Pago mensual', NOW()),
(2, 120.00, 'Transferencia', 'Pago trimestral', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 45.00, 'Tarjeta', 'Pago mensual', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 450.00, 'Transferencia', 'Pago anual', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(5, 45.00, 'Efectivo', 'Pago mensual', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(6, 120.00, 'Tarjeta', 'Pago trimestral', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(16, 45.00, 'Efectivo', 'Renovación mensual', NOW()),
(17, 45.00, 'Tarjeta', 'Renovación anticipada', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(18, 45.00, 'Efectivo', 'Último pago', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- =============================================================
-- 8. ENTRENADORES
-- =============================================================
INSERT INTO trainers (first_name, last_name, email, phone, specialization, photo) VALUES
('Roberto', 'Castillo', 'roberto@gym.com', '912345678', 'Fuerza y acondicionamiento', 'roberto.jpg'),
('Elena', 'Vega', 'elena@gym.com', '923456789', 'Yoga y pilates', 'elena.jpg'),
('Miguel', 'Ríos', 'miguel@gym.com', '934567890', 'CrossFit', 'miguel.jpg');

-- =============================================================
-- ✅ ¡DATOS DE PRUEBA LISTOS!
-- Resultado esperado en el dashboard:
-- • Socios activos: 18
-- • Asistencias hoy: 10
-- • Membresías por vencer: 3
-- • Últimos pagos: 9
-- =============================================================