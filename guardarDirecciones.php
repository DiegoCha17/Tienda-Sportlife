<?php
header('Content-Type: application/json');
session_start();


// Incluir conexión a la base de datos
include 'Conexionbd.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id']) && !isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no autenticado'
    ]);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}


// Obtener ID del usuario
$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['id'];

// Obtener y validar datos del formulario
$ciudad = trim($_POST['ciudad'] ?? '');
$provincia = trim($_POST['provincia'] ?? '');
$pais = trim($_POST['pais'] ?? '');
$codigo_postal = trim($_POST['codigo_postal'] ?? '');

// Validaciones básicas
$errors = [];

if (empty($ciudad)) {
    $errors[] = 'La ciudad es requerida';
}

if (empty($provincia)) {
    $errors[] = 'La provincia es requerida';
}

if (empty($pais)) {
    $errors[] = 'El país es requerido';
}

if (empty($codigo_postal)) {
    $errors[] = 'El código postal es requerido';
} elseif (!preg_match('/^\d{5}$/', $codigo_postal)) {
    $errors[] = 'El código postal debe tener 5 dígitos';
}

// Si hay errores de validación
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Errores de validación',
        'errors' => $errors
    ]);
    exit;
}

try {
    // Verificar si la dirección ya existe para el usuario
    $stmt_check = $mysqli->prepare("SELECT id FROM direcciones WHERE id_usuario = ? AND ciudad = ? AND provincia = ? AND pais = ? AND codigo_postal = ?");
    $stmt_check->bind_param("issss", $id_usuario, $ciudad, $provincia, $pais, $codigo_postal);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        $stmt_check->close();
        echo json_encode([
            'success' => false,
            'message' => 'Esta dirección ya está registrada'
        ]);
        exit;
    }
    $stmt_check->close();
    
    // Insertar nueva dirección
    $stmt_insert = $mysqli->prepare("INSERT INTO direcciones (id_usuario, ciudad, provincia, pais, codigo_postal) VALUES (?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("issss", $id_usuario, $ciudad, $provincia, $pais, $codigo_postal);
    
    if ($stmt_insert->execute()) {
        $direccion_id = $mysqli->insert_id;
        $stmt_insert->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Dirección guardada exitosamente',
            'direccion_id' => $direccion_id,
            'data' => [
                'id' => $direccion_id,
                'ciudad' => $ciudad,
                'provincia' => $provincia,
                'pais' => $pais,
                'codigo_postal' => $codigo_postal
            ]
        ]);
    } else {
        $stmt_insert->close();
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar la dirección en la base de datos'
        ]);
    }
    
} catch (Exception $e) {
    // Log del error (opcional)
    error_log("Error al guardar dirección: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}

// Cerrar conexión
if (isset($mysqli)) {
    $mysqli->close();
}
?>