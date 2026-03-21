<?php
/**
 * VIZENGO - Sistema de Gestión de Pedidos
 * API para gestión de pedidos
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
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
        listarPedidos($db, $usuario);
        break;
        
    case 'obtener':
        obtenerPedido($db, $_GET['id'] ?? 0);
        break;
        
    case 'crear':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        crearPedido($db, $usuario);
        break;
        
    case 'actualizar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        actualizarPedido($db, $usuario);
        break;
        
    case 'actualizar_estado':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }
        actualizarEstado($db, $usuario);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}

function listarPedidos($db, $usuario) {
    try {
        $estado = $_GET['estado'] ?? '';
        $filtro = $_GET['filtro'] ?? '';
        
        $sql = "SELECT p.*, u.nombre_completo as vendedor_nombre 
                FROM pedidos p 
                LEFT JOIN usuarios u ON p.vendedor_id = u.id 
                WHERE 1=1";
        $params = [];
        
        // Filtrar por rol
        if ($usuario['rol'] === 'vendedor') {
            $sql .= " AND p.vendedor_id = ?";
            $params[] = $usuario['id'];
        }
        
        if (!empty($estado)) {
            $sql .= " AND p.estado = ?";
            $params[] = $estado;
        }
        
        if (!empty($filtro)) {
            $sql .= " AND (p.codigo_pedido LIKE ? OR p.cliente_nombre LIKE ?)";
            $busqueda = "%$filtro%";
            $params[] = $busqueda;
            $params[] = $busqueda;
        }
        
        $sql .= " ORDER BY p.fecha_registro DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $pedidos = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'pedidos' => $pedidos,
            'total' => count($pedidos)
        ]);
    } catch (PDOException $e) {
        error_log("Error al listar pedidos: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
    }
}

function obtenerPedido($db, $id) {
    try {
        if (empty($id)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de pedido requerido']);
            return;
        }
        
        $stmt = $db->prepare("SELECT p.*, u.nombre_completo as vendedor_nombre 
                              FROM pedidos p 
                              LEFT JOIN usuarios u ON p.vendedor_id = u.id 
                              WHERE p.id = ?");
        $stmt->execute([$id]);
        $pedido = $stmt->fetch();
        
        if (!$pedido) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Pedido no encontrado']);
            return;
        }
        
        // Obtener integrantes
        $stmt = $db->prepare("SELECT * FROM integrantes WHERE pedido_id = ?");
        $stmt->execute([$id]);
        $pedido['integrantes'] = $stmt->fetchAll();
        
        // Obtener diseño
        $stmt = $db->prepare("SELECT d.*, u.nombre_completo as disenador_nombre 
                              FROM disenos d 
                              LEFT JOIN usuarios u ON d.disenador_id = u.id 
                              WHERE d.pedido_id = ?");
        $stmt->execute([$id]);
        $pedido['diseno'] = $stmt->fetch();
        
        // Obtener planchado
        $stmt = $db->prepare("SELECT pl.*, u.nombre_completo as disenador_nombre 
                              FROM planchados pl 
                              LEFT JOIN usuarios u ON pl.disenador_id = u.id 
                              WHERE pl.pedido_id = ?");
        $stmt->execute([$id]);
        $pedido['planchado'] = $stmt->fetch();
        
        // Obtener costura
        $stmt = $db->prepare("SELECT c.*, u.nombre_completo as disenador_nombre 
                              FROM costuras c 
                              LEFT JOIN usuarios u ON c.disenador_id = u.id 
                              WHERE c.pedido_id = ?");
        $stmt->execute([$id]);
        $pedido['costura'] = $stmt->fetch();
        
        // Obtener entrega
        $stmt = $db->prepare("SELECT e.*, u.nombre_completo as vendedor_nombre 
                              FROM entregas e 
                              LEFT JOIN usuarios u ON e.vendedor_id = u.id 
                              WHERE e.pedido_id = ?");
        $stmt->execute([$id]);
        $pedido['entrega'] = $stmt->fetch();
        
        echo json_encode(['success' => true, 'pedido' => $pedido]);
    } catch (PDOException $e) {
        error_log("Error al obtener pedido: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
    }
}

function crearPedido($db, $usuario) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar permisos
        if ($usuario['rol'] !== 'administrador' && $usuario['rol'] !== 'vendedor') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'No tiene permisos para crear pedidos']);
            return;
        }
        
        // Generar código de pedido
        $stmt = $db->query("SELECT MAX(CAST(SUBSTRING(codigo_pedido, 5) AS UNSIGNED)) as max_id FROM pedidos WHERE codigo_pedido LIKE 'PED-%'");
        $maxId = $stmt->fetch()['max_id'] ?? 0;
        $codigoPedido = 'PED-' . str_pad($maxId + 1, 3, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO pedidos (codigo_pedido, cliente_nombre, cliente_contacto, cliente_telefono, cliente_email, 
                                     tipo_contrato, lugar_entrega, direccion_envio, vendedor_nombre,
                                     camiseta_tipo, camiseta_tela, camiseta_talla_principal,
                                     short_tipo, short_tela, short_talla,
                                     medias_tipo, medias_detalles,
                                     banderolas_merch_articulo, banderolas_merch_regalo, banderolas_merch_especificaciones,
                                     observacion_general, observaciones_diseno,
                                     hora_entrega, fecha_entrega,
                                     cantidad_total, precio_unitario, anticipo, saldo_pendiente, 
                                     estado, vendedor_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $saldoPendiente = ($data['cantidad_total'] * $data['precio_unitario']) - ($data['anticipo'] ?? 0);
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $codigoPedido,
            $data['cliente_nombre'] ?? '',
            $data['cliente_nombre'] ?? '',
            $data['cliente_telefono'] ?? '',
            $data['cliente_email'] ?? '',
            $data['tipo_contrato'] ?? 'PEDIDO',
            $data['lugar_entrega'] ?? '',
            $data['direccion_envio'] ?? '',
            $data['vendedor_nombre'] ?? '',
            $data['camiseta_tipo'] ?? '',
            $data['camiseta_tela'] ?? '',
            $data['camiseta_talla_principal'] ?? '',
            $data['short_tipo'] ?? '',
            $data['short_tela'] ?? '',
            $data['short_talla'] ?? '',
            $data['medias_tipo'] ?? '',
            $data['medias_detalles'] ?? '',
            $data['banderolas_merch_articulo'] ?? '',
            $data['banderolas_merch_regalo'] ?? false,
            $data['banderolas_merch_especificaciones'] ?? '',
            $data['observacion_general'] ?? '',
            $data['observaciones_diseno'] ?? '',
            $data['hora_entrega'] ?? null,
            $data['fecha_entrega'] ?? null,
            $data['cantidad_total'] ?? 0,
            $data['precio_unitario'] ?? 0,
            $data['anticipo'] ?? 0,
            $saldoPendiente,
            'contrato',
            $usuario['id']
        ]);
        
        $pedidoId = $db->lastInsertId();
        
        // Registrar en seguimiento
        registrarSeguimiento($db, $pedidoId, null, 'contrato', $usuario['id'], 'Pedido creado');
        
        echo json_encode([
            'success' => true,
            'mensaje' => 'Pedido creado exitosamente',
            'pedido_id' => $pedidoId,
            'codigo_pedido' => $codigoPedido
        ]);
    } catch (PDOException $e) {
        error_log("Error al crear pedido: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
    }
}

function actualizarPedido($db, $usuario) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de pedido requerido']);
            return;
        }
        
        $sql = "UPDATE pedidos SET 
                cliente_nombre = ?, cliente_contacto = ?, cliente_telefono = ?, cliente_email = ?,
                tipo_prenda = ?, cantidad_total = ?, precio_unitario = ?, anticipo = ?, 
                saldo_pendiente = ?, fecha_entrega = ?, observaciones = ?
                WHERE id = ?";
        
        $saldoPendiente = ($data['cantidad_total'] * $data['precio_unitario']) - ($data['anticipo'] ?? 0);
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['cliente_nombre'],
            $data['cliente_contacto'],
            $data['cliente_telefono'],
            $data['cliente_email'],
            $data['tipo_prenda'],
            $data['cantidad_total'],
            $data['precio_unitario'],
            $data['anticipo'],
            $saldoPendiente,
            $data['fecha_entrega'],
            $data['observaciones'],
            $data['id']
        ]);
        
        echo json_encode(['success' => true, 'mensaje' => 'Pedido actualizado exitosamente']);
    } catch (PDOException $e) {
        error_log("Error al actualizar pedido: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
    }
}

function actualizarEstado($db, $usuario) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id']) || empty($data['estado'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID y estado requeridos']);
            return;
        }
        
        // Obtener estado actual
        $stmt = $db->prepare("SELECT estado FROM pedidos WHERE id = ?");
        $stmt->execute([$data['id']]);
        $pedido = $stmt->fetch();
        
        if (!$pedido) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Pedido no encontrado']);
            return;
        }
        
        $estadoAnterior = $pedido['estado'];
        $estadoNuevo = $data['estado'];
        
        // Actualizar estado
        $stmt = $db->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->execute([$estadoNuevo, $data['id']]);
        
        // Registrar en seguimiento
        registrarSeguimiento($db, $data['id'], $estadoAnterior, $estadoNuevo, $usuario['id'], $data['observaciones'] ?? '');
        
        echo json_encode(['success' => true, 'mensaje' => 'Estado actualizado exitosamente']);
    } catch (PDOException $e) {
        error_log("Error al actualizar estado: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
    }
}

function registrarSeguimiento($db, $pedidoId, $estadoAnterior, $estadoNuevo, $usuarioId, $observaciones = '') {
    try {
        $stmt = $db->prepare("INSERT INTO seguimiento (pedido_id, estado_anterior, estado_nuevo, usuario_id, observaciones) 
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$pedidoId, $estadoAnterior, $estadoNuevo, $usuarioId, $observaciones]);
    } catch (PDOException $e) {
        error_log("Error al registrar seguimiento: " . $e->getMessage());
    }
}

?>
