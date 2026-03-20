<?php
/**
 * VIZENGO - Sistema de Gestión de Pedidos
 * API para gestión de planchado y costura
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
$tipo = $_GET['tipo'] ?? ''; // planchado o costura

switch ($tipo) {
    case 'planchado':
        gestionarPlanchado($db, $usuario, $accion);
        break;
        
    case 'costura':
        gestionarCostura($db, $usuario, $accion);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Tipo no válido']);
}

function gestionarPlanchado($db, $usuario, $accion) {
    switch ($accion) {
        case 'obtener':
            obtenerPlanchado($db, $_GET['pedido_id'] ?? 0);
            break;
            
        case 'guardar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                exit;
            }
            guardarPlanchado($db, $usuario);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }
}

function gestionarCostura($db, $usuario, $accion) {
    switch ($accion) {
        case 'obtener':
            obtenerCostura($db, $_GET['pedido_id'] ?? 0);
            break;
            
        case 'guardar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Método no permitido']);
                exit;
            }
            guardarCostura($db, $usuario);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
    }
}

function obtenerPlanchado($db, $pedidoId) {
    try {
        if (empty($pedidoId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de pedido requerido']);
            return;
        }
        
        $stmt = $db->prepare("SELECT pl.*, u.nombre_completo as disenador_nombre 
                              FROM planchados pl 
                              LEFT JOIN usuarios u ON pl.disenador_id = u.id 
                              WHERE pl.pedido_id = ?");
        $stmt->execute([$pedidoId]);
        $planchado = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'planchado' => $planchado
        ]);
    } catch (PDOException $e) {
        error_log("Error al obtener planchado: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
    }
}

function guardarPlanchado($db, $usuario) {
    try {
        // Validar permisos
        if ($usuario['rol'] !== 'administrador' && $usuario['rol'] !== 'disenador') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tiene permisos para registrar planchado']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['pedido_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de pedido requerido']);
            return;
        }
        
        // Verificar si ya existe registro
        $stmt = $db->prepare("SELECT id FROM planchados WHERE pedido_id = ?");
        $stmt->execute([$data['pedido_id']]);
        $existente = $stmt->fetch();
        
        if ($existente) {
            // Actualizar
            $sql = "UPDATE planchados SET 
                    tipo_planchado = ?, temperatura = ?, tiempo_presion = ?, observaciones = ?, completado = ?
                    WHERE pedido_id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['tipo_planchado'] ?? '',
                $data['temperatura'] ?? '',
                $data['tiempo_presion'] ?? '',
                $data['observaciones'] ?? '',
                $data['completado'] ?? false,
                $data['pedido_id']
            ]);
            
            $mensaje = 'Planchado actualizado';
        } else {
            // Insertar
            $sql = "INSERT INTO planchados (pedido_id, disenador_id, tipo_planchado, temperatura, tiempo_presion, observaciones, completado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['pedido_id'],
                $usuario['id'],
                $data['tipo_planchado'] ?? '',
                $data['temperatura'] ?? '',
                $data['tiempo_presion'] ?? '',
                $data['observaciones'] ?? '',
                $data['completado'] ?? false
            ]);
            
            $mensaje = 'Planchado registrado';
        }
        
        // Si está completado, actualizar estado del pedido
        if (!empty($data['completado']) && $data['completado']) {
            actualizarEstadoPedido($db, $data['pedido_id'], 'costura', $usuario['id']);
        } elseif (empty($existente)) {
            // Si es nuevo registro, actualizar estado a 'planchado'
            actualizarEstadoPedido($db, $data['pedido_id'], 'planchado', $usuario['id']);
        }
        
        echo json_encode([
            'success' => true,
            'mensaje' => $mensaje
        ]);
    } catch (PDOException $e) {
        error_log("Error al guardar planchado: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
    }
}

function obtenerCostura($db, $pedidoId) {
    try {
        if (empty($pedidoId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de pedido requerido']);
            return;
        }
        
        $stmt = $db->prepare("SELECT c.*, u.nombre_completo as disenador_nombre 
                              FROM costuras c 
                              LEFT JOIN usuarios u ON c.disenador_id = u.id 
                              WHERE c.pedido_id = ?");
        $stmt->execute([$pedidoId]);
        $costura = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'costura' => $costura
        ]);
    } catch (PDOException $e) {
        error_log("Error al obtener costura: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
    }
}

function guardarCostura($db, $usuario) {
    try {
        // Validar permisos
        if ($usuario['rol'] !== 'administrador' && $usuario['rol'] !== 'disenador') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tiene permisos para registrar costura']);
            return;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['pedido_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de pedido requerido']);
            return;
        }
        
        // Verificar si ya existe registro
        $stmt = $db->prepare("SELECT id FROM costuras WHERE pedido_id = ?");
        $stmt->execute([$data['pedido_id']]);
        $existente = $stmt->fetch();
        
        if ($existente) {
            // Actualizar
            $sql = "UPDATE costuras SET 
                    tipo_costura = ?, hilo_color = ?, maquina_usada = ?, observaciones = ?, completado = ?
                    WHERE pedido_id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['tipo_costura'] ?? '',
                $data['hilo_color'] ?? '',
                $data['maquina_usada'] ?? '',
                $data['observaciones'] ?? '',
                $data['completado'] ?? false,
                $data['pedido_id']
            ]);
            
            $mensaje = 'Costura actualizada';
        } else {
            // Insertar
            $sql = "INSERT INTO costuras (pedido_id, disenador_id, tipo_costura, hilo_color, maquina_usada, observaciones, completado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $data['pedido_id'],
                $usuario['id'],
                $data['tipo_costura'] ?? '',
                $data['hilo_color'] ?? '',
                $data['maquina_usada'] ?? '',
                $data['observaciones'] ?? '',
                $data['completado'] ?? false
            ]);
            
            $mensaje = 'Costura registrada';
        }
        
        // Si está completado, actualizar estado del pedido a 'entrega' (listo para entrega)
        if (!empty($data['completado']) && $data['completado']) {
            actualizarEstadoPedido($db, $data['pedido_id'], 'entrega', $usuario['id']);
        } elseif (empty($existente)) {
            // Si es nuevo registro, actualizar estado a 'costura'
            actualizarEstadoPedido($db, $data['pedido_id'], 'costura', $usuario['id']);
        }
        
        echo json_encode([
            'success' => true,
            'mensaje' => $mensaje
        ]);
    } catch (PDOException $e) {
        error_log("Error al guardar costura: " . $e->getMessage());
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
        $stmt->execute([$pedidoId, $estadoAnterior, $estado, $usuarioId, '']);
    } catch (PDOException $e) {
        error_log("Error al actualizar estado: " . $e->getMessage());
    }
}

?>
