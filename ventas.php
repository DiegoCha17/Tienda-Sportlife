<?php
require_once "Conexionbd.php";
session_start();

/* -------- Router API (mismo archivo) -------- */
$action = $_GET['action'] ?? '';
if ($action) {
  header('Content-Type: application/json; charset=utf-8');

  // Helpers
  $q      = trim($_REQUEST['q'] ?? '');
  $estado = trim($_REQUEST['estado'] ?? '');
  $rango  = trim($_REQUEST['rango'] ?? '');
  $page   = max(1, (int)($_REQUEST['page'] ?? 1));
  $per    = min(100, max(1, (int)($_REQUEST['per_page'] ?? 10)));

  // WHERE dinámico (para list/export)
  $where = [];
  $params = [];
  $types = '';

  if ($q !== '') {
    $where[] = "(u.nombre LIKE CONCAT('%', ?, '%') OR p.id LIKE CONCAT('%', ?, '%'))";
    $params[] = $q; $params[] = $q;
    $types .= 'ss';
  }
  if ($estado !== '') {
    $where[] = "LOWER(p.estado) = LOWER(?)";
    $params[] = $estado;
    $types .= 's';
  }
  if ($rango === '30') {
    $where[] = "p.fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
  } elseif ($rango === '90') {
    $where[] = "p.fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
  } elseif ($rango === 'year') {
    $where[] = "YEAR(p.fecha_creacion) = YEAR(CURDATE())";
  }
  $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

  // ---- Endpoints
  if ($action === 'totales') {
    $sql = "SELECT 
              COALESCE(SUM(total_general),0) AS total_ventas, 
              COALESCE(SUM(subtotal),0) AS total_subtotal, 
              COALESCE(SUM(total_impuesto),0) AS total_impuestos, 
              COALESCE(SUM(total_envio),0) AS total_envio, 
              COUNT(*) AS total_pedidos
            FROM pedidos";
    $res = $mysqli->query($sql);
    $tot = $res ? $res->fetch_assoc() : [
      'total_ventas'=>0,'total_subtotal'=>0,'total_impuestos'=>0,'total_envio'=>0,'total_pedidos'=>0
    ];
    echo json_encode(['success'=>true,'totales'=>$tot]);
    exit;
  }

  if ($action === 'list') {
    // total
    $sqlCount = "SELECT COUNT(*) AS total
                 FROM pedidos p
                 INNER JOIN usuarios u ON p.id_usuario = u.id
                 $whereSql";
    $stmt = $mysqli->prepare($sqlCount);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();

    // page
    $offset = ($page-1)*$per;
    $sql = "SELECT 
              p.id AS id_pedido,
              u.nombre AS cliente, 
              p.total_general AS subtotal,
              DATE_FORMAT(p.fecha_creacion, '%Y-%m-%d %H:%i') AS fecha_creacion,
              p.estado
            FROM pedidos p
            INNER JOIN usuarios u ON p.id_usuario = u.id
            $whereSql
            ORDER BY p.fecha_creacion DESC
            LIMIT ?, ?";
    $stmt = $mysqli->prepare($sql);
    if ($types) {
      $types2 = $types.'ii';
      $params2 = array_merge($params, [$offset, $per]);
      $stmt->bind_param($types2, ...$params2);
    } else {
      $stmt->bind_param('ii', $offset, $per);
    }
    $stmt->execute();
    $r = $stmt->get_result();
    $rows = [];
    while($x = $r->fetch_assoc()) $rows[] = $x;
    $stmt->close();

    echo json_encode(['success'=>true, 'page'=>$page, 'per_page'=>$per, 'total'=>$total, 'rows'=>$rows]);
    exit;
  }

  if ($action === 'detalle') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'ID inválido']); exit; }

    $sql = "SELECT p.id, p.total_general, p.subtotal, p.total_impuesto, p.total_envio, p.estado, 
                   DATE_FORMAT(p.fecha_creacion, '%Y-%m-%d %H:%i') AS fecha_creacion,
                   u.nombre AS cliente, u.email
            FROM pedidos p
            INNER JOIN usuarios u ON p.id_usuario = u.id
            WHERE p.id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $pedido = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Detalles (ajusta columnas si difieren)
    $items = [];
    $sql2 = "SELECT producto, cantidad, precio_unitario
             FROM pedido_detalles
             WHERE id_pedido = ?";
    if ($stmt2 = $mysqli->prepare($sql2)) {
      $stmt2->bind_param('i', $id);
      $stmt2->execute();
      $res2 = $stmt2->get_result();
      while($row = $res2->fetch_assoc()) $items[] = $row;
      $stmt2->close();
    }

    echo json_encode(['success'=>true, 'pedido'=>$pedido, 'items'=>$items]);
    exit;
  }

  if ($action === 'anular' && $_SERVER['REQUEST_METHOD']==='POST') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'ID inválido']); exit; }
    $stmt = $mysqli->prepare("UPDATE pedidos SET estado='anulado' WHERE id=?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success'=>(bool)$ok]);
    exit;
  }

  if ($action === 'export') {
    // export CSV (usa mismos filtros)
    header_remove('Content-Type');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ventas_export.csv');

    $sql = "SELECT 
              p.id AS id_pedido,
              u.nombre AS cliente, 
              p.total_general AS subtotal,
              DATE_FORMAT(p.fecha_creacion, '%Y-%m-%d %H:%i:%s') AS fecha_creacion,
              p.estado
            FROM pedidos p
            INNER JOIN usuarios u ON p.id_usuario = u.id
            $whereSql
            ORDER BY p.fecha_creacion DESC";
    $stmt = $mysqli->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Cliente','Fecha','Total','Estado']);
    while($r = $res->fetch_assoc()){
      fputcsv($out, [$r['id_pedido'],$r['cliente'],$r['fecha_creacion'],number_format((float)$r['subtotal'],2,'.',''),$r['estado']]);
    }
    fclose($out);
    exit;
  }

  echo json_encode(['success'=>false,'error'=>'Acción no válida']);
  exit;
}

/* -------- Vista HTML (no-API) -------- */
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>📑 Ventas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Tu hoja de estilos moderna -->
  <link rel="stylesheet" href="estilosVentas.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body class="ventas-page">
  <section class="ventas-hero">
    <div class="ventas-container">
      <div class="breadcrumb-mini">Panel / Ventas</div>
      <h1><i class="fa-solid fa-receipt"></i> Ventas</h1>
    </div>
  </section>

  <div class="ventas-container">
    <!-- Tarjetas de totales -->
    <div class="ventas-controls" id="cards-totales" style="grid-template-columns: repeat(5, minmax(0,1fr)); margin-bottom:1rem;">
      <!-- Se llena por JS -->
    </div>

    <!-- Controles -->
    <div class="ventas-controls">
      <div class="control-search">
        <input type="search" placeholder="Buscar por cliente o #venta..." id="q">
        <i class="fa-solid fa-magnifying-glass"></i>
      </div>
      <div class="control-select">
        <select id="estado">
          <option value="">Todos los estados</option>
          <option value="pagado">Pagado</option>
          <option value="pendiente">Pendiente</option>
        </select>
        <select id="rango">
          <option value="">Todo</option>
          <option value="30">Últimos 30 días</option>
          <option value="90">Últimos 90 días</option>
          <option value="year">Este año</option>
        </select>
      </div>
      <div class="control-actions">
        <button class="btn btn-outline" id="btn-export"><i class="fa-solid fa-file-export"></i> Exportar</button>
      </div>
    </div>

    <!-- Tabla -->
    <div class="ventas-table-wrap" id="wrap-tabla">
      <table class="ventas-table" id="tabla-ventas">
        <thead>
          <tr>
            <th>ID</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th>
          </tr>
        </thead>
        <tbody id="tbody-ventas"></tbody>
      </table>
    </div>

    <div class="ventas-empty" id="ventas-empty" style="display:none">
      <i class="fa-regular fa-folder-open"></i>
      <h3>No hay ventas que coincidan</h3>
      <p class="text-muted">Prueba cambiando los filtros o el rango de fechas.</p>
    </div>

    <div class="ventas-pagination" id="paginacion"></div>

    <div style="margin-top:1.2rem;">
      <a href="indexAdmin.php" class="btn btn-outline">⬅ Volver al Panel</a>
    </div>
  </div>

  <!-- Modal Ver Detalle -->
  <div class="modal-ventas" id="modal-ventas">
    <div class="modal-ventas__panel">
      <div class="modal-ventas__header">
        <h3><i class="fa-solid fa-receipt"></i> Detalle de venta</h3>
        <button class="btn-icon btn-icon--danger" id="btn-cerrar-modal"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div class="modal-ventas__body">
        <div id="detalle-contenido"></div>
      </div>
      <div class="modal-ventas__footer">
        <button class="btn btn-outline" id="btn-cerrar-modal-2">Cerrar</button>
      </div>
    </div>
  </div>

  <script src="ventas.js"></script>
</body>
</html>
