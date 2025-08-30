<?php
header('Content-Type: application/json; charset=utf-8');
include 'Conexionbd.php';

try {
    // Verificar conexión
    if ($mysqli->connect_errno) {
        throw new Exception('Error de conexión: ' . $mysqli->connect_error);
    }

    // Obtener datos del POST (JSON)
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['id'])) {
        throw new Exception('ID de producto no proporcionado');
    }
    
    $id = intval($data['id']);
    
    // Obtener información del producto antes de eliminarlo (para eliminar imagen)
    $selectQuery = "SELECT imagen FROM productos WHERE id = ?";
    $selectStmt = $mysqli->prepare($selectQuery);
    
    if (!$selectStmt) {
        throw new Exception('Error preparando consulta de selección: ' . $mysqli->error);
    }
    
    $selectStmt->bind_param("i", $id);
    $selectStmt->execute();
    $result = $selectStmt->get_result();
    $product = $result->fetch_assoc();
    $selectStmt->close();
    
    if (!$product) {
        throw new Exception('Producto no encontrado');
    }
    
    // Eliminar producto de la base de datos
    $deleteQuery = "DELETE FROM productos WHERE id = ?";
    $deleteStmt = $mysqli->prepare($deleteQuery);
    
    if (!$deleteStmt) {
        throw new Exception('Error preparando consulta de eliminación: ' . $mysqli->error);
    }
    
    $deleteStmt->bind_param("i", $id);
    
    if (!$deleteStmt->execute()) {
        throw new Exception('Error ejecutando consulta: ' . $deleteStmt->error);
    }
    
    $deleteStmt->close();
    
    // Eliminar imagen del servidor si existe
    if ($product['imagen'] && file_exists('imagenes/' . $product['imagen'])) {
        unlink('imagenes/' . $product['imagen']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Producto eliminado correctamente'
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$mysqli->close();
?>