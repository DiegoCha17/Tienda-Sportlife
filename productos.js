
class Productos {
    constructor() {
        this.productos = [];
        this.categoriaActual = 'todos';
        this.contenedorGrid = null;
    }

    
    inicializar(contenedorId = 'productsGrid') {
        console.log('📦 Inicializando sistema de productos...');
        this.contenedorGrid = document.getElementById(contenedorId);
        
        if (!this.contenedorGrid) {
            console.error('❌ Contenedor de productos no encontrado:', contenedorId);
            return false;
        }
        
        console.log('✅ Sistema de productos listo');
        return true;
    }

   
    async cargarTodos() {
        console.log('📦 Cargando todos los productos...');
        try {
            const respuesta = await fetch('obtener_todos_productos.php');
            
            if (!respuesta.ok) {
                throw new Error(`HTTP ${respuesta.status}: ${respuesta.statusText}`);
            }
            
            const datos = await respuesta.json();
            
            if (datos.success) {
                this.productos = datos.productos;
                this.categoriaActual = 'todos';
                console.log(`✅ ${this.productos.length} productos cargados`);
                this.renderizar();
                return this.productos;
            } else {
                throw new Error(datos.message);
            }
        } catch (error) {
            console.error('❌ Error cargando todos los productos:', error);
            this.mostrarError('Error al cargar productos: ' + error.message);
            return [];
        }
    }

    async cargarPorCategoria(categoriaId, nombreCategoria = '') {
        console.log(`📂 Cargando productos de categoría ${categoriaId}...`);
        try {
            const respuesta = await fetch(`obtener_productos_categoria.php?categoria=${categoriaId}`);
            
            if (!respuesta.ok) {
                throw new Error(`HTTP ${respuesta.status}: ${respuesta.statusText}`);
            }
            
            const datos = await respuesta.json();
            
            if (datos.success) {
                this.productos = datos.productos;
                this.categoriaActual = categoriaId;
                console.log(`✅ ${this.productos.length} productos de ${nombreCategoria} cargados`);
                this.renderizar();
                return this.productos;
            } else {
                throw new Error(datos.message);
            }
        } catch (error) {
            console.error(`❌ Error cargando categoría ${categoriaId}:`, error);
            this.mostrarError('Error al cargar productos: ' + error.message);
            return [];
        }
    }

    
    renderizar(productsToRender = this.productos) {
        if (!this.contenedorGrid) {
            console.error('❌ No hay contenedor para renderizar');
            return;
        }

        if (productsToRender.length === 0) {
            this.mostrarEstadoVacio();
            return;
        }

        this.contenedorGrid.innerHTML = productsToRender.map(producto => 
            this.crearTarjetaProducto(producto)
        ).join('');
    }

   
    crearTarjetaProducto(producto) {
        const imagenProducto = this.procesarImagen(producto.imagen, producto.categoria_id);
        const stockClass = this.obtenerClaseStock(producto.cantidad);
        const stockTexto = this.obtenerTextoStock(producto.cantidad);
        const botonTexto = producto.cantidad > 0 ? 'Añadir al Carrito' : 'Sin Stock';
        const botonDisabled = producto.cantidad <= 0 ? 'disabled' : '';
        const botonClass = producto.cantidad <= 0 ? 'disabled' : '';

        return `
            <div class="product-card" data-category="${producto.categoria_id || producto.id_categoria}">
                ${imagenProducto}
                <div class="product-info">
                    <div class="product-name">${this.escaparTexto(producto.nombre)}</div>
                    <div class="product-description">
                        ${producto.marca_nombre ? `${producto.marca_nombre}` : 'Producto deportivo de calidad'}
                        ${producto.categoria_nombre ? ` • ${producto.categoria_nombre}` : ''}
                    </div>
                    <div class="product-details">
                        <div class="product-price">₡${parseFloat(producto.precio).toFixed(2)}</div>
                        <div class="product-stock ${stockClass}">
                            ${stockTexto}
                        </div>
                    </div>
                    <button class="add-to-cart ${botonClass}" 
                            onclick="addToCart(${producto.id}, '${this.escaparTexto(producto.nombre)}', ${producto.precio}, '${producto.imagen || ''}', ${producto.cantidad})"
                            ${botonDisabled}>
                        <i class="fas fa-cart-plus"></i> ${botonTexto}
                    </button>
                </div>
            </div>
        `;
    }

    
    procesarImagen(imagen, categoriaId) {
        if (imagen) {
            return `
                <div class="product-image">
                    <img src="imagenes/${imagen}" alt="Producto" 
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div style="display:none; align-items:center; justify-content:center; height:100%; width:100%; color:white; font-size:3rem;">
                        ${this.obtenerIconoCategoria(categoriaId)}
                    </div>
                    ${this.categoriaActual === 'todos' ? this.obtenerBadgeCategoria(categoriaId) : ''}
                </div>
            `;
        } else {
            return `
                <div class="product-image">
                    <div style="display:flex; align-items:center; justify-content:center; height:100%; width:100%; color:white; font-size:3rem;">
                        ${this.obtenerIconoCategoria(categoriaId)}
                    </div>
                    ${this.categoriaActual === 'todos' ? this.obtenerBadgeCategoria(categoriaId) : ''}
                </div>
            `;
        }
    }

  
    obtenerIconoCategoria(categoriaId) {
        const iconos = {
            '1': '<i class="fas fa-tshirt"></i>',      // Ropa
            '2': '<i class="fas fa-shoe-prints"></i>', // Calzado
            '3': '<i class="fas fa-dumbbell"></i>',    // Gym 
            '4': '<i class="fas fa-pills"></i>',       // Suplementos
            '5': '<i class="fas fa-tools"></i>'        // Accesorios
        };
        return iconos[categoriaId] || '<i class="fas fa-box"></i>';
    }

   
    obtenerBadgeCategoria(categoriaId) {
        const categorias = {
            '1': 'Ropa',
            '2': 'Calzado',
            '3': 'Gym',        
            '4': 'Suplementos',
            '5': 'Accesorios'  
        };
        const nombreCategoria = categorias[categoriaId] || 'Producto';
        return `<div class="category-badge">${nombreCategoria}</div>`;
    }

    
    obtenerClaseStock(cantidad) {
        if (cantidad > 10) return 'stock-high';
        if (cantidad > 0) return 'stock-medium';
        return 'stock-low';
    }

    obtenerTextoStock(cantidad) {
        if (cantidad > 0) {
            return `${cantidad} disponibles`;
        }
        return 'Sin stock';
    }

    
    filtrarPorCategoria(categoriaId) {
        if (this.categoriaActual === 'todos') {
            // Si tenemos todos los productos cargados, filtrar localmente
            let productosFiltrados;
            if (categoriaId === 'todos' || categoriaId === 'all') {
                productosFiltrados = this.productos;
            } else {
                productosFiltrados = this.productos.filter(producto => 
                    (producto.categoria_id || producto.id_categoria) == categoriaId
                );
            }
            this.renderizar(productosFiltrados);
        } else {
            // Si no tenemos todos, cargar específicamente
            if (categoriaId === 'todos' || categoriaId === 'all') {
                this.cargarTodos();
            } else {
                this.cargarPorCategoria(categoriaId);
            }
        }
    }

   
    buscar(termino) {
        const terminoBusqueda = termino.toLowerCase().trim();
        
        if (terminoBusqueda === '') {
            this.renderizar();
            return;
        }

        const productosFiltrados = this.productos.filter(producto => 
            producto.nombre.toLowerCase().includes(terminoBusqueda) ||
            (producto.marca_nombre && producto.marca_nombre.toLowerCase().includes(terminoBusqueda)) ||
            (producto.categoria_nombre && producto.categoria_nombre.toLowerCase().includes(terminoBusqueda))
        );

        this.renderizar(productosFiltrados);
    }

    
    mostrarEstadoVacio() {
        const nombresCategoria = {
            'todos': 'productos',
            '1': 'ropa deportiva',
            '2': 'calzado deportivo', 
            '3': 'equipos de gym',  
            '4': 'suplementos',
            '5': 'accesorios'       
        };

        const iconosCategoria = {
            'todos': 'fas fa-search',
            '1': 'fas fa-tshirt',
            '2': 'fas fa-shoe-prints',
            '3': 'fas fa-dumbbell',  
            '4': 'fas fa-pills',
            '5': 'fas fa-tools'      
        };

        const nombreCategoria = nombresCategoria[this.categoriaActual] || 'productos';
        const iconoCategoria = iconosCategoria[this.categoriaActual] || 'fas fa-box';

        this.contenedorGrid.innerHTML = `
            <div class="empty-state">
                <i class="${iconoCategoria}"></i>
                <h3>No se encontraron ${nombreCategoria}</h3>
                <p>Lo sentimos, actualmente no tenemos ${nombreCategoria} disponibles en nuestro inventario.</p>
                ${this.categoriaActual !== 'todos' ? `
                    <button onclick="productos.cargarTodos()" class="filter-all-btn">
                        Ver todos los productos
                    </button>
                ` : ''}
            </div>
        `;
    }

    
    mostrarError(mensaje) {
        if (!this.contenedorGrid) return;

        this.contenedorGrid.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Error al cargar productos</h3>
                <p>${mensaje}</p>
                <button onclick="productos.recargar()" class="retry-btn">
                    <i class="fas fa-redo"></i> Reintentar
                </button>
            </div>
        `;
    }

    
    async recargar() {
        if (this.categoriaActual === 'todos') {
            return await this.cargarTodos();
        } else {
            return await this.cargarPorCategoria(this.categoriaActual);
        }
    }

    
    escaparTexto(texto) {
        if (!texto) return '';
        return texto.replace(/'/g, "\\'").replace(/"/g, '\\"');
    }

    obtenerProductos() {
        return this.productos;
    }

    obtenerCantidadProductos() {
        return this.productos.length;
    }

    obtenerCategoria() {
        return this.categoriaActual;
    }

    
    async cargarRopa() {
        return await this.cargarPorCategoria(1, 'ropa');
    }

    async cargarCalzado() {
        return await this.cargarPorCategoria(2, 'calzado');
    }

    async cargarGym() {
        return await this.cargarPorCategoria(3, 'gym');
    }

    async cargarSuplementos() {
        return await this.cargarPorCategoria(4, 'suplementos');
    }

    async cargarAccesorios() {
        return await this.cargarPorCategoria(5, 'accesorios');
    }
    
}

function loadAllProducts() {
    return productos.cargarTodos();
}

function loadRopaProducts() {
    return productos.cargarRopa();
}

function loadCalzadoProducts() {
    return productos.cargarCalzado();
}

function loadAccesoriosProducts() {
    return productos.cargarAccesorios();
}

function loadSuplementosProducts() {
    return productos.cargarSuplementos();
}

function loadGymProducts() {
    return productos.cargarGym();
}

function renderProducts() {
    productos.renderizar();
}

function filterByCategory(categoryId) {
    productos.filtrarPorCategoria(categoryId);
}

function showError(message) {
    productos.mostrarError(message);
}


let productos;


document.addEventListener('DOMContentLoaded', function() {
    productos = new Productos();
    productos.inicializar();
});