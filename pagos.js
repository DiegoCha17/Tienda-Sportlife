// JAVASCRIPT PARA PÁGINA DE PAGOS
document.addEventListener('DOMContentLoaded', function() {
    
    // Elementos del DOM
    const metodosPago = document.querySelectorAll('.metodo-pago');
    const btnPagar = document.getElementById('btnPagar');
    const camposTarjeta = document.getElementById('campos-tarjeta');
    const camposPaypal = document.getElementById('campos-paypal');
    const pagoForm = document.getElementById('pagoForm');
    
    // Campos de tarjeta
    const numeroTarjeta = document.getElementById('numero_tarjeta');
    const nombreTarjeta = document.getElementById('nombre_tarjeta');
    const fechaExpiracion = document.getElementById('fecha_expiracion');
    const cvv = document.getElementById('cvv');

    // Inicialización
    init();

    function init() {
        setupMetodosPago();
        setupFormateoTarjeta();
        setupValidacionFormulario();
        setupSubmitForm();
    }

    // Configurar selección de métodos de pago
    function setupMetodosPago() {
        metodosPago.forEach(metodo => {
            metodo.addEventListener('click', function() {
                seleccionarMetodoPago(this);
            });
        });
    }

    function seleccionarMetodoPago(metodoSeleccionado) {
        // Limpiar selecciones anteriores
        metodosPago.forEach(metodo => {
            metodo.classList.remove('selected');
            const radio = metodo.querySelector('input[type="radio"]');
            radio.checked = false;
        });
        
        // Seleccionar método actual
        metodoSeleccionado.classList.add('selected');
        const radioSeleccionado = metodoSeleccionado.querySelector('input[type="radio"]');
        radioSeleccionado.checked = true;
        
        // Mostrar campos apropiados
        const tipoMetodo = metodoSeleccionado.dataset.metodo;
        mostrarCamposMetodo(tipoMetodo);
        
        // Validar y habilitar botón
        validarFormulario();
    }

    function mostrarCamposMetodo(tipoMetodo) {
        // Ocultar todos los campos
        if (camposTarjeta) camposTarjeta.classList.remove('show');
        if (camposPaypal) camposPaypal.classList.remove('show');
        
        // Mostrar campos según el tipo
        if (tipoMetodo === 'tarjeta_credito' || tipoMetodo === 'tarjeta_debito') {
            if (camposTarjeta) {
                camposTarjeta.classList.add('show');
                // Enfocar el primer campo
                setTimeout(() => {
                    if (numeroTarjeta) numeroTarjeta.focus();
                }, 300);
            }
        } else if (tipoMetodo === 'paypal') {
            if (camposPaypal) camposPaypal.classList.add('show');
        }
    }

    // Configurar formateo de campos de tarjeta
    function setupFormateoTarjeta() {
        if (numeroTarjeta) {
            numeroTarjeta.addEventListener('input', formatearNumeroTarjeta);
            numeroTarjeta.addEventListener('blur', validarNumeroTarjeta);
        }
        
        if (fechaExpiracion) {
            fechaExpiracion.addEventListener('input', formatearFechaExpiracion);
            fechaExpiracion.addEventListener('blur', validarFechaExpiracion);
        }
        
        if (cvv) {
            cvv.addEventListener('input', formatearCVV);
        }
        
        if (nombreTarjeta) {
            nombreTarjeta.addEventListener('input', formatearNombreTarjeta);
        }
    }

    function formatearNumeroTarjeta(e) {
        let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        
        // Limitar a 19 caracteres (16 dígitos + 3 espacios)
        if (formattedValue.length > 19) {
            formattedValue = formattedValue.substring(0, 19);
        }
        
        e.target.value = formattedValue;
        
        // Detectar tipo de tarjeta
        detectarTipoTarjeta(value);
        
        // Validar en tiempo real
        validarFormulario();
    }

    function detectarTipoTarjeta(numero) {
        const iconoTarjeta = document.querySelector('.metodo-pago.selected .metodo-pago-icon i');
        if (!iconoTarjeta) return;

        // Remover clases anteriores
        iconoTarjeta.className = iconoTarjeta.className.replace(/fa-cc-\w+/g, '');
        
        // Detectar tipo
        if (numero.startsWith('4')) {
            iconoTarjeta.classList.add('fa-cc-visa');
        } else if (numero.startsWith('5') || numero.startsWith('2')) {
            iconoTarjeta.classList.add('fa-cc-mastercard');
        } else if (numero.startsWith('3')) {
            iconoTarjeta.classList.add('fa-cc-amex');
        } else {
            iconoTarjeta.classList.add('fa-credit-card');
        }
    }

    function validarNumeroTarjeta() {
        if (!numeroTarjeta) return true;
        
        const valor = numeroTarjeta.value.replace(/\s/g, '');
        const esValido = valor.length >= 13 && valor.length <= 19 && /^[0-9]+$/.test(valor);
        
        toggleCampoError(numeroTarjeta, !esValido);
        return esValido;
    }

    function formatearFechaExpiracion(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        
        e.target.value = value;
        validarFormulario();
    }

    function validarFechaExpiracion() {
        if (!fechaExpiracion) return true;
        
        const valor = fechaExpiracion.value;
        const regex = /^(0[1-9]|1[0-2])\/([0-9]{2})$/;
        
        if (!regex.test(valor)) {
            toggleCampoError(fechaExpiracion, true);
            return false;
        }
        
        const [mes, año] = valor.split('/');
        const fechaActual = new Date();
        const añoActual = fechaActual.getFullYear() % 100;
        const mesActual = fechaActual.getMonth() + 1;
        
        const añoTarjeta = parseInt(año);
        const mesTarjeta = parseInt(mes);
        
        const esValido = añoTarjeta > añoActual || (añoTarjeta === añoActual && mesTarjeta >= mesActual);
        
        toggleCampoError(fechaExpiracion, !esValido);
        return esValido;
    }

    function formatearCVV(e) {
        const valor = e.target.value.replace(/[^0-9]/g, '');
        e.target.value = valor.substring(0, 4);
        validarFormulario();
    }

    function formatearNombreTarjeta(e) {
        // Solo letras y espacios
        const valor = e.target.value.replace(/[^a-zA-ZÀ-ÿ\s]/g, '');
        e.target.value = valor.toUpperCase();
        validarFormulario();
    }

    function toggleCampoError(campo, esError) {
        if (esError) {
            campo.style.borderColor = '#dc3545';
            campo.classList.add('is-invalid');
        } else {
            campo.style.borderColor = '#28a745';
            campo.classList.remove('is-invalid');
        }
    }

    // Configurar validación del formulario
    function setupValidacionFormulario() {
        // Validar cuando cambie cualquier campo
        const campos = [numeroTarjeta, nombreTarjeta, fechaExpiracion, cvv];
        campos.forEach(campo => {
            if (campo) {
                campo.addEventListener('input', validarFormulario);
                campo.addEventListener('blur', validarFormulario);
            }
        });
    }

    function validarFormulario() {
        const metodoSeleccionado = document.querySelector('input[name="metodo_pago"]:checked');
        
        if (!metodoSeleccionado) {
            habilitarBotonPagar(false);
            return;
        }
        
        const tipoMetodo = metodoSeleccionado.value;
        
        if (tipoMetodo === 'paypal') {
            habilitarBotonPagar(true);
        } else if (tipoMetodo === 'tarjeta_credito' || tipoMetodo === 'tarjeta_debito') {
            const camposValidos = validarCamposTarjeta();
            habilitarBotonPagar(camposValidos);
        }
    }

    function validarCamposTarjeta() {
        if (!numeroTarjeta || !nombreTarjeta || !fechaExpiracion || !cvv) {
            return false;
        }
        
        const numeroValido = numeroTarjeta.value.replace(/\s/g, '').length >= 13;
        const nombreValido = nombreTarjeta.value.trim().length >= 2;
        const fechaValida = /^(0[1-9]|1[0-2])\/([0-9]{2})$/.test(fechaExpiracion.value);
        const cvvValido = cvv.value.length >= 3;
        
        return numeroValido && nombreValido && fechaValida && cvvValido;
    }

    function habilitarBotonPagar(habilitar) {
        if (btnPagar) {
            btnPagar.disabled = !habilitar;
            
            if (habilitar) {
                btnPagar.style.opacity = '1';
                btnPagar.style.cursor = 'pointer';
            } else {
                btnPagar.style.opacity = '0.6';
                btnPagar.style.cursor = 'not-allowed';
            }
        }
    }

    // Configurar envío del formulario
    function setupSubmitForm() {
        if (pagoForm) {
            pagoForm.addEventListener('submit', function(e) {
                // Validar una vez más antes del envío
                const metodoSeleccionado = document.querySelector('input[name="metodo_pago"]:checked');
                
                if (!metodoSeleccionado) {
                    e.preventDefault();
                    mostrarError('Por favor selecciona un método de pago.');
                    return;
                }
                
                const tipoMetodo = metodoSeleccionado.value;
                
                if ((tipoMetodo === 'tarjeta_credito' || tipoMetodo === 'tarjeta_debito') && !validarCamposTarjeta()) {
                    e.preventDefault();
                    mostrarError('Por favor completa todos los campos de la tarjeta correctamente.');
                    return;
                }
                
                // Mostrar estado de procesamiento
                mostrarProcesando();
            });
        }
    }

    function mostrarProcesando() {
        if (btnPagar) {
            btnPagar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando pago...';
            btnPagar.disabled = true;
            btnPagar.style.opacity = '0.8';
        }
    }

    function mostrarError(mensaje) {
        // Crear o actualizar alerta de error
        let alerta = document.querySelector('.alert-error-temp');
        
        if (!alerta) {
            alerta = document.createElement('div');
            alerta.className = 'alert alert-danger-pagos alert-error-temp';
            alerta.style.position = 'fixed';
            alerta.style.top = '20px';
            alerta.style.right = '20px';
            alerta.style.zIndex = '9999';
            alerta.style.maxWidth = '400px';
            document.body.appendChild(alerta);
        }
        
        alerta.innerHTML = `
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Error:</strong> ${mensaje}
        `;
        
        // Remover después de 5 segundos
        setTimeout(() => {
            if (alerta.parentNode) {
                alerta.parentNode.removeChild(alerta);
            }
        }, 5000);
    }

    // Función para simular carga inicial
    function simularCargaInicial() {
        const elementos = document.querySelectorAll('.card-pagos');
        elementos.forEach((elemento, index) => {
            elemento.style.opacity = '0';
            elemento.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                elemento.style.transition = 'all 0.5s ease';
                elemento.style.opacity = '1';
                elemento.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    // Ejecutar animación inicial
    simularCargaInicial();

    // Función para limpiar formulario (utilidad)
    function limpiarFormulario() {
        metodosPago.forEach(metodo => metodo.classList.remove('selected'));
        if (camposTarjeta) camposTarjeta.classList.remove('show');
        if (camposPaypal) camposPaypal.classList.remove('show');
        
        const campos = [numeroTarjeta, nombreTarjeta, fechaExpiracion, cvv];
        campos.forEach(campo => {
            if (campo) {
                campo.value = '';
                campo.style.borderColor = '';
                campo.classList.remove('is-invalid');
            }
        });
        
        habilitarBotonPagar(false);
    }

    // Exponer funciones globalmente si es necesario
    window.PagosJS = {
        limpiarFormulario: limpiarFormulario,
        validarFormulario: validarFormulario
    };
});