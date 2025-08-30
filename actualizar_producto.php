<?php
header('Content-Type: application/json; charset=utf-8');
include 'Conexionbd.php';

try {
    // Verificar conexión
    if ($mysqli->connect_errno) {
        throw new Exception('Error de conexión: ' . $mysqli->connect_error);
    }

    // Validar datos requeridos
    if (!isset($_POST['id']) || !isset($_POST['nombre']) || !isset($_POST['precio']) || 
        !isset($_POST['id_categoria']) || !isset($_POST['id_marca']) || !isset($_POST['cantidad'])) {
        throw new Exception('Faltan datos requeridos');
    }

    $id = intval($_POST['id']);
    $nombre = trim($_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $id_categoria = intval($_POST['id_categoria']);
    $id_marca = intval($_POST['id_marca']);
    $cantidad = intval($_POST['cantidad']);
    $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 1;
    
    // Obtener imagen actual
    $currentImageQuery = "SELECT imagen FROM productos WHERE id = ?";
    $currentStmt = $mysqli->prepare($currentImageQuery);
    $currentStmt->bind_param("i", $id);
    $currentStmt->execute();
    $result = $currentStmt->get_result();
    $currentProduct = $result->fetch_assoc();
    $currentStmt->close();
    
    if (!$currentProduct) {
        throw new Exception('Producto no encontrado');
    }
    
    $imagen = $currentProduct['imagen']; // Mantener imagen actual por defecto
    
    // Manejo de nueva imagen
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
                // Eliminar imagen anterior si existe
                if ($imagen && file_exists($uploadDir . $imagen)) {
                    unlink($uploadDir . $imagen);
                }
                $imagen = $fileName; // Solo guardar el nombre del archivo
            }
        } else {
            throw new Exception('Formato de imagen no permitido. Use JPG, PNG o WEBP.');
        }
    }
    
    // Preparar consulta de actualización
    $query = "UPDATE productos SET nombre = ?, precio = ?, id_categoria = ?, id_marca = ?, 
              cantidad = ?, imagen = ?, activo = ? WHERE id = ?";
    
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . $mysqli->error);
    }
    
    // Bind parameters: s=string, d=double, i=integer
    $stmt->bind_param("sdiiisii", $nombre, $precio, $id_categoria, $id_marca, $cantidad, $imagen, $activo, $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error ejecutando consulta: ' . $stmt->error);
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Producto actualizado correctamente'
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$mysqli->close();
?>