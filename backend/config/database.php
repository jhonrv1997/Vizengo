<?php
/**
 * VIZENGO - Sistema de Gestión de Pedidos
 * Archivo de configuración de base de datos
 * 
 * Instrucciones para Cpanel:
 * 1. Crear una base de datos MySQL desde cPanel
 * 2. Crear un usuario y asignarle privilegios a la base de datos
 * 3. Actualizar las constantes DB_NAME, DB_USER, DB_PASS con tus datos
 */

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'vizengo_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'VIZENGO');
define('APP_VERSION', '1.0.0');
define('BASE_URL', '/');

// Rutas de uploads
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('DISENOS_DIR', UPLOAD_DIR . 'disenos/');
define('INTEGRANTES_DIR', UPLOAD_DIR . 'integrantes/');

// Tamaño máximo de archivos (en bytes)
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Tipos de archivo permitidos
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Configuración de sesión
define('SESSION_TIMEOUT', 3600); // 1 hora

// Roles de usuario
define('ROLE_VENDEDOR', 'vendedor');
define('ROLE_DISENADOR', 'disenador');
define('ROLE_ADMINISTRADOR', 'administrador');

// Estados del pedido
define('STATUS_CONTRATO', 'contrato');
define('STATUS_INTEGRANTES', 'integrantes');
define('STATUS_DISENO', 'diseno');
define('STATUS_PLANCHADO', 'planchado');
define('STATUS_COSTURA', 'costura');
define('STATUS_ENTREGA', 'entrega');
define('STATUS_COMPLETADO', 'completado');

?>
