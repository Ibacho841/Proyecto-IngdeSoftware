// ---------------------------- AVISOS AL INICIAR SESIÓN (CU21 stock crítico / CU22 cierre pendiente) --------------------------------------

// Consulta el estado de ambos avisos. No muta la sesión.
async function consultarAvisos() {
  try {
    const fd = new FormData();
    fd.append("accion", "consultar");
    const r = await fetch("ajax/avisos.ajax.php", { method: "POST", body: fd, cache: "no-store" });
    const j = await r.json();
    if (j && j.ok) return j;
  } catch (e) {
    // Silencioso: si falla la consulta, simplemente no se muestran avisos.
  }
  return null;
}

// Se llama desde el "didOpen" del modal, ya pintado en pantalla
function marcarAvisoMostrado() {
  const fd = new FormData();
  fd.append("accion", "marcar");
  fetch("ajax/avisos.ajax.php", { method: "POST", body: fd, cache: "no-store" }).catch(() => {});
}

function construirBloqueStock(stock) {
  const lista = stock.productos.map(n => `• ${n}`).join("<br>");
  const restantes = stock.cantidad - stock.productos.length;
  const extra = restantes > 0 ? `<br><small>y ${restantes} más…</small>` : "";
  return `<div class="text-left">
    <strong>Stock bajo o agotado</strong><br>
    Hay <strong>${stock.cantidad}</strong> producto(s) con stock crítico:<br>${lista}${extra}
  </div>`;
}

function construirBloqueCierre(cierre) {
  return `<div class="text-left">
    <strong>Cierre de caja pendiente</strong><br>
    Hoy se registraron <strong>${cierre.ventasHoy}</strong> venta(s) y aún no se ha realizado el cierre de caja del día.
  </div>`;
}

function mostrarAvisos(datos) {
  const hayStock  = !!(datos.stock  && datos.stock.mostrar);
  const hayCierre = !!(datos.cierre && datos.cierre.mostrar);

  if (!hayStock && !hayCierre) return;

  let config;

  if (hayStock && hayCierre) {
    // Modal combinado (no encadenado)
    config = {
      icon: "warning",
      title: "Avisos pendientes",
      html: construirBloqueStock(datos.stock) + "<hr>" + construirBloqueCierre(datos.cierre),
      confirmButtonText: "Ver stock crítico",
      showDenyButton: true,
      denyButtonText: "Ir a Cierre de Caja",
      showCancelButton: true,
      cancelButtonText: "Cerrar",
      didOpen: marcarAvisoMostrado
    };
  } else if (hayStock) {
    config = {
      icon: "warning",
      title: "Stock bajo o agotado",
      html: construirBloqueStock(datos.stock),
      confirmButtonText: "Ver stock crítico",
      showCancelButton: true,
      cancelButtonText: "Cerrar",
      didOpen: marcarAvisoMostrado
    };
  } else {
    config = {
      icon: "info",
      title: "Cierre de caja pendiente",
      html: construirBloqueCierre(datos.cierre),
      confirmButtonText: "Ir a Cierre de Caja",
      showCancelButton: true,
      cancelButtonText: "Cerrar",
      didOpen: marcarAvisoMostrado
    };
  }

  Swal.fire(config).then((result) => {
    if (hayStock && hayCierre) {
      if (result.isConfirmed) window.location = "index.php?ruta=stock-critico";
      else if (result.isDenied) window.location = "index.php?ruta=cierre-caja";
    } else if (hayStock) {
      if (result.isConfirmed) window.location = "index.php?ruta=stock-critico";
    } else {
      if (result.isConfirmed) window.location = "index.php?ruta=cierre-caja";
    }
  });
}

$(function () {
  if (typeof Swal === "undefined") return;

  consultarAvisos().then((datos) => {
    if (datos) mostrarAvisos(datos);
  });
});
