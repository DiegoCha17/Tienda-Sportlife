<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!isset($_SESSION['id']) && !isset($_SESSION['id_usuario'])) {
  echo json_encode(['error' => 'auth']); exit;
}
require_once 'Conexionbd.php';

$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* Verificar que el pedido pertenezca al usuario */
$sql = "SELECT p.*, d.ciudad, d.provincia, d.pais, d.codigo_postal,
        pg.estado AS estado_pago, pg.metodo AS metodo_pago, pg.numero_transaccion
        FROM pedidos p
        LEFT JOIN direcciones d ON d.id = p.id_direccion
        LEFT JOIN (
          SELECT pp.*
          FROM pagos pp
          JOIN (
            SELECT id_pedido, MAX(fecha_creacion) mx
            FROM pagos GROUP BY id_pedido
          ) ult ON ult.id_pedido = pp.id_pedido AND ult.mx = pp.fecha_creacion
        ) pg ON pg.id_pedido = p.id
        WHERE p.id = ? AND p.id_usuario = ? LIMIT 1";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ii', $id, $id_usuario);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) { echo json_encode(['error' => 'not_found']); exit; }

/* Ítems del pedido (ajusta el nombre de tabla/columnas si difiere) */
$items = [];
if ($res = $mysqli->query("SHOW TABLES LIKE 'detalle_pedido'")) {
  if ($res->num_rows > 0) {
    $q = $mysqli->prepare("SELECT nombre, precio, cantidad, imagen FROM detalle_pedido WHERE id_pedido = ?");
    $q->bind_param('i', $id);
    $q->execute();
    $items = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    $q->close();
  }
}

$out = [
  'numero_seguimiento' => $pedido['numero_seguimiento'],
  'fecha'              => date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])),
  'estado_pedido'      => $pedido['estado'],
  'total'              => (float)$pedido['total_general'],
  'direccion'          => trim(($pedido['ciudad'] ?? '').', '.($pedido['provincia'] ?? '').', '.($pedido['pais'] ?? '').' · CP '.$pedido['codigo_postal']),
  'estado_pago'        => $pedido['estado_pago'] ?? null,
  'metodo_pago'        => $pedido['metodo_pago'] ?? null,
  'numero_transaccion' => $pedido['numero_transaccion'] ?? null,
  'items'              => $items,
];

echo json_encode($out);
