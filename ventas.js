// === utilidades ===
function moneyUSD(n){ return '₡' + Number(n || 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}); }
function badgeClass(estado){
  const e=(estado||'').toLowerCase();
  if (e==='pagado') return 'badge-pagado';
  if (e==='anulado') return 'badge-anulado';
  return 'badge-pendiente';
}
function htmlesc(s){ return (s??'').toString().replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])); }

// === refs ===
const q = document.getElementById('q');
const estadoSel = document.getElementById('estado');
const rangoSel = document.getElementById('rango');
const tbody = document.getElementById('tbody-ventas');
const empty = document.getElementById('ventas-empty');
const paginacion = document.getElementById('paginacion');
const btnExport = document.getElementById('btn-export');
const cardsTotales = document.getElementById('cards-totales');
const modal = document.getElementById('modal-ventas');
const btnCerrar1 = document.getElementById('btn-cerrar-modal');
const btnCerrar2 = document.getElementById('btn-cerrar-modal-2');
const detalleDiv = document.getElementById('detalle-contenido');

let currentPage = 1;
const perPage = 10;

// === renderers ===
function buildRow(row){
  const e = (row.estado||'').toLowerCase();
  return `
    <tr data-estado="${htmlesc(e)}">
      <td class="td-id mono">#${htmlesc(row.id_pedido)}</td>
      <td>${htmlesc(row.cliente)}</td>
      <td class="td-fecha">${htmlesc(row.fecha_creacion)}</td>
      <td class="td-importe">${moneyUSD(row.subtotal)}</td>
      <td><span class="badge ${badgeClass(e)}">
        ${e==='pagado' ? '<i class="fa-solid fa-circle-check"></i>' : e==='anulado' ? '<i class="fa-regular fa-circle-xmark"></i>' : '<i class="fa-regular fa-clock"></i>'}
        ${e? (e.charAt(0).toUpperCase()+e.slice(1)) : 'Pendiente'}</span>
      </td>
    </tr>
  `;
}

function renderPagination(total, page, perPage){
  const totalPages = Math.max(1, Math.ceil(total/perPage));
  let html = '';
  const prev = Math.max(1, page-1);
  const next = Math.min(totalPages, page+1);
  html += `<button class="page-btn" data-page="${prev}">«</button>`;
  const maxButtons = Math.min(totalPages, 10);
  let start = Math.max(1, page - 4);
  let end = Math.min(totalPages, start + maxButtons - 1);
  if (end - start < maxButtons - 1) start = Math.max(1, end - maxButtons + 1);
  for(let p=start; p<=end; p++){
    html += `<button class="page-btn ${p===page?'active':''}" data-page="${p}">${p}</button>`;
  }
  html += `<button class="page-btn" data-page="${next}">»</button>`;
  paginacion.innerHTML = html;

  paginacion.querySelectorAll('.page-btn').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const p = parseInt(btn.getAttribute('data-page'),10)||1;
      currentPage = p;
      loadTable();
    });
  });
}

function renderTotales(t){
  cardsTotales.innerHTML = `
    <div class="control-range"><div><div class="text-muted" style="font-weight:700;">Total Pedidos</div><div style="font-size:1.3rem;font-weight:900;">${(t.total_pedidos||0)}</div></div></div>
    <div class="control-range"><div><div class="text-muted" style="font-weight:700;">Subtotal</div><div style="font-size:1.3rem;font-weight:900;">${moneyUSD(t.total_subtotal)}</div></div></div>
    <div class="control-range"><div><div class="text-muted" style="font-weight:700;">Impuestos</div><div style="font-size:1.3rem;font-weight:900;">${moneyUSD(t.total_impuestos)}</div></div></div>
    <div class="control-range"><div><div class="text-muted" style="font-weight:700;">Envío</div><div style="font-size:1.3rem;font-weight:900;">${moneyUSD(t.total_envio)}</div></div></div>
    <div class="control-range"><div><div class="text-muted" style="font-weight:700;">Total Ventas</div><div style="font-size:1.3rem;font-weight:900;">${moneyUSD(t.total_ventas)}</div></div></div>
  `;
}

function renderDetalle(data){
  if (!data?.pedido) {
    detalleDiv.innerHTML = `<div class="ventas-empty"><h3>No existe el pedido.</h3></div>`;
    return;
  }
  const p = data.pedido;
  const items = data.items || [];
  const rows = items.map(it=>`
    <tr>
      <td>${htmlesc(it.producto)}</td>
      <td>${htmlesc(it.cantidad)}</td>
      <td>${moneyUSD(it.precio_unitario)}</td>
      <td>${moneyUSD((it.cantidad||0)*(it.precio_unitario||0))}</td>
    </tr>
  `).join('');
  detalleDiv.innerHTML = `
    <div class="ventas-controls" style="grid-template-columns: repeat(2, minmax(0,1fr));">
      <div><div class="text-muted">Cliente</div><div style="font-weight:800">${htmlesc(p.cliente||'')}</div></div>
      <div><div class="text-muted">Fecha</div><div style="font-weight:800">${htmlesc(p.fecha_creacion||'')}</div></div>
    </div>

    <div class="ventas-table-wrap" style="margin-top:1rem">
      <table class="ventas-table">
        <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio</th><th>Importe</th></tr></thead>
        <tbody>${rows || '<tr><td colspan="4" class="text-muted">Sin ítems</td></tr>'}</tbody>
      </table>
    </div>

    <div class="ventas-controls" style="margin-top:1rem; grid-template-columns: repeat(3, minmax(0,1fr));">
      <div><div class="text-muted">Subtotal</div><div style="font-weight:900">${moneyUSD(p.subtotal)}</div></div>
      <div><div class="text-muted">Impuestos + Envío</div><div style="font-weight:900">${moneyUSD((p.total_impuesto||0)+(p.total_envio||0))}</div></div>
      <div><div class="text-muted">Total</div><div style="font-weight:900">${moneyUSD(p.total_general)}</div></div>
    </div>
  `;
}

// === data loading ===
async function loadTotales(){
  const r = await fetch('ventas.php?action=totales', {headers:{'X-Requested-With':'XMLHttpRequest'}});
  const j = await r.json();
  if (j.success) renderTotales(j.totales);
}
async function loadTable(){
  const params = new URLSearchParams({
    action: 'list',
    page: currentPage,
    per_page: perPage,
    q: (q.value||'').trim(),
    estado: (estadoSel.value||''),
    rango: (rangoSel.value||'')
  });
  const r = await fetch('ventas.php?'+params.toString(), {headers:{'X-Requested-With':'XMLHttpRequest'}});
  const j = await r.json();
  tbody.innerHTML = (j.rows||[]).map(buildRow).join('');
  empty.style.display = (j.rows && j.rows.length) ? 'none' : '';
  renderPagination(j.total||0, j.page||1, j.per_page||10);

  // acciones
  tbody.querySelectorAll('.btn-ver').forEach(b=>{
    b.addEventListener('click', async ()=>{
      const id = b.getAttribute('data-id');
      const rr = await fetch('ventas.php?action=detalle&id='+encodeURIComponent(id), {headers:{'X-Requested-With':'XMLHttpRequest'}});
      const jj = await rr.json();
      renderDetalle(jj);
      modal.classList.add('is-open');
      document.body.style.overflow='hidden';
    });
  });
  tbody.querySelectorAll('.btn-imprimir').forEach(b=>{
    b.addEventListener('click', ()=>{
      const id = b.getAttribute('data-id');
      // si tienes una vista de impresión separada, cámbiala aquí:
      window.open('ventas.php?action=export&single='+encodeURIComponent(id), '_blank'); // placeholder simple
    });
  });
  tbody.querySelectorAll('.btn-eliminar').forEach(b=>{
    b.addEventListener('click', async ()=>{
      const id = b.getAttribute('data-id');
      if (!confirm('¿Anular este pedido?')) return;
      const fd = new FormData();
      fd.append('id', id);
      const rr = await fetch('ventas.php?action=anular', {method:'POST', body: fd});
      const jj = await rr.json();
      if (jj.success) {
        await loadTable();
        await loadTotales();
      } else {
        alert('No se pudo anular: '+(jj.error||'Error'));
      }
    });
  });
}

// === listeners ===
[q, estadoSel, rangoSel].forEach(el=>{
  if (!el) return;
  el.addEventListener('input', ()=>{ currentPage=1; loadTable(); });
  el.addEventListener('change', ()=>{ currentPage=1; loadTable(); });
});
btnExport.addEventListener('click', ()=>{
  const params = new URLSearchParams({
    action: 'export',
    q: (q.value||'').trim(),
    estado: (estadoSel.value||''),
    rango: (rangoSel.value||'')
  });
  window.location.href = 'ventas.php?'+params.toString();
});
[btnCerrar1, btnCerrar2].forEach(b=>{
  if (!b) return;
  b.addEventListener('click', ()=>{
    modal.classList.remove('is-open');
    document.body.style.overflow='';
  });
});

// === init ===
loadTotales();
loadTable();
