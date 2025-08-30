<?php
// Cookies de sesión más seguras 
$useHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
  'lifetime' => 0,
  'httponly' => true,
  'secure' => $useHttps,
  'samesite' => 'Lax'
]);
session_start();

include("Conexionbd.php");

$mensaje = "";
$ok = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre   = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $clave    = $_POST['clave'] ?? '';
    $cedula   = trim($_POST['cedula'] ?? '');
    $rol      = 2; // cliente

    $ok = false;   
    $mensaje = ""; 

    // 1. Vacíos
    if ($nombre === "" || $apellido === "" || $correo === "" || $clave === "" || $cedula === "") {
        $mensaje = "⚠️ Completa todos los campos obligatorios.";
    }
    // 2. Correo válido
    elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "⚠️ Ingresa un correo válido.";
    }
    // 3. Clave
    elseif (strlen($clave) < 6) {
        $mensaje = "⚠️ La contraseña debe tener al menos 6 caracteres.";
    }
    // 4. Cédula: solo números y 9 dígitos (trátala como string para no perder ceros)
    elseif (!ctype_digit($cedula) || strlen($cedula) !== 9) {
        $mensaje = "⚠️ La cédula debe contener exactamente 9 números.";
    }
    // 5. Teléfono (opcional): solo números y 8 dígitos si se ingresa
    elseif ($telefono !== "" && (!ctype_digit($telefono) || strlen($telefono) !== 8)) {
        $mensaje = "⚠️ El teléfono debe contener exactamente 8 números.";
    }
    else {
        // Validar duplicados por correo o cédula
        $chk = $mysqli->prepare("SELECT id FROM usuarios WHERE correo = ? OR cedula = ? LIMIT 1");
        if (!$chk) {
            $mensaje = "❌ Error interno (prep chk).";
        } else {
            $chk->bind_param("ss", $correo, $cedula);
            $chk->execute();
            $res = $chk->get_result();

            if ($res && $res->num_rows > 0) {
                $mensaje = "⚠️ Este correo o cédula ya están registrados.";
            } else {
                $hash = password_hash($clave, PASSWORD_DEFAULT);

                // IMPORTANTE: usa 's' para cédula para no perder ceros a la izquierda
                $ins = $mysqli->prepare("
                    INSERT INTO usuarios (id_rol, correo, clave, nombre, apellido, telefono, cedula)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$ins) {
                    $mensaje = "❌ Error interno (prep ins).";
                } else {
                    // Tipos: i s s s s s s  (cedula como string)
                    $ins->bind_param("issssss", $rol, $correo, $hash, $nombre, $apellido, $telefono, $cedula);

                    if ($ins->execute()) {
                        $ok = true;
                    } else {
                        $mensaje = "❌ Error al registrar. Intenta de nuevo.";
                    }
                    $ins->close();
                }
            }
            if ($res) { $res->free(); }
            $chk->close();
        }
    }

    
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro - Tienda Deportiva</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="estilos.css">
</head>
<body class="login-page">

  <div class="login-card">
    <div class="icon-sport">🏅</div>
    <h2>Crear cuenta</h2>
    <p class="text-muted">Únete a la Tienda Deportiva</p>

    <?php if ($mensaje): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <?php if ($ok): ?>
      <div class="alert alert-success">¡Registro exitoso! Ahora puedes iniciar sesión.</div>
      <a class="btn btn-login w-100 text-white" href="Login.php">Ir al Login</a>
    <?php else: ?>
      <form method="POST" action="">
  <div class="mb-2">
    <input type="text" class="form-control" name="nombre" placeholder="Nombre" required>
  </div>
  <div class="mb-2">
    <input type="text" class="form-control" name="apellido" placeholder="Apellido" required>
  </div>
  <div class="mb-2">
    <input type="email" class="form-control" name="correo" placeholder="Correo electrónico" required>
  </div>
  <div class="mb-2">
    <input type="text" class="form-control" name="cedula" placeholder="Cédula" required>
  </div>
  <div class="mb-2">
    <input type="tel" class="form-control" name="telefono" placeholder="Teléfono (opcional)">
  </div>
  <div class="mb-3">
    <input type="password" class="form-control" name="clave" placeholder="Contraseña (mín. 6)" required>
  </div>
  <button type="submit" class="btn btn-login w-100 text-white">Crear cuenta</button>
</form>
      <div class="mt-3">
        <a href="Login.php" class="link-primary">¿Ya tienes cuenta? Inicia sesión</a>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
