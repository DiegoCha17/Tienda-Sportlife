<?php 
require_once "Conexionbd.php"; 
session_start(); 

// Verificar si el usuario está logueado
if (!isset($_SESSION['correo'])) {
    header("Location: login.php");
    exit();
}

// Obtener los datos del usuario desde la base de datos
$correo_usuario = $_SESSION['correo'];
$sql = "SELECT * FROM usuarios WHERE correo = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $correo_usuario);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();
} else {
    // Si no se encuentra el usuario, cerrar sesión y redirigir
    session_destroy();
    header("Location: login.php");
    exit();
}

// Procesar actualización de datos si se envió el formulario
$mensaje = "";
if ($_POST && isset($_POST['actualizar_perfil'])) {
    $nuevo_nombre = $_POST['nombre'];
    $nuevo_apellido = $_POST['apellido'];
    $nuevo_telefono = $_POST['telefono'];
    $nueva_clave = $_POST['clave'];
    
    // Si se proporciona una nueva clave, encriptarla
    if (!empty($nueva_clave)) {
        $nueva_clave = password_hash($nueva_clave, PASSWORD_DEFAULT);
        $sql_update = "UPDATE usuarios SET nombre = ?, Apellido = ?, telefono = ?, clave = ? WHERE correo = ?";
        $stmt_update = $mysqli->prepare($sql_update);
        $stmt_update->bind_param("sssss", $nuevo_nombre, $nuevo_apellido, $nuevo_telefono, $nueva_clave, $correo_usuario);
    } else {
        $sql_update = "UPDATE usuarios SET nombre = ?, apellido = ?, telefono = ? WHERE correo = ?";
        $stmt_update = $mysqli->prepare($sql_update);
        $stmt_update->bind_param("ssss", $nuevo_nombre, $nuevo_apellido, $nuevo_telefono, $correo_usuario);
    }
    
    if ($stmt_update->execute()) {
        $mensaje = "Perfil actualizado correctamente";
        // Actualizar los datos del usuario en la variable
        $usuario['nombre'] = $nuevo_nombre;
        $usuario['Apellido'] = $nuevo_apellido;
        $usuario['telefono'] = $nuevo_telefono;
    } else {
        $mensaje = "Error al actualizar el perfil";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil | SportLife</title>
    <!-- Carga de Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Carga de Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Configuración de Tailwind -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        inter: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#22C55E',
                        secondary: '#3B82F6',
                        dark: '#1E3A8A',
                        light: '#FFFFFF',
                        lightGreen: '#D1FAE5',
                        darkGreen: '#16A34A',
                        lightBlue: '#BFDBFE',
                        darkBlue: '#1F2937',
                    }
                }
            }
        }
    </script>
</head>
<body class="font-inter antialiased bg-gray-50">

    <!-- Encabezado (Header) -->
    <header class="bg-white shadow-md py-4 px-6 md:px-12 flex justify-between items-center rounded-b-xl">
        <div class="flex items-center">
            <a href="Index.php" class="text-2xl font-bold text-dark flex items-center">
                <span class="text-primary mr-2 text-3xl">🏃‍♂️</span> SportLife
            </a>
        </div>
        
        <!-- Navegación Principal (Escritorio) -->
        <nav class="hidden md:flex space-x-8">
            <a href="Index.php" class="text-dark hover:text-primary transition-colors duration-300 font-medium">Inicio</a>
            <a href="indexProductos.php" class="text-dark hover:text-primary transition-colors duration-300 font-medium">Todos</a>
            <a href="indexRopa.php" class="text-dark hover:text-primary transition-colors duration-300 font-medium">Ropa</a>
            <a href="indexCalzado.php" class="text-dark hover:text-primary transition-colors duration-300 font-medium">Calzado</a>
            <a href="indexAccesorios.php" class="text-dark hover:text-primary transition-colors duration-300 font-medium">Accesorios</a>
            <a href="indexGym.php" class="text-dark hover:text-primary transition-colors duration-300 font-medium">Gym</a>
            <a href="indexSuplementos.php" class="text-dark hover:text-primary transition-colors duration-300 font-medium">Suplementos</a>
        </nav>

        <!-- Iconos de Usuario y Carrito -->
        <div class="flex items-center space-x-4">
            <a href="perfil_usuario.php" class="p-2 rounded-full bg-lightGreen text-primary transition-colors duration-300">
                <i class="fa-solid fa-user fa-icon"></i>
            </a>
            <a href="Carritos.php" class="p-2 rounded-full hover:bg-lightGreen transition-colors duration-300 relative">
                <i class="fa-solid fa-cart-shopping fa-icon"></i>
                <span class="absolute -top-1 -right-1 bg-secondary text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center" data-cart-count></span>
            </a>
            <a href="logout.php" class="p-2 rounded-full hover:bg-red-100 text-red-600 transition-colors duration-300">
                <i class="fa-solid fa-right-from-bracket fa-icon"></i>
            </a>
        </div>



    </header>

    <!-- Contenido Principal -->
    <main class="container mx-auto px-6 md:px-12 py-8">
        
        <!-- Título de la página -->
        <div class="bg-gradient-to-r from-primary to-secondary text-white py-8 px-6 rounded-2xl mb-8 text-center shadow-xl">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Mi Perfil</h1>
            <p class="text-lg">Gestiona tu información personal</p>
        </div>

        <!-- Mostrar mensaje si existe -->
        <?php if (!empty($mensaje)): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo strpos($mensaje, 'Error') !== false ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-green-100 text-green-700 border border-green-200'; ?>">
                <div class="flex items-center">
                    <i class="fas <?php echo strpos($mensaje, 'Error') !== false ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Información del Usuario -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-lg p-6 border-b-4 border-primary">
                    <div class="text-center mb-6">
                        <div class="w-24 h-24 bg-gradient-to-r from-primary to-secondary rounded-full flex items-center justify-center mx-auto mb-4 text-white text-3xl">
                            <i class="fas fa-user"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-dark"><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['Apellido']); ?></h2>
                        <p class="text-gray-600"><?php echo htmlspecialchars($usuario['correo']); ?></p>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-center p-3 bg-lightGreen rounded-lg">
                            <i class="fas fa-envelope text-primary mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-600">Correo Electrónico</p>
                                <p class="font-medium text-dark"><?php echo htmlspecialchars($usuario['correo']); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-center p-3 bg-lightBlue rounded-lg">
                            <i class="fas fa-phone text-secondary mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-600">Teléfono</p>
                                <p class="font-medium text-dark"><?php echo !empty($usuario['telefono']) ? htmlspecialchars($usuario['telefono']) : 'No especificado'; ?></p>
                            </div>
                        </div>

                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-id-card text-gray-500 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-600">ID de Usuario</p>
                                <p class="font-medium text-dark">#<?php echo htmlspecialchars($usuario['id']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de Edición -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-lg p-6 border-b-4 border-secondary">
                    <h3 class="text-2xl font-bold text-dark mb-6 flex items-center">
                        <i class="fas fa-edit text-secondary mr-3"></i>
                        Editar Información
                    </h3>
                    
                    <form method="POST" action="" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                                <input 
                                    type="text" 
                                    id="nombre" 
                                    name="nombre" 
                                    value="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors duration-300"
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="apellido" class="block text-sm font-medium text-gray-700 mb-2">Apellido</label>
                                <input 
                                    type="text" 
                                    id="apellido" 
                                    name="apellido" 
                                    value="<?php echo htmlspecialchars($usuario['Apellido']); ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors duration-300"
                                    required
                                >
                            </div>
                        </div>

                        <div>
                            <label for="correo" class="block text-sm font-medium text-gray-700 mb-2">Correo Electrónico</label>
                            <input 
                                type="email" 
                                id="correo" 
                                name="correo" 
                                value="<?php echo htmlspecialchars($usuario['correo']); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed"
                                disabled
                            >
                            <p class="text-sm text-gray-500 mt-1">El correo electrónico no se puede modificar</p>
                        </div>

                        <div>
                            <label for="telefono" class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                            <input 
                                type="tel" 
                                id="telefono" 
                                name="telefono" 
                                value="<?php echo htmlspecialchars($usuario['telefono']); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors duration-300"
                                placeholder="Ej: +506 8888-8888"
                            >
                        </div>

                        <div>
                            <label for="clave" class="block text-sm font-medium text-gray-700 mb-2">Nueva Contraseña</label>
                            <input 
                                type="password" 
                                id="clave" 
                                name="clave" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary transition-colors duration-300"
                                placeholder="Dejar en blanco para mantener la actual"
                            >
                            <p class="text-sm text-gray-500 mt-1">Solo completa si deseas cambiar tu contraseña</p>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-4 pt-4">
                            <button 
                                type="submit" 
                                name="actualizar_perfil"
                                class="flex-1 bg-gradient-to-r from-primary to-secondary text-white font-bold py-3 px-6 rounded-lg shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 flex items-center justify-center"
                            >
                                <i class="fas fa-save mr-2"></i>
                                Guardar Cambios
                            </button>
                            
                            <a 
                                href="Index.php"
                                class="flex-1 bg-gray-500 text-white font-bold py-3 px-6 rounded-lg shadow-lg hover:bg-gray-600 transform hover:scale-105 transition-all duration-300 flex items-center justify-center text-center"
                            >
                                <i class="fas fa-arrow-left mr-2"></i>
                                Volver al Inicio
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sección de Acciones Adicionales -->
        <div class="mt-8 bg-white rounded-lg shadow-lg p-6 border-b-4 border-primary">
            <h3 class="text-2xl font-bold text-dark mb-4 flex items-center">
                <i class="fas fa-cogs text-primary mr-3"></i>
                Acciones de Cuenta
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="historial_pedidos.php" class="bg-lightGreen hover:bg-green-200 p-4 rounded-lg transition-colors duration-300 text-center">
                    <i class="fas fa-history text-primary text-2xl mb-2 block"></i>
                    <span class="font-medium text-dark">Historial de Pedidos</span>
                </a>
                
                <a href="Carritos.php" class="bg-lightBlue hover:bg-blue-200 p-4 rounded-lg transition-colors duration-300 text-center">
                    <i class="fas fa-shopping-cart text-secondary text-2xl mb-2 block"></i>
                    <span class="font-medium text-dark">Mi Carrito</span>
                </a>
                
                <a href="logout.php" class="bg-red-100 hover:bg-red-200 p-4 rounded-lg transition-colors duration-300 text-center">
                    <i class="fas fa-sign-out-alt text-red-600 text-2xl mb-2 block"></i>
                    <span class="font-medium text-red-600">Cerrar Sesión</span>
                </a>
            </div>
        </div>
    </main>

    <!-- Pie de Página (Footer) -->
    <footer class="bg-dark text-white py-12 px-6 md:px-12 mt-16 rounded-t-xl">
        <div class="container mx-auto text-center">
            <div class="text-gray-500 text-sm">
                &copy; 2025 SportLife. Todos los derechos reservados.
            </div>
        </div>
    </footer>

     <script src="carrito.js"></script>

</body>
</html>