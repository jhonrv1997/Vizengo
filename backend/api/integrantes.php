<?php
/**
 * VIZENGO - Sistema de Gestión de Pedidos
 * API para gestión de integrantes
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar sesión
if (!verificarSesion()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$usuario = obtenerUsuarioActual();
$db = Database::getInstance()->getConnection();
$accion = $_GET['accion'] ?? '';

switch ($accion) {
    case 'listar':
        listarIntegrantes($db, $_GET['pedido_id'] ?? 0);
        break;
        
    case 'agregar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        agregarIntegrante($db, $usuario);
        break;
        
    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        eliminarIntegrante($db, $_POST);
        break;
        
    case 'actualizar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        actualizarIntegrante($db, $usuario);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}

function listarIntegrantes($db, $pedidoId) {
    try {
        if (empty($pedidoId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de pedido requerido']);
            return;
        }
        
        $stmt = $db->prepare("SELECT * FROM integrantes WHERE pedido_id = ? ORDER BY nombre_completo");
        $stmt->execute([$pedidoId]);
        $integrantes = $stmt->fetchAll();
        
        // Obtener resumen de tallas
        $stmt = $db->prepare("SELECT talla, COUNT(*) as cantidad FROM integrantes WHERE pedido_id = ? GROUP BY talla ORDER BY FIELD(talla, 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL')");
        $stmt->execute([$pedidoId]);
        $resumenTallas = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'integrantes' => $integrantes,
            'resumen_tallas' => $resumenTallas,
            'total' => count($integrantes)
        ]);
    } catch (PDOException $e) {
        error_log("Error al listar integrantes: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
    }
}

function agregarIntegrante($db, $usuario) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['pedido_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de pedido requerido']);
            return;
        }
        
        // Manejar imagen si existe
        $imagenRuta = null;
        if (!empty($data['imagen_data'])) {
            // Guardar imagen (data URL a archivo)
            $imagenRuta = guardarImagen($data['imagen_data'], $data['pedido_id']);
        }
        
        $sql = "INSERT INTO integrantes (pedido_id, nombre_completo, talla, tipo_cuello, numero_camisa, imagen_ruta, observaciones) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['pedido_id'],
            $data['nombre_completo'],
            $data['talla'],
            $data['tipo_cuello'] ?? 'Cuello Redondo',
            $data['numero_camisa'] ?? null,
            $imagenRuta,
            $data['observaciones'] ?? ''
        ]);
        
        echo json_encode([
            'success' => true,
            'mensaje' => 'Integrante agregado exitosamente',
            'id' => $db->lastInsertId()
        ]);
    } catch (PDOException $e) {
        error_log("Error al agregar integrante: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
    }
}

function eliminarIntegrante($db, $data) {
    try {
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID requerido']);
            return;
        }
        
        // Obtener ruta de imagen para eliminarla
        $stmt = $db->prepare("SELECT imagen_ruta FROM integrantes WHERE id = ?");
        $stmt->execute([$data['id']]);
        $integrante = $stmt->fetch();
        
        if ($integrante && $integrante['imagen_ruta']) {
            $rutaCompleta = __DIR__ . '/../' . $integrante['imagen_ruta'];
            if (file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM integrantes WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        echo json_encode(['success' => true, 'mensaje' => 'Integrante eliminado']);
    } catch (PDOException $e) {
        error_log("Error al eliminar integrante: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
    }
}

function actualizarIntegrante($db, $usuario) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID requerido']);
            return;
        }
        
        $sql = "UPDATE integrantes SET 
                nombre_completo = ?, talla = ?, tipo_cuello = ?, numero_camisa = ?, observaciones = ?
                WHERE id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['nombre_completo'],
            $data['talla'],
            $data['tipo_cuello'],
            $data['numero_camisa'],
            $data['observaciones'],
            $data['id']
        ]);
        
        echo json_encode(['success' => true, 'mensaje' => 'Integrante actualizado']);
    } catch (PDOException $e) {
        error_log("Error al actualizar integrante: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
    }
}

function guardarImagen($imageData, $pedidoId) {
    try {
        // Eliminar prefixo data:image/...;base64,
        $parts = explode(',', $imageData);
        if (count($parts) < 2) {
            return null;
        }
        
        $imageData = $parts[1];
        $imageBinary = base64_decode($imageData);
        
        // Generar nombre único
        $nombreArchivo = 'int_' . $pedidoId . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.png';
        $rutaGuardado = INTEGRANTES_DIR . $nombreArchivo;
        
        // Guardar archivo
        if (file_put_contents($rutaGuardado, $imageBinary)) {
            return 'assets/uploads/integrantes/' . $nombreArchivo;
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error al guardar imagen: " . $e->getMessage());
        return null;
    }
}

?>
