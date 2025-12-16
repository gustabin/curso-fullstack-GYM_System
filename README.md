# 🏋️ Sistema de Gestión para GYM – Curso Full-Stack

**Curso práctico de desarrollo web con HTML, CSS, JavaScript, PHP 8+, MySQL y REST API**  
**Duración**: 2 semanas (4 sesiones de 2 horas)  
**Nivel**: Principiantes absolutos → Intermedio  
**Instructor**: [Gustavo Arias / StackCodeLab](https://stackcodelab.com)  

---

## 🎯 ¿Qué construirás?

Un **sistema profesional de gestión para gimnasios**, 100% funcional, seguro y desplegable en la nube, con los siguientes módulos:

- ✅ **Autenticación segura** (login, logout, recuperación de contraseña)
- ✅ **Gestión de socios** (CRUD + foto opcional)
- ✅ **Membresías** (planes, duración, precio, beneficios)
- ✅ **Inscripciones** (asignar membresías a socios)
- ✅ **Control de asistencias** (registro diario con validación de membresía activa)
- ✅ **Entrenadores** (CRUD con foto y especialidad)
- ✅ **Pagos manuales** (vinculados a inscripciones, con método y notas)
- ✅ **Reportes** (filtros por fecha, socio, método + exportación a CSV)
- ✅ **Dashboard inteligente** (métricas en tiempo real + gráfico de tendencias con Chart.js)
- ✅ **Seguridad reforzada** (prepared statements, hash de contraseñas, protección contra XSS e inyección SQL)
- ✅ **Arquitectura modular y limpia** (sin clases, enfoque procedural)

---

## 🧰 Stack Tecnológico

| Capa | Tecnologías |
|------|-------------|
| **Frontend** | HTML5, CSS3 (Bootstrap 4), JavaScript (Vanilla + jQuery), AJAX, DataTables, SweetAlert, Chart.js |
| **Backend** | PHP 8+ (estilo modular, sin clases), MySQLi (prepared statements), REST API interna |
| **Base de datos** | MySQL / MariaDB |
| **Herramientas** | VSCode, XAMPP (local), Postman (pruebas de API), Git, Hostinger (deploy) |
| **Extras** | PHPMailer (emails), Subida segura de archivos, Variables de entorno (`.env`) |

---

## 📁 Estructura del Proyecto

```
/gym_system
├── /auth                → Login, logout, recuperación de contraseña
├── /members             → Gestión de socios
├── /memberships         → Tipos de membresías
├── /enrollments         → Asignación de membresías a socios
├── /attendances         → Registro de asistencias diarias
├── /trainers            → Entrenadores
├── /payments            → Registro de pagos + reporte
├── /dashboard           → Métricas y gráficos
├── /config              → Conexión a BD + schema.sql
├── /images              → Fotos de socios y entrenadores
├── /php                 → Endpoints de la API REST
├── /vendor              → PHPMailer
├── index.html           → Página de bienvenida pública
├── .env                 → Variables de entorno (NO se sube a Git)
├── .gitignore           → Ignora archivos sensibles
└── README.md            → ¡Este archivo!
```

---

## 🔒 Características de Seguridad

- **Contraseñas**: `password_hash()` y `password_verify()`
- **Consultas**: 100% con **prepared statements** (evita inyección SQL)
- **Sesiones**: validadas en cada módulo privado
- **Archivos**: subida con validación de tipo y tamaño
- **Credenciales**: **nunca en código** → se usan `.env` o `.env.php` fuera de la web
- **Mensajes de error**: genéricos en producción (sin detalles de BD)

---

## 🧪 Pruebas con Postman

Cada módulo incluye pruebas en **Postman** antes de conectar el frontend.  
Ejemplo de endpoints:

- `POST /php/auth.php?action=login` → login
- `GET /php/members.php?action=getAll` → listar socios
- `POST /php/attendances.php?action=register` → registrar asistencia

📁 **Colección de Postman incluida** en la carpeta `/docs` (al finalizar el curso).

---

## 🚀 Cómo ejecutar el sistema

### En local (XAMPP)

1. Clona o descarga este repositorio en `C:\xampp\htdocs\gym_system`
2. Importa `config/schema.sql` en phpMyAdmin
3. Crea un usuario de prueba en la tabla `users`:
   ```sql
   INSERT INTO users (username, email, password_hash, role) VALUES
   ('admin', 'admin@gym.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
   ```
   > Contraseña: `password`
4. Accede a: `http://localhost/gym_system/`

### En producción (Hostinger, etc.)

1. Sube todos los archivos (excepto `.env`)
2. Crea un archivo `.env` en la raíz con tus credenciales:
   ```env
   DB_HOST=localhost
   DB_USER=tu_usuario
   DB_PASS=tu_contraseña_segura
   DB_NAME=tu_base_de_datos
   ```
3. Asegúrate de que `/config/database.php` use `.env` (ver código).

---

## 📚 ¿Qué aprenderás?

- Fundamentos de desarrollo web (HTML, CSS, JS)
- Comunicación frontend ↔ backend con AJAX y REST API
- Diseño de bases de datos relacionales
- Validación y sanitización de datos
- Manejo de sesiones y autenticación
- Subida y manejo de archivos
- Uso de librerías profesionales (DataTables, SweetAlert, Chart.js)
- Pruebas de API con Postman
- Deploy real en hosting
- Buenas prácticas de seguridad y mantenibilidad

---

## 📂 Recursos del Curso

- 📝 **Guías de clase paso a paso** (PDF/Markdown)
- 💻 **Código fuente completo y comentado**
- 🧪 **Colección de Postman** para pruebas
- 📊 **Base de datos de ejemplo** (`schema.sql`)
- 📁 **Estructura lista para producción**

---

## 🌟 ¡Listo para tu portafolio!

Al terminar este curso, tendrás un **proyecto full-stack profesional** que podrás:

- Mostrar a empleadores o clientes
- Desplegar en tu dominio
- Extender con nuevas funcionalidades (reservas, notificaciones, facturación electrónica, etc.)

---

## 📬 Soporte y Preguntas

¿Tienes dudas?  
💬 Únete a nuestra comunidad en [StackCodeLab](https://stackcodelab.com)  
📧 o escríbenos a: soporte@stackcodelab.com

---

> 💡 **"No solo aprenderás a programar: aprenderás a resolver problemas reales con código."**  
> — StackCodeLab Academy

---

⭐ **¡Felicitaciones por iniciar tu camino como desarrollador full-stack!**
