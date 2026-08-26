<?php

// MODELO Y CONTROLADOR
require_once "../controladores/productos.controlador.php";
require_once "../modelos/producto.modelo.php";
require_once "../controladores/ventas.controlador.php";
require_once "../modelos/ventas.modelo.php";

session_start();

header('Content-Type: application/json; charset=utf-8');

$accion = $_POST["accion"] ?? "consultar";

try {

    // Solo responde si hay una sesión iniciada
    if (!isset($_SESSION["iniciarSesion"]) || $_SESSION["iniciarSesion"] !== "ok") {
        echo json_encode(["ok" => false, "msg" => "Sesión no iniciada"]);
        exit;
    }

    // ---------------------------- MARCAR AVISOS COMO MOSTRADOS ----------------------------
    if ($accion === "marcar") {
        $_SESSION["avisosPendientes"] = false;
        echo json_encode(["ok" => true]);
        exit;
    }

    // ---------------------------- CONSULTAR ESTADO DE AVISOS ----------------------------
    $stock  = ["mostrar" => false, "cantidad" => 0, "productos" => []];
    $cierre = ["mostrar" => false, "ventasHoy" => 0];

    // Ventana de avisos abierta solo una vez por sesión (bandera seteada en el login)
    if (!empty($_SESSION["avisosPendientes"])) {

        $rol        = $_SESSION["rol"] ?? "";
        $esAdmin    = ($rol === "Administrador");
        $esVendedor = ($rol === "Vendedor");

        // CU21: Notificando stock bajo o agotado (Administrador y Vendedor)
        if ($esAdmin || $esVendedor) {

            $criticos = ControladorProductos::ctrProductosStockCritico();
            $cantidad = is_array($criticos) ? count($criticos) : 0;

            if ($cantidad > 0) {
                $stock = [
                    "mostrar"   => true,
                    "cantidad"  => $cantidad,
                    "productos" => array_slice(array_column($criticos, "nombre"), 0, 5)
                ];
            }
        }

        // CU22: Notificando cierre de caja pendiente (solo Administrador)
        if ($esAdmin) {

            $ventasHoy = ControladorVentas::ctrObtenerCantidadDelDia();

            if ($ventasHoy > 0 && !ControladorVentas::ctrExisteCierreHoy()) {
                $cierre = [
                    "mostrar"   => true,
                    "ventasHoy" => $ventasHoy
                ];
            }
        }
    }

    echo json_encode([
        "ok"     => true,
        "stock"  => $stock,
        "cierre" => $cierre
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["ok" => false, "msg" => "Error al consultar avisos"]);
}
