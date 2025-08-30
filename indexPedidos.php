<?php
session_start();
if (!isset($_SESSION['id']) && !isset($_SESSION['id_usuario'])) {
    header('Location: Login.php');
    exit;
}

// Incluir conexión
include 'Conexionbd.php';

$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['id'];

// Obtener direcciones del usuario
$direcciones = [];
$stmt_direcciones = $mysqli->prepare("SELECT id, ciudad, provincia, pais, codigo_postal FROM direcciones WHERE id_usuario = ?");
$stmt_direcciones->bind_param("i", $id_usuario);
$stmt_direcciones->execute();
$result_direcciones = $stmt_direcciones->get_result();
while ($row = $result_direcciones->fetch_assoc()) {
    $direcciones[] = $row;
}
$stmt_direcciones->close();

// Procesar el pedido si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['realizar_pedido'])) {
    $id_direccion = $_POST['id_direccion'];
    $items_carrito = json_decode($_POST['items_carrito'], true);
    
    if (!empty($items_carrito) && $id_direccion) {
        // Calcular totales
        $subtotal = 0;
        foreach ($items_carrito as $item) {
            $precio = isset($item['precio']) ? $item['precio'] : (isset($item['price']) ? $item['price'] : 0);
            $cantidad = isset($item['cantidad']) ? $item['cantidad'] : (isset($item['quantity']) ? $item['quantity'] : 0);
            $subtotal += $precio * $cantidad;
        }
        $impuestos = $subtotal * 0.13;
        $envio = 5.00;
        $total = $subtotal + $impuestos + $envio;
        
       

        // Insertar pedido
$stmt = $mysqli->prepare("INSERT INTO pedidos 
(id_usuario, id_direccion, estado, subtotal, total_impuesto, total_envio, total_general, numero_seguimiento, fecha_creacion) 
VALUES (?, ?, 'pendiente', ?, ?, ?, ?, ?, NOW())");

$numero_seguimiento = 'TD' . date('Ymd') . rand(1000, 9999);
$stmt->bind_param("iidddds", $id_usuario, $id_direccion, $subtotal, $impuestos, $envio, $total, $numero_seguimiento);

if ($stmt->execute()) {

$pedido_id = $mysqli->insert_id;

// Insertar detalles del pedido
$stmt_detalle = $mysqli->prepare("INSERT INTO factura (pedido_id, producto_id, cantidad, precio) VALUES (?, ?, ?, ?)");

foreach ($items_carrito as $item) {
    $producto_id = $item['id']; 
    $cantidad = isset($item['cantidad']) ? $item['cantidad'] : (isset($item['quantity']) ? $item['quantity'] : 0);
    $precio = isset($item['precio']) ? $item['precio'] : (isset($item['price']) ? $item['price'] : 0);

    $stmt_detalle->bind_param("iiid", $pedido_id, $producto_id, $cantidad, $precio);
    $stmt_detalle->execute();
}
$stmt_detalle->close();

// Redirigir a pagos
header('Location: indexPagos.php?pedido_id=' . $pedido_id);
exit;
} else {
$error_message = "Error al procesar el pedido. Inténtalo de nuevo.";
}

$stmt->close();

    } else {
        $error_message = "Datos incompletos para procesar el pedido.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Realizar Pedido | Tienda Deportiva</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="estilosPedidos.css">
</head>

<body class="pedidos-page">
    <!-- HERO -->
    <section class="pedidos-hero">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-md-center">
                <div>
                    <p class="breadcrumb-mini mb-2">
                        <i class="fas fa-home"></i> Inicio / 
                        <i class="fas fa-shopping-cart"></i> Carrito / 
                        <i class="fas fa-credit-card"></i> Realizar Pedido
                    </p>
                    <h1 class="fw-bold">
                        <i class="fas fa-shopping-bag"></i>
                        Finalizar Compra
                    </h1>
                </div>
                <div class="mt-3 mt-md-0">
                    <a href="Carritos.php" class="btn btn-outline-pedidos">
                        <i class="fas fa-arrow-left"></i>
                        Volver al carrito
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- CONTENIDO -->
    <main class="container my-4 my-md-5">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success-pedidos mb-4" role="alert">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>¡Éxito!</strong>
                    <p class="mb-3"><?php echo $success_message; ?></p>
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <a href="Index.php" class="btn btn-primary-pedidos">
                            <i class="fas fa-home"></i>
                            Ir al inicio
                        </a>
                        <a href="#" class="btn btn-outline-pedidos">
                            <i class="fas fa-list"></i>
                            Ver mis pedidos
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if (isset($clear_cart)): ?>
                <script>
                    // Limpiar carrito del localStorage
                    localStorage.removeItem('cart');
                    // Actualizar contador si existe
                    document.addEventListener('DOMContentLoaded', function() {
                        const cartCounts = document.querySelectorAll('[data-cart-count]');
                        cartCounts.forEach(el => el.textContent = '0');
                    });
                </script>
            <?php endif; ?>
            
        <?php else: ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger-pedidos mb-4" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Error</strong>
                        <p class="mb-0"><?php echo $error_message; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- FORMULARIO -->
                <div class="col-lg-8">
                    <section class="card-pedidos">
                        <div class="card-header-pedidos">
                            <h2><i class="fas fa-map-marker-alt"></i> Dirección de Envío</h2>
                        </div>
                        <div class="card-body-pedidos">
                            <form id="pedidoForm" method="POST">
                                <input type="hidden" name="realizar_pedido" value="1">
                                <input type="hidden" name="items_carrito" id="itemsCarrito">
                                
                                <?php if (empty($direcciones)): ?>
                                    <div class="empty-direcciones">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <h4>No tienes direcciones registradas</h4>
                                        <p>Necesitas agregar una dirección de envío para continuar con tu pedido.</p>
                                        <button type="button" class="btn btn-primary-pedidos" data-bs-toggle="modal" data-bs-target="#modalDireccion">
                                            <i class="fas fa-plus"></i>
                                            Agregar dirección
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="direcciones-grid">
                                        <?php foreach ($direcciones as $direccion): ?>
                                            <div class="direccion-card">
                                                <input type="radio" name="id_direccion" value="<?php echo $direccion['id']; ?>" 
                                                       id="dir_<?php echo $direccion['id']; ?>" class="direccion-radio">
                                                <label for="dir_<?php echo $direccion['id']; ?>" class="direccion-label">
                                                    <div class="direccion-info">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($direccion['ciudad']); ?></strong>
                                                            <p><?php echo htmlspecialchars($direccion['provincia']); ?>, <?php echo htmlspecialchars($direccion['pais']); ?></p>
                                                            <small>Código Postal: <?php echo htmlspecialchars($direccion['codigo_postal']); ?></small>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="text-center mt-4">
    <button type="button" class="btn btn-morado" data-bs-toggle="modal" data-bs-target="#modalDireccion">
        <i class="fas fa-plus"></i>
        Agregar nueva dirección
    </button>
</div>

                                <?php endif; ?>
                            </form>
                        </div>
                    </section>
                </div>

                <!-- RESUMEN -->
                <aside class="col-lg-4">
                    <section class="card-pedidos summary-pedidos">
                        <div class="card-header-pedidos">
                            <h2><i class="fas fa-receipt"></i> Resumen del Pedido</h2>
                        </div>
                        <div class="card-body-pedidos">
                            <div id="resumenPedido">
                                <div class="resumen-loading">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <p>Cargando resumen...</p>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" form="pedidoForm" class="btn btn-success-pedidos btn-lg" id="btnRealizarPedido" disabled>
                                    <i class="fas fa-credit-card"></i>
                                    Realizar Pedido
                                </button>
                                
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        <?php endif; ?>
    </main>

    <!-- MODAL NUEVA DIRECCIÓN -->
    <div class="modal fade" id="modalDireccion" tabindex="-1" aria-labelledby="modalDireccionLabel" aria-hidden="true">
        <div class="modal-dialog">
        <div class="modal-content modal-content-pedidos">
                <div class="modal-header-pedidos">
                    <h3 id="modalDireccionLabel"><i class="fas fa-map-marker-alt"></i> Nueva Dirección</h3>
                    <button type="button" class="btn-close-pedidos" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body-pedidos">
                    <form id="formDireccion">
                        <div class="form-group-pedidos">
                            <label for="ciudad"><i class="fas fa-city"></i> Ciudad</label>
                            <input type="text" class="form-control-pedidos" id="ciudad" name="ciudad" required 
                                   placeholder="Ej: San José">
                        </div>
                        <div class="form-group-pedidos">
                            <label for="provincia"><i class="fas fa-map"></i> Provincia</label>
                            <input type="text" class="form-control-pedidos" id="provincia" name="provincia" required
                                   placeholder="Ej: San José">
                        </div>
                        <div class="form-group-pedidos">
                            <label for="pais"><i class="fas fa-flag"></i> País</label>
                            <input type="text" class="form-control-pedidos" id="pais" name="pais" value="Costa Rica" required>
                        </div>
                        <div class="form-group-pedidos">
                            <label for="codigo_postal"><i class="fas fa-mail-bulk"></i> Código Postal</label>
                            <input type="text" class="form-control-pedidos" id="codigo_postal" name="codigo_postal" required
                                   placeholder="Ej: 10101" pattern="[0-9]{5}" title="Ingrese 5 dígitos">
                        </div>
                    </form>
                </div>
                <div class="modal-footer-pedidos">
                    <button type="button" class="btn btn-outline-pedidos" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary-pedidos" id="btnGuardarDireccion">
                        <i class="fas fa-save"></i>
                        Guardar dirección
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="pedidos.js"></script>
</body>
</html>