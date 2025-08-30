<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Incluir conexión
include 'Conexionbd.php';

// Verificar conexión
if ($mysqli->connect_errno) {
    echo json_encode([
        "success" => false,
        "message" => "Error de conexión: " . $mysqli->connect_error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


$mysqli->set_charset("utf8");

// Obtener el ID de categoría de los parámetros GET con múltiples métodos
$categoria_id = 0;

// Método 1: GET parameter
if (isset($_GET['categoria']) && $_GET['categoria'] !== '') {
    $categoria_id = intval($_GET['categoria']);
}


if ($categoria_id <= 0 && isset($_POST['categoria']) && $_POST['categoria'] !== '') {
    $categoria_id = intval($_POST['categoria']);
}


if ($categoria_id <= 0) {
    $input = file_get_contents('php://input');
    if ($input) {
        $data = json_decode($input, true);
        if (isset($data['categoria'])) {
            $categoria_id = intval($data['categoria']);
        }
    }
}


if ($categoria_id <= 0) {
    $categoria_id = 1;
}


$debug_info = [
    'GET_categoria' => $_GET['categoria'] ?? 'no_set',
    'POST_categoria' => $_POST['categoria'] ?? 'no_set',
    'categoria_id_final' => $categoria_id,
    'metodo' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
];

// Consulta para traer productos de la categoría específica
$query = "
    SELECT p.id, p.nombre, p.precio, p.id_categoria, c.nombre AS categoria_nombre, 
           p.id_marca, m.nombre AS marca_nombre, p.cantidad, p.activo, p.imagen
    FROM productos p
    LEFT JOIN categorias c ON p.id_categoria = c.id
    LEFT JOIN marcas m ON p.id_marca = m.id
    WHERE p.id_categoria = ? AND p.activo = 1
    ORDER BY p.nombre ASC
";

$stmt = $mysqli->prepare($query);
if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Error preparando consulta: " . $mysqli->error,
        "debug" => $debug_info
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("i", $categoria_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Error ejecutando consulta: " . $mysqli->error,
        "debug" => $debug_info
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$productos = [];
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

// Respuesta exitosa
echo json_encode([
    "success" => true,
    "productos" => $productos,
    "total" => count($productos),
    "categoria_id" => $categoria_id,
    "debug" => $debug_info
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$mysqli->close();
?>