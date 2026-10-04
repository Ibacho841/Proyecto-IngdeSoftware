<?php

class ControladorProductos{
    
    /* ===================================== PRODUCTOS =============================================== */
    // ---------------------------- AGREGAR PRODUCTO --------------------------------------
    public static function ctrCrearProducto() { 

        if (!isset($_POST["nuevoCodigo"])) {
            return;
        }

        // ---------- Validaciones de campos ----------
        $ok = (
            preg_match('/^[0-9]+$/', $_POST["nuevoCodigo"]) && 
            mb_strlen($_POST["nuevoNombreProducto"], 'UTF-8') <= 100 &&
            preg_match('/^[\p{L}\p{N}\s°\.\,\-\(\)\/&%+\#:\'"]+$/u', $_POST["nuevoNombreProducto"]) && 
            preg_match('/^[a-zA-Z0-9\sáéíóúÁÉÍÓÚñÑ().,-]+$/u', $_POST["nuevoFormato"]) &&   
            preg_match('/^[0-9a-zA-Z\s°mlMLccCCL.]+$/', $_POST["nuevoTamano"]) && 
            mb_strlen($_POST["nuevaMarca"], 'UTF-8') <= 50 &&
            preg_match('/^[\p{L}\p{N}\s\.\-&\'"]+$/u', $_POST["nuevaMarca"]) && 
            preg_match('/^[0-9]+$/', $_POST["nuevaCantidad"]) && 
            preg_match('/^\d+(\.\d{1,2})?$/', $_POST["nuevoPrecioCompra"]) && 
            preg_match('/^\d+(\.\d{1,2})?$/', $_POST["nuevoPrecioVenta"]) && 
            preg_match('/^[a-zA-Z0-9\sáéíóúÁÉÍÓÚñÑ.\-\/&]+$/u', $_POST["nuevoProveedor"])
        );

        if (!$ok) {
            echo '<script>
                Swal.fire({
                    icon:"error",
                    title:"Datos inválidos",
                    text:"Revise los campos del formulario (especialmente Nombre o Marca)."
                });
            </script>';
            return;
        }

        // ---------- Fecha opcional ----------
        $fechaV = isset($_POST["nuevaFechaVencimiento"]) ? trim($_POST["nuevaFechaVencimiento"]) : "";
        $fechaV = ($fechaV === "") ? null : $_POST["nuevaFechaVencimiento"];

        // ---------- Imagen opcional ----------
        $rutaImagen = null;
        if (isset($_FILES["nuevaImagen"]) && $_FILES["nuevaImagen"]["error"] === UPLOAD_ERR_OK) {
            $tmp  = $_FILES["nuevaImagen"]["tmp_name"];
            $size = $_FILES["nuevaImagen"]["size"];
            $name = $_FILES["nuevaImagen"]["name"];

            if ($size <= 2 * 1024 * 1024) { // 2 MB
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if ($ext === "jpeg") $ext = "jpg";
                if (in_array($ext, ["jpg", "png"])) {
                    $carpeta = "vistas/imagenes/productos";
                    if (!is_dir($carpeta)) {
                        mkdir($carpeta, 0755, true);
                    }
                    $archivoNuevo = $carpeta . "/" . uniqid("prod_") . "." . $ext;
                    if (move_uploaded_file($tmp, $archivoNuevo)) {
                        $rutaImagen = $archivoNuevo;
                    }
                }
            }
        }

        // ---------- Insertar producto ----------
        $tabla = "producto";
        $datos = array( 
            "codigo"            => $_POST["nuevoCodigo"], 
            "nombre"            => $_POST["nuevoNombreProducto"], 
            "formato"           => $_POST["nuevoFormato"], 
            "tamano"            => $_POST["nuevoTamano"], 
            "marca"             => $_POST["nuevaMarca"], 
            "cantidad"          => (int) $_POST["nuevaCantidad"],
            "precio_compra"     => $_POST["nuevoPrecioCompra"],
            "precio_venta"      => $_POST["nuevoPrecioVenta"], 
            "fecha_vencimiento" => $fechaV,
            "proveedor"         => $_POST["nuevoProveedor"], 
            "imagen"            => $rutaImagen
        ); 

        $respuesta = ModeloProductos::mdlIngresarProducto($tabla, $datos);

        // ======================================================
        // ✅ REGISTRAR EN HISTORIAL SI TODO SALE BIEN
        // ======================================================
        if ($respuesta === "ok") { 

            require_once "modelos/historial.modelo.php";
            require_once "controladores/historial.controlador.php";

            $usuario = isset($_SESSION["usuario"]) ? $_SESSION["usuario"] : "desconocido";
            $modulo = "Productos";
            $accion = "INSERT";
            $idRegistro = $_POST["nuevoCodigo"]; // o el ID autogenerado si lo devuelves
            $valorAnterior = null;
            $valorNuevo = json_encode($datos, JSON_UNESCAPED_UNICODE);

            ControladorHistorial::ctrRegistrarCambio(
                $usuario,
                $modulo,
                $accion,
                $idRegistro,
                $valorAnterior,
                $valorNuevo
            );

            echo '<script>
                Swal.fire({
                    icon: "success",
                    title: "¡Producto registrado!",
                    text: "El producto ha sido guardado correctamente.",
                    confirmButtonText: "Cerrar"
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location = "crear-producto";
                    }
                });
            </script>'; 

        } else {
            $detalle = (is_string($respuesta) && strpos($respuesta, "error") === 0) ? $respuesta : "Ocurrió un problema al guardar el producto.";
            echo '<script>
                Swal.fire({
                    icon: "error",
                    title: "Error al registrar",
                    text: '.json_encode($detalle).',
                    confirmButtonText: "Cerrar"
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location = "crear-producto";
                    }
                });
            </script>'; 
        }
    }

    // --------------------------- MOSTRAR PRODUCTOS --------------------------------------
    public static function ctrMostrarProductos($item, $valor){ 

        // Define la tabla
        $tabla = "producto"; 
        // Llama al modelo para consultar
        $respuesta = ModeloProductos::mdlMostrarProductos($tabla, $item, $valor); 
        return $respuesta; 
    } 

    // --------------------------- EDITAR PRODUCTO ----------------------------------------
    public static function ctrEditarProducto(){

        if(isset($_POST["editarProducto"])){

            // Precios: solo numeros mayores o iguales a 0 (hasta 2 decimales)
            if (
                !preg_match('/^\d+(\.\d{1,2})?$/', $_POST["editarPrecioCompra"] ?? "") ||
                !preg_match('/^\d+(\.\d{1,2})?$/', $_POST["editarPrecioVenta"] ?? "")
            ) {
                echo '<script>
                    Swal.fire({
                        icon: "error",
                        title: "Precio inválido",
                        text: "Los precios deben ser números mayores o iguales a 0.",
                        confirmButtonText: "Cerrar"
                    }).then((r)=>{ if(r.isConfirmed){ window.location="crear-producto"; }});
                </script>';
                return;
            }

            if (
                preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+$/u', $_POST["editarNombreProducto"]) &&
                preg_match('/^[a-zA-Z0-9\sáéíóúÁÉÍÓÚñÑ().,-]+$/u', $_POST["editarFormato"])
            ){

                // ===== Validación y normalización de fecha de vencimiento =====
                $fechaVenc = null;
                if (isset($_POST["editarFechaVencimiento"])) {
                    $fv = trim($_POST["editarFechaVencimiento"]);
                    if ($fv !== "") {
                        $dt = DateTime::createFromFormat('Y-m-d', $fv) ?: DateTime::createFromFormat('d/m/Y', $fv);
                        if ($dt) {
                            $fechaVenc = $dt->format('Y-m-d');
                        } else {
                            echo '<script>
                                Swal.fire({
                                    icon: "error",
                                    title: "Fecha inválida",
                                    text: "Use el formato YYYY-MM-DD o DD/MM/YYYY para la fecha de vencimiento.",
                                    confirmButtonText: "Cerrar"
                                }).then((r)=>{ if(r.isConfirmed){ window.location="crear-producto"; }});
                            </script>';
                            return;
                        }
                    }
                }

                $rutaImagen = isset($_POST["imagenActual"]) ? trim($_POST["imagenActual"]) : "";

                if (isset($_FILES["editarImagen"]) && $_FILES["editarImagen"]["error"] === UPLOAD_ERR_OK) {
                    $tmp  = $_FILES["editarImagen"]["tmp_name"];
                    $size = $_FILES["editarImagen"]["size"];
                    $name = $_FILES["editarImagen"]["name"];

                    if ($size <= 2 * 1024 * 1024) {
                        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                        if ($ext === "jpeg") $ext = "jpg";
                        if (in_array($ext, ["jpg", "png"])) {
                            $carpeta = "vistas/imagenes/productos";
                            if (!is_dir($carpeta)) {
                                mkdir($carpeta, 0755, true);
                            }
                            $archivoNuevo = $carpeta . "/" . uniqid("prod_") . "." . $ext;
                            if (move_uploaded_file($tmp, $archivoNuevo)) {
                                if (!empty($rutaImagen) && file_exists($rutaImagen)) {
                                    @unlink($rutaImagen);
                                }
                                $rutaImagen = $archivoNuevo;
                            }
                        }
                    }
                }

                $tabla = "producto";

                $datos = array(
                    "id"                 => $_POST["idProducto"],
                    "nombre"             => $_POST["editarNombreProducto"],
                    "codigo"             => $_POST["editarCodigo"],
                    "formato"            => $_POST["editarFormato"],
                    "tamano"             => $_POST["editarTamano"],
                    "marca"              => $_POST["editarMarca"],
                    "cantidad"           => $_POST["editarCantidad"],
                    "precio_compra"      => $_POST["editarPrecioCompra"],
                    "precio_venta"       => $_POST["editarPrecioVenta"],
                    "proveedor"          => $_POST["editarProveedor"],
                    "imagen"             => $rutaImagen,
                    "fecha_vencimiento"  => $fechaVenc
                );

                /* Obtener los datos anteriores ANTES de actualizar */
                $productoAnterior = ModeloProductos::mdlObtenerProductoPorId($tabla, $_POST["idProducto"]);

                // Actualiza el producto
                $respuesta = ModeloProductos::mdlEditarProducto($tabla, $datos);

                /* Si se actualiza correctamente, registrar cambios*/
                // --------------------------- REGISTRAR CAMBIOS ----------------------------------------
                if ($respuesta == "ok") {

                    require_once "modelos/historial.modelo.php";
                    require_once "controladores/historial.controlador.php";

                    $usuario = isset($_SESSION["usuario"]) ? $_SESSION["usuario"] : "desconocido";
                    $modulo = "Productos";
                    $accion = "UPDATE";
                    $idRegistro = $_POST["idProducto"];

                    // Compara campo por campo
                    $cambios = [];
                    foreach ($datos as $campo => $valorNuevo) {
                        $valorViejo = isset($productoAnterior[$campo]) ? $productoAnterior[$campo] : null;
                        if ($valorViejo != $valorNuevo) {
                            $cambios[$campo] = [
                                "antes" => $valorViejo,
                                "después" => $valorNuevo
                            ];
                        }
                    }

                    // Guardar solo si hay cambios
                    if (!empty($cambios)) {
                        $jsonCambios = json_encode($cambios, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                        ControladorHistorial::ctrRegistrarCambio($usuario, $modulo, $accion, $idRegistro, "Campos modificados", $jsonCambios);
                    }

                    echo '<script>
                        Swal.fire({
                            icon: "success",
                            title: "¡Producto editado!",
                            text: "El producto ha sido editado correctamente.",
                            confirmButtonText: "Cerrar"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location = "crear-producto";
                            }
                        });
                    </script>';
                }

            } else {
                echo '<script>
                    Swal.fire({
                        icon: "error",
                        title: "Error al registrar",
                        text: "El nombre y/o formato no pueden llevar caracteres no válidos.",
                        confirmButtonText: "Cerrar"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location = "crear-producto";
                        }
                    });
                </script>';
            }
        }
    }

    // --------------------------- ELIMINAR PRODUCTO --------------------------------------
    public static function ctrEliminarProducto(){

        // Revisa si viene id por GET
        if(isset($_GET["idProducto"])){

            $tabla = "producto";
            $idProducto = $_GET["idProducto"];

            /* =======================================================
            🔹 1. Obtener los datos antes de eliminar (para historial)
            ======================================================= */
            $productoEliminado = ModeloProductos::mdlObtenerProductoPorId($tabla, $idProducto);

            // Llama al modelo para eliminar
            $respuesta = ModeloProductos::mdlEliminarProducto($tabla, $idProducto);

            /* =======================================================
            🔹 2. Si la eliminación fue exitosa, registrar en historial
            ======================================================= */
            if ($respuesta == "ok") {

                require_once "modelos/historial.modelo.php";
                require_once "controladores/historial.controlador.php";

                $usuario = isset($_SESSION["usuario"]) ? $_SESSION["usuario"] : "desconocido";
                $modulo = "Productos";
                $accion = "DELETE";
                $idRegistro = $idProducto;

                // Guardar los datos del producto eliminado
                $jsonEliminado = json_encode($productoEliminado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                ControladorHistorial::ctrRegistrarCambio(
                    $usuario,
                    $modulo,
                    $accion,
                    $idRegistro,
                    "Registro eliminado",
                    $jsonEliminado
                );

                // Mostrar alerta de éxito
                echo '<script>
                    Swal.fire({
                        icon: "success",
                        title: "¡Producto Borrado!",
                        text: "El producto ha sido eliminado correctamente.",
                        confirmButtonText: "Cerrar"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location = "crear-producto";
                        }
                    });
                </script>';
            }
        }
    }

    /* ========================================= STOCK =============================================== */
    // --------------------------- AÑADIR STOCK -------------------------------------------
    public static function ctrAgregarStock() {

        // Revisa si viene el id del producto para stock
        if (isset($_POST["idProductoStock"])) {

            // Define la tabla
            $tabla = "producto";
            $id = $_POST["idProductoStock"];
            $cantidadNueva = $_POST["nuevaCantidadStock"];
            $fechaRecepcion = $_POST["nuevaFechaRecepcion"];
            $fechaVencimiento = $_POST["nuevaFechaVencimiento"];

            // Llama al modelo para sumar stock
            $respuesta = ModeloProductos::mdlSumarStock($tabla, $id, $cantidadNueva, $fechaRecepcion, $fechaVencimiento);

            // Si se actualizó correctamente
            if ($respuesta == "ok") {

                /* =======================================================
                🔹 Registrar en historial
                ======================================================= */
                require_once "modelos/historial.modelo.php";
                require_once "controladores/historial.controlador.php";

                // Datos del producto antes de la modificación
                $productoAntes = ModeloProductos::mdlObtenerProductoPorId($tabla, $id);

                $usuario = isset($_SESSION["usuario"]) ? $_SESSION["usuario"] : "desconocido";
                $modulo = "Stock";
                $accion = "UPDATE";
                $idRegistro = $id;

                // Armamos el registro de cambio
                $valorAnterior = json_encode($productoAntes, JSON_UNESCAPED_UNICODE);
                $valorNuevo = json_encode([
                    "producto" => $productoAntes["nombre"] ?? "Desconocido",
                    "cantidad_agregada" => $cantidadNueva,
                    "fecha_recepcion" => $fechaRecepcion ?: "Sin información",
                    "fecha_vencimiento" => $fechaVencimiento ?: "Sin información"
                ], JSON_UNESCAPED_UNICODE);

                // Guardamos en historial
                ControladorHistorial::ctrRegistrarCambio(
                    $usuario,
                    $modulo,
                    $accion,
                    $idRegistro,
                    $valorAnterior,
                    $valorNuevo
                );

                /* =======================================================
                🔹 Mensaje visual de éxito
                ======================================================= */
                echo '<script>
                    Swal.fire({
                        icon: "success",
                        title: "Stock actualizado",
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location = "anadir-stock";
                    });
                </script>';
            }
        }
    }

    // --------------------------- CONFIGURAR STOCK MÍNIMO --------------------------------
    public function ctrConfigurarStockMinimo() {
        if (!isset($_POST['configurarStockMinimo'])) return;

        // === Caso A: lote (producto_id[] + minimos[ID]) ===
        if (!empty($_POST['producto_id']) && is_array($_POST['producto_id'])) {

            $ids  = array_map('intval', $_POST['producto_id']);
            $mins = (isset($_POST['minimos']) && is_array($_POST['minimos'])) ? $_POST['minimos'] : [];

            $ok = 0; $fail = 0; $errs = [];

            foreach ($ids as $id) {
                if ($id <= 0) { $fail++; $errs[] = "ID inválido"; continue; }

                $min = isset($mins[$id]) ? (int)$mins[$id] : 0;
                if ($min < 0) { $fail++; $errs[] = "ID $id: mínimo inválido"; continue; }

                $res = ModeloProductos::mdlConfigurarStockMinimo("producto", [
                    "id"           => $id,
                    "stock_minimo" => $min
                ]);

                if ($res === 'ok') {

                    /* =======================================================
                    🔹 Registrar en historial
                    ======================================================= */
                    require_once "modelos/historial.modelo.php";
                    require_once "controladores/historial.controlador.php";

                    $producto = ModeloProductos::mdlObtenerProductoPorId("producto", $id);
                    $usuario = $_SESSION["usuario"] ?? "desconocido";

                    $valorAnterior = null;
                    $valorNuevo = json_encode([
                        "producto" => $producto["nombre"] ?? "Desconocido",
                        "nuevo_stock_minimo" => $min
                    ], JSON_UNESCAPED_UNICODE);

                    ControladorHistorial::ctrRegistrarCambio(
                        $usuario,
                        "Stock",
                        "UPDATE",
                        $id,
                        $valorAnterior,
                        $valorNuevo
                    );
                    /* ======================================================= */
                    $ok++;

                } else { 
                    $fail++; 
                    $errs[] = "ID $id: error BD"; 
                }
            }

            // --- Mantener tus SweetAlerts intactos ---
            if ($ok && !$fail) {
                echo "<script>
                        Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Se actualizaron $ok productos correctamente.',
                        timer: 2000,
                        showConfirmButton: false
                        }).then(() => { window.location.replace(window.location.pathname + window.location.search); });
                    </script>";
            } elseif ($ok && $fail) {
                $errores = htmlspecialchars(implode('; ', $errs));
                echo "<script>
                        Swal.fire({
                        icon: 'warning',
                        title: 'Parcial',
                        html: 'Actualizados $ok productos.<br>Errores: $fail.<br><small>$errores</small>',
                        timer: 3500,
                        showConfirmButton: false
                        }).then(() => { window.location.reload(); });
                    </script>";
            } else {
                $errores = htmlspecialchars(implode('; ', $errs));
                echo "<script>
                        Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: 'No se guardó nada.<br><small>$errores</small>'
                        }).then(() => { window.location.reload(); });
                    </script>";
            }
            return;
        }

        // === Caso B: uno solo (producto_id + nuevoStockMinimo) ===
        if (!empty($_POST['producto_id']) && !is_array($_POST['producto_id'])) {

            $id  = (int) $_POST['producto_id'];
            $min = isset($_POST['nuevoStockMinimo']) ? (int) $_POST['nuevoStockMinimo'] : 0;

            if ($id > 0 && $min >= 0) {
                $res = ModeloProductos::mdlConfigurarStockMinimo("producto", [
                    "id"           => $id,
                    "stock_minimo" => $min
                ]);

                if ($res === 'ok') {

                    /* =======================================================
                    🔹 Registrar en historial
                    ======================================================= */
                    require_once "modelos/historial.modelo.php";
                    require_once "controladores/historial.controlador.php";

                    $producto = ModeloProductos::mdlObtenerProductoPorId("producto", $id);
                    $usuario = $_SESSION["usuario"] ?? "desconocido";

                    $valorAnterior = null;
                    $valorNuevo = json_encode([
                        "producto" => $producto["nombre"] ?? "Desconocido",
                        "nuevo_stock_minimo" => $min
                    ], JSON_UNESCAPED_UNICODE);

                    ControladorHistorial::ctrRegistrarCambio(
                        $usuario,
                        "Stock mínimo",
                        "UPDATE",
                        $id,
                        $valorAnterior,
                        $valorNuevo
                    );
                    /* ======================================================= */

                    echo "<script>
                            Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Stock mínimo actualizado.',
                            timer: 2000,
                            showConfirmButton: false
                            }).then(() => { window.location.reload(); });
                        </script>";
                } else {
                    echo "<script>
                            Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo actualizar.'
                            }).then(() => { window.location.reload(); });
                        </script>";
                }
            } else {
                echo "<script>
                        Swal.fire({
                        icon: 'warning',
                        title: 'Datos incompletos',
                        text: 'Por favor, revise la información.'
                        }).then(() => { window.location.reload(); });
                    </script>";
            }
        }
    }


    // --------------------------- OBTENER LISTADO DE STOCK CRÍTICO -----------------------
    public static function ctrProductosStockCritico() {

        // Define la tabla
        $tabla = "producto";
        // Devuelve listado crítico desde el modelo
        return ModeloProductos::mdlProductosStockCritico($tabla);
    }

    // --------------------------- OBTENER DESCARGAR LISTADO DE STOCK CRÍTICO -------------
    public static function ctrExportarStockCriticoPDF() {
        
        // Llama al propio método para obtener los datos
        $datos = self::ctrProductosStockCritico();

        // Fecha/hora Chile
        $tz    = new DateTimeZone('America/Santiago');
        $ahora = new DateTime('now', $tz);
        $fecha = $ahora->format('d-m-Y H:i'); // 24h

        // Revisa si está disponible IntlDateFormatter
        if (class_exists('IntlDateFormatter')) {
                // Crea formateador local
                $fmt = new IntlDateFormatter('es_CL', IntlDateFormatter::LONG, IntlDateFormatter::SHORT, 'America/Santiago');
            $fecha = ucfirst($fmt->format($ahora));
        }

        // Construir HTML del reporte
        $html = '
        <html>
        <head>
        <meta charset="utf-8">
        <style>
            @page { margin: 24px 24px 40px 24px; }
            body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
            h1 { font-size: 18px; margin: 0 0 6px 0; }
            .sub { color:#666; margin-bottom:12px; }
            table { width:100%; border-collapse: collapse; }
            th, td { border:1px solid #ddd; padding:6px 8px; }
            th { background:#f5f5f5; text-align:left; }
            .text-right { text-align:right; }
            .badge { display:inline-block; padding:2px 6px; border-radius:4px; font-size:11px; }
            .bg-danger { background:#dc3545; color:#fff; }
            .bg-warning { background:#ffc107; color:#222; }
            footer { position: fixed; bottom: -10px; left:0; right:0; text-align:center; font-size:10px; color:#666; }
        </style>
        </head>
        <body>
        <h1>Listado de Stock Crítico</h1>
        <div class="sub">Generado: ' . htmlspecialchars($fecha) . '</div>
        <table>
            <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Formato</th>
                <th>Tamaño</th>
                <th>Marca</th>
                <th class="text-right">Cantidad</th>
                <th class="text-right">Stock mínimo</th>
                <th class="text-right">Precio venta</th>
                <th>Proveedor</th>
                <th>Estado</th>
            </tr>
            </thead>
            <tbody>';

        // Si no hay productos críticos
        if (empty($datos)) {
            $html .= '<tr><td colspan="9" style="text-align:center;color:#777;padding:16px">No hay productos críticos</td></tr>';
        // Si hay datos
        } else {
            foreach ($datos as $p) {
                $estado = ((int)$p["cantidad"] === 0)
                    ? '<span class="badge bg-danger">Agotado</span>'
                    : '<span class="badge bg-warning">Bajo mínimo</span>';

                $html .= '<tr>
                    <td>'.htmlspecialchars($p["codigo"]).'</td>
                    <td>'.htmlspecialchars($p["nombre"]).'</td>
                    <td>'.htmlspecialchars($p["formato"]).'</td>
                    <td>'.htmlspecialchars($p["tamano"]).'</td>
                    <td>'.htmlspecialchars($p["marca"]).'</td>
                    <td class="text-right">'.(int)$p["cantidad"].'</td>
                    <td class="text-right">'.(int)($p["stock_minimo"] ?? 0).'</td>
                    <td class="text-right">$'.number_format((float)$p["precio_venta"], 0, ",", ".").'</td>
                    <td>'.htmlspecialchars($p["proveedor"] ?? "").'</td>
                    <td>'.$estado.'</td>
                </tr>';
            }
        }

        // Render con Composer
        $html .= '</tbody></table>
        <footer>Botillería Joaquín — Reporte de Stock Crítico</footer>
        </body></html>';

        // Render con Composer
        require_once __DIR__ . "/../vendor/autoload.php";

        // Crea instancia de Dompdf con opciones
        $dompdf = new Dompdf\Dompdf([
            "chroot" => __DIR__ . "/../",
            "isRemoteEnabled" => true
        ]);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        // Descargar
        $dompdf->stream("stock_critico.pdf", ["Attachment" => true]);
        exit;
    }

    // --------------------------- CAMBIAR ESTADO PRODUCTO --------------------------------
    public static function ctrCambiarEstadoProducto(){

        // Verifica que vengan ambos datos por POST
        if(isset($_POST["idProducto"]) && isset($_POST["nuevoEstado"])){

            // Define la tabla
            $tabla = "producto";

            // Arma arreglo con id y nuevo estado
            $datos = array(
                "id" => $_POST["idProducto"],
                "estado" => $_POST["nuevoEstado"]
            );

            // Llama al modelo para actualizar
            $respuesta = ModeloProductos::mdlCambiarEstadoProducto($tabla, $datos);

            // Devuelve la respuesta 
            return $respuesta;
        }
    }

    /* ========================================= FILTRAR =============================================== */
    //--------------------------- MOSTRAR SOLO PRODUCTOS ACTIVOS --------------------------
    public static function ctrMostrarProductosActivos(){
        // Define la tabla
        $tabla = "producto";
        // Llama al modelo y devuelve
        return ModeloProductos::mdlMostrarProductosActivos($tabla);
    }

    // --------------------------- FILTRAR PRODUCTOS POR ESTADO ---------------------------
    public static function ctrFiltrarProductosPorEstado($estado) {
        // Define la tabla
        $tabla = "producto";
        // Llama al modelo y devuelve
        return ModeloProductos::mdlFiltrarProductosPorEstado($tabla, $estado);
    }

    // --------------------------- MOSTRAR PRODUCTOS RECIENTES ----------------------------
    public static function ctrMostrarProductosRecientes($dias = 7, $limite = 50){
        // Define la tabla
        $tabla = "producto";
        // Llama al modelo y devuelve
        return ModeloProductos::mdlMostrarProductosRecientes($tabla, $dias, $limite);
    }

}
