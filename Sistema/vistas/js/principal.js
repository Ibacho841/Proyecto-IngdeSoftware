// ---------------------------- NAVBAR: NOTIFICACIONES (CONTADOR + LISTA) --------------------------------------
// AdminLTE (antiguo): inicializar árbol del sidebar si existe
try { if ($('.sidebar-menu').length && typeof $('.sidebar-menu').tree === 'function') { $('.sidebar-menu').tree(); } } catch(e) {}

function renderNotifCount(n){
  const $b = $("#notifCount");
  if (n > 0) { $b.text(n).show(); } else { $b.hide(); }
}

async function notifCount(){
  try{
    const fd = new FormData();
    fd.append("accion","contar");
    const r = await fetch("ajax/notificaciones.ajax.php",{ method:"POST", body:fd, cache:"no-store" });
    const j = await r.json();
    if (j && j.ok){ renderNotifCount(j.count || 0); }
  }catch(e){
    // opcional: console.warn("notifCount error:", e);
  }
}

function renderNotifList(items){
  const $list = $("#notifList");
  if (!Array.isArray(items) || items.length === 0){
    $list.html('<div class="px-3 py-3 text-muted">Sin alertas por ahora.</div>');
    return;
  }
  const html = items.map(p => {
    const cantidad = parseInt(p.cantidad,10) || 0;
    const minimo   = parseInt(p.stock_minimo,10) || 0;
    const estado   = (cantidad === 0) ? 'Agotado' : 'Crítico';
    const estadoCls= (cantidad === 0) ? 'text-danger' : 'text-warning';
    const nombre   = String(p.nombre||'').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const codigo   = String(p.codigo||'').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const id       = parseInt(p.id,10) || 0;
    const delta    = cantidad - minimo;

    return `
      <a href="inventario?focus=${id}" class="list-group-item list-group-item-action">
        <div class="d-flex w-100 justify-content-between">
          <h6 class="mb-1" style="font-size:.95rem; font-weight:600;">
            ${nombre} <small class="text-muted">[${codigo}]</small>
          </h6>
          <small class="${estadoCls}">${estado}</small>
        </div>
        <p class="mb-1">Producto ${cantidad===0 ? 'agotado' : 'bajo stock mínimo'}.</p>
        <small class="text-muted">
          Cant.: <strong>${cantidad}</strong> · Mín.: <strong>${minimo}</strong> ${cantidad>0 ? `· Dif.: <strong>${delta}</strong>` : ''}
        </small>
      </a>`;
  }).join('');
  $list.html(html);
}

async function notifList(){
  try{
    const fd = new FormData();
    fd.append("accion","listar");
    const r = await fetch("ajax/notificaciones.ajax.php",{ method:"POST", body:fd, cache:"no-store" });
    const j = await r.json();
    if (j && j.ok){
      renderNotifList(j.items || []);
      renderNotifCount(j.count || (j.items ? j.items.length : 0));
    }
  }catch(e){
    $("#notifList").html('<div class="px-3 py-3 text-danger">No se pudieron cargar las notificaciones.</div>');
    // opcional: console.error("notifList error:", e);
  }
}

// Init + refresco
$(function(){
  notifCount();
  notifList();
  // refrescar cada 60s
  setInterval(() => { notifCount(); notifList(); }, 60000);
});
