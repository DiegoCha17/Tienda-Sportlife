<?php
require_once "Conexionbd.php";
header('Content-Type: application/json');

try {
    // Consulta para obtener productos con categorías y marcas
    $query = "SELECT 
                p.id,
                p.nombre,
                p.precio,
                p.cantidad,
                p.imagen,
                p.id_categoria,
                p.id_marca,
                p.activo,
                c.nombre AS categoria_nombre,
                m.nombre AS marca_nombre
              FROM productos p
              LEFT JOIN categorias c ON p.id_categoria = c.id
              LEFT JOIN marcas m ON p.id_marca = m.id
              WHERE p.activo = 1
              ORDER BY p.id DESC";
    
    $result = $mysqli->query($query);
    
    if (!$result) {
        throw new Exception("Error en la consulta: " . $mysqli->error);
    }
    
    $productos = array();
    
    while ($row = $result->fetch_assoc()) {
        $productos[] = array(
            'id' => (int)$row['id'],
            'nombre' => $row['nombre'],
            'precio' => (float)$row['precio'],
            'cantidad' => (int)$row['cantidad'],
            'imagen' => $row['imagen'],
            'categoria_nombre' => $row['categoria_nombre'], 
            'categoria_id' => (int)$row['id_categoria'],    
            'id_categoria' => (int)$row['id_categoria'],    
            'marca_nombre' => $row['marca_nombre'] ?: 'Sin marca', 
            'id_marca' => (int)$row['id_marca'],
            'activo' => (int)$row['activo']
        );
    }
    
    echo json_encode(array(
        'success' => true,
        'productos' => $productos,
        'total' => count($productos),
        'message' => 'Productos cargados exitosamente'
    ));

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => $e->getMessage(),
        'productos' => array()
    ));
}

$mysqli->close();
?>