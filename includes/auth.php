<?php
/**
 * VIZENGO - Sistema de Gestión de Pedidos
 * Funciones de autenticación y sesión
 */

require_once __DIR__ . '/database.php';

function iniciarSesion($usuario, $password) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, nombre_usuario, nombre_completo, password_hash, rol FROM usuarios WHERE nombre_usuario = ? AND activo = 1");
        $stmt->execute([$usuario]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Actualizar última sesión
            $updateStmt = $db->prepare("UPDATE usuarios SET ultima_sesion = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'nombre_usuario' => $user['nombre_usuario'],
                    'nombre_completo' => $user['nombre_completo'],
                    'rol' => $user['rol']
                ]
            ];
        }
        
        return ['success' => false, 'error' => 'Usuario o contraseña incorrectos'];
    } catch (PDOException $e) {
        error_log("Error en inicio de sesión: " . $e->getMessage());
        return ['success' => false, 'error' => 'Error en el servidor'];
    }
}

function verificarSesion() {
    session_start();
    
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_rol'])) {
        return false;
    }
    
    // Verificar timeout de sesión
    if (isset($_SESSION['ultimo_acceso']) && (time() - $_SESSION['ultimo_acceso']) > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    
    $_SESSION['ultimo_acceso'] = time();
    return true;
}

function obtenerUsuarioActual() {
    if (!verificarSesion()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['usuario_id'],
        'nombre_usuario' => $_SESSION['nombre_usuario'],
        'nombre_completo' => $_SESSION['nombre_completo'],
        'rol' => $_SESSION['usuario_rol']
    ];
}

function cerrarSesion() {
    session_start();
    session_destroy();
}

function tienePermiso($rolesPermitidos) {
    $usuario = obtenerUsuarioActual();
    if (!$usuario) {
        return false;
    }
    
    if (!is_array($rolesPermitidos)) {
        $rolesPermitidos = [$rolesPermitidos];
    }
    
    return in_array($usuario['rol'], $rolesPermitidos);
}

function redirigirSegunRol() {
    $usuario = obtenerUsuarioActual();
    if (!$usuario) {
        header('Location: ../index.html');
        exit;
    }
    
    // Las redirecciones específicas se manejan en cada página
    return $usuario;
}

function generarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verificarTokenCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
