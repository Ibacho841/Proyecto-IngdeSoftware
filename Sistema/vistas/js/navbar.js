// ---------------------------- PROMOCIONES: MODAL SELECCIÓN Y VALIDACIÓN --------------------------------------
(function(){
  var $m = document.getElementById('modalPromociones');
  if (!$m) return;

  var $tabla   = $m.querySelector('#promo_tablaProd');
  var $tbody   = $tabla ? $tabla.tBodies[0] : null;
  var $buscar  = $m.querySelector('#promo_buscarProd');
  var $count   = $m.querySelector('#promo_countSel');
  var $tipo    = $m.querySelector('#promo_tipo');
  var $param   = $m.querySelector('#promo_param');
  var $help    = $m.querySelector('#promo_param_help');
  var $form    = $m.querySelector('#formPromo');
  var $hidden  = $m.querySelector('#promo_hidden_inputs');

  // --- Estado de selección (IDs) ---
  var seleccionados = new Set();

  // Asegurar que cada fila pueda recibir foco via teclado
  function makeRowsFocusable(){
    if (!$tbody) return;
    Array.from($tbody.rows).forEach(function(tr){
      tr.setAttribute('tabindex', '0');
      tr.classList.add('promo_row');   // por si no estaba
      tr.setAttribute('role','row');
      tr.setAttribute('aria-selected', tr.classList.contains('selected') ? 'true' : 'false');
    });
  }
  makeRowsFocusable();

  // --- Mostrar contador e inputs ocultos ---
  function syncHiddenInputs(){
    if (!$hidden) return;
    $hidden.innerHTML = '';
    seleccionados.forEach(function(id){
      var inp = document.createElement('input');
      inp.type = 'hidden';
      inp.name = 'productos[]';
      inp.value = String(id);
      $hidden.appendChild(inp);
    });
  }
  function actualizarContador(){
    if ($count) $count.textContent = String(seleccionados.size);
    syncHiddenInputs();
  }

  // --- Alternar selección de una fila ---
  function toggleRow(tr){
    if (!tr || !('id' in tr.dataset || 'Id' in tr.dataset)){ /* nada */ }
    var id = tr.dataset.id || tr.getAttribute('data-id');
    if (!id) return;

    var isSelected = tr.classList.toggle('selected');
    tr.setAttribute('aria-selected', isSelected ? 'true' : 'false');

    if (isSelected) seleccionados.add(id);
    else seleccionados.delete(id);

    actualizarContador();
  }

  // --- Filtro de búsqueda ---
  function filtrarTabla(q){
    q = (q||'').trim().toLowerCase();
    if (!$tbody) return;
    Array.from($tbody.rows).forEach(function(tr){
      var haystack = (tr.dataset.buscar || '').toLowerCase();
      tr.style.display = (!q || haystack.indexOf(q) !== -1) ? '' : 'none';
    });
  }

  if ($buscar){
    $buscar.addEventListener('input', function(){
      filtrarTabla(this.value);
    });

    // Atajos en el buscador:
    // Enter -> toggle primera visible
    // Ctrl+Enter -> seleccionar todas las visibles
    // Escape -> limpiar
    $buscar.addEventListener('keydown', function(e){
      if (e.key === 'Escape'){
        this.value = '';
        filtrarTabla('');
        e.preventDefault();
        return;
      }
      if (e.key === 'Enter'){
        var visibles = Array.from($tbody.rows).filter(function(tr){return tr.style.display !== 'none';});
        if (visibles.length === 0) return;
        if (e.ctrlKey || e.metaKey){
          visibles.forEach(function(tr){ if (!tr.classList.contains('selected')) toggleRow(tr); });
        } else {
          toggleRow(visibles[0]);
          // mover foco a la primera visible para seguir con ↑/↓
          visibles[0].focus();
        }
        e.preventDefault();
      }
      // ↓ en el buscador mueve el foco a la primera fila visible
      if (e.key === 'ArrowDown'){
        var visibles = Array.from($tbody.rows).filter(function(tr){return tr.style.display !== 'none';});
        if (visibles.length > 0){ visibles[0].focus(); e.preventDefault(); }
      }
    });
  }

  // --- Click en fila ---
  if ($tabla){
    $tabla.addEventListener('click', function(e){
      var tr = e.target.closest('tr.promo_row');
      if (!tr) return;
      toggleRow(tr);
    });

    // Navegación por teclado en la tabla
    $tabla.addEventListener('keydown', function(e){
      var tr = e.target.closest('tr.promo_row');
      if (!tr) return;

      var visibles = Array.from($tbody.rows).filter(function(r){return r.style.display !== 'none';});
      var idx = visibles.indexOf(tr);

      if (e.key === 'Enter' || e.key === ' '){ // Enter o Space -> toggle
        toggleRow(tr);
        e.preventDefault();
      } else if (e.key === 'ArrowDown'){
        if (idx >= 0 && idx < visibles.length - 1){
          visibles[idx+1].focus();
          e.preventDefault();
        }
      } else if (e.key === 'ArrowUp'){
        if (idx > 0){
          visibles[idx-1].focus();
          e.preventDefault();
        } else if ($buscar){ // si estamos en la primera, vuelve al buscador
          $buscar.focus();
          e.preventDefault();
        }
      } else if (e.key === 'Home'){
        if (visibles.length) { visibles[0].focus(); e.preventDefault(); }
      } else if (e.key === 'End'){
        if (visibles.length) { visibles[visibles.length-1].focus(); e.preventDefault(); }
      }
    });
  }

  // --- Lógica de "Parámetro" según tipo ---
  function toggleParametro(){
    var t = $tipo ? $tipo.value : '';
    if (!$param) return;
    if (t === '2x1' || !t){
      $param.value = '';
      $param.disabled = (t === '2x1');
      $param.required = false;
      if ($help) $help.textContent = 'En 2x1 no se requiere parámetro.';
    } else if (t === 'descuento'){
      $param.disabled = false; $param.required = true;
      if ($help) $help.textContent = 'Ej: 10 para 10% de descuento.';
    } else if (t === 'precio_fijo'){
      $param.disabled = false; $param.required = true;
      if ($help) $help.textContent = 'Ej: 1990 para precio fijo.';
    }
  }
  if ($tipo) $tipo.addEventListener('change', toggleParametro);
  toggleParametro();

  // --- Validación al enviar ---
  if ($form){
    $form.addEventListener('submit', function(e){
      // al menos 1 producto
      if (seleccionados.size === 0){
        e.preventDefault();
        return (window.Swal)
          ? Swal.fire('Atención','Seleccione al menos un producto.','warning')
          : alert('Seleccione al menos un producto.');
      }

      // fechas
      var iniEl = document.getElementById('promo_inicio');
      var finEl = document.getElementById('promo_fin');
      var ini = iniEl ? iniEl.value : '';
      var fin = finEl ? finEl.value : '';
      if (!ini || !fin || (new Date(fin) <= new Date(ini))){
        e.preventDefault();
        return (window.Swal)
          ? Swal.fire('Atención','La fecha de fin debe ser posterior a la de inicio.','warning')
          : alert('Fechas inválidas.');
      }

      // parámetro según tipo
      var t = $tipo ? $tipo.value : '';
      var p = $param ? parseFloat($param.value) : NaN;
      if (t === 'descuento'){
        if (isNaN(p) || p <= 0 || p >= 100){
          e.preventDefault();
          return (window.Swal)
            ? Swal.fire('Atención','El descuento debe ser un porcentaje entre 1 y 99.','warning')
            : alert('Descuento inválido.');
        }
      } else if (t === 'precio_fijo'){
        if (isNaN(p) || p < 0){
          e.preventDefault();
          return (window.Swal)
            ? Swal.fire('Atención','El precio fijo debe ser 0 o mayor.','warning')
            : alert('Precio fijo inválido.');
        }
      }
    });
  }

  // --- Reset al abrir el modal ---
  function resetModalState(){
    seleccionados.clear();
    actualizarContador();
    if ($buscar) { $buscar.value = ''; filtrarTabla(''); }
    if ($tbody){
      Array.from($tbody.rows).forEach(function(tr){
        tr.style.display = '';
        tr.classList.remove('selected');
        tr.setAttribute('aria-selected','false');
      });
      // foco inicial al buscador
      if ($buscar) $buscar.focus();
    }
    toggleParametro();
  }

  if (window.jQuery){
    $('#modalPromociones').on('shown.bs.modal', resetModalState);
  } else {
    // fallback: si no usas jQuery/Bootstrap events
    $m.addEventListener('transitionend', function(e){
      if (e.target === $m && $m.classList.contains('show')) resetModalState();
    });
  }
})();

// ---------------------------- NOTIFICACIONES: CARGA Y REFRESCO AUTO --------------------------------------
(function(){
  const URL = 'ajax/notificaciones.ajax.php';
  const $badge = $('#notifBadge');
  const $count = $('#notifCount');
  const $list  = $('#notifList');

  async function cargarNotificaciones(){
    try{
      const resp = await fetch(URL, {cache:'no-store'});
      const data = await resp.json();
      if(!data.ok){ throw new Error('Respuesta inválida'); }

      const items = Array.isArray(data.items) ? data.items : [];
      const n = data.count || items.length;

      // Badge
      if(n > 0){
        $badge.text(n).removeClass('d-none');
      } else {
        $badge.addClass('d-none');
      }
      $count.text('(' + n + ')');

      // Lista
      if(n === 0){
        $list.html('<div class="px-3 py-3 text-muted">Sin alertas por ahora.</div>');
        return;
      }

      const html = items.map(p => {
        const cantidad = parseInt(p.cantidad,10) || 0;
        const minimo   = parseInt(p.stock_minimo,10) || 0;
        const delta    = cantidad - minimo;
        const estado   = (cantidad === 0) ? 'Agotado' : 'Crítico';
        const estadoCls= (cantidad === 0) ? 'text-danger' : 'text-warning';
        const nombre   = (p.nombre || '').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        const codigo   = (p.codigo || '').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        const id       = parseInt(p.id,10) || 0;

        return `
          <a href="inventario?focus=${id}" class="list-group-item list-group-item-action">
            <div class="d-flex w-100 justify-content-between">
              <h6 class="mb-1" style="font-size:.95rem; font-weight:600;">
                ${nombre} <small class="text-muted">[${codigo}]</small>
              </h6>
              <small class="${estadoCls}">${estado}</small>
            </div>
            <p class="mb-1">El siguiente producto está ${cantidad===0 ? 'agotado' : 'bajo el stock mínimo'}.</p>
            <small class="text-muted">
              Cant.: <strong>${cantidad}</strong> · Mín.: <strong>${minimo}</strong> ${cantidad>0 ? `· Diferencia: <strong>${delta}</strong>` : ''}
            </small>
          </a>`;
      }).join('');

      $list.html(html);

    }catch(e){
      $list.html('<div class="px-3 py-3 text-danger">No se pudieron cargar las notificaciones.</div>');
      console.error(e);
    }
  }

  // Carga inicial y refresca cada 60s
  cargarNotificaciones();
  setInterval(cargarNotificaciones, 60000);
})();
