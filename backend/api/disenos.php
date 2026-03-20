<?php
/**
 * VIZENGO - Sistema de Gestión de Pedidos
 * API para gestión de diseños
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
        listarDisenos($db, $_GET['pedido_id'] ?? null);
        break;
        
    case 'subir':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        subirDiseno($db, $usuario);
        break;
        
    case 'eliminar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        eliminarDiseno($db);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}

function listarDisenos($db, $pedidoId = null) {
    try {
        $sql = "SELECT d.*, p.codigo_pedido, p.cliente_nombre, u.nombre_completo as disenador_nombre 
                FROM disenos d 
                INNER JOIN pedidos p ON d.pedido_id = p.id 
                LEFT JOIN usuarios u ON d.disenador_id = u.id";
        
        $params = [];
        if ($pedidoId) {
            $sql .= " WHERE d.pedido_id = ?";
            $params[] = $pedidoId;
        }
        
        $sql .= " ORDER BY d.fecha_subida DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $disenos = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'disenos' => $disenos,
            'total' => count($disenos)
        ]);
    } catch (PDOException $e) {
        error_log("Error al listar diseños: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
    }
}

function subirDiseno($db, $usuario) {
    try {
        // Validar permisos
        if ($usuario['rol'] !== 'administrador' && $usuario['rol'] !== 'disenador') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tiene permisos para subir diseños']);
            return;
        }
        
        $pedidoId = $_POST['pedido_id'] ?? null;
        $observaciones = $_POST['observaciones'] ?? '';
        
        if (empty($pedidoId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de pedido requerido']);
            return;
        }
        
        // Manejar archivo subido
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Archivo no válido']);
            return;
        }
        
        $archivo = $_FILES['archivo'];
        
        // Validar tipo de archivo
        $tiposPermitidos = ALLOWED_IMAGE_TYPES;
        if (!in_array($archivo['type'], $tiposPermitidos)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido']);
            return;
        }
        
        // Validar tamaño
        if ($archivo['size'] > MAX_FILE_SIZE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Archivo demasiado grande']);
            return;
        }
        
        // Generar nombre único
        $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombreArchivo = 'diseno_' . $pedidoId . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $rutaGuardado = DISENOS_DIR . $nombreArchivo;
        
        // Mover archivo
        if (!move_uploaded_file($archivo['tmp_name'], $rutaGuardado)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al guardar el archivo']);
            return;
        }
        
        // Guardar en base de datos
        $sql = "INSERT INTO disenos (pedido_id, disenador_id, archivo_ruta, archivo_nombre, archivo_tipo, archivo_tamaño, observaciones) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $pedidoId,
            $usuario['id'],
            'assets/uploads/disenos/' . $nombreArchivo,
            $archivo['name'],
            $archivo['type'],
            $archivo['size'],
            $observaciones
        ]);
        
        $disenoId = $db->lastInsertId();
        
        // Actualizar estado del pedido a 'diseno'
        actualizarEstadoPedido($db, $pedidoId, 'diseno', $usuario['id']);
        
        echo json_encode([
            'success' => true,
            'mensaje' => 'Diseño subido exitosamente',
            'diseno_id' => $disenoId,
            'archivo_ruta' => 'assets/uploads/disenos/' . $nombreArchivo
        ]);
    } catch (PDOException $e) {
        error_log("Error al subir diseño: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
    }
}

function eliminarDiseno($db) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID requerido']);
            return;
        }
        
        // Obtener ruta del archivo
        $stmt = $db->prepare("SELECT archivo_ruta FROM disenos WHERE id = ?");
        $stmt->execute([$data['id']]);
        $diseno = $stmt->fetch();
        
        if ($diseno && $diseno['archivo_ruta']) {
            $rutaCompleta = __DIR__ . '/../' . $diseno['archivo_ruta'];
            if (file_exists($rutaCompleta)) {
                unlink($rutaCompleta);
            }
        }
        
        $stmt = $db->prepare("DELETE FROM disenos WHERE id = ?");
        $stmt->execute([$data['id']]);
        
        echo json_encode(['success' => true, 'mensaje' => 'Diseño eliminado']);
    } catch (PDOException $e) {
        error_log("Error al eliminar diseño: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
    }
}

function actualizarEstadoPedido($db, $pedidoId, $estado, $usuarioId) {
    try {
        // Obtener estado actual
        $stmt = $db->prepare("SELECT estado FROM pedidos WHERE id = ?");
        $stmt->execute([$pedidoId]);
        $pedido = $stmt->fetch();
        
        if (!$pedido) return;
        
        $estadoAnterior = $pedido['estado'];
        
        // Actualizar estado
        $stmt = $db->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $pedidoId]);
        
        // Registrar en seguimiento
        $stmt = $db->prepare("INSERT INTO seguimiento (pedido_id, estado_anterior, estado_nuevo, usuario_id, observaciones) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$pedidoId, $estadoAnterior, $estado, $usuarioId, 'Diseño subido']);
    } catch (PDOException $e) {
        error_log("Error al actualizar estado: " . $e->getMessage());
    }
}

?>
