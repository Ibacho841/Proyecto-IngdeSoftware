<?php
require_once "../modelos/producto.modelo.php";
require_once "../controladores/productos.controlador.php";

header('Content-Type: application/json; charset=utf-8');

try {
  $notis = ControladorProductos::ctrProductosStockCritico();
  echo json_encode([
    "ok" => true,
    "count" => count($notis),
    "items" => $notis
  ]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["ok"=>false, "msg"=>"Error al cargar notificaciones"]);
}
