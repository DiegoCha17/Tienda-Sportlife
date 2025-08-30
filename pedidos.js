
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar la página de pedidos
    initPedidos();
});

function initPedidos() {
    // Verificar si hay datos en el carrito
    const cartData = JSON.parse(localStorage.getItem('cart') || '[]');
    
    if (cartData.length === 0) {
        // Si no hay productos, mostrar mensaje y redirigir
        showEmptyCartMessage();
        setTimeout(() => {
            window.location.href = 'carrito.php';
        }, 3000);
        return;
    }

    // Normalizar datos del carrito para compatibilidad
    const normalizedCart = cartData.map(item => ({
        id: item.id,
        nombre: item.name || item.nombre,
        precio: parseFloat(item.price || item.precio || 0),
        cantidad: parseInt(item.quantity || item.cantidad || 1),
        imagen: item.image || item.imagen || ''
    }));

    // Guardar items 
    const itemsCarritoInput = document.getElementById('itemsCarrito');
    if (itemsCarritoInput) {
        itemsCarritoInput.value = JSON.stringify(normalizedCart);
    }

    // Mostrar resumen del pedido
    mostrarResumen(normalizedCart);

    
    configurarValidacion();

    
    configurarModalDireccion();
}

function showEmptyCartMessage() {
    const resumenContainer = document.getElementById('resumenPedido');
    if (resumenContainer) {
        resumenContainer.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Carrito vacío</h5>
                <p class="text-muted">Redirigiendo al carrito...</p>
                <div class="spinner-border text-primary mt-3" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
        `;
    }
}

function mostrarResumen(items) {
    const resumenContainer = document.getElementById('resumenPedido');
    if (!resumenContainer) return;

    if (!items || items.length === 0) {
        showEmptyCartMessage();
        return;
    }

    let subtotal = 0;
    let html = '<div class="productos-resumen">';

    items.forEach(item => {
        const precio = parseFloat(item.precio || 0);
        const cantidad = parseInt(item.cantidad || 1);
        const total_item = precio * cantidad;
        subtotal += total_item;
        
        html += `
            <div class="producto-resumen-item">
                <div class="producto-info">
                    <strong>${escapeHtml(item.nombre || 'Producto')}</strong>
                    <small>Cantidad: ${cantidad} × ₡${precio.toLocaleString('es-CR', {minimumFractionDigits: 2})}</small>
                </div>
                <div class="producto-precio">₡${total_item.toLocaleString('es-CR', {minimumFractionDigits: 2})}</div>
            </div>
        `;
    });

    const impuestos = subtotal * 0.13;
    const envio = 5000.00; 
    const total = subtotal + impuestos + envio;

    html += `
        </div>
        <div class="totales-resumen">
            <div class="linea-total">
                <span><i class="fas fa-tag me-1"></i>Subtotal</span>
                <strong>₡${subtotal.toLocaleString('es-CR', {minimumFractionDigits: 2})}</strong>
            </div>
            <div class="linea-total">
                <span><i class="fas fa-percentage me-1"></i>Impuestos (13%)</span>
                <strong>₡${impuestos.toLocaleString('es-CR', {minimumFractionDigits: 2})}</strong>
            </div>
            <div class="linea-total">
                <span><i class="fas fa-truck me-1"></i>Envío</span>
                <strong>₡${envio.toLocaleString('es-CR', {minimumFractionDigits: 2})}</strong>
            </div>
            <div class="linea-total total-final">
                <span><i class="fas fa-money-bill-wave me-1"></i>Total</span>
                <strong>₡${total.toLocaleString('es-CR', {minimumFractionDigits: 2})}</strong>
            </div>
        </div>
    `;

    resumenContainer.innerHTML = html;
}

function configurarValidacion() {
    const direccionRadios = document.querySelectorAll('input[name="id_direccion"]');
    const btnRealizarPedido = document.getElementById('btnRealizarPedido');
    const pedidoForm = document.getElementById('pedidoForm');

    // Habilitar botón cuando se seleccione una dirección
    direccionRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            if (btnRealizarPedido) {
                btnRealizarPedido.disabled = false;
                btnRealizarPedido.innerHTML = '<i class="fas fa-credit-card"></i> Realizar Pedido';
            }
        });
    });

    // Validar antes del envío
    if (pedidoForm) {
        pedidoForm.addEventListener('submit', function(e) {
            const selectedAddress = document.querySelector('input[name="id_direccion"]:checked');
            const cartData = JSON.parse(localStorage.getItem('cart') || '[]');
            
            if (!selectedAddress) {
                e.preventDefault();
                showNotification('Por favor selecciona una dirección de envío.', 'error');
                return;
            }

            if (cartData.length === 0) {
                e.preventDefault();
                showNotification('Tu carrito está vacío.', 'error');
                return;
            }

            // Cambiar texto del botón
            if (btnRealizarPedido) {
                btnRealizarPedido.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando pedido...';
                btnRealizarPedido.disabled = true;
            }

            // Mostrar mensaje de procesamiento
            showNotification('Procesando tu pedido...', 'info');
        });
    }
}

function configurarModalDireccion() {
    const btnGuardar = document.getElementById('btnGuardarDireccion');
    const modal = document.getElementById('modalDireccion');
    const form = document.getElementById('formDireccion');

    if (btnGuardar) {
        btnGuardar.addEventListener('click', guardarDireccion);
    }

    // Resetear formulario cuando se cierre el modal
    if (modal && form) {
        modal.addEventListener('hidden.bs.modal', function() {
            form.reset();
            
            // Limpiar mensajes de error personalizados
            const inputs = form.querySelectorAll('.form-control-pedidos');
            inputs.forEach(input => {
                input.classList.remove('is-invalid');
                const feedback = input.parentNode.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.remove();
                }
            });
        });
    }

    // Validación en tiempo real
    if (form) {
        const inputs = form.querySelectorAll('.form-control-pedidos');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                const feedback = this.parentNode.querySelector('.invalid-feedback');
                if (feedback) {
                    feedback.remove();
                }
            });
        });
    }
}

function guardarDireccion() {
    const form = document.getElementById('formDireccion');
    const btnGuardar = document.getElementById('btnGuardarDireccion');
    
    if (!form || !btnGuardar) return;

    // Validar formulario
    if (!validarFormularioDireccion(form)) {
        return;
    }

    const formData = new FormData(form);
    
    // Cambiar estado del botón
    const originalText = btnGuardar.innerHTML;
    btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
    btnGuardar.disabled = true;
    
    fetch('guardarDirecciones.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showNotification('Dirección guardada correctamente', 'success');
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalDireccion'));
            if (modal) {
                modal.hide();
            }
            // Recargar página para mostrar nueva dirección
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification('Error al guardar la dirección: ' + (data.message || 'Error desconocido'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al procesar la solicitud. Inténtalo de nuevo.', 'error');
    })
    .finally(() => {
        // Restaurar botón
        btnGuardar.innerHTML = originalText;
        btnGuardar.disabled = false;
    });
}

function validarFormularioDireccion(form) {
    let isValid = true;
    const requiredFields = [
        { id: 'ciudad', name: 'Ciudad', min: 2 },
        { id: 'provincia', name: 'Provincia', min: 2 },
        { id: 'pais', name: 'País', min: 2 },
        { id: 'codigo_postal', name: 'Código Postal', min: 5, pattern: /^\d{5}$/ }
    ];

    requiredFields.forEach(field => {
        const input = form.querySelector(`#${field.id}`);
        if (input) {
            const value = input.value.trim();
            let error = null;

            // Limpiar errores previos
            input.classList.remove('is-invalid');
            const existingFeedback = input.parentNode.querySelector('.invalid-feedback');
            if (existingFeedback) {
                existingFeedback.remove();
            }

            // Validar campo
            if (!value) {
                error = `${field.name} es obligatorio`;
            } else if (value.length < field.min) {
                error = `${field.name} debe tener al menos ${field.min} caracteres`;
            } else if (field.pattern && !field.pattern.test(value)) {
                error = `${field.name} tiene un formato inválido`;
            }

            if (error) {
                input.classList.add('is-invalid');
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                feedback.textContent = error;
                input.parentNode.appendChild(feedback);
                isValid = false;
            }
        }
    });

    return isValid;
}

function limpiarCarrito() {
    localStorage.removeItem('cart');
    
    // Actualizar contadores de carrito en todas las páginas
    const cartCounts = document.querySelectorAll('[data-cart-count], .cart-count');
    cartCounts.forEach(el => {
        el.textContent = '0';
        el.style.display = 'none';
    });

    console.log('Carrito limpiado del localStorage');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Función para mostrar notificaciones
function showNotification(message, type = 'success') {
    // Remover notificación existente
    const existing = document.querySelector('.notification-pedidos');
    if (existing) {
        existing.remove();
    }

    const notification = document.createElement('div');
    notification.className = `notification notification-pedidos ${type}`;
    
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-triangle',
        warning: 'fa-exclamation-circle',
        info: 'fa-info-circle'
    };

    const colors = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    };
    
    notification.innerHTML = `
        <i class="fas ${icons[type] || icons.info}"></i>
        <span>${message}</span>
    `;
    
    Object.assign(notification.style, {
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '1rem 1.5rem',
        borderRadius: '12px',
        color: 'white',
        fontWeight: '600',
        zIndex: '9999',
        backgroundColor: colors[type] || colors.info,
        boxShadow: '0 8px 32px rgba(0,0,0,0.2)',
        transform: 'translateX(100%)',
        transition: 'all 0.3s ease',
        display: 'flex',
        alignItems: 'center',
        gap: '0.5rem',
        minWidth: '300px',
        backdropFilter: 'blur(10px)',
        border: '1px solid rgba(255,255,255,0.2)'
    });
    
    document.body.appendChild(notification);
    
    // Animar entrada
    requestAnimationFrame(() => {
        notification.style.transform = 'translateX(0)';
    });
    
    
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        notification.style.opacity = '0';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 4000);
}

// Función para verificar si el pedido fue exitoso
function pedidoExitoso(numeroSeguimiento) {
    limpiarCarrito();
    showNotification(`¡Pedido realizado exitosamente! Número de seguimiento: ${numeroSeguimiento}`, 'success');
}

// Exportar funciones para uso global si es necesario
window.PedidosJS = {
    limpiarCarrito,
    pedidoExitoso,
    showNotification
};