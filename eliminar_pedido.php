<?php
session_start();
if (!isset($_SESSION['id']) && !isset($_SESSION['id_usuario'])) {
    header('Location: Login.php');
    exit;
}
require_once 'Conexionbd.php';

$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['id'];
$id_pedido  = (int)($_GET['id'] ?? 0);

// Solo permite eliminar pedidos pendientes del usuario
$stmt = $mysqli->prepare("DELETE FROM pedidos WHERE id = ? AND id_usuario = ? AND estado = 'pendiente'");
$stmt->bind_param('ii', $id_pedido, $id_usuario);
$stmt->execute();

header('Location: historial_pedidos.php');
exit;
