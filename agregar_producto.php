<?php
header('Content-Type: application/json; charset=utf-8');
include 'Conexionbd.php';

try {
    // Verificar conexión
    if ($mysqli->connect_errno) {
        throw new Exception('Error de conexión: ' . $mysqli->connect_error);
    }

    // Validar datos requeridos
    if (!isset($_POST['nombre']) || !isset($_POST['precio']) || !isset($_POST['id_categoria']) || 
        !isset($_POST['id_marca']) || !isset($_POST['cantidad'])) {
        throw new Exception('Faltan datos requeridos');
    }

    $nombre = trim($_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $id_categoria = intval($_POST['id_categoria']);
    $id_marca = intval($_POST['id_marca']);
    $cantidad = intval($_POST['cantidad']);
    $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 1;
    
    // Manejo de imagen
    $imagen = null;
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'imagenes/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $uploadPath)) {
                $imagen = $fileName; // Solo guardar el nombre del archivo, no la ruta completa
            }
        } else {
            throw new Exception('Formato de imagen no permitido. Use JPG, PNG o WEBP.');
        }
    }
    
    // Preparar consulta con MySQLi
    $query = "INSERT INTO productos (nombre, precio, id_categoria, id_marca, cantidad, imagen, activo) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . $mysqli->error);
    }
    
    
    $stmt->bind_param("sdiiisi", $nombre, $precio, $id_categoria, $id_marca, $cantidad, $imagen, $activo);
    
    if (!$stmt->execute()) {
        throw new Exception('Error ejecutando consulta: ' . $stmt->error);
    }
    
    $insertId = $mysqli->insert_id;
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Producto agregado correctamente',
        'id' => $insertId
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$mysqli->close();
?>