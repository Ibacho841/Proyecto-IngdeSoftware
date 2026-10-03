// ---------------------------- FORMATEAR MONEDA CLP --------------------------------------
function CLP(n){ 
  n = Math.round(Number(n)||0); 
  return n.toLocaleString("es-CL"); 
}

// ---------------------------- SIMULAR PROMOCIÓN PARA UNA LÍNEA --------------------------------------
function simularPromo(p, precioUnit, cantidad){
  const tipo  = p.tipo;
  const param = Number(p.parametro||0);
  const base  = Number(precioUnit);
  const qty   = Number(cantidad);

  const out = {
    id_promocion: p.id,
    tipo, parametro: param, observacion: p.obs || "",
    precio_unit_final: base,
    paga_unidades: qty, gratis: 0,
    total_linea: base * qty,
    etiqueta: "", detalle: "", show_tachado: false
  };

  if(tipo === "descuento"){
    const pf = Math.round(base * (1 - param/100));
    out.precio_unit_final = pf;
    out.total_linea = pf * qty;
    out.etiqueta = `-${param}%`;
    out.detalle  = `Descuento de ${param}% aplicado`;
    out.show_tachado = true;
  }else if(tipo === "precio_fijo"){
    const pf = Math.round(param);
    out.precio_unit_final = pf;
    out.total_linea = pf * qty;
    out.etiqueta = "Precio fijo";
    out.detalle  = `Precio fijo promocional $${CLP(pf)}`;
    out.show_tachado = true;
  }else if(tipo === "2x1"){
    const pares = Math.floor(qty/2), resto = qty%2, paga = pares+resto, gratis = qty - paga;
    out.paga_unidades = paga;
    out.gratis = gratis;
    out.total_linea = paga * base;        // clave: en 2x1 pagas solo 'paga'
    out.etiqueta = "2x1";
    out.detalle  = `Promo 2x1: ${gratis} unidad(es) gratis`;
  }
  return out;
}

// ---------------------------- ELEGIR MEJOR PROMO (MENOR TOTAL) --------------------------------------
function mejorPromo(productId, precioUnit, cantidad){
  const promos = (window.MAPA_PROMOS && window.MAPA_PROMOS[productId]) || [];
  if(!promos.length) return null;
  let best = null, bestTotal = Number(precioUnit)*Number(cantidad);
  for(const p of promos){
    const sim = simularPromo(p, precioUnit, cantidad);
    if(sim.total_linea < bestTotal){ best = sim; bestTotal = sim.total_linea; }
  }
  return best;
}

// ---------------------------- APLICAR PROMO EN FILA DE CARRITO --------------------------------------
// ---------------------------- APLICAR PROMO EN FILA DE CARRITO --------------------------------------
function aplicarPromoEnFila($row){
  const $nombreInput = $row.find("input.agregarProducto");
  const idProducto   = Number($nombreInput.attr("idProducto"));

  const $qty = $row.find("input.nuevaCantidad");
  const qty  = Math.max(1, Number($qty.val() || 1));

  const $precio  = $row.find("input.nuevoPrecio");
  const baseUnit = Number($precio.attr("precioReal")); // precio unitario original

  const promo = mejorPromo(idProducto, baseUnit, qty);

  // total de línea por defecto (sin promo)
  let totalLinea   = baseUnit * qty;
  let badgeHtml    = "";      // arriba del nombre
  let tachadoHtml  = "";      // arriba del precio

  if (promo){
    totalLinea = promo.total_linea;

    const cls = promo.tipo === "descuento"   ? "is-descuento"
              : promo.tipo === "precio_fijo" ? "is-precio"
              : "is-2x1";

    badgeHtml = `
      <span class="promo-pill ${cls}" data-tipo="${promo.tipo}" title="${promo.detalle}">
        ${promo.etiqueta}
      </span>`;

    if (promo.show_tachado){
      tachadoHtml = `<span class="text-muted"><s>$${CLP(baseUnit)}</s></span>`;
    }

    // Guardar la promo aplicada en la fila (para backend o logs)
    $row.data("promoAplicada", {
      id_promocion:       promo.id_promocion,
      tipo:               promo.tipo,
      parametro:          promo.parametro,
      etiqueta:           promo.etiqueta,
      detalle:            promo.detalle,
      observacion:        promo.observacion,
      precio_unit_base:   baseUnit,
      precio_unit_final:  promo.precio_unit_final,
      paga_unidades:      promo.paga_unidades,
      gratis:             promo.gratis
    });

  } else {
    $row.removeData("promoAplicada");
  }

  // --- CONTENEDOR PILL (arriba del nombre) ---
  let $badge = $row.find(".promo-badge");
  if(!$badge.length){
    $badge = $('<div class="promo-badge"></div>');
    // IMPORTANTE: ahora va ANTES del input, para que quede arriba
    $nombreInput.before($badge);
  }
  // si no hay promo, dejamos el contenedor vacío para conservar altura
  $badge.html(badgeHtml || "");

  // --- PRECIO ORIGINAL TACHADO (arriba del precio) ---
  let $orig = $row.find(".precio-original");
  if(!$orig.length){
    $orig = $('<div class="precio-original"></div>');
    // este contenedor ya estaba bien ubicado: arriba del input de precio
    $row.find(".ingresoPrecio").prepend($orig);
  }
  $orig.html(tachadoHtml || ""); // vacío = mantiene altura por CSS

  // --- Total de la línea (su input) ---
  $precio.val( CLP(totalLinea) );
}

// ---------------------------- BOTON: AGREGAR PRODUCTO --------------------------------------
$(document).on("click", ".btnAgregarProducto", function () {

  var $btn = $(this);
  var idProducto = $btn.attr("idProducto");

  if ($btn.hasClass("btn-default") || $btn.prop("disabled")) return;

  var datos = new FormData();
  datos.append("idProducto", idProducto);

  $.ajax({
    url: "ajax/productos.ajax.php",
    method: "POST",
    data: datos,
    cache: false,
    contentType: false,
    processData: false,
    dataType: "json",
    success: function (respuesta) {

      var nombre = respuesta["nombre"];
      var stock  = parseInt(respuesta["cantidad"], 10) || 0;
      var precio = parseFloat(respuesta["precio_venta"]) || 0;

      if (stock <= 0) {
        Swal.fire({
          icon: "warning",
          title: "Sin stock",
          text: "Este producto no tiene stock disponible.",
          confirmButtonText: "Entendido"
        });
        return;
      }

      $btn.removeClass("btn-primary").addClass("btn-default").prop("disabled", true);

      // Agregar al detalle (solo añadí .promo-badge y .precio-original como slots)
      $(".nuevoProducto").append(
        '<div class="row align-items-center py-2 border-bottom text-center producto-item productoVenta">' +

          // Nombre + slot de badge
          '<div class="col-sm-4">' +
            '<input type="text" class="form-control agregarProducto" name="agregarProducto" idProducto="' + idProducto + '" value="' + nombre + '" readonly>' +
            '<span class="promo-badge ms-2"></span>' +
          '</div>' +

          // Cantidad + botones
          '<div class="col-sm-2">' +
            '<div class="input-group justify-content-center">' +
              '<button class="btn btn-sm btn-outline-secondary btnRestar" type="button">–</button>' +
              '<input type="number" class="form-control form-control-sm nuevaCantidad text-center" name="nuevaCantidad" min="1" step="1" stock="' + stock + '" value="1" style="width: 60px;">' +
              '<button class="btn btn-sm btn-outline-secondary btnSumar" type="button">+</button>' +
            '</div>' +
          '</div>' +

          // Precio (total línea) + slot para precio original tachado
          '<div class="col-sm-3 ingresoPrecio">' +
            '<div class="precio-original small mb-1"></div>' +
            '<div class="input-group">' +
              '<span class="input-group-text">$</span>' +
              '<input type="text" class="form-control nuevoPrecio" precioReal="' + precio + '" name="nuevoPrecio" value="' + Number(precio).toLocaleString("es-CL") + '" readonly required>' +
            '</div>' +
          '</div>' +

          // Botón eliminar
          '<div class="col-sm-3">' +
            '<button type="button" class="btn btn-danger btn-sm btnQuitarProducto" idProducto="' + idProducto + '">' +
              '<i class="fa fa-trash"></i> Eliminar' +
            '</button>' +
          '</div>' +

        '</div>'
      );

      // === aplicar promo de inmediato sobre la fila recién creada ===
      var $rowNueva = $(".nuevoProducto .producto-item").last();
      aplicarPromoEnFila($rowNueva);

      sumarTotal();
      guardarProductos();
    },
  });
});

// ---------------------------- BOTON: QUITAR PRODUCTO --------------------------------------
$(document).on("click", ".btnQuitarProducto", function(){
  var id = $(this).attr("idProducto");
  $(this).closest(".producto-item").remove();
  $("button.btnAgregarProducto[idProducto='"+id+"']")
    .removeClass("btn-default").addClass("btn-primary").prop("disabled", false);
  sumarTotal();
  guardarProductos();
});

// ---------------------------- CAMBIAR CANTIDAD (VENTA) --------------------------------------
$(".formularioVenta").on("input change", "input.nuevaCantidad", function () {

  const contenedorProducto = $(this).closest(".producto-item");
  const inputPrecio = contenedorProducto.find(".nuevoPrecio");
  const precioReal = Number(inputPrecio.attr("precioReal"));
  let cantidad = Number($(this).val());
  const stockDisponible = Number($(this).attr("stock"));

  // Solo cantidades enteras: si viene un decimal (ej. 1,5) se baja al entero
  if (!Number.isInteger(cantidad)) {
    cantidad = Math.max(1, Math.floor(cantidad));
    $(this).val(cantidad);
  }

  if (isNaN(cantidad) || cantidad < 1) {
    cantidad = 1; $(this).val(1);
  }

  if (cantidad > stockDisponible) {
    Swal.fire({
      title: 'La cantidad supera el stock disponible',
      text: "Solo hay " + stockDisponible + " unidades",
      icon: 'warning',
      confirmButtonColor: '#3085d6',
      confirmButtonText: 'Cerrar'
    });
    cantidad = stockDisponible; $(this).val(stockDisponible);
  }

  // >>> aplicar promo sobre esta fila (en lugar de multiplicar a secas)
  aplicarPromoEnFila(contenedorProducto);

  sumarTotal();
  guardarProductos();
});

$(".formularioVenta").on("submit", function(e) {
  e.preventDefault();
});

// ---------------------------- SUMAR TOTAL VENTA --------------------------------------
function sumarTotal() {
  var precioProducto = $(".nuevoPrecio");
  var arrayTotal = [];

  for (var i = 0; i < precioProducto.length; i++) {
    var valor = Number($(precioProducto[i]).val().replace(/\./g, ""));
    arrayTotal.push(valor);
  }

  var sumaTotal = arrayTotal.reduce((total, numero) => total + numero, 0);
  $("#totalVenta").val(sumaTotal.toLocaleString("es-CL")); 
}

// ---------------------------- SELECCIONAR MÉTODO DE PAGO --------------------------------------
$("#metodoPago").change(function () {
  var metodo = $(this).val();

  if (metodo == "efectivo") {
    $("#contenedorMetodos").html(
      '<div class="row">' +
        '<div class="col-md-6">' +
          '<label class="fw-bold">Recibido</label>' +
          '<div class="input-group mb-2">' +
            '<span class="input-group-text">$</span>' +
            '<input type="number" class="form-control valorRecibido" placeholder="0" required>' +
          '</div>' +
        '</div>' +
        '<div class="col-md-6">' +
          '<label class="fw-bold">Vuelto</label>' +
          '<div class="input-group mb-2">' +
            '<span class="input-group-text">$</span>' +
            '<input type="text" class="form-control vueltoEfectivo" readonly value="0">' +
          '</div>' +
        '</div>' +
      '</div>'
    );
  } else {
    $("#contenedorMetodos").html('');
  }
});

// ---------------------------- CALCULAR VUELTO EN EFECTIVO --------------------------------------
$(document).on("input", ".valorRecibido", function () {
  var efectivo   = Number($(this).val());
  var totalTexto = $(".totalVenta").val();
  var total      = Number(totalTexto.replace(/\./g, ""));
  var cambio     = efectivo - total;
  $(".vueltoEfectivo").val( cambio >= 0 ? cambio.toLocaleString("es-CL") : "0" );
});

// ---------------------------- GUARDAR VENTA (SUBMIT FORMULARIO) --------------------------------------
$(".formularioVenta").off("submit").on("submit", function (e) {
  e.preventDefault();

  guardarProductos();
  guardarMetodos();

  if (!$("#metodoPago").val()) {
    Swal.fire({ icon: "warning", title: "Método de pago requerido", text: "Por favor selecciona un método de pago." });
    return;
  }

  if ($(".nuevoProducto .productoVenta").length === 0) {
    Swal.fire({ icon: "warning", title: "Sin productos", text: "Debes agregar al menos un producto antes de guardar la venta." });
    return;
  }

  if (typeof sumarTotal === 'function') sumarTotal();
  let totalLimpio = $("#totalVenta").val().replace(/\./g, "");
  $("#totalVenta").val(totalLimpio);

  var metodo = $("#metodoPago").val();
  var total  = Number(totalLimpio || 0);

  if (metodo === "efectivo") {
    var efectivo = Number($(".valorRecibido").val() || 0);
    if (efectivo < total) {
      Swal.fire("Atención", "El efectivo recibido no puede ser menor al total de la venta.", "warning");
      return;
    }
  }
  this.submit();
});

// ---------------------------- GUARDAR PRODUCTOS --------------------------------------
function guardarProductos(){
  var listaProductos  = [];
  $(".nuevoProducto .productoVenta").each(function(){
    var $row      = $(this);
    var $nombre   = $row.find(".agregarProducto");
    var $cantidad = $row.find(".nuevaCantidad");
    var $precio   = $row.find(".nuevoPrecio");

    var precioTotalNum = Number(String($precio.val()).replace(/\./g,"")) || 0;
    var promo = $row.data("promoAplicada") || null;

    listaProductos.push({
      "id": $nombre.attr("idProducto"),
      "nombre": $nombre.val(),
      "cantidad": $cantidad.val(),
      "precioUnitario": $precio.attr("precioReal"),  // unitario base
      "precioTotal": precioTotalNum,                 // total de la línea
      "promocion": promo                             // NUEVO
    });
  });
  $("#listaProductos").val(JSON.stringify(listaProductos));
}

// ---------------------------- GUARDAR MÉTODO DE PAGO --------------------------------------
function guardarMetodos() {
  var metodo = $("#metodoPago").val();
  if (metodo === "efectivo") {
    $("#listaMetodoPago").val("Efectivo");
  } else if (metodo === "tarjeta") {
    $("#listaMetodoPago").val("Tarjeta");
  } else {
    $("#listaMetodoPago").val("");
  }
}

// ---------------------------- BOTÓN SUMAR CANTIDAD --------------------------------------
$(".formularioVenta").on("click", ".btnSumar", function () {
  const grupo = $(this).closest(".input-group");
  const input = grupo.find(".nuevaCantidad");
  let cantidad = parseInt(input.val());
  const stock = parseInt(input.attr("stock"));

  if (cantidad < stock) {
    input.val(++cantidad).trigger("change");
  } else {
    Swal.fire({
      icon: "warning",
      title: "Stock insuficiente",
      text: "No puedes agregar más unidades que el stock disponible."
    });
  }
});

// ---------------------------- BOTÓN RESTAR CANTIDAD --------------------------------------
$(".formularioVenta").on("click", ".btnRestar", function () {
  const grupo = $(this).closest(".input-group");
  const input = grupo.find(".nuevaCantidad");
  let cantidad = parseInt(input.val());

  if (cantidad > 1) {
    input.val(--cantidad).trigger("change");
  }
});

// ---------------------------- BUSCAR PRODUCTOS --------------------------------------
$(function () {
  const tabla = $('#tablaProductos').DataTable({
    dom: 't<"d-flex justify-content-between align-items-center mt-2"ip>',
    searching: true,
    pageLength: 8,
    order: [[2, 'asc']], // por defecto: Nombre
    language: {
      decimal: ",", thousands: ".",
      lengthMenu: "Mostrar _MENU_ registros",
      info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
      infoEmpty: "Mostrando 0 a 0 de 0 registros",
      infoFiltered: "(filtrado de _MAX_ registros totales)",
      loadingRecords: "Cargando...",
      zeroRecords: "No se encontraron resultados",
      emptyTable: "Ningún dato disponible en la tabla",
      paginate: { first: "Primero", previous: "Anterior", next: "Siguiente", last: "Último" }
    },
    columnDefs: [
      { targets: 0, orderable: false, searchable: false }, // imagen
      { targets: 8, orderable: false, searchable: false }, // acciones
      { // stock (col 6) -> numérico
        targets: 6,
        render: function (data, type) {
          if (type === 'sort' || type === 'type') {
            const n = parseFloat(String(data).replace(/[^0-9.-]/g, ''));
            return isNaN(n) ? 0 : n;
          }
          return data;
        }
      },
      { // precio (col 7) -> numérico
        targets: 7,
        render: function (data, type) {
          if (type === 'sort' || type === 'type') {
            const n = parseFloat(String(data).replace(/[^0-9.-]/g, ''));
            return isNaN(n) ? 0 : n;
          }
          return data;
        }
      }
    ]
  });

  // búsqueda normalizada (minúsculas + sin acentos)
  const norm = s => (s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  const $input = $('#buscarVentaProducto');
  const $btn   = $('#btnBuscarVentaProducto');
  const doSearch = () => tabla.search( norm($input.val()) ).draw();

  $input.on('input', doSearch);
  $btn.on('click', doSearch);
});

// ---------------------------- MOSTRAR/OCULTAR OBSERVACIÓN --------------------------------------
$(document).ready(function () {
  $("#btnMostrarObservacion").on("click", function () {
    // Toggle del campo
    $("#campoObservacion").slideToggle();

    // Cambia el texto y estilo del botón según estado
    if ($(this).text().includes("Agregar")) {
      $(this).text("Ocultar observación");
      $(this).removeClass("btn-outline-secondary").addClass("btn-outline-danger");
    } else {
      $(this).text("Agregar observación");
      $(this).removeClass("btn-outline-danger").addClass("btn-outline-secondary");
    }
  });
});

// ---------------------------- PROMO SHOWCASE --------------------------------------
(function(){
  var panel = document.getElementById('promoShowcaseBody');
  var btn   = document.querySelector('.promo-toggle');
  if (!panel || !btn) return;

  // Recordar estado (opcional)
  var st = localStorage.getItem('promoShowcaseOpen');
  if (st === '1') { $(panel).addClass('show'); btn.textContent = 'Ocultar'; }

  $(panel).on('shown.bs.collapse',  function(){ btn.textContent = 'Ocultar'; localStorage.setItem('promoShowcaseOpen','1'); });
  $(panel).on('hidden.bs.collapse', function(){ btn.textContent = 'Mostrar'; localStorage.setItem('promoShowcaseOpen','0'); });
})();

// ---------------------------- ELIMINAR PROMOCIÓN --------------------------------------
$(document).on("click", ".btnEliminarPromocion", function(){

    // Obtener el ID de la promoción desde el botón
    var idPromocion = $(this).attr("idPromocion");

    // Confirmación con SweetAlert2
    Swal.fire({
        title: '¿Está seguro de eliminar la promoción?',
        text: "¡Esta acción no se puede deshacer!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Redirige a la misma página (crear-venta) con el ID
            window.location = "index.php?ruta=crear-venta&idPromocion=" + idPromocion;
        }
    });
});

// ---------------------------- CIERRE DE CAJA (CHECK + BOTÓN) --------------------------------------
(function () {
    var chk = document.getElementById('chkConfirmaCierre');
    var btn = document.getElementById('btnConfirmarCierre');
    var form = document.getElementById('formCierreCaja');

    if (chk && btn) {
      chk.addEventListener('change', function () {
        btn.disabled = !chk.checked;
      });
    }

    if (form && btn) {
      form.addEventListener('submit', function () {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2"></span>Guardando...';
      });
    }
  })();

// ---------------------------- ESTADO INICIAL BOTÓN CIERRE DE CAJA --------------------------------------
document.addEventListener('DOMContentLoaded', function () {
  const chk = document.getElementById('chkConfirmaCierre');
  const btn = document.getElementById('btnConfirmarCierre');
  if (chk && btn) btn.disabled = !chk.checked;
  if (chk && btn) chk.addEventListener('change', () => { btn.disabled = !chk.checked; });
});

// ---------------------------- CIERRE DE CAJA ----------------------------
document.addEventListener("DOMContentLoaded", () => {
  const inputBoletas = document.getElementById("montoBoletasDigitales");
  const spanRetencionBoletas = document.getElementById("retencionBoletasDigitales");
  const totalGeneralElem = document.getElementById("totalGeneral");
  const totalFinalElem = document.getElementById("totalFinal");
  const gananciaDiaElem = document.getElementById("gananciaDia");
  const reinvLocalElem = document.getElementById("reinvLocal");
  const chkConfirma = document.getElementById("chkConfirmaCierre");
  const btnConfirmar = document.getElementById("btnConfirmarCierre");

  // Elementos opcionales (solo domingo)
  const filaDescuentoLuz = document.getElementById("filaDescuentoLuz");
  const montoDescuentoLuzElem = document.getElementById("montoDescuentoLuz");
  const montoReinversionSemanalElem = document.getElementById("montoReinversionSemanal");
  const montoReinversionSemanalFinalElem = document.getElementById("montoReinversionSemanalFinal");

  const esDomingo = !!filaDescuentoLuz;

  const reinversionSemanalBase = montoReinversionSemanalElem
    ? parseMonto(montoReinversionSemanalElem.textContent)
    : 0;

  // ---------------- FUNCIONES AUXILIARES ----------------
  function parseMonto(texto) {
    if (!texto) return 0;
    // Detectar si hay signo negativo en el texto
    const esNegativo = texto.includes("-");
    // Extraer solo los números y convertirlos a float
    const valor = parseFloat(texto.replace(/[^0-9,]+/g, "").replace(",", ".")) || 0;
    // Si hay "-", devolverlo como negativo
    return esNegativo ? -valor : valor;
  }

  const formatoCLP = new Intl.NumberFormat("es-CL", {
    style: "currency",
    currency: "CLP",
    minimumFractionDigits: 0,
  });

  function obtenerRetencionTarjeta() {
    const elem = document.getElementById("retencionTarjeta");
    return elem ? parseMonto(elem.textContent) : 0;
  }

  // ---------------- FUNCIÓN CENTRAL ----------------
  function recalcularCierre() {
    // Valor de boletas digitales
    const boletas = inputBoletas?.value.trim() === "" ? 0 : parseFloat(inputBoletas.value) || 0;
    const retencionBoletas = boletas > 0 ? boletas * 0.2 : 0;

    // Actualiza visualmente la retención de boletas digitales
    if (spanRetencionBoletas)
      spanRetencionBoletas.textContent =
        retencionBoletas > 0 ? "-" + formatoCLP.format(retencionBoletas) : "-$0";

    const totalGeneral = parseMonto(totalGeneralElem.textContent);
    const retencionTarjeta = obtenerRetencionTarjeta();
    const descuentoContadora = 5000;
    const descuentoLuz =
      esDomingo && montoDescuentoLuzElem ? parseMonto(montoDescuentoLuzElem.textContent) : 0;

    // Normalizar valores para evitar sumar negativos
    const retencionBoletasPos = Math.abs(retencionBoletas);
    const retencionTarjetaPos = Math.abs(retencionTarjeta);

    // Cálculo correcto del total final
    let totalFinal =
      totalGeneral - retencionBoletasPos - retencionTarjetaPos - descuentoContadora;
    totalFinal = Math.max(totalFinal, 0);
    totalFinalElem.textContent = formatoCLP.format(totalFinal);

    // Ganancia (20%) y reinversión diaria (80%)
    const ganancia = totalFinal * 0.2;
    const reinversionDia = totalFinal * 0.8;
    gananciaDiaElem.textContent = formatoCLP.format(ganancia);
    reinvLocalElem.textContent = formatoCLP.format(reinversionDia);

    // Solo recalcular reinversión semanal si es domingo
    if (esDomingo && montoReinversionSemanalElem) {
      const reinversionSemanalActualizada = reinversionSemanalBase + reinversionDia;

      // Forzar el descuento a positivo (para que siempre reste)
      const descuentoLuzValor = Math.abs(
        parseMonto(montoDescuentoLuzElem?.textContent || "0")
      );

      const reinversionSemanalFinal = Math.max(
        reinversionSemanalActualizada - descuentoLuzValor,
        0
      );

      montoReinversionSemanalElem.textContent = formatoCLP.format(
        reinversionSemanalActualizada
      );

      if (montoReinversionSemanalFinalElem)
        montoReinversionSemanalFinalElem.textContent = formatoCLP.format(
          reinversionSemanalFinal
        );
    }
  }

  // ---------------- INICIALIZACIÓN ----------------
  recalcularCierre();
  if (inputBoletas) inputBoletas.addEventListener("input", recalcularCierre);

  if (chkConfirma && btnConfirmar)
    chkConfirma.addEventListener("change", () => {
      btnConfirmar.disabled = !chkConfirma.checked;
    });

  // Colores visuales
  if (filaDescuentoLuz) filaDescuentoLuz.style.backgroundColor = "#fff3cd";
  if (montoReinversionSemanalElem)
    montoReinversionSemanalElem.closest("tr").style.backgroundColor = "#e3f2fd";

  // Ocultar reinversión semanal si no es domingo
  if (!esDomingo && montoReinversionSemanalElem)
    montoReinversionSemanalElem.closest("tr").style.display = "none";
});

$('#modalResumenMetodoPago').on('shown.bs.modal', function () {
  $.ajax({
    url: "../ajax/ventas.ajax.php",
    method: "POST",
    data: { accion: "resumen_metodo_pago" },
    dataType: "json",
    success: function(respuesta) {
      console.log("Respuesta AJAX:", respuesta); // 🔍 Ver en consola
      if (respuesta.error) {
        $("#tablaResumenMetodoPago").html(`<tr><td colspan='3'>${respuesta.error}</td></tr>`);
        return;
      }
      let html = "";
      let totalGeneral = 0;
      if (respuesta.length === 0) {
        html = "<tr><td colspan='3'>No hay ventas registradas hoy.</td></tr>";
      } else {
        respuesta.forEach(r => {
          totalGeneral += parseFloat(r.monto_total);
          html += `
            <tr>
              <td>${r.metodo_pago}</td>
              <td>${r.cantidad_ventas}</td>
              <td>$${parseFloat(r.monto_total).toLocaleString()}</td>
            </tr>`;
        });
        html += `
          <tr class="font-weight-bold bg-light">
            <td colspan="2">TOTAL GENERAL</td>
            <td>$${totalGeneral.toLocaleString()}</td>
          </tr>`;
      }
      $("#tablaResumenMetodoPago").html(html);
    },
    error: function(xhr, status, error) {
      $("#tablaResumenMetodoPago").html(`<tr><td colspan='3'>Error: ${error}</td></tr>`);
      console.error("Error AJAX:", xhr.responseText);
    }
  });
});
