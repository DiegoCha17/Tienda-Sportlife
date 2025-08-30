<?php
session_start();
if (!isset($_SESSION['id']) && !isset($_SESSION['id_usuario'])) {
    header('Location: Login.php');
    exit;
}

// CSRF: genera el token 
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}


// Incluir conexión
include 'Conexionbd.php';

$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['id'];

// Verificar que exista información del pedido
if (!isset($_GET['pedido_id'])) {
    header('Location: Carritos.php');
    exit;
}

$pedido_id = $_GET['pedido_id'];

// Obtener información del pedido
$pedido_info = null;
$stmt = $mysqli->prepare("SELECT p.*, d.ciudad, d.provincia, d.pais, d.codigo_postal 
                         FROM pedidos p 
                         JOIN direcciones d ON p.id_direccion = d.id 
                         WHERE p.id = ? AND p.id_usuario = ?");
$stmt->bind_param("ii", $pedido_id, $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
$pedido_info = $result->fetch_assoc();
$stmt->close();

// Verificar que el pedido existe y pertenece al usuario
if (!$pedido_info) {
    header('Location: Carritos.php');
    exit;
}

// Procesar pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procesar_pago'])) {

    //  Validación CSRF
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
        http_response_code(419);
        $error_pago = 'Token CSRF inválido o ausente.';
    } else {

        //  Normalizar método de pago del formulario a ENUM BD
        $metodo_form = $_POST['metodo_pago'] ?? '';
        $mapMetodo = [
            'tarjeta_credito' => 'credito',
            'tarjeta_debito'  => 'debito',
            'paypal'          => 'paypal',
        ];
        if (!isset($mapMetodo[$metodo_form])) {
            $error_pago = 'Método de pago inválido.';
        } else {
            $metodo_pago = $mapMetodo[$metodo_form];

            //  Validar campos según método
            $datos_validos = false;
            if ($metodo_pago === 'paypal') {
                // En productivo rediriges a PayPal y confirmas IPN/Webhook
                $datos_validos = true;
            } else { // credito | debito
                $datos_validos =
                    !empty($_POST['numero_tarjeta']) &&
                    !empty($_POST['nombre_tarjeta']) &&
                    !empty($_POST['fecha_expiracion']) &&
                    !empty($_POST['cvv']);
            }

            if (!$datos_validos) {
                $error_pago = 'Datos de pago incompletos o inválidos.';
            } else {
                // Transacción para evitar doble cobro
                $mysqli->begin_transaction();
                try {
                    // Bloquea el pedido y verifica estado
                    $stmt = $mysqli->prepare("
                        SELECT estado, total_general
                        FROM pedidos
                        WHERE id = ? AND id_usuario = ?
                        FOR UPDATE
                    ");
                    $stmt->bind_param('ii', $pedido_id, $id_usuario);
                    $stmt->execute();
                    $r = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!$r) {
                        throw new Exception('Pedido no existe.');
                    }
                    if ($r['estado'] !== 'pendiente') {
                        throw new Exception('El pedido ya fue procesado previamente.');
                    }

                    //Resultado del cobro
                    $cobroExitoso = true;

                    if (!$cobroExitoso) {
                        // registra intento fallido y lanza
                        $stmtPayF = $mysqli->prepare("
                            INSERT INTO pagos (id_pedido, metodo, monto, estado, fecha_creacion)
                            VALUES (?, ?, ?, 'fallido', NOW())
                        ");
                        $monto = (float)$r['total_general'];
                        $stmtPayF->bind_param('isd', $pedido_id, $metodo_pago, $monto);
                        $stmtPayF->execute();
                        $stmtPayF->close();
                        throw new Exception('El cobro fue rechazado por el emisor.');
                    }

                    //  marca pedido como pagado
                    $stmtUpd = $mysqli->prepare("
                        UPDATE pedidos SET estado = 'pagado'
                        WHERE id = ? AND id_usuario = ?
                    ");
                    $stmtUpd->bind_param('ii', $pedido_id, $id_usuario);
                    if (!$stmtUpd->execute()) {
                        throw new Exception($stmtUpd->error);
                    }
                    $stmtUpd->close();

                    //  registra pago aprobado
                    $numero_transaccion = 'TXN' . date('YmdHis') . rand(100, 999);
                    $monto = (float)$r['total_general'];

                    $stmtPay = $mysqli->prepare("
                        INSERT INTO pagos (id_pedido, metodo, monto, estado, numero_transaccion, fecha_creacion)
                        VALUES (?, ?, ?, 'aprobado', ?, NOW())

                    ");

                    if (!$stmtPay) { throw new Exception('Prepare pagos: ' . $mysqli->error); }

                    $stmtPay->bind_param('isds', $pedido_id, $metodo_pago, $monto, $numero_transaccion);
                    if (!$stmtPay->execute()) {
                        throw new Exception('Execute pagos: ' . $stmtPay->error);
                    }
                    $stmtPay->close();


                    // Vaciar carrito activo del usuario
                    $stmtClr = $mysqli->prepare("DELETE FROM carritos WHERE id_usuario = ? AND estado = 'activo'");
                    if ($stmtClr) {
                        $stmtClr->bind_param('i', $id_usuario);
                        $stmtClr->execute();
                        $stmtClr->close();
                    }

                    //  Confirmar transacción
                    $mysqli->commit();
                    $pago_completado = true;
                    $numero_transaccion_final = $numero_transaccion;
                    $clear_cart = true;

                } catch (Throwable $e) {
                    $mysqli->rollback();
                    $error_pago = 'Error al procesar el pago: ' . $e->getMessage();
                }
            }
        }
    }
}

// Obtener datos del cliente
$stmtCliente = $mysqli->prepare("SELECT id, nombre, apellido, cedula, correo 
                                FROM usuarios 
                                WHERE id = ?");
$stmtCliente->bind_param("i", $id_usuario);
$stmtCliente->execute();
$cliente_info = $stmtCliente->get_result()->fetch_assoc();
$stmtCliente->close();

// Obtener productos del pedido
$stmtProd = $mysqli->prepare("
    SELECT pr.nombre, d.cantidad, d.precio, (d.cantidad * d.precio) AS subtotal
    FROM factura d
    INNER JOIN productos pr ON d.producto_id = pr.id
    WHERE d.pedido_id = ?
");
$stmtProd->bind_param("i", $pedido_id);
$stmtProd->execute();
$productos = $stmtProd->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtProd->close();

?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Pagar Pedido | Tienda Deportiva</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="estilosPagos.css">
</head>

<body class="pagos-page">
    <!-- HERO -->
    <section class="pagos-hero">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-md-center">
                <div>
                    <p class="breadcrumb-mini mb-2 opacity-75">
                        <i class="fas fa-home"></i> Inicio / 
                        <i class="fas fa-shopping-cart"></i> Carrito / 
                        <i class="fas fa-shopping-bag"></i> Pedido /
                        <i class="fas fa-credit-card"></i> Pago
                    </p>
                    <h1 class="fw-bold">
                        <i class="fas fa-credit-card"></i>
                        Procesar Pago
                    </h1>
                    <p class="mb-0 opacity-75">Pedido #<?php echo htmlspecialchars($pedido_info['numero_seguimiento']); ?></p>
                </div>
               
            </div>
        </div>
    </section>

    <!-- CONTENIDO -->
    <main class="container my-4 my-md-5">
        <?php if (isset($pago_completado) && $pago_completado): ?>
            <!-- PAGO EXITOSO -->
            <div class="alert alert-success-pagos" role="alert">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <h3 class="fw-bold">¡Pago Procesado Exitosamente!</h3>
                <p class="mb-3">Tu pedido ha sido pagado y está siendo procesado.</p>
                
                <div class="row justify-content-center mb-4">
                    <div class="col-md-8">
                        <div class="bg-white bg-opacity-25 rounded p-3">
                            <div class="row">
                                <div class="col-sm-6 mb-2">
                                    <strong>Número de Pedido:</strong><br>
                                    <span class="fs-6"><?php echo htmlspecialchars($pedido_info['numero_seguimiento']); ?></span>
                                </div>
                                <div class="col-sm-6">
                                    <strong>Número de Transacción:</strong><br>
                                    <span class="fs-6"><?php echo htmlspecialchars($numero_transaccion_final); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
                    <a href="Index.php" class="btn btn-light btn-lg">
                        <i class="fas fa-home"></i>
                        Ir al inicio
                    </a>
                    <a href="historial_pedidos.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-list"></i>
                        Ver mis pedidos
                    </a>
                    <a href="#" class="btn btn-outline-light btn-lg" onclick="imprimirRecibo(); return false;">
    <i class="fas fa-print"></i>
    Imprimir recibo
</a>

                </div>
            </div>

            <?php if (isset($clear_cart)): ?>
                <script>
                    // Limpiar carrito del localStorage
                    if (typeof(Storage) !== "undefined") {
                        localStorage.removeItem('cart');
                        localStorage.removeItem('cartCount');
                    }
                    
                    // Actualizar contador si existe
                    document.addEventListener('DOMContentLoaded', function() {
                        const cartCounts = document.querySelectorAll('[data-cart-count]');
                        cartCounts.forEach(el => el.textContent = '0');
                        
                        const cartBadges = document.querySelectorAll('.cart-badge, .badge-cart');
                        cartBadges.forEach(badge => badge.textContent = '0');
                    });
                </script>
            <?php endif; ?>

        <?php else: ?>
            
            <?php if (isset($error_pago)): ?>
                <div class="alert alert-danger-pagos mb-4" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Error en el pago:</strong>
                        <p class="mb-0"><?php echo htmlspecialchars($error_pago); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- MÉTODOS DE PAGO -->
                <div class="col-lg-8">
                    <div class="card-pagos">
                        <div class="card-header-pagos">
                            <h2 class="mb-0"><i class="fas fa-credit-card"></i> Método de Pago</h2>
                        </div>
                        <div class="card-body-pagos">
                            <form id="pagoForm" method="POST">
                                <input type="hidden" name="procesar_pago" value="1">
                                <input type="hidden" name="pedido_id" value="<?php echo $pedido_info['id']; ?>">
                                <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">

                                <!-- MÉTODOS DE PAGO -->
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <div class="metodo-pago text-center" data-metodo="tarjeta_credito">
                                            <input type="radio" name="metodo_pago" value="tarjeta_credito" id="tarjeta_credito">
                                            <label for="tarjeta_credito" class="w-100">
                                                <div class="metodo-pago-icon">
                                                    <i class="fas fa-credit-card"></i>
                                                </div>
                                                <h6 class="fw-bold">Tarjeta de Crédito</h6>
                                                <small class="text-muted">Visa, MasterCard, AMEX</small>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="metodo-pago text-center" data-metodo="tarjeta_debito">
                                            <input type="radio" name="metodo_pago" value="tarjeta_debito" id="tarjeta_debito">
                                            <label for="tarjeta_debito" class="w-100">
                                                <div class="metodo-pago-icon">
                                                    <i class="fas fa-money-check-alt"></i>
                                                </div>
                                                <h6 class="fw-bold">Tarjeta de Débito</h6>
                                                <small class="text-muted">Visa Débito, Maestro</small>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="metodo-pago text-center" data-metodo="paypal">
                                            <input type="radio" name="metodo_pago" value="paypal" id="paypal">
                                            <label for="paypal" class="w-100">
                                                <div class="metodo-pago-icon">
                                                    <i class="fab fa-paypal"></i>
                                                </div>
                                                <h6 class="fw-bold">PayPal</h6>
                                                <small class="text-muted">Cuenta PayPal</small>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- CAMPOS DE TARJETA -->
                                <div id="campos-tarjeta" class="campos-tarjeta">
                                    <h5 class="mb-3"><i class="fas fa-info-circle"></i> Información de la Tarjeta</h5>
                                    
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="numero_tarjeta" class="form-label">
                                                <i class="fas fa-credit-card"></i> Número de Tarjeta
                                            </label>
                                            <input type="text" class="form-control-pagos" id="numero_tarjeta" name="numero_tarjeta"
                                                   placeholder="1234 5678 9012 3456" maxlength="19" autocomplete="cc-number">
                                        </div>
                                        
                                        <div class="col-md-8">
                                            <label for="nombre_tarjeta" class="form-label">
                                                <i class="fas fa-user"></i> Nombre del Titular
                                            </label>
                                            <input type="text" class="form-control-pagos" id="nombre_tarjeta" name="nombre_tarjeta"
                                                   placeholder="Como aparece en la tarjeta" autocomplete="cc-name">
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label for="fecha_expiracion" class="form-label">
                                                <i class="fas fa-calendar"></i> Fecha de Expiración
                                            </label>
                                            <input type="text" class="form-control-pagos" id="fecha_expiracion" name="fecha_expiracion"
                                                   placeholder="MM/YY" maxlength="5" autocomplete="cc-exp">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="cvv" class="form-label">
                                                <i class="fas fa-lock"></i> Código CVV
                                            </label>
                                            <input type="password" class="form-control-pagos" id="cvv" name="cvv"
                                                   placeholder="123" maxlength="4" autocomplete="cc-csc">
                                        </div>
                                    </div>

                                    <div class="seguridad-info">
                                        <i class="fas fa-shield-alt"></i>
                                        <strong>Transacción segura:</strong> Tu información está protegida con encriptación SSL de 256 bits.
                                    </div>
                                </div>

                                <!-- CAMPOS PAYPAL -->
                                <div id="campos-paypal" class="campos-tarjeta">
                                    <div class="text-center py-4">
                                        <i class="fab fa-paypal fa-4x text-primary mb-3"></i>
                                        <h5>Serás redirigido a PayPal</h5>
                                        <p class="text-muted">Inicia sesión en tu cuenta PayPal para completar el pago de forma segura.</p>
                                        <div class="mt-3">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle"></i>
                                                El proceso de pago se realizará en el sitio seguro de PayPal
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- RESUMEN DEL PEDIDO -->
                <div class="col-lg-4">
                    <div class="card-pagos">
                        <div class="card-header-pagos">
                            <h3 class="mb-0"><i class="fas fa-receipt"></i> Resumen del Pedido</h3>
                        </div>
                        <div class="card-body-pagos">
                            <!-- Información del pedido -->
                            <div class="resumen-pago">
                                <h6 class="fw-bold mb-3"><i class="fas fa-info-circle"></i> Detalles del Pedido</h6>
                                <p class="mb-2">
                                    <strong>Número:</strong> 
                                    <span class="text-primary"><?php echo htmlspecialchars($pedido_info['numero_seguimiento']); ?></span>
                                </p>
                                <p class="mb-3">
                                    <strong>Estado:</strong> 
                                    <span class="badge bg-warning text-dark">Pendiente de pago</span>
                                </p>
                                
                                <hr>
                                
                                <h6 class="fw-bold mb-2"><i class="fas fa-map-marker-alt"></i> Dirección de Envío</h6>
                                <p class="mb-0 small">
                                    <?php echo htmlspecialchars($pedido_info['ciudad']); ?><br>
                                    <?php echo htmlspecialchars($pedido_info['provincia']); ?>, <?php echo htmlspecialchars($pedido_info['pais']); ?><br>
                                    <strong>CP:</strong> <?php echo htmlspecialchars($pedido_info['codigo_postal']); ?>
                                </p>
                            </div>

                            <!-- Totales -->
                            <div class="resumen-item">
                                <span>Subtotal:</span>
                                <span>₡<?php echo number_format($pedido_info['subtotal'], 2); ?></span>
                            </div>
                            
                            <div class="resumen-item">
                                <span>Impuestos (13%):</span>
                                <span>₡<?php echo number_format($pedido_info['total_impuesto'], 2); ?></span>
                            </div>
                            
                            <div class="resumen-item">
                                <span>Envío:</span>
                                <span>₡<?php echo number_format($pedido_info['total_envio'], 2); ?></span>
                            </div>
                            
                            <div class="resumen-item total">
                                <span>Total a Pagar:</span>
                                <span>₡<?php echo number_format($pedido_info['total_general'], 2); ?></span>
                            </div>

                            <!-- Botón de pago -->
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" form="pagoForm" class="btn btn-pagar btn-lg" id="btnPagar" disabled>
                                    <i class="fas fa-lock"></i>
                                    Pagar ₡<?php echo number_format($pedido_info['total_general'], 2); ?>
                                </button>
                                <a href="indexPedidos.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i>
                                    Volver al pedido
                                </a>
                            </div>

                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt"></i>
                                    Pago 100% seguro y encriptado
                                </small>
                            </div>

                           


                        </div>
                    </div>
                </div>
            </div>

            
        <?php endif; ?>


         <!-- RECIBO A IMPRIMIR -->
<div id="recibo" style="display:none; font-family: Arial, sans-serif;">
    <h2>Recibo de Pedido</h2>
    <p><strong>Número de Pedido:</strong> <?php echo htmlspecialchars($pedido_info['numero_seguimiento']); ?></p>
    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($cliente_info['nombre'] . ' ' . $cliente_info['apellido']); ?></p>
    <p><strong>Identificación:</strong> <?php echo htmlspecialchars($cliente_info['cedula']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($cliente_info['correo']); ?></p>
    <hr>

    <h3>Productos</h3>
    <table border="1" cellspacing="0" cellpadding="5" width="100%">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($productos as $prod): ?>
            <tr>
                <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                <td><?php echo (int)$prod['cantidad']; ?></td>
                <td>₡<?php echo number_format($prod['precio'], 2); ?></td>
                <td>₡<?php echo number_format($prod['subtotal'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr>
    <p><strong>Subtotal:</strong> ₡<?php echo number_format($pedido_info['subtotal'], 2); ?></p>
    <p><strong>Impuestos:</strong> ₡<?php echo number_format($pedido_info['total_impuesto'], 2); ?></p>
    <p><strong>Envío:</strong> ₡<?php echo number_format($pedido_info['total_envio'], 2); ?></p>
    <h3>Total: ₡<?php echo number_format($pedido_info['total_general'], 2); ?></h3>
</div>
    </main>

   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="pagos.js"></script>
</body>

<script>
function imprimirRecibo() {
    var printContents = document.getElementById('recibo').innerHTML;
    var originalContents = document.body.innerHTML;

    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    
}
</script>

</html>