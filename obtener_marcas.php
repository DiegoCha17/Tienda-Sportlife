<?php
header('Content-Type: application/json');
include 'Conexionbd.php';

if ($mysqli->connect_errno) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión: ' . $mysqli->connect_error
    ]);
    exit;
}

$query = "SELECT * FROM marcas ORDER BY nombre";
$result = $mysqli->query($query);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en la consulta: ' . $mysqli->error
    ]);
    exit;
}

$marcas = [];
while ($row = $result->fetch_assoc()) {
    $marcas[] = $row;
}

echo json_encode([
    'success' => true,
    'marcas' => $marcas
]);

$mysqli->close();
?>