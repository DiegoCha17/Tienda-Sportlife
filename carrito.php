<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
ob_start();                      // evita que warnings/HTML rompan el JSON
ini_set('display_errors', '0');  // no imprimir notices en la salida JSON
error_reporting(E_ALL & ~E_NOTICE);

require_once __DIR__ . '/Conexionbd.php';

if (!isset($mysqli) || $mysqli->connect_errno) {
  http_response_code(500);
  echo json_encode(['success'=>false,'error'=>'DB_CONNECTION']); exit;
}
$mysqli->set_charset('utf8mb4');

// --- Helpers ---
function json_out($arr, int $code = 200) {
  http_response_code($code);
  ob_clean();
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

function totales(mysqli $db, int $uid): array {
  $sql = "SELECT c.cantidad, p.precio
          FROM carritos c
          JOIN productos p ON p.id = c.id_producto
          WHERE c.id_usuario = ? AND c.estado = 'activo'";
  $st = $db->prepare($sql);
  $st->bind_param('i', $uid);
  $st->execute();
  $rs = $st->get_result();
  $subtotal = 0.0;
  while ($r = $rs->fetch_assoc()) {
    $subtotal += (float)$r['precio'] * (int)$r['cantidad'];
  }
  $st->close();
  $impuestos = round($subtotal * 0.13, 2); 
  $envio     = 0.00;                       
  $total     = round($subtotal + $impuestos + $envio, 2);
  return [
    'subtotal' => round($subtotal, 2),
    'impuestos'=> $impuestos,
    'envio'    => $envio,
    'total'    => $total
  ];
}


function urlImg(string $nombre): string {
  if ($nombre === '') return '';
  $base = 'imagenes';
  $clean = trim($nombre, "\" \t\r\n");
  $clean = str_replace('\\', '/', $clean);
  $clean = ltrim($clean, '/');
  if (stripos($clean, 'imagenes/') === 0) {
    $clean = substr($clean, 9);
  }
  $segments = array_map('rawurlencode', array_filter(explode('/', $clean)));
  return $base . '/' . implode('/', $segments);
}

// --- Autenticación ---
$idUsuario = (int)($_SESSION['id_usuario'] ?? $_SESSION['id'] ?? 0);
if ($idUsuario <= 0) {
  json_out(['success'=>false,'error'=>'UNAUTHENTICATED'], 401);
}


$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
  switch ($action) {

    case 'list': {
      $sql = "SELECT c.id_producto, c.cantidad,
                     p.nombre, p.precio, p.imagen, p.cantidad AS stock
              FROM carritos c
              JOIN productos p ON p.id = c.id_producto
              WHERE c.id_usuario = ? AND c.estado = 'activo'
              ORDER BY c.id DESC";
      $st = $mysqli->prepare($sql);
      $st->bind_param('i', $idUsuario);
      $st->execute();
      $rs = $st->get_result();
      $items = [];
      while ($r = $rs->fetch_assoc()) {
        $items[] = [
          'id'       => (int)$r['id_producto'],
          'name'     => (string)$r['nombre'],
          'price'    => (float)$r['precio'],
          'image'    => urlImg((string)$r['imagen']),
          'quantity' => (int)$r['cantidad'],
          'stock'    => (int)$r['stock'],
          'total'    => round(((float)$r['precio']) * (int)$r['cantidad'], 2),
        ];
      }
      $st->close();
      json_out(['success'=>true, 'items'=>$items, 'totales'=>totales($mysqli,$idUsuario)]);
    }

    case 'add_item': {
      $idp  = (int)($_POST['id_producto'] ?? 0);
      $cant = max(1, (int)($_POST['cantidad'] ?? 1));
      if ($idp <= 0) json_out(['success'=>false,'error'=>'PRODUCTO_INVALIDO'], 400);

      // (Opcional) Validar stock máximo
      $st = $mysqli->prepare("SELECT cantidad FROM productos WHERE id=?");
      $st->bind_param('i', $idp);
      $st->execute();
      $st->bind_result($stock);
      $st->fetch(); $st->close();
      $stock = (int)$stock;

      // cantidad actual en carrito
      $st = $mysqli->prepare("SELECT cantidad FROM carritos WHERE id_usuario=? AND estado='activo' AND id_producto=?");
      $st->bind_param('ii', $idUsuario, $idp);
      $st->execute();
      $st->bind_result($enCarrito);
      $st->fetch(); $st->close();
      $enCarrito = (int)$enCarrito;

      if ($stock > 0 && ($enCarrito + $cant) > $stock) {
        json_out(['success'=>false,'error'=>'STOCK_INSUFICIENTE','max'=>$stock], 409);
      }

      // Upsert
      $sql = "INSERT INTO carritos (id_usuario, id_producto, estado, cantidad)
              VALUES (?, ?, 'activo', ?)
              ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)";
      $st = $mysqli->prepare($sql);
      $st->bind_param('iii', $idUsuario, $idp, $cant);
      $ok = $st->execute(); $st->close();

      json_out(['success'=>$ok, 'totales'=>totales($mysqli,$idUsuario)]);
    }

    case 'update_qty': {
      $idp  = (int)($_POST['id_producto'] ?? 0);
      $cant = (int)($_POST['cantidad'] ?? 0);
      if ($idp <= 0 || $cant < 0) json_out(['success'=>false,'error'=>'DATOS_INVALIDOS'], 400);

      if ($cant === 0) {
        $sql = "DELETE FROM carritos WHERE id_usuario=? AND id_producto=? AND estado='activo'";
        $st  = $mysqli->prepare($sql);
        $st->bind_param('ii', $idUsuario, $idp);
      } else {
        // (Opcional) validar stock
        $stc = $mysqli->prepare("SELECT cantidad FROM productos WHERE id=?");
        $stc->bind_param('i', $idp);
        $stc->execute();
        $stc->bind_result($stock);
        $stc->fetch(); $stc->close();
        if ((int)$stock > 0 && $cant > (int)$stock) {
          json_out(['success'=>false,'error'=>'STOCK_INSUFICIENTE','max'=>(int)$stock], 409);
        }

        $sql = "UPDATE carritos
                SET cantidad = ?
                WHERE id_usuario = ? AND id_producto = ? AND estado = 'activo'";
        $st  = $mysqli->prepare($sql);
        $st->bind_param('iii', $cant, $idUsuario, $idp);
      }

      $ok = $st->execute(); $st->close();
      json_out(['success'=>$ok, 'totales'=>totales($mysqli,$idUsuario)]);
    }

    case 'remove_item': {
      $idp = (int)($_POST['id_producto'] ?? 0);
      if ($idp <= 0) json_out(['success'=>false,'error'=>'PRODUCTO_INVALIDO'], 400);

      $sql = "DELETE FROM carritos WHERE id_usuario=? AND id_producto=? AND estado='activo'";
      $st  = $mysqli->prepare($sql);
      $st->bind_param('ii', $idUsuario, $idp);
      $ok  = $st->execute(); $st->close();

      json_out(['success'=>$ok, 'totales'=>totales($mysqli,$idUsuario)]);
    }

    case 'clear': {
      $sql = "DELETE FROM carritos WHERE id_usuario=? AND estado='activo'";
      $st  = $mysqli->prepare($sql);
      $st->bind_param('i', $idUsuario);
      $ok  = $st->execute(); $st->close();

      json_out(['success'=>$ok, 'totales'=>totales($mysqli,$idUsuario)]);
    }

    default:
      json_out(['success'=>false,'error'=>'ACCION_NO_SOPORTADA'], 400);
  }

} catch (Throwable $e) {
  json_out(['success'=>false,'error'=>'SERVER_ERROR','detail'=>$e->getMessage()], 500);
}
