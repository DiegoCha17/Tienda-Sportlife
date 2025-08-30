<?php
session_start();
if (!isset($_SESSION['id']) && !isset($_SESSION['id_usuario'])) {
  header('Location: Login.php');
  exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Carrito | Tienda Deportiva</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Tus estilos -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="estilos.css">
</head>

<body class="carrito-page">
  <!-- HERO -->
  <section class="cart-hero py-4 text-white">
    <div class="container">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
        <div>
          <p class="breadcrumb-mini mb-1">Inicio / Carrito</p>
          <h1 class="fw-bold">
            Tu Carrito
            <span class="badge bg-light text-dark ms-2" data-cart-count>0</span>
          </h1>
        </div>
        <a href="Index.php" class="btn btn-outline-light mt-3 mt-md-0">Seguir comprando</a>
      </div>
    </div>
  </section>

  <!-- CONTENIDO -->
  <main class="container my-5">
    <div class="row g-4">
      <!-- Lista -->
      <div class="col-lg-8">
        <section class="card-soft p-4 shadow-sm" aria-labelledby="titulo-productos">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 id="titulo-productos" class="h5 mb-0">Productos en tu carrito</h2>
            <button id="btnClear" class="btn btn-outline-danger btn-sm" type="button">Vaciar carrito</button>
          </div>

          <!-- Skeleton opcional (si lo vas a usar en tu JS, déjalo con este id) -->
          <div id="cartSkeleton" class="cart-list" style="display:none;">
            <div class="cart-item placeholder-glow">
              <div class="placeholder col-12" style="height:100px;border-radius:12px;"></div>
            </div>
            <div class="cart-item placeholder-glow">
              <div class="placeholder col-12" style="height:100px;border-radius:12px;"></div>
            </div>
          </div>

          <!-- Contenedor que pintará carrito.js -->
          <div id="cartContainer" class="cart-list" aria-live="polite" aria-busy="false">
            <div class="empty text-center py-5">
              🛒 <p class="mt-3 mb-0">Tu carrito está vacío. Agrega productos para comenzar.</p>
            </div>
          </div>
        </section>
      </div>

      <!-- Resumen -->
      <aside class="col-lg-4">
        <section class="card-soft p-4 shadow-sm summary sticky-top" aria-labelledby="titulo-resumen">
          <h2 id="titulo-resumen" class="h5 fw-bold mb-3">Resumen de compra</h2>
          <div id="cartSummary">
            <div class="line d-flex justify-content-between"><span>Subtotal</span><strong>₡0.00</strong></div>
            <div class="line d-flex justify-content-between"><span>Impuestos (13%)</span><strong>₡0.00</strong></div>
            <div class="line d-flex justify-content-between"><span>Envío</span><strong>₡5.00</strong></div>
            <hr>
            <div class="total d-flex justify-content-between fs-5 fw-bold">
              <span>Total</span><span class="text-primary">₡5.00</span>
            </div>
          </div>
          <div class="d-grid gap-2 mt-4">
            <button id="btnCheckout" class="btn btn-primary btn-lg" type="button">Realizar pedido</button>
            <a href="Index.php" class="btn btn-outline">Seguir comprando</a>
          </div>
        </section>
      </aside>
    </div>
  </main>

  
  <script src="carrito.js"></script>
</body>
</html>
