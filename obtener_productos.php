<?php 
require_once "Conexionbd.php"; 
header('Content-Type: application/json');

if ($mysqli->connect_errno) {
    echo json_encode([
        "success" => false,
        "message" => "Error de conexión: " . $mysqli->connect_error
    ]);
    exit;
}

// Consulta para traer productos con categoría y marca
$query = "
    SELECT p.id, p.nombre, p.precio, p.id_categoria, c.nombre AS categoria_nombre,
           p.id_marca, m.nombre AS marca_nombre, p.cantidad, p.activo, p.imagen
    FROM productos p
    LEFT JOIN categorias c ON p.id_categoria = c.id
    LEFT JOIN marcas m ON p.id_marca = m.id
    ORDER BY p.nombre ASC
";

$result = $mysqli->query($query);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Error en la consulta: " . $mysqli->error
    ]);
    exit;
}

$productos = [];
while ($row = $result->fetch_assoc()) {
    // Procesar la imagen para incluir la ruta completa si existe
    if ($row['imagen']) {
        // Verificar si el archivo existe físicamente
        $imagePath = 'imagenes/' . $row['imagen'];
        if (file_exists($imagePath)) {
            $row['imagen_url'] = $imagePath;
            $row['tiene_imagen'] = true;
        } else {
            $row['imagen_url'] = null;
            $row['tiene_imagen'] = false;
        }
    } else {
        $row['imagen_url'] = null;
        $row['tiene_imagen'] = false;
    }
    
    // Asegurar que los campos numéricos sean del tipo correcto
    $row['precio'] = floatval($row['precio']);
    $row['cantidad'] = intval($row['cantidad']);
    $row['activo'] = intval($row['activo']);
    $row['id'] = intval($row['id']);
    $row['id_categoria'] = intval($row['id_categoria']);
    $row['id_marca'] = intval($row['id_marca']);
    
    $productos[] = $row;
}

echo json_encode([
    "success" => true,
    "productos" => $productos
]);

$mysqli->close();
?>