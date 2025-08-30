/*
 *SISTEMA DE ADMINISTRACIÓN DE PRODUCTOS
 */

class Administrador {
    constructor() {
        this.productos = [];
        this.categorias = [];
        this.marcas = [];
        this.productoEditandoId = null;
        this.modoActual = null; // 'agregar' o 'editar'
    }

    /*
     INICIALIZACIÓN DEL SISTEMA
     */
    async inicializar() {
        console.log('🚀 Inicializando Panel de Administración...');
        
        try {
            await Promise.all([
                this.cargarCategorias(),
                this.cargarMarcas(),
                this.cargarProductos()
            ]);
            
            this.configurarEventos();
            console.log('✅ Panel de Administración listo');
        } catch (error) {
            console.error('❌ Error inicializando admin:', error);
            this.mostrarNotificacion('Error al inicializar el panel', 'error');
        }
    }
    /*
     GESTIÓN DE PRODUCTOS
     */
    async cargarProductos() {
        try {
            const respuesta = await fetch('obtener_todos_productos.php');
            const datos = await respuesta.json();
            
            if (datos.success) {
                this.productos = datos.productos;
                this.renderizarProductos();
                this.actualizarEstadisticas();
                console.log(`📦 ${this.productos.length} productos cargados`);
            } else {
                throw new Error(datos.message);
            }
        } catch (error) {
            console.error('Error cargando productos:', error);
            this.mostrarNotificacion('Error al cargar productos: ' + error.message, 'error');
        }
    }

    async cargarCategorias() {
        try {
            const respuesta = await fetch('obtener_categorias.php');
            const datos = await respuesta.json();
            
            if (datos.success) {
                this.categorias = datos.categorias;
                this.llenarSelectCategorias();
                console.log(`📂 ${this.categorias.length} categorías cargadas`);
            }
        } catch (error) {
            console.error('Error cargando categorías:', error);
        }
    }

    async cargarMarcas() {
        try {
            const respuesta = await fetch('obtener_marcas.php');
            const datos = await respuesta.json();
            
            if (datos.success) {
                this.marcas = datos.marcas;
                this.llenarSelectMarcas();
                console.log(`🏷️ ${this.marcas.length} marcas cargadas`);
            }
        } catch (error) {
            console.error('Error cargando marcas:', error);
        }
    }

    /*
      RENDERIZADO DE PRODUCTOS
     */
    renderizarProductos(productosAMostrar = this.productos) {
        const grid = document.getElementById('productsGrid');
        
        if (productosAMostrar.length === 0) {
            grid.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No se encontraron productos</h3>
                    <p>Comienza agregando tu primer producto</p>
                </div>
            `;
            return;
        }

        grid.innerHTML = productosAMostrar.map(producto => {
            // Normalizar ruta de imagen
            let rutaImagen = producto.imagen ? `imagenes/${producto.imagen.replace(/\\/g, "/")}` : null;

            return `
                <div class="product-card">
                    <div class="product-image ${rutaImagen ? '' : 'no-image'}" 
                         ${rutaImagen ? `style="background-image: url('${rutaImagen}')"` : ''}>
                        ${!rutaImagen ? `<i class="fas fa-image"></i>` : ''}
                    </div>
                    <div class="product-info">
                        <div class="product-name">${producto.nombre}</div>
                        <div class="product-price">₡${parseFloat(producto.precio).toFixed(2)}</div>
                        <div class="product-description">
                            <strong>Categoría:</strong> ${producto.categoria_nombre}<br>
                            <strong>Marca:</strong> ${producto.marca_nombre}<br>
                            <strong>Stock:</strong> <span style="color: ${producto.cantidad < 10 ? '#e74c3c' : '#27ae60'}">${producto.cantidad} unidades</span><br>
                            <strong>Estado:</strong> <span style="color: ${producto.activo == 1 ? '#27ae60' : '#e74c3c'}">${producto.activo == 1 ? 'Activo' : 'Inactivo'}</span>
                        </div>
                        <div class="product-actions">
                            <button class="btn btn-warning btn-small" onclick="admin.editarProducto(${producto.id})">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="btn btn-danger btn-small" onclick="admin.eliminarProducto(${producto.id})">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    /*
      GESTIÓN DE MODALES 
     */
    abrirModal(modo, productoId = null) {
        if (modo === 'add') modo = 'agregar';
        if (modo === 'edit') modo = 'editar';
        
        this.modoActual = modo;
        const modal = document.getElementById('productModal');
        const modalTitle = document.getElementById('modalTitle');
        const formulario = document.getElementById('productForm');
        
        // LIMPIAR COMPLETAMENTE EL FORMULARIO PRIMERO
        this.limpiarFormularioCompleto();
        
        if (modo === 'agregar') {
            modalTitle.innerHTML = '<i class="fas fa-plus"></i> Agregar Producto';
            this.productoEditandoId = null;
            document.getElementById('productId').value = '';
            console.log('➕ Modo: AGREGAR producto');
        } else if (modo === 'editar') {
            modalTitle.innerHTML = '<i class="fas fa-edit"></i> Editar Producto';
            const producto = this.productos.find(p => p.id == productoId);
            if (producto) {
                this.llenarFormularioProducto(producto);
                this.productoEditandoId = productoId;
                console.log('✏️ Modo: EDITAR producto ID:', productoId);
            }
        } else {
            console.error('❌ Modo no válido:', modo);
            this.mostrarNotificacion('Error: Modo no válido: ' + modo, 'error');
            return;
        }
        
        // Mostrar modal
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        
        // Focus en el primer campo
        setTimeout(() => {
            document.getElementById('productName').focus();
        }, 300);
    }

    /*
     NUEVA FUNCIÓN: Limpiar formulario completamente
     */
    limpiarFormularioCompleto() {
        const formulario = document.getElementById('productForm');
        const currentImagePreview = document.getElementById('currentImagePreview');
        
        // Resetear formulario HTML
        formulario.reset();
        
        // Limpiar campos específicos manualmente
        document.getElementById('productId').value = '';
        document.getElementById('productName').value = '';
        document.getElementById('productPrice').value = '';
        document.getElementById('productQuantity').value = '';
        
        // Resetear selects a la primera opción
        document.getElementById('productCategory').selectedIndex = 0;
        document.getElementById('productBrand').selectedIndex = 0;
        document.getElementById('productStatus').selectedIndex = 0;
        
        // LIMPIAR INPUT DE ARCHIVO 
        const imageInput = document.getElementById('productImage');
        if (imageInput) {
            imageInput.value = '';
            imageInput.files = null;
        }
        
        // Ocultar preview de imagen
        if (currentImagePreview) {
            currentImagePreview.style.display = 'none';
        }
        
        console.log('🧹 Formulario limpiado completamente');
    }

    cerrarModal() {
        const modal = document.getElementById('productModal');
        modal.style.display = 'none';
        document.body.style.overflow = '';
        
        // Limpiar estado
        this.productoEditandoId = null;
        this.modoActual = null;
        
        // Limpiar formulario al cerrar
        this.limpiarFormularioCompleto();
        
        console.log('🚪 Modal cerrado y estado limpiado');
    }

    llenarFormularioProducto(producto) {
        document.getElementById('productId').value = producto.id;
        document.getElementById('productName').value = producto.nombre;
        document.getElementById('productPrice').value = producto.precio;
        document.getElementById('productCategory').value = producto.id_categoria;
        document.getElementById('productBrand').value = producto.id_marca;
        document.getElementById('productQuantity').value = producto.cantidad;
        document.getElementById('productStatus').value = producto.activo;
        
        // Mostrar imagen actual si existe
        if (producto.imagen) {
            const currentImagePreview = document.getElementById('currentImagePreview');
            const currentImage = document.getElementById('currentImage');
            
            if (currentImagePreview && currentImage) {
                // Limpiar cualquier ruta problemática
                let imagePath = producto.imagen;
                if (imagePath.startsWith('\\')) {
                    imagePath = imagePath.substring(1);
                }
                imagePath = imagePath.replace(/\\/g, '/');
                
                currentImage.src = `imagenes/${imagePath}`;
                currentImage.onerror = function() {
                    currentImagePreview.style.display = 'none';
                };
                currentImagePreview.style.display = 'block';
            }
        }
    }

    /*
     OPERACIONES CRUD 
     */
    async guardarProducto(datosFormulario) {
        console.log('💾 Guardando producto en modo:', this.modoActual);
        
        // Validar modo con compatibilidad
        if (!this.modoActual || (this.modoActual !== 'agregar' && this.modoActual !== 'editar' && this.modoActual !== 'add' && this.modoActual !== 'edit')) {
            console.error('❌ Modo no válido:', this.modoActual);
            this.mostrarNotificacion('Error: Modo no válido - ' + this.modoActual, 'error');
            return;
        }
        
        // Normalizar modo para el backend
        let modoNormalizado = this.modoActual;
        if (modoNormalizado === 'add') modoNormalizado = 'agregar';
        if (modoNormalizado === 'edit') modoNormalizado = 'editar';
        
        // Validar ID para edición
        if ((modoNormalizado === 'editar' || modoNormalizado === 'edit') && !this.productoEditandoId) {
            this.mostrarNotificacion('Error: No se pudo identificar el producto a editar', 'error');
            return;
        }
        
        // Mostrar loading
        this.mostrarLoadingModal();
        
        try {
            const url = (modoNormalizado === 'agregar' || modoNormalizado === 'add') ? 'agregar_producto.php' : 'actualizar_producto.php';
            
            // Para editar, asegurar que el ID esté en el FormData
            if (modoNormalizado === 'editar' || modoNormalizado === 'edit') {
                datosFormulario.set('id', this.productoEditandoId);
            }
            
            // Log para debug
            console.log('📡 Enviando a:', url);
            console.log('🔧 Modo actual:', this.modoActual);
            console.log('🔧 Modo normalizado:', modoNormalizado);
            console.log('🆔 ID producto:', this.productoEditandoId);
            
            const respuesta = await fetch(url, {
                method: 'POST',
                body: datosFormulario
            });
            
            const datos = await respuesta.json();
            console.log('📨 Respuesta del servidor:', datos);
            
            if (datos.success) {
                this.mostrarNotificacion(
                    (modoNormalizado === 'agregar' || modoNormalizado === 'add') ? 'Producto agregado correctamente' : 'Producto actualizado correctamente',
                    'success'
                );
                await this.cargarProductos(); // Recargar productos
                this.cerrarModal();
            } else {
                throw new Error(datos.message);
            }
        } catch (error) {
            console.error('❌ Error:', error);
            this.mostrarNotificacion('Error: ' + error.message, 'error');
        } finally {
            this.ocultarLoadingModal();
        }
    }

    async eliminarProducto(id) {
        if (!confirm('¿Estás seguro de que deseas eliminar este producto?')) {
            return;
        }

        try {
            const respuesta = await fetch('eliminar_producto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({id: id})
            });
            
            const datos = await respuesta.json();
            
            if (datos.success) {
                this.mostrarNotificacion('Producto eliminado correctamente', 'success');
                await this.cargarProductos(); // Recargar productos
            } else {
                throw new Error(datos.message);
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarNotificacion('Error al eliminar producto: ' + error.message, 'error');
        }
    }

    editarProducto(id) {
        this.abrirModal('editar', id);
    }

    /*
      FUNCIONES AUXILIARES
     */
    llenarSelectCategorias() {
        const select = document.getElementById('productCategory');
        select.innerHTML = '<option value="">Seleccionar categoría</option>';
        
        this.categorias.forEach(categoria => {
            select.innerHTML += `<option value="${categoria.id}">${categoria.nombre}</option>`;
        });
    }

    llenarSelectMarcas() {
        const select = document.getElementById('productBrand');
        select.innerHTML = '<option value="">Seleccionar marca</option>';
        
        this.marcas.forEach(marca => {
            select.innerHTML += `<option value="${marca.id}">${marca.nombre}</option>`;
        });
    }

    obtenerNombreCategoria(id) {
        const categoria = this.categorias.find(c => c.id == id);
        return categoria ? categoria.nombre : 'Sin categoría';
    }

    obtenerNombreMarca(id) {
        const marca = this.marcas.find(b => b.id == id);
        return marca ? marca.nombre : 'Sin marca';
    }
    /*
      ESTADÍSTICAS
     */
    actualizarEstadisticas() {
        const totalProductos = this.productos.length;
        const valorTotal = this.productos.reduce((suma, producto) => 
            suma + (parseFloat(producto.precio) * parseInt(producto.cantidad)), 0);
        const categoriasSet = new Set(this.productos.map(producto => producto.id_categoria));
        const stockBajo = this.productos.filter(producto => parseInt(producto.cantidad) < 10).length;

        document.getElementById('totalProducts').textContent = totalProductos;
        document.getElementById('totalValue').textContent = `₡${valorTotal.toFixed(2)}`;
        document.getElementById('categories').textContent = categoriasSet.size;
        document.getElementById('lowStock').textContent = stockBajo;
    }

    /*
      BÚSQUEDA
     */
    buscarProductos(termino) {
        const terminoBusqueda = termino.toLowerCase();
        const productosFiltrados = this.productos.filter(producto => 
            producto.nombre.toLowerCase().includes(terminoBusqueda) ||
            this.obtenerNombreCategoria(producto.id_categoria).toLowerCase().includes(terminoBusqueda) ||
            this.obtenerNombreMarca(producto.id_marca).toLowerCase().includes(terminoBusqueda)
        );
        this.renderizarProductos(productosFiltrados);
    }

    /*
      FUNCIONES DE LOADING PARA EL MODAL
     */
    mostrarLoadingModal() {
        const overlay = document.getElementById('modalLoadingOverlay');
        if (overlay) {
            overlay.classList.add('active');
        }
    }

    ocultarLoadingModal() {
        const overlay = document.getElementById('modalLoadingOverlay');
        if (overlay) {
            overlay.classList.remove('active');
        }
    }

    /*
     VALIDACIÓN DE IMAGEN
     */
    validarImagen(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        // Validar tamaño (5MB)
        if (file.size > 5 * 1024 * 1024) {
            this.mostrarNotificacion('La imagen es demasiado grande. Máximo 5MB.', 'error');
            e.target.value = '';
            return;
        }
        
        // Validar tipo
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            this.mostrarNotificacion('Tipo de archivo no válido. Use JPG, PNG o WEBP.', 'error');
            e.target.value = '';
            return;
        }
    }

    /*
     NOTIFICACIONES
     */
    mostrarNotificacion(mensaje, tipo = 'success') {
        const notificacion = document.createElement('div');
        notificacion.className = `notification ${tipo}`;
        
        const icono = tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        notificacion.innerHTML = `
            <i class="fas ${icono}"></i>
            <span>${mensaje}</span>
        `;
        
        // Estilos para móvil
        if (window.innerWidth <= 768) {
            notificacion.style.cssText = `
                position: fixed;
                top: 10px;
                left: 10px;
                right: 10px;
                margin: 0;
                z-index: 20000;
            `;
        }
        
        document.body.appendChild(notificacion);
        
        setTimeout(() => {
            if (notificacion.parentNode) {
                notificacion.remove();
            }
        }, 3000);
    }

    /*
     CONFIGURACIÓN DE EVENTOS
     */
    configurarEventos() {
        // Formulario de producto
        document.getElementById('productForm').addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Validar que tengamos un modo válido
            if (!this.modoActual) {
                this.mostrarNotificacion('Error: Modo no definido', 'error');
                return;
            }
            
            const datosFormulario = new FormData(e.target);
            this.guardarProducto(datosFormulario);
        });

        // Búsqueda
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.buscarProductos(e.target.value);
            });
        }

        // Cerrar modal con clic fuera
        window.addEventListener('click', (e) => {
            const modal = document.getElementById('productModal');
            if (e.target === modal) {
                this.cerrarModal();
            }
        });

        // Cerrar modal con Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modal = document.getElementById('productModal');
                if (modal && modal.style.display === 'block') {
                    this.cerrarModal();
                }
            }
        });

        // Validación de imagen
        const imageInput = document.getElementById('productImage');
        if (imageInput) {
            imageInput.addEventListener('change', this.validarImagen.bind(this));
        }
    }
}

// Funciones globales para compatibilidad con el HTML existente - MEJORADAS
function openModal(mode, productId = null) {
    console.log('🔍 openModal llamado con modo:', mode, 'ID:', productId);
    if (!admin) {
        console.error('❌ Admin no está inicializado');
        return;
    }
    admin.abrirModal(mode, productId);
}

function closeModal() {
    if (!admin) {
        console.error('❌ Admin no está inicializado');
        return;
    }
    admin.cerrarModal();
}

function editProduct(id) {
    admin.editarProducto(id);
}

function deleteProduct(id) {
    admin.eliminarProducto(id);
}

// Instancia global
let admin;

// Inicialización automática cuando se carga el DOM
document.addEventListener('DOMContentLoaded', function() {
    admin = new Administrador();
    admin.inicializar();
});