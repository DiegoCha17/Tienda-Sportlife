/*
  SISTEMA DE CARRITO DE COMPRAS
 */
const API_URL = 'carrito.php'; 

async function apiCarrito(action, payload = {}, method = 'POST') {
  const baseOpts = { credentials: 'include' };

  if (method === 'GET') {
    const qs = new URLSearchParams({ action, ...payload }).toString();
    const res = await fetch(`${API_URL}?${qs}`, baseOpts);
    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
      const txt = await res.text();
      throw new Error('Respuesta no JSON: ' + txt.slice(0, 120));
    }
    return await res.json();
  }

  const body = new URLSearchParams({ action, ...payload });
  const res = await fetch(API_URL, {
    ...baseOpts,
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body
  });
  const ct = res.headers.get('content-type') || '';
  if (!ct.includes('application/json')) {
    const txt = await res.text();
    throw new Error('Respuesta no JSON: ' + txt.slice(0, 120));
  }
  return await res.json();
}

// ===== buildImg: NORMALIZA RUTAS SIN DUPLICAR "imagenes" NI DOBLE % =====
function buildImg(src) {
  if (!src) {
    return 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHZpZXdCb3g9IjAgMCA4MCA4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iODAiIGhlaWdodD0iODAiIGZpbGw9IiNGM0Y0RjYiLz48dGV4dCB4PSIyMCIgeT0iNDYiIGZpbGw9IiM5Q0EzQUYiPk5vIGltYWdlPC90ZXh0Pjwvc3ZnPg==';
  }

  // 1) Normaliza string (quita comillas, barras invertidas, espacios raros)
  src = String(src).trim().replace(/^"+|"+$/g, '').replace(/\\+/g, '/').replace(/\s+/g, ' ');

  // 2) Si es URL absoluta, respétala (y evita doble %)
  if (/^https?:\/\//i.test(src)) {
    try { src = decodeURI(src); } catch {}
    return src.replace(/ /g, '%20');
  }

  // 3) Quita barras iniciales y repeticiones "imagenes/"
  src = src.replace(/^\/+/, '').replace(/^(imagenes\/)+/i, '');

  // 4) Evita doble codificación: decodifica una vez y re-codifica por segmentos
  try { src = decodeURIComponent(src); } catch {}
  const encoded = src.split('/').map(s => encodeURIComponent(s)).join('/');

  // 5) Base correcta según dónde vive tu app
  const base = location.pathname.includes('/Proyectoweb/')
    ? '/Proyectoweb/imagenes'
    : '/imagenes';

  return `${base}/${encoded}`;
}


/* =========================
   Clase Carrito
   ========================= */
class Carrito {
  constructor() {
    this.items = JSON.parse(localStorage.getItem('cart')) || [];
    this.contadorElemento = null;
  }

  /** INICIALIZACIÓN DEL CARRITO */
  inicializar(contadorId = 'cartCount') {
    console.log('🛒 Inicializando sistema de carrito...');
    this.contadorElemento = document.getElementById(contadorId);
    this.actualizarContador();
    this.configurarEventos();
    console.log(`✅ Carrito listo con ${this.items.length} tipos de productos`);
  }

  /** Refresca lista, resumen y badge */
  refreshUI() {
    if (document.getElementById('cartContainer')) this.renderizarCarrito('cartContainer');
    if (document.getElementById('cartSummary'))   this.actualizarResumen('cartSummary');
    this.actualizarContador();
  }

// 2. Debug en mergeConServidor para ver qué llega del servidor
setFromServer(itemsServer = []) {
  this.items = itemsServer.map(it => ({
    id: it.id ?? it.id_producto ?? it.ID ?? it.codigo ?? it.sku,
    name: it.name ?? it.nombre ?? 'Producto',
    price: Number(it.price ?? it.precio ?? 0),
    image: it.image ?? it.imagen ?? '',
    quantity: Number(it.quantity ?? it.cantidad ?? 1),
  })).filter(x => x.id != null);

  this.guardarCarrito();
  this.refreshUI();
}

async syncFromServer() {
  try {
    const r = await apiCarrito('list', {}, 'GET');
    if (r?.success && Array.isArray(r.items)) {
      this.setFromServer(r.items); 
    }
  } catch (e) {
    console.error('syncFromServer error:', e);
  }
}


async syncFromServer() {
  try {
    const data = await apiCarrito('list', {}, 'GET');
if (data?.success && Array.isArray(data.items)) {
  carrito.setFromServer(data.items); 
}

  } catch (e) {
    console.error('syncFromServer error:', e);
  }
}



  /** AGREGAR PRODUCTO AL CARRITO */
  async agregarProducto(id, nombre, precio, imagen = '', stockDisponible = 999) {
    console.log(`🛒 Agregando: ${nombre} (ID: ${id})`);
    if (!Number.isFinite(precio)) precio = parseFloat(precio || 0);

    if (stockDisponible <= 0) {
      this.mostrarNotificacion('Producto sin stock disponible', 'error');
      return false;
    }

    const itemExistente = this.items.find(it => String(it.id) === String(id));
    if (itemExistente) {
      if (itemExistente.quantity < stockDisponible) {
        itemExistente.quantity += 1;
      } else {
        this.mostrarNotificacion(`Stock máximo alcanzado para ${nombre}`, 'error');
        return false;
      }
    } else {
      this.items.push({ id, name: nombre, price: parseFloat(precio), image: imagen, quantity: 1 });
    }

    this.guardarCarrito();
    this.refreshUI();

  try {
  const data = await apiCarrito('add_item', { id_producto: id, cantidad: 1 });
  if (!data?.success) throw new Error(data?.error || 'No se pudo agregar en el servidor');

  this.mostrarNotificacion(`${nombre} agregado al carrito`, 'success');
  await this.syncFromServer(); 
  return true;
} catch (e) {
  


  // Revertir si falla el backend
  const it = this.items.find(x => String(x.id) === String(id));
  if (it) {
    if (it.quantity > 1) it.quantity -= 1;
    else this.items = this.items.filter(x => String(x.id) !== String(id));
  }
  this.guardarCarrito();
  this.refreshUI();
  console.error(e);
  this.mostrarNotificacion(String(e.message || e), 'error');
  return false;
}

  }

  /** REMOVER PRODUCTO DEL CARRITO */
  async removerProducto(id) {
    const index = this.items.findIndex(item => String(item.id) === String(id));
    if (index === -1) return false;

    const nombreProducto = this.items[index].name;

    try {
      const data = await apiCarrito('remove_item', { id_producto: id });
      if (!data?.success) throw new Error(data?.error || 'No se pudo eliminar en el servidor');

      this.items.splice(index, 1);
      this.guardarCarrito();
      this.refreshUI();

      this.mostrarNotificacion(`${nombreProducto} eliminado del carrito`, 'success');
      await this.syncFromServer();
      return true;
    } catch (e) {
      console.error(e);
      this.mostrarNotificacion(String(e.message || e), 'error');
      return false;
    }
  }

  /** ACTUALIZAR CANTIDAD DE UN PRODUCTO */
  async actualizarCantidad(id, nuevaCantidad) {
    const item = this.items.find(it => String(it.id) === String(id));
    if (!item) return false;

    let qty = parseInt(nuevaCantidad, 10);
    if (isNaN(qty) || qty < 1) qty = 1;
    if (qty > 99) qty = 99;

    if (qty === item.quantity) return true;

    const anterior = item.quantity;
    item.quantity = qty;
    this.guardarCarrito();
    this.refreshUI(); 

    try {
      const data = await apiCarrito('update_qty', { id_producto: id, cantidad: qty });
      if (!data?.success) throw new Error(data?.error || 'No se pudo actualizar en el servidor');
      await this.syncFromServer();
      return true;
    } catch (e) {
      // Revertir si falla backend
      item.quantity = anterior;
      this.guardarCarrito();
      this.refreshUI();
      console.error(e);
      this.mostrarNotificacion(String(e.message || e), 'error');
      return false;
    }
  }

  /** INFORMACIÓN DEL CARRITO */
  obtenerItems() { return this.items; }
  obtenerCantidadTotal() { return this.items.reduce((suma, it) => suma + (Number(it.quantity)||0), 0); }
  obtenerSubtotal() { return this.items.reduce((suma, it) => suma + (Number(it.price)*Number(it.quantity)), 0); }
  obtenerImpuestos(p = 0.13) { return this.obtenerSubtotal() * p; }
  obtenerTotal(envio = 5.00, impuestos = 0.13) { return this.obtenerSubtotal() + this.obtenerImpuestos(impuestos) + envio; }

  /** VERIFICACIONES */
  estaEnCarrito(id) { return this.items.some(it => String(it.id) === String(id)); }
  obtenerCantidadProducto(id) { const it = this.items.find(i => String(i.id) === String(id)); return it ? it.quantity : 0; }

  /** LIMPIAR CARRITO */
  async limpiarCarrito() {
    try {
      const data = await apiCarrito('clear', {}, 'GET');
      if (!data?.success) throw new Error(data?.error || 'No se pudo vaciar en el servidor');
      this.items = [];
      this.guardarCarrito();
      this.refreshUI();
      this.mostrarNotificacion('Carrito vaciado', 'success');
      await this.syncFromServer();
    } catch (e) {
      console.error(e);
      this.mostrarNotificacion(String(e.message || e), 'error');
    }
  }

  /** CONTADOR VISUAL */
  actualizarContador() {
    const total = this.obtenerCantidadTotal();

    if (this.contadorElemento) {
      this.contadorElemento.textContent = total;
      if (total > 0) {
        this.contadorElemento.style.transform = 'scale(1.2)';
        setTimeout(() => { this.contadorElemento.style.transform = 'scale(1)'; }, 200);
      }
    }
    document.querySelectorAll('[data-cart-count]').forEach(el => el.textContent = total);
  }

  /** PERSISTENCIA */
  guardarCarrito() {
    localStorage.setItem('cart', JSON.stringify(this.items));
    console.log(`💾 Carrito guardado: ${this.items.length} tipos de productos`);
  }
  cargarCarrito() {
    const raw = localStorage.getItem('cart');
    if (raw) {
      this.items = JSON.parse(raw);
      this.actualizarContador();
      console.log(`📦 Carrito cargado: ${this.items.length} tipos de productos`);
    }
  }




renderizarCarrito(contenedorId) {
  const cont = document.getElementById(contenedorId);
  if (!cont) return;

  console.log('=== DEBUG RENDERIZAR CARRITO ===');
  console.log('Total items:', this.items.length);
  
  // Mostrar datos de cada producto
  this.items.forEach((item, index) => {
    console.log(`Producto ${index + 1}:`);
    console.log('  ID:', item.id);
    console.log('  Nombre:', item.name);
    console.log('  Imagen original:', item.image);
    console.log('  Imagen procesada:', buildImg(item.image));
    console.log('  Precio:', item.price);
    console.log('  Cantidad:', item.quantity);
    console.log('---');
  });

  if (this.items.length === 0) {
    cont.innerHTML = `<div class="empty text-center py-5">🛒 <p class="mt-3 mb-0">Tu carrito está vacío. Agrega productos para comenzar.</p></div>`;
    return;
  }

  cont.innerHTML = this.items.map((item, index) => {
    const imgSrc = buildImg(item.image);
    
    console.log(`Renderizando producto ${index + 1}: ${item.name}`);
    console.log(`  Usando imagen: ${imgSrc}`);
    
    return `
      <div class="cart-item">
        <img src="${imgSrc}" 
             onerror="console.error('ERROR imagen producto ${index + 1}:', this.src); this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iODAiIHZpZXdCb3g9IjAgMCA4MCA4MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjgwIiBoZWlnaHQ9IjgwIiBmaWxsPSIjRjNGNEY2Ii8+CjxjaXJjbGUgY3g9IjQwIiBjeT0iMzUiIHI9IjgiIGZpbGw9IiM5Q0EzQUYiLz4KPHBhdGggZD0iTTI4IDUySDUyVjQ4SDI4VjUyWiIgZmlsbD0iIzlDQTNBRiIvPgo8dGV4dCB4PSI0MCIgeT0iNjgiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSI4IiBmaWxsPSIjOUNBM0FGIj5TaW4gaW1hZ2VuPC90ZXh0Pgo8L3N2Zz4=';"
             onload="console.log('OK imagen producto ${index + 1}:', this.src);"
             style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
        <div>
          <p class="cart-title mb-1">${this.escaparTexto(item.name)}</p>
          <div class="cart-unit">Unit: ₡${Number(item.price).toFixed(2)}</div>
        </div>
        <div class="text-end">
          <div class="d-flex align-items-center justify-content-end gap-2 mb-2">
            <div class="qty">
              <button onclick="carrito.actualizarCantidad(${item.id}, ${Math.max(1, Number(item.quantity) - 1)})">−</button>
              <input type="number" value="${Number(item.quantity)}" min="1"
                     onchange="carrito.actualizarCantidad(${item.id}, this.value)">
              <button onclick="carrito.actualizarCantidad(${item.id}, ${Number(item.quantity) + 1})">+</button>
            </div>
            <button class="remove" title="Eliminar" onclick="carrito.removerProducto(${item.id})">🗑</button>
          </div>
          <div class="cart-price">₡${(Number(item.price) * Number(item.quantity)).toFixed(2)}</div>
        </div>
      </div>
    `;
  }).join('');
}


  /** RESUMEN */
  actualizarResumen(resumenId) {
    const resumen = document.getElementById(resumenId);
    if (!resumen) return;

    const subtotal = this.obtenerSubtotal();
    const impuestos = this.obtenerImpuestos();
    const envio = 5.00;
    const total = this.obtenerTotal(envio);

    resumen.innerHTML = `
      <div class="space-y-3 text-lg">
        <div class="d-flex justify-content-between"><span>Subtotal:</span><span class="fw-semibold">₡${subtotal.toFixed(2)}</span></div>
        <div class="d-flex justify-content-between"><span>Impuestos (13%):</span><span class="fw-semibold">₡${impuestos.toFixed(2)}</span></div>
        <div class="d-flex justify-content-between"><span>Envío:</span><span class="fw-semibold">₡${envio.toFixed(2)}</span></div>
        <div class="d-flex justify-content-between border-top pt-3 fw-bold fs-5">
          <span>Total General:</span><span class="text-primary">₡${total.toFixed(2)}</span>
        </div>
      </div>
    `;
  }

  /** NOTIFICACIONES */
  mostrarNotificacion(mensaje, tipo = 'success') {
    const n = document.createElement('div');
    n.className = `notification ${tipo}`;
    n.innerHTML = `<i class="fas ${tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'}"></i> ${this.escaparTexto(mensaje)}`;
    Object.assign(n.style, {
      position: 'fixed', top: '20px', right: '20px', padding: '12px 20px',
      borderRadius: '8px', color: 'white', fontSize: '14px', fontWeight: 'bold',
      zIndex: '10000', boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
      backgroundColor: tipo === 'success' ? '#22C55E' : '#EF4444',
      transform: 'translateX(100%)', transition: 'transform 0.3s ease'
    });
    document.body.appendChild(n);
    setTimeout(() => { n.style.transform = 'translateX(0)'; }, 100);
    setTimeout(() => {
      n.style.transform = 'translateX(100%)';
      setTimeout(() => { n.remove(); }, 300);
    }, 3000);
  }

  /** EVENTOS GLOBALES */
  configurarEventos() {
    window.addEventListener('storage', (e) => {
      if (e.key === 'cart') this.cargarCarrito();
    });
    window.addEventListener('beforeunload', () => this.guardarCarrito());
  }

  /** UTILIDADES */
  exportarCarrito() {
    return {
      items: this.items,
      cantidadTotal: this.obtenerCantidadTotal(),
      subtotal: this.obtenerSubtotal(),
      total: this.obtenerTotal()
    };
  }
  importarCarrito(datosCarrito) {
    if (datosCarrito && Array.isArray(datosCarrito.items)) {
      this.items = datosCarrito.items;
      this.guardarCarrito();
      this.actualizarContador();
      return true;
    }
    return false;
  }
  escaparTexto(t = '') {
    return String(t).replace(/[&<>"']/g, s => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[s]));
  }
}


function addToCart(id, name, price, image = '', stock = 999) { return carrito.agregarProducto(id, name, price, image, stock); }
function removeFromCart(id) { return carrito.removerProducto(id); }
function updateCartCount() { carrito.actualizarContador(); }
function clearCart() { carrito.limpiarCarrito(); }


let carrito;

document.addEventListener('DOMContentLoaded', async () => {
  if (!window.carrito) {
    window.carrito = new Carrito();
  }
  carrito = window.carrito;
  carrito.inicializar();

  const hasContainers = document.getElementById('cartContainer') || document.getElementById('cartSummary');
  if (hasContainers) {
    const skel = document.getElementById('cartSkeleton');
    if (skel) skel.style.display = '';

    try {
      const data = await apiCarrito('list', {}, 'GET');
      if (data?.success && Array.isArray(data.items)) {
        // 👇 MERGE en vez de sustituir
        carrito.setFromServer(data.items);
      }
    } catch (e) {
      console.error('Error cargando carrito desde BD:', e);
    } finally {
      if (skel) skel.style.display = 'none';
      carrito.refreshUI();
    }
  }

  const btnClear = document.getElementById('btnClear');
  if (btnClear) {
    btnClear.addEventListener('click', async () => {
      await carrito.limpiarCarrito();
      carrito.refreshUI();
    });
  }

// REEMPLAZA ESTA SECCIÓN EN TU carrito.js (líneas ~315-330)
const btnCheckout = document.getElementById('btnCheckout');
if (btnCheckout) {
  btnCheckout.addEventListener('click', async () => {
    // Verificar que hay productos en el carrito
    if (carrito.obtenerCantidadTotal() === 0) {
      alert('Tu carrito está vacío. Agrega productos antes de realizar un pedido.');
      return;
    }

    try {
      
      window.location.href = 'indexPedidos.php';
      
    } catch (e) {
      console.error('Error en checkout:', e);
      alert('Error: ' + (e?.message || e));
    }
  });
}
}
);
