<?php
session_start();
if (!isset($_SESSION['id']) && !isset($_SESSION['id_usuario'])) {
  header('Location: Login.php'); exit;
}
require_once 'Conexionbd.php';

$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['id'];

/* Trae los pedidos del usuario + último pago (si existe) + dirección */
$sql = "
SELECT
  p.id,
  p.numero_seguimiento,
  p.estado        AS estado_pedido,
  p.total_general,
  p.fecha_creacion,
  d.ciudad, d.provincia, d.pais, d.codigo_postal,
  pg.estado       AS estado_pago,
  pg.metodo       AS metodo_pago,
  pg.numero_transaccion
FROM pedidos p
LEFT JOIN direcciones d ON d.id = p.id_direccion
LEFT JOIN (
  SELECT pp.*
  FROM pagos pp
  JOIN (
    SELECT id_pedido, MAX(fecha_creacion) AS mx
    FROM pagos
    GROUP BY id_pedido
  ) ult ON ult.id_pedido = pp.id_pedido AND ult.mx = pp.fecha_creacion
) pg ON pg.id_pedido = p.id
WHERE p.id_usuario = ?
ORDER BY p.fecha_creacion DESC
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $id_usuario);
$stmt->execute();
$pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function badgePedido($estado) {
  $map = [
    'pendiente' => ['bg-yellow-100 text-yellow-800', 'Pendiente'],
    'pagado'    => ['bg-green-100 text-green-800', 'Pagado'],
    'enviado'   => ['bg-blue-100 text-blue-800', 'Enviado'],
    'entregado' => ['bg-emerald-100 text-emerald-800', 'Entregado'],
    'cancelado' => ['bg-red-100 text-red-800', 'Cancelado'],
  ];
  [$cls,$txt] = $map[$estado] ?? ['bg-gray-100 text-gray-700','-'];
  return "<span class=\"px-2 py-1 rounded-md text-xs font-semibold {$cls}\">{$txt}</span>";
}
function badgePago($estado) {
  $map = [
    'aprobado'  => ['bg-green-100 text-green-800', 'Aprobado'],
    'pendiente' => ['bg-yellow-100 text-yellow-800', 'Pendiente'],
    'fallido'   => ['bg-red-100 text-red-800', 'Fallido'],
  ];
  [$cls,$txt] = $map[$estado] ?? ['bg-gray-100 text-gray-700','Sin pago'];
  return "<span class=\"px-2 py-1 rounded-md text-xs font-semibold {$cls}\">{$txt}</span>";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Historial de Pedidos | SportLife</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body class="bg-gray-50">
  <!-- Header simple (puedes reutilizar tu layout) -->
  <header class="bg-white shadow py-4 px-6 md:px-12 flex items-center justify-between">
    <a href="Index.php" class="text-2xl font-bold text-gray-800 flex items-center">
      <span class="text-emerald-500 mr-2 text-3xl">🏃‍♂️</span> SportLife
    </a>
    <div class="flex items-center gap-3">
      <a href="perfil_usuario.php" class="p-2 rounded-full bg-emerald-100 text-emerald-600"><i class="fa-solid fa-user"></i></a>
      <a href="Carritos.php" class="p-2 rounded-full hover:bg-emerald-100 relative">
        <i class="fa-solid fa-cart-shopping"></i>
        <span data-cart-count
              class="absolute -top-1 -right-1 bg-blue-600 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"></span>
      </a>
      <a href="logout.php" class="p-2 rounded-full hover:bg-red-100 text-red-600"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </header>

  

  <main class="max-w-7xl mx-auto px-6 md:px-12 py-8">
    <div class="bg-gradient-to-r from-emerald-500 to-blue-500 text-white rounded-2xl p-8 mb-8 shadow">
      <h1 class="text-3xl md:text-4xl font-bold">Historial de pedidos</h1>
      <p class="opacity-90">Revisa el estado y detalle de tus compras.</p>
       <div class="mt-3 flex justify-end">
                    <a href="Index.php" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left"></i>
                        Volver al inicio
                    </a>
                </div>
    </div>

    <?php if (empty($pedidos)): ?>
      <div class="bg-white rounded-xl p-10 shadow text-center">
        <i class="fa-solid fa-box-open text-4xl text-gray-400 mb-3"></i>
        <h3 class="text-lg font-semibold text-gray-800">Aún no tienes pedidos</h3>
        <p class="text-gray-500 mb-6">Cuando compres, aparecerán aquí.</p>
        <a href="indexProductos.php" class="inline-flex items-center gap-2 px-5 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
          <i class="fa-solid fa-store"></i> Explorar productos
        </a>
      </div>
    <?php else: ?>

      <!-- Tabla (desktop) -->
      <div class="hidden md:block bg-white rounded-xl shadow overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-100 text-gray-700 text-sm">
            <tr>
              <th class="px-6 py-3 text-left">Pedido</th>
              <th class="px-6 py-3 text-left">Fecha</th>
              <th class="px-6 py-3 text-left">Estado</th>
              <th class="px-6 py-3 text-left">Pago</th>
              <th class="px-6 py-3 text-left">Método</th>
              <th class="px-6 py-3 text-right">Total</th>
              <th class="px-6 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <?php foreach ($pedidos as $p): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 font-semibold text-gray-800">
                  #<?= htmlspecialchars($p['numero_seguimiento']) ?>
                </td>
                <td class="px-6 py-4 text-gray-600">
                  <?= date('d/m/Y H:i', strtotime($p['fecha_creacion'])) ?>
                </td>
                <td class="px-6 py-4"><?= badgePedido($p['estado_pedido']) ?></td>
                <td class="px-6 py-4">
                  <?= badgePago($p['estado_pago'] ?? '') ?>
                  <?php if (!empty($p['numero_transaccion'])): ?>
                    <div class="text-xs text-gray-500">TX: <?= htmlspecialchars($p['numero_transaccion']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 capitalize text-gray-700">
                  <?= htmlspecialchars($p['metodo_pago'] ?? '-') ?>
                </td>
                <td class="px-6 py-4 text-right font-semibold text-gray-800">
                  ₡<?= number_format((float)$p['total_general'], 2) ?>
                </td>
                <td class="px-6 py-4 text-right space-x-2">
                <td class="px-6 py-4 text-right">
  <div class="flex justify-end gap-2">
    <button class="inline-flex items-center gap-1 px-3 py-2 rounded-md text-sm font-medium bg-blue-600 text-white hover:bg-blue-700"
            data-open-detalle
            data-id="<?= (int)$p['id'] ?>">
      <i class="fa-regular fa-eye"></i> Ver detalle
    </button>

    <?php if ($p['estado_pedido'] === 'pendiente'): ?>
      <a href="indexPagos.php?pedido_id=<?= (int)$p['id'] ?>"
   class="inline-flex items-center gap-1 px-3 py-2 rounded-md text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700">
   <i class="fa-solid fa-credit-card"></i> Pagar
</a>

      <a href="eliminar_pedido.php?id=<?= (int)$p['id'] ?>"
         onclick="return confirm('¿Seguro que deseas eliminar este pedido?')"
         class="inline-flex items-center gap-1 px-3 py-2 rounded-md text-sm font-medium bg-red-600 text-white hover:bg-red-700">
         <i class="fa-solid fa-trash"></i> Eliminar
      </a>
    <?php endif; ?>
  </div>
</td>

              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Cards (mobile) -->
      <div class="md:hidden space-y-4">
        <?php foreach ($pedidos as $p): ?>
        <div class="bg-white rounded-xl shadow p-4">
          <div class="flex items-center justify-between">
            <div class="font-semibold text-gray-800">#<?= htmlspecialchars($p['numero_seguimiento']) ?></div>
            <div class="text-sm text-gray-500"><?= date('d/m/Y', strtotime($p['fecha_creacion'])) ?></div>
          </div>
          <div class="flex items-center gap-2 mt-2">
            <?= badgePedido($p['estado_pedido']) ?>
            <?= badgePago($p['estado_pago'] ?? '') ?>
          </div>
          <div class="mt-2 text-sm text-gray-600">
            Método: <span class="capitalize"><?= htmlspecialchars($p['metodo_pago'] ?? '-') ?></span>
            <?php if (!empty($p['numero_transaccion'])): ?>
              · TX: <?= htmlspecialchars($p['numero_transaccion']) ?>
            <?php endif; ?>
          </div>
          <div class="mt-2 font-semibold text-gray-800">
            Total: ₡<?= number_format((float)$p['total_general'], 2) ?>
          </div>
          <div class="mt-3 flex flex-col gap-2">
  <button class="w-full inline-flex items-center justify-center gap-1 px-3 py-2 rounded-md text-sm font-medium bg-blue-600 text-white hover:bg-blue-700"
          data-open-detalle
          data-id="<?= (int)$p['id'] ?>">
    <i class="fa-regular fa-eye"></i> Ver detalle
  </button>

  <?php if ($p['estado_pedido'] === 'pendiente'): ?>
    <a href="indexPagos.php?id=<?= (int)$p['id'] ?>"
       class="w-full inline-flex items-center justify-center gap-1 px-3 py-2 rounded-md text-sm font-medium bg-emerald-600 text-white hover:bg-emerald-700">
       <i class="fa-solid fa-credit-card"></i> Pagar
    </a>

    <a href="eliminar_pedido.php?id=<?= (int)$p['id'] ?>"
       onclick="return confirm('¿Seguro que deseas eliminar este pedido?')"
       class="w-full inline-flex items-center justify-center gap-1 px-3 py-2 rounded-md text-sm font-medium bg-red-600 text-white hover:bg-red-700">
       <i class="fa-solid fa-trash"></i> Eliminar
    </a>
  <?php endif; ?>
</div>
        </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  </main>

  <!-- Modal detalle -->
  <div id="modalDetalle" class="fixed inset-0 hidden items-center justify-center z-50">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="relative bg-white w-[95%] max-w-3xl rounded-2xl shadow-lg p-6">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-xl font-bold text-gray-800">
          <i class="fa-solid fa-receipt text-blue-600 mr-2"></i>
          Detalle del pedido <span id="md-nro" class="text-gray-500"></span>
        </h3>
        <button id="md-close" class="p-2 rounded hover:bg-gray-100">
          <i class="fa-solid fa-xmark text-gray-600"></i>
        </button>
      </div>

      <div id="md-body" class="space-y-4">
        <div class="text-gray-500">Cargando…</div>
      </div>

      <div class="mt-5 text-right">
        <button id="md-close2" class="px-4 py-2 rounded-md bg-gray-200 hover:bg-gray-300 text-gray-800">Cerrar</button>
      </div>
    </div>
  </div>

  <script>
  // Badge del carrito 
  document.addEventListener('DOMContentLoaded', async () => {
    if (window.carrito && typeof carrito.syncFromServer === 'function') {
      await carrito.syncFromServer();
      carrito.refreshUI();
    } else {
      // fallback: solo desde localStorage
      const items = JSON.parse(localStorage.getItem('cart') || '[]');
      document.querySelectorAll('[data-cart-count]').forEach(el => el.textContent = (items.reduce((s,i)=>s+(+i.quantity||0),0) || 0));
    }
  });

  // Modal
  const modal = document.getElementById('modalDetalle');
  const body  = document.getElementById('md-body');
  const nro   = document.getElementById('md-nro');
  function openModal() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
  function closeModal(){ modal.classList.add('hidden'); modal.classList.remove('flex'); }
  document.getElementById('md-close').onclick = closeModal;
  document.getElementById('md-close2').onclick = closeModal;
  modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

  // Cargar detalle por fetch
  document.querySelectorAll('[data-open-detalle]').forEach(btn => {
    btn.addEventListener('click', async () => {
      const id = btn.dataset.id;
      body.innerHTML = '<div class="text-gray-500">Cargando…</div>';
      openModal();

      try {
        const res = await fetch('pedido_detalle.php?id=' + encodeURIComponent(id));
        const data = await res.json();

        nro.textContent = data.numero_seguimiento ? ('#' + data.numero_seguimiento) : '';
        // Construir HTML
        let html = `
          <div class="grid md:grid-cols-3 gap-4 text-sm">
            <div class="bg-gray-50 rounded-lg p-3">
              <div class="font-semibold text-gray-700 mb-1">Resumen</div>
              <div class="text-gray-600">Fecha: ${data.fecha || '-'}</div>
              <div class="text-gray-600">Estado: ${data.estado_pedido || '-'}</div>
              <div class="text-gray-600">Total: ₡${Number(data.total || 0).toFixed(2)}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <div class="font-semibold text-gray-700 mb-1">Pago</div>
              <div class="text-gray-600">Estado: ${data.estado_pago || '-'}</div>
              <div class="text-gray-600">Método: ${data.metodo_pago || '-'}</div>
              <div class="text-gray-600">Transacción: ${data.numero_transaccion || '-'}</div>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
              <div class="font-semibold text-gray-700 mb-1">Envío</div>
              <div class="text-gray-600">${data.direccion || '-'}</div>
            </div>
          </div>
        `;

        if (Array.isArray(data.items) && data.items.length) {
          html += `
            <div class="mt-4">
              <div class="font-semibold text-gray-800 mb-2">Productos</div>
              <div class="divide-y bg-white border rounded-lg">
                ${data.items.map(it => `
                  <div class="flex items-center gap-3 p-3">
                    <img src="${it.imagen || ''}" onerror="this.style.display='none';"
                         class="w-14 h-14 object-cover rounded" alt="">
                    <div class="flex-1">
                      <div class="font-medium text-gray-800">${it.nombre || 'Producto'}</div>
                      <div class="text-gray-500 text-sm">Cant: ${it.cantidad || 0} · ₡${Number(it.precio || 0).toFixed(2)}</div>
                    </div>
                    <div class="font-semibold text-gray-800">
                      ₡${(Number(it.precio||0)*Number(it.cantidad||0)).toFixed(2)}
                    </div>
                  </div>
                `).join('')}
              </div>
            </div>
          `;
        }

        body.innerHTML = html;
      } catch (e) {
        body.innerHTML = `<div class="text-red-600">No se pudo cargar el detalle.</div>`;
      }
    });
  });
  </script>
</body>
</html>
