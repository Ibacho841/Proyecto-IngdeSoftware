<?php
// Inicia la sesión para poder trabajar con variables de sesión como $_SESSION["iniciarSesion"]
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sistema de Inventario y Ventas</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="vistas/plugins/fontawesome-free/css/all.min.css">

  <!-- AdminLTE -->
  <link rel="stylesheet" href="vistas/dist/css/adminlte.css">

  <!-- CSS personalizados -->
  <link rel="stylesheet" href="vistas/css/inicio.css">
  <link rel="stylesheet" href="vistas/css/sidebar.css">
  <link rel="stylesheet" href="vistas/css/navbar.css">

  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

  <!-- jQuery -->
  <script src="vistas/plugins/jquery/jquery.min.js"></script>

  <!-- Bootstrap Bundle -->
  <script src="vistas/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- AdminLTE -->
  <script src="vistas/js/adminlte.js"></script>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>

<!-- Agrega clases de estilo dependiendo si el usuario está logueado o no -->
<body class="hold-transition sidebar-mini <?php echo (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] != "ok") ? 'login-page' : 'sidebar-collapse'; ?>">

<?php 
// Si el usuario está logueado correctamente
if (isset($_SESSION["iniciarSesion"]) && $_SESSION["iniciarSesion"] == "ok") {

  echo '<div class="wrapper">'; // Contenedor principal para todo el sitio si se está logueado

  // Incluye la barra superior del sitio
  include "html/navbar.php";

  // Incluye el menú lateral
  include "html/sidebar.php";

  // Carga el contenido dinámico según la ruta de la URL
  if (isset($_GET["ruta"])) {
    $ruta = $_GET["ruta"];

    // Permite acceso solo a rutas específicas
    if (
      $ruta == "inicio" ||
      $ruta == "crear-venta" ||
      $ruta == "ver-ventas" ||
      $ruta == "inventario" ||
      $ruta == "cerrar-sesion" ||
      $ruta == "stock-critico" ||
      $ruta == "registrarpc" ||
      $ruta == "historial" ||
      $ruta == "cierre-caja" ||
      $ruta == "ganancias" ||
      

      // Estas rutas son exclusivas del rol "Administrador"
      ($ruta == "crear-producto" && $_SESSION["rol"] == "Administrador") ||
      ($ruta == "anadir-stock" && $_SESSION["rol"] == "Administrador") ||
      ($ruta == "usuarios" && $_SESSION["rol"] == "Administrador")||
      ($ruta == "productos-vendidos" && $_SESSION["rol"] == "Administrador")||
      ($ruta == "reporte-ventas" && $_SESSION["rol"] == "Administrador")
    ) {
      // Incluye el archivo PHP correspondiente a la ruta
      include "html/" . $ruta . ".php";
    }

  } else {
    // Si no hay ruta, se carga por defecto la página de inicio
    include "html/inicio.php";
  }

  // Incluye el pie de página
  include "html/footer.php";

  echo '</div>'; // Cierra el contenedor principal

} else {
  // Si no se ha iniciado sesión, muestra solo el formulario de login
  include "html/login.php";
}
?>

</div> <!-- Esto parece un div suelto innecesario, podrías eliminarlo si no da problemas -->

<!-- Avisos emergentes al iniciar sesión (stock crítico / cierre de caja pendiente) -->
<script src="vistas/js/avisos.js"></script>

</body>
</html>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("btnCalcular");
  const tipoSelect = document.getElementById("tipoPeriodo");
  const resultadoDiv = document.getElementById("resultadoGanancia");
  const monto = document.getElementById("gananciaPeriodo");
  const detalle = document.getElementById("detallePeriodo");

  btn.addEventListener("click", async () => {
    const tipo = tipoSelect.value;

    try {
      const response = await fetch("ajax/ganancias.ajax.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `accion=calcularGanancia&periodo=${tipo}`,
      });

      const data = await response.json();
      if (!data.ok) throw new Error(data.error || "Error desconocido");

      const formatoCLP = new Intl.NumberFormat("es-CL", {
        style: "currency",
        currency: "CLP",
        minimumFractionDigits: 0,
      });

      monto.textContent = formatoCLP.format(data.ganancia);
      detalle.textContent = `Período: ${data.descripcion}`;
      resultadoDiv.style.display = "block";
    } catch (err) {
      Swal.fire({
        icon: "error",
        title: "Error al calcular ganancia",
        text: err.message,
      });
    }
  });
});
</script>
