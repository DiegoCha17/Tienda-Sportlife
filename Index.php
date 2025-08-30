<?php require_once "Conexionbd.php"; ?>
<?php session_start(); ?>

<!DOCTYPE html>
<html lang="es">
    
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SportLife | Tu Tienda de Deportes</title>
    <!-- Carga de Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Carga de Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="estilos.css" rel="stylesheet">
    
    
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
<body class="index-page font-inter antialiased">

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
            <a href="perfil_usuario.php" class="p-2 rounded-full hover-lightGreen text-primary transition-colors duration-300">
                <i class="fa-solid fa-user fa-icon"></i>
            </a>
            <!-- Iconos de Usuario y Carrito -->
        <div class="flex items-center space-x-4">
            <!-- Icono de usuario con enlace al login/registro -->
            <?php if(isset($_SESSION['correo'])): ?>
                <a href="logout.php" class="p-2 rounded-full hover:bg-lightGreen transition-colors duration-300">
                    <i class="fa-solid fa-right-from-bracket fa-icon"></i>
                </a>
            <?php else: ?>
                <a href="auth.html" class="p-2 rounded-full hover:bg-lightGreen transition-colors duration-300">
                    <i class="fa-solid fa-user fa-icon"></i>
                </a>
            <?php endif; ?>
            
            <!-- Icono de carrito -->
            <a href="Carritos.php" class="p-2 rounded-full hover:bg-lightGreen transition-colors duration-300 relative">
                <i class="fa-solid fa-cart-shopping fa-icon"></i>
                <span class="absolute -top-1 -right-1 bg-secondary text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center" id="cartCount">0</span>
            </a>
            <!-- Botón de Menú Móvil -->
            <button class="md:hidden p-2 rounded-full hover:bg-lightGreen transition-colors duration-300" id="mobile-menu-button">
                <i class="fa-solid fa-bars fa-icon"></i>
            </button>
    
        </div>
    </header>

    
    <!-- Menú Móvil (Oculto por defecto) -->
    <nav id="mobile-menu" class="hidden md:hidden bg-white shadow-lg py-4 rounded-b-xl mx-4">
        <ul class="flex flex-col items-center space-y-4">
            <li><a href="Index.php" class="block text-primary font-semibold transition-colors duration-300 text-lg">Inicio</a></li>
            <li><a href="indexProductos.php" class="block text-dark hover:text-primary transition-colors duration-300 font-medium text-lg">Todos</a></li>
            <li><a href="indexRopa.php" class="block text-dark hover:text-primary transition-colors duration-300 font-medium text-lg">Ropa</a></li>
            <li><a href="indexCalzado.php" class="block text-dark hover:text-primary transition-colors duration-300 font-medium text-lg">Calzado</a></li>
            <li><a href="indexAccesorios.php" class="block text-dark hover:text-primary transition-colors duration-300 font-medium text-lg">Accesorios</a></li>
            <li><a href="indexGym.php" class="block text-dark hover:text-primary transition-colors duration-300 font-medium text-lg">Gym</a></li>
            <li><a href="indexSuplementos.php" class="block text-dark hover:text-primary transition-colors duration-300 font-medium text-lg">Suplementos</a></li>
        </ul>
    </nav>

        
        <div class="filters-container mb-6" style="display: none;">
            <div class="flex flex-wrap justify-center gap-2">
                <button onclick="filterByCategory('all')" class="filter-btn active">Todos</button>
                <button onclick="filterByCategory('1')" class="filter-btn">Ropa</button>
                <button onclick="filterByCategory('2')" class="filter-btn">Calzado</button>
                <button onclick="filterByCategory('3')" class="filter-btn">Accesorios</button>
                <button onclick="filterByCategory('4')" class="filter-btn">Suplementos</button>
                <button onclick="filterByCategory('5')" class="filter-btn">Gym</button>
            </div>
        </div>

    <!-- Sección Hero / Banner Principal -->
    <section class="relative bg-gradient-to-r from-primary to-secondary text-white py-20 px-6 md:px-12 mt-8 mx-4 rounded-2xl overflow-hidden shadow-xl">
        <div class="absolute inset-0 bg-cover bg-center opacity-20" style="background-image: url('https://placehold.co/1200x600/22C55E/FFFFFF?text=Equipamiento+Deportivo');"></div>
        <div class="relative max-w-4xl mx-auto text-center">
            <h2 class="text-4xl md:text-5xl font-extrabold leading-tight mb-4 drop-shadow-md">
                ¡Equípate para Rendir al Máximo! 🚀
            </h2>
            <p class="text-lg md:text-xl mb-8 drop-shadow-sm">
                Descubre la mejor selección de ropa, calzado y accesorios deportivos con un diseño innovador.
            </p>
            <a href="indexProductos.php" class="inline-block bg-white text-dark font-bold py-3 px-8 rounded-full text-lg shadow-lg hover:scale-105 transition-transform duration-300 ease-in-out transform hover:ring-4 hover:ring-lightBlue hover:ring-opacity-50">
                Explorar Productos
            </a>
        </div>
    </section>

    <!-- Sección de Productos Destacados -->
    <section class="py-16 px-6 md:px-12">
        <h2 class="text-3xl font-bold text-center text-dark mb-10">Nuestros Productos Destacados</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <!-- Tarjeta de Producto 1 -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:scale-105 transition-transform duration-300 ease-in-out border-b-4 border-primary hover:border-secondary">
                <div class="w-full h-48 bg-lightGreen flex items-center justify-center">
                    <img src="https://placehold.co/400x300/22C55E/FFFFFF?text=Zapatillas+Running" alt="Zapatillas de Running" class="object-cover w-full h-full">
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-dark mb-2">Zapatillas de Running Ultralight</h3>
                    <p class="text-gray-600 text-sm mb-3">Diseñadas para máxima velocidad y confort en cada pisada.</p>
                    <div class="flex justify-between items-center">
                        <span class="text-primary text-2xl font-bold">₡199.99</span>
                        <button class="bg-secondary text-white px-4 py-2 rounded-full text-sm font-medium hover:bg-darkBlue transition-colors duration-300 shadow-md"
                            onclick="window.location.href='indexCalzado.php'">
                            Ver Calzado
                        </button>

                    </div>
                </div>
            </div>

            <!-- Tarjeta de Producto 2 -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:scale-105 transition-transform duration-300 ease-in-out border-b-4 border-secondary hover:border-primary">
                <div class="w-full h-48 bg-lightBlue flex items-center justify-center">
                    <img src="https://placehold.co/400x300/3B82F6/FFFFFF?text=Camiseta+Deportiva" alt="Camiseta Deportiva" class="object-cover w-full h-full">
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-dark mb-2">Camiseta Deportiva Transpirable</h3>
                    <p class="text-gray-600 text-sm mb-3">Ideal para cualquier tipo de entrenamiento, te mantiene fresco.</p>
                    <div class="flex justify-between items-center">
                        <span class="text-primary text-2xl font-bold">₡129.99</span>
                        <button class="bg-secondary text-white px-4 py-2 rounded-full text-sm font-medium hover:bg-darkBlue transition-colors duration-300 shadow-md"
                            onclick="window.location.href='indexRopa.php'">
                            Ver Ropa
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tarjeta de Producto 3 -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:scale-105 transition-transform duration-300 ease-in-out border-b-4 border-primary hover:border-secondary">
                <div class="w-full h-48 bg-lightGreen flex items-center justify-center">
                    <img src="https://placehold.co/400x300/22C55E/FFFFFF?text=Mochila+Gym" alt="Mochila de Gym" class="object-cover w-full h-full">
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-dark mb-2">Mochila de Gimnasio Premium</h3>
                    <p class="text-gray-600 text-sm mb-3">Gran capacidad y compartimentos especializados para tu equipo.</p>
                    <div class="flex justify-between items-center">
                        <span class="text-primary text-2xl font-bold">₡149.99</span>
                        <button class="bg-secondary text-white px-4 py-2 rounded-full text-sm font-medium hover:bg-darkBlue transition-colors duration-300 shadow-md"
                            onclick="window.location.href='indexGym.php'">
                            Ver Gym
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tarjeta de Producto 4 -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:scale-105 transition-transform duration-300 ease-in-out border-b-4 border-secondary hover:border-primary">
                <div class="w-full h-48 bg-lightBlue flex items-center justify-center">
                    <img src="https://placehold.co/400x300/3B82F6/FFFFFF?text=Reloj+Deportivo" alt="Reloj Deportivo" class="object-cover w-full h-full">
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-dark mb-2">Smartwatch Deportivo Avanzado</h3>
                    <p class="text-gray-600 text-sm mb-3">Monitorea tu rendimiento con precisión y estilo.</p>
                    <div class="flex justify-between items-center">
                        <span class="text-primary text-2xl font-bold">₡149.99</span>
                        <button class="bg-secondary text-white px-4 py-2 rounded-full text-sm font-medium hover:bg-darkBlue transition-colors duration-300 shadow-md"
                            onclick="window.location.href='indexAccesorios.php'">
                            Ver Accesorios
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-12">
            <a href="indexProductos.php" class="inline-block bg-dark text-white font-bold py-3 px-8 rounded-full text-lg shadow-lg hover:bg-darkBlue transition-colors duration-300">
                Ver Todo el Catálogo
            </a>
        </div>
    </section>

    <!-- Sección de Suscripción a Newsletter -->
    <section class="bg-gradient-to-r from-primary to-secondary text-white py-16 px-6 md:px-12 mt-8 mx-4 rounded-2xl shadow-xl text-center">
        <h2 class="text-3xl font-bold mb-4">¡No te pierdas nuestras ofertas exclusivas! 🎁</h2>
        <p class="text-lg mb-8">Suscríbete a nuestro newsletter para recibir descuentos y novedades directamente en tu bandeja de entrada.</p>
        <form class="max-w-xl mx-auto flex flex-col sm:flex-row gap-4">
            <input 
                type="email" 
                placeholder="Tu correo electrónico" 
                class="flex-grow p-4 rounded-full border border-white bg-white bg-opacity-20 placeholder-white placeholder-opacity-70 focus:outline-none focus:ring-2 focus:ring-white text-white text-lg"
            >
            <button 
                type="submit" 
                class="bg-dark text-white font-bold py-4 px-8 rounded-full text-lg shadow-lg hover:scale-105 transition-transform duration-300 ease-in-out hover:bg-darkBlue"
            >
                Suscribirme
            </button>
        </form>
    </section>

    <!-- Pie de Página (Footer) -->
    <footer class="bg-dark text-white py-12 px-6 md:px-12 mt-16 rounded-t-xl">
        <div class="container mx-auto grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
                <h3 class="text-xl font-bold mb-4">SportLife</h3>
                <p class="text-gray-400 text-sm">Tu destino #1 para equipamiento deportivo de alta calidad y rendimiento.</p>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-4">Navegación</h3>
                <ul class="space-y-2">
                    <li><a href="Index.php" class="text-gray-400 hover:text-primary transition-colors duration-300">Inicio</a></li>
                    <li><a href="indexProductos.php" class="text-gray-400 hover:text-primary transition-colors duration-300">Catálogo Completo</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-primary transition-colors duration-300">Sobre Nosotros</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-primary transition-colors duration-300">Contacto</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-4">Ayuda</h3>
                <ul class="space-y-2">
                    <li><a href="#" class="text-gray-400 hover:text-primary transition-colors duration-300">Preguntas Frecuentes</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-primary transition-colors duration-300">Envío y Devoluciones</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-primary transition-colors duration-300">Términos y Condiciones</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-primary transition-colors duration-300">Política de Privacidad</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-4">Contáctanos</h3>
                <p class="text-gray-400">Email: info@sportlife.com</p>
                <p class="text-gray-400">Teléfono: +506 2200-0000</p>
                <div class="flex space-x-4 mt-4">
                    <!-- Iconos de Redes Sociales -->
                    <a href="#" class="text-gray-400 hover:text-secondary transition-colors duration-300"><i class="fab fa-facebook-f fa-lg"></i></a>
                    <a href="#" class="text-gray-400 hover:text-secondary transition-colors duration-300"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="text-gray-400 hover:text-secondary transition-colors duration-300"><i class="fab fa-twitter fa-lg"></i></a>
                </div>
            </div>
        </div>
        <div class="text-center text-gray-500 text-sm mt-10 border-t border-gray-700 pt-8">
            &copy; 2025 SportLife. Todos los derechos reservados.
        </div>
    </footer>

    <!-- Cargar JavaScript del carrito -->
    <script src="carrito.js"></script>

    <!-- JavaScript para el menú móvil MEJORADO -->
    <script>
        // Manejo del menú móvil
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            const overlay = document.getElementById('mobile-menu-overlay');
            const button = this;
            
            if (mobileMenu.classList.contains('hidden')) {
                // Abrir menú
                mobileMenu.classList.remove('hidden');
                overlay.classList.add('active');
                // Cambiar icono a X
                button.innerHTML = '<i class="fa-solid fa-xmark fa-icon"></i>';
                // Prevenir scroll del body
                document.body.style.overflow = 'hidden';
            } else {
                // Cerrar menú
                mobileMenu.classList.add('hidden');
                overlay.classList.remove('active');
                // Cambiar icono a hamburguesa
                button.innerHTML = '<i class="fa-solid fa-bars fa-icon"></i>';
                // Restaurar scroll del body
                document.body.style.overflow = '';
            }
        });

        // Cerrar menú móvil al hacer clic en un enlace
        document.querySelectorAll('#mobile-menu a').forEach(link => {
            link.addEventListener('click', function() {
                const mobileMenu = document.getElementById('mobile-menu');
                const overlay = document.getElementById('mobile-menu-overlay');
                const button = document.getElementById('mobile-menu-button');
                
                // Cerrar menú
                mobileMenu.classList.add('hidden');
                overlay.classList.remove('active');
                // Restaurar icono hamburguesa
                button.innerHTML = '<i class="fa-solid fa-bars fa-icon"></i>';
                // Restaurar scroll del body
                document.body.style.overflow = '';
            });
        });

        // Cerrar menú móvil al hacer clic en el overlay
        document.getElementById('mobile-menu-overlay').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            const button = document.getElementById('mobile-menu-button');
            
            // Cerrar menú
            mobileMenu.classList.add('hidden');
            this.classList.remove('active');
            // Restaurar icono hamburguesa
            button.innerHTML = '<i class="fa-solid fa-bars fa-icon"></i>';
            // Restaurar scroll del body
            document.body.style.overflow = '';
        });

        // Cerrar menú móvil con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const mobileMenu = document.getElementById('mobile-menu');
                const overlay = document.getElementById('mobile-menu-overlay');
                const button = document.getElementById('mobile-menu-button');
                
                if (!mobileMenu.classList.contains('hidden')) {
                    mobileMenu.classList.add('hidden');
                    overlay.classList.remove('active');
                    button.innerHTML = '<i class="fa-solid fa-bars fa-icon"></i>';
                    document.body.style.overflow = '';
                }
            }
        });

        // Actualizar contador del carrito al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof carrito !== 'undefined') {
                carrito.actualizarContador();
            }
        });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', async () => {
    // Asegura que la clase Carrito esté disponible
    if (window.carrito && typeof carrito.syncFromServer === 'function') {
    await carrito.syncFromServer();   
    carrito.refreshUI();             
  }
});
</script>

</body>
</html>