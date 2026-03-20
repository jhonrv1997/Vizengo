<?php
/**
 * VIZENGO - Sistema de Gestión de Pedidos
 * API para autenticación de usuarios
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/auth.php';

$accion = $_GET['accion'] ?? '';

switch ($accion) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $usuario = trim($data['usuario'] ?? '');
        $password = $data['password'] ?? '';
        
        if (empty($usuario) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Usuario y contraseña requeridos']);
            exit;
        }
        
        $resultado = iniciarSesion($usuario, $password);
        
        if ($resultado['success']) {
            session_start();
            $_SESSION['usuario_id'] = $resultado['user']['id'];
            $_SESSION['nombre_usuario'] = $resultado['user']['nombre_usuario'];
            $_SESSION['nombre_completo'] = $resultado['user']['nombre_completo'];
            $_SESSION['usuario_rol'] = $resultado['user']['rol'];
            $_SESSION['ultimo_acceso'] = time();
            
            echo json_encode([
                'success' => true,
                'user' => $resultado['user'],
                'mensaje' => 'Inicio de sesión exitoso'
            ]);
        } else {
            http_response_code(401);
            echo json_encode($resultado);
        }
        break;
        
    case 'logout':
        cerrarSesion();
        echo json_encode(['success' => true, 'mensaje' => 'Sesión cerrada correctamente']);
        break;
        
    case 'verificar':
        if (verificarSesion()) {
            echo json_encode([
                'success' => true,
                'usuario' => obtenerUsuarioActual()
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'No autenticado']);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}

?>
