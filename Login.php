<?php
session_start();
include("Conexionbd.php");

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST['correo'] ?? '');
    $clave  = $_POST['clave'] ?? '';


    try {
        $stmt = $mysqli->prepare("SELECT id, id_rol, nombre, correo, clave FROM usuarios WHERE correo = ? LIMIT 1");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado && $resultado->num_rows === 1) {
            $usuario = $resultado->fetch_assoc();
            $hashBD  = $usuario['clave'];
            $loginOK = false;

            // Verificar la contraseña 
            if (password_verify($clave, $hashBD)) {
                $loginOK = true;
                // Si el hash necesita ser rehasheado (p. ej., cambio de algoritmo o costo), actualiza la base de datos
                if (password_needs_rehash($hashBD, PASSWORD_DEFAULT)) {
                    $nuevo_hash = password_hash($clave, PASSWORD_DEFAULT);
                    $up = $mysqli->prepare("UPDATE usuarios SET clave=? WHERE id=?");
                    $up->bind_param("si", $nuevo_hash, $usuario['id']);
                    $up->execute();
                    $up->close();
                }
            } 
            // Si la verificación anterior falla, intenta verificar si es un hash MD5 heredado
            else if (hash_equals($hashBD, md5($clave))) {
                $loginOK = true;
                // Si la verificación MD5 es exitosa, hashea la contraseña y actualiza la base de datos
                $nuevo_hash = password_hash($clave, PASSWORD_DEFAULT);
                $up = $mysqli->prepare("UPDATE usuarios SET clave=? WHERE id=?");
                $up->bind_param("si", $nuevo_hash, $usuario['id']);
                $up->execute();
                $up->close();
            }

            if ($loginOK) {
                // Regenerar el ID de sesión para prevenir ataques de fijación de sesión
                session_regenerate_id(true);
                // después de session_regenerate_id(true);
$_SESSION['id']          = (int)$usuario['id'];
$_SESSION['id_usuario']  = (int)$usuario['id'];  
$_SESSION['correo']      = $usuario['correo'];
$_SESSION['rol']         = (int)$usuario['id_rol'];


                // Redirección por rol
                if ($_SESSION['rol'] === 1) {
                    header("Location: IndexAdmin.php");
                    exit;
                } else if ($_SESSION['rol'] === 2) {
                    header("Location: Index.php");
                    exit;
                } else {
                    $mensaje = "⚠️ Rol no válido";
                }
            } else {
                $mensaje = "⚠️ Contraseña incorrecta";
            }

        } else {
            $mensaje = "⚠️ Usuario no encontrado";
        }
    } catch (Exception $e) {
        $mensaje = "Error de conexión: " . $e->getMessage();
    } finally {
        // Cerrar la sentencia de forma segura
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Login - Tienda Deportiva</title>
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
 <link rel="stylesheet" href="estilos.css">
</head>
<body class="login-page">

<div class="login-card">
 <div class="icon-sport">⚽</div>
 <h2>Iniciar Sesión</h2>
 <p class="text-muted">Bienvenido a la Tienda Deportiva</p>

 <?php if ($mensaje): ?>
 <div class="alert alert-danger"><?= htmlspecialchars($mensaje) ?></div>
 <?php endif; ?>

 <form method="POST" action="">
 <div class="mb-3">
 <input type="email" class="form-control" name="correo" placeholder="Correo electrónico" required>
 </div>
 <div class="mb-3">
 <input type="password" class="form-control" name="clave" placeholder="Contraseña" required>
</div>
<button type="submit" class="btn btn-login w-100 text-white">Ingresar</button>
 </form>

<div class="mt-3">
 <a href="RegistroUsuario.php" class="link-primary">¿No tienes cuenta? Regístrate</a>
</div>
 </div>
</body>
</html>