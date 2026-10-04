<?php
// Llamamos al controlador directamente
require_once "controladores/ventas.controlador.php";
require_once "modelos/ventas.modelo.php";

$totales = ControladorVentas::ctrResumenMetodoPagoDirecto();
?>

<!-- Contenedor principal del contenido de la página -->
<div class="content-wrapper">

  <!-- Encabezado del contenido -->
  <section class="content-header">
    <div class="container-fluid">
      
      <!-- TITULO PAGINA: AÑADIR STOCK -->
      <div class="row mb-2 align-items-center">
        <div class="col-sm-6">
          <h1>Crear Productos</h1>
        </div>

        <!-- BOTON: AGREGAR PRODUCTO -->
        <div class="col-sm-6 text-right">
          <button class="btn btn-sm btn-agregar-producto fw-bold" 
                  data-toggle="modal" data-target="#modalAgregarProducto">
            <i class="fas fa-box me-1"></i> Agregar producto
          </button>
        </div>
      </div>

      <!-- DESCRIPCION DE LA PAGINA -->
      <div class="row">
        <div class="col-12">
          <p class="descripcion-pagina mb-0">
            En esta sección podrá registrar nuevos productos en el sistema, 
            definiendo su código, nombre, formato, tamaño, marca, precios y stock mínimo.
          </p>
        </div>
      </div>

       <!-- LINEA DIVISORA -->
      <hr class="linea-divisora">
    </div>
  </section>

  <!--------------------- CONTENIDO PRINCIPAL PAGINA ------------------------>
   <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-body">
          <!-- Controles -->
          <div class="row g-2 align-items-end mb-3 filtros-sc w-100">

            <!-- ORDENAR POR -->
            <div class="col-auto">
              <label class="form-label mb-1 me-2">Ordenar por</label>
              <select id="criterioOrdenSC" class="form-select form-select-sm w-auto select-bonito">
                <option data-col="1">Código de barras</option>
                <option data-col="2" selected>Producto</option>
                <option data-col="3">Formato</option>
                <option data-col="4">Tamaño</option>
                <option data-col="5">Marca</option>
                <option data-col="6">Cantidad</option>
                <option data-col="7">Precio de compra</option>
                <option data-col="8">Precio de venta</option>
                <option data-col="9">Proveedor</option>
                <option data-col="10">Fecha de vencimiento</option>
              </select>
            </div>

            <div class="col-auto">
              <label class="form-label mb-1 me-2">Dirección</label>
              <select id="direccionOrdenSC" class="form-select form-select-sm w-auto select-bonito">
                <option value="asc" selected>Ascendente</option>
                <option value="desc">Descendente</option>
              </select>
            </div>

            <!-- BUSCADOR -->
            <div class="col ms-auto d-flex justify-content-end">
              <div class="input-group input-group-sm" id="buscadorProductosWrap" style="max-width: 300px;">
                <input type="text" id="buscarProducto" class="form-control" placeholder="Buscar producto...">
                <span class="input-group-text bg-white" id="btnBuscarProducto" role="button" aria-label="Buscar">
                  <i class="fas fa-search"></i>
                </span>
              </div>
            </div>
          </div>

        <!-- TABLA DE DATOS -->
        <div class="card-body pt-2">
          <div class="table-responsive">
            <table id="tablaProductos" class="table-productos">
              <thead>
                <tr>
                  <th>Imagen</th>
                  <th>Código de barras</th>
                  <th>Nombre</th>
                  <th>Formato</th>
                  <th>Tamaño</th>
                  <th>Marca</th>
                  <th>Cantidad</th>
                  <th>Precio de compra</th>
                  <th>Precio de venta</th>
                  <th>Proveedor</th>
                  <th>Fecha vencimiento</th> 
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                  $item = null;
                  $valor = null;
                  $productos = ControladorProductos::ctrMostrarProductos($item, $valor);

                  foreach ($productos as $key => $value){

                    // Imagen
                    $src = (!empty($value["imagen"]) && file_exists($value["imagen"])) 
                            ? $value["imagen"] 
                            : "vistas/imagenes/sinfoto.png";

                    // === VENCIMIENTO ===
                    $rawVence  = $value["fecha_vencimiento"] ?? $value["vencimiento"] ?? $value["fecha_caducidad"] ?? '';
                    $venceISO  = '';
                    $venceCL   = '';
                    $badgeVenc = '';
                    if (!empty($rawVence)) {
                      $tv = strtotime($rawVence);
                      if ($tv) {
                        $venceISO = date('Y-m-d', $tv);
                        $venceCL  = date('d-m-Y', $tv);
                        // Etiquetas: Vencido / Pronto a vencer (<=30 días)
                        $dias = floor(($tv - strtotime('today')) / 86400);
                        if ($dias < 0) {
                          $badgeVenc = '<span class="badge bg-danger">Vencido</span>';
                        } elseif ($dias <= 30) {
                          $badgeVenc = '<span class="badge bg-warning text-dark">Pronto a vencer</span>';
                        }
                      }
                    }

                    // Clase para pintar SIEMPRE en rojo + negrita cuando falten 30 días o menos (incluye vencido)
                    $claseFecha = (isset($dias) && $dias <= 30) ? 'text-danger fw-bold' : '';

                    echo '<tr>
                      <td class="text-center">
                        <img src="'.$src.'" class="img-thumbnail" style="width:100px; height:100px; object-fit:cover;"
                            alt="Foto producto"
                            onerror="this.onerror=null;this.src=\'vistas/imagenes/sinfoto.png\';">
                      </td>
                      <td>'.$value["codigo"].'</td>
                      <td>'.$value["nombre"].'</td>
                      <td>'.$value["formato"].'</td>
                      <td>'.$value["tamano"].'</td>
                      <td>'.$value["marca"].'</td>
                      <td>'.$value["cantidad"].'</td>
                      <td>'.$value["precio_compra"].'</td>
                      <td>'.$value["precio_venta"].'</td>
                      <td>'.$value["proveedor"].'</td>

                      <!-- Fecha vencimiento: SIEMPRE roja + negrita, badge debajo -->
                      <td '.($venceISO ? 'data-order="'.$venceISO.'"' : '').'>
                        <span class="'.$claseFecha.'">'.($venceCL ?: '-').'</span>'
                        . ($badgeVenc ? '<br>'.$badgeVenc : '') .
                      '</td>

                      <td>
                        <div class="btn-group">
                          <button class="btn btn-editar btn-sm btnEditarProducto"
                                  idProducto="'.$value["id"].'"
                                  data-toggle="modal"
                                  data-target="#modalEditarProducto"
                                  title="Editar producto">
                            <i class="fas fa-edit"></i>
                          </button>
                          <button class="btn btn-danger btn-sm btnEliminarProducto"
                                  idProducto="'.$value["id"].'"
                                  title="Eliminar producto">
                            <i class="fas fa-trash-alt"></i>
                          </button>
                        </div>
                      </td>
                    </tr>';
                  }
                ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!--------------------- MODAL: AGREGAR PRODUCTO------------------------>
<div class="modal fade" id="modalAgregarProducto" tabindex="-1" role="dialog" aria-labelledby="modalAgregarProductoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content shadow-lg border-0 rounded-xl overflow-hidden">

      <!-- Formulario -->
      <form role="form" method="post" enctype="multipart/form-data">
        <!-- Encabezado del modal -->
        <div class="modal-header modal-header-custom text-white">
          <div class="d-flex align-items-center">
            <div class="modal-icon mr-2"><i class="fas fa-box"></i></div>
            <h5 class="modal-title mb-0" id="modalAgregarProductoLabel">Agregar nuevo producto</h5>
          </div>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <!-- Cuerpo del modal -->
        <div class="modal-body p-4">
          <div class="row">

            <!-- Código de barras -->
            <div class="col-md-6 mb-3">
              <label class="font-weight-semibold" for="nuevoCodigo">Código de barras</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                </div>
                <input type="text" class="form-control" id="nuevoCodigo" name="nuevoCodigo" placeholder="Ej: 7804320950023" required>
              </div>
            </div>

            <!-- Nombre -->
            <div class="col-md-6 mb-3">
              <label class="font-weight-semibold" for="nuevoNombreProducto">Nombre del producto</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-tag"></i></span>
                </div>
                <input type="text" class="form-control" id="nuevoNombreProducto" name="nuevoNombreProducto" placeholder="Ej: Pisco Mistral 35°" required>
              </div>
            </div>

            <!-- Tamaño -->
            <div class="col-md-4 mb-3">
              <label class="font-weight-semibold" for="nuevoTamano">Tamaño</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                </div>
                <input type="text" class="form-control" id="nuevoTamano" name="nuevoTamano" placeholder="Ej: 700ml, 1L, 350cc" required>
              </div>
            </div>

            <!-- Formato -->
            <div class="col-md-6 mb-3">
              <label class="font-weight-semibold" for="nuevoFormato">Formato</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-box-open"></i></span>
                </div>
                <input type="text" class="form-control" id="nuevoFormato" name="nuevoFormato"
                      placeholder="Ej: Botella, Lata, Caja, Pack 6 unid" required>
              </div>
            </div>

            <!-- Marca -->
            <div class="col-md-4 mb-3">
              <label class="font-weight-semibold" for="nuevaMarca">Marca</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-industry"></i></span>
                </div>
                <input type="text" class="form-control" id="nuevaMarca" name="nuevaMarca" placeholder="Ej: Mistral, Coca-Cola" required>
              </div>
            </div>

            <!-- Cantidad Actual -->
            <div class="col-md-4 mb-3">
              <label class="font-weight-semibold" for="nuevaCantidad">Cantidad</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                </div>
                <input type="number" class="form-control" id="nuevaCantidad" name="nuevaCantidad" placeholder="Ej: 12" min="0" required>
              </div>
            </div>

            <!-- Precio compra -->
            <div class="col-md-6 mb-3">
              <label class="font-weight-semibold" for="nuevoPrecioCompra">Precio de compra (CLP)</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-hand-holding-usd"></i></span>
                </div>
                <input type="number" class="form-control" id="nuevoPrecioCompra" name="nuevoPrecioCompra" placeholder="Ej: 3500" min="0" step="any" required>
              </div>
            </div>

            <!-- Precio venta -->
            <div class="col-md-6 mb-3">
              <label class="font-weight-semibold" for="nuevoPrecioVenta">Precio de venta (CLP)</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-cash-register"></i></span>
                </div>
                <input type="number" class="form-control" id="nuevoPrecioVenta" name="nuevoPrecioVenta" placeholder="Ej: 4990" min="0" step="any" required>
              </div>
            </div>

            <!-- Fecha de vencimiento -->
            <div class="col-md-6 mb-3">
              <label class="font-weight-semibold" for="nuevaFechaVencimiento">Fecha de vencimiento</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                </div>
                <!-- quitar required -->
                <input type="date" class="form-control" id="nuevaFechaVencimiento" name="nuevaFechaVencimiento">
              </div>
              <small class="text-muted">Opcional</small>
            </div>

            <!-- Proveedor -->
            <div class="col-md-6 mb-2">
              <label class="font-weight-semibold" for="nuevoProveedor">Proveedor</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-truck-loading"></i></span>
                </div>
                <input type="text" class="form-control" id="nuevoProveedor" name="nuevoProveedor" placeholder="Ej: Distribuidora Los Andes" required>
              </div>
            </div>

            <!-- Subir Foto -->
            <div class="col-md-12 mb-3">
              <div class="row align-items-center">
                <!-- Vista previa -->
                <div class="col-md-3 text-center">
                  <img src="vistas/imagenes/sinfoto.png" 
                      id="previewFoto"
                      class="img-thumbnail shadow-sm rounded" 
                      style="max-width: 120px; max-height: 120px;" 
                      alt="Foto producto">
                </div>
                <!-- Input de archivo -->
                <div class="col-md-9">
                  <label class="font-weight-semibold mb-2" for="nuevaImagen">Subir Foto (opcional)</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-image"></i></span>
                    </div>
                    <!-- quitar required -->
                    <input type="file" 
                          class="form-control nuevaImagen" 
                          id="nuevaImagen"
                          name="nuevaImagen" 
                          accept="image/*">
                  </div>
                  <small class="text-muted">Formatos permitidos: JPG, PNG. Peso máximo 2 MB.</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer del modal -->
        <div class="modal-footer d-flex justify-content-between">
          <button type="button" class="btn btn-light border" data-dismiss="modal">
            <i class="fas fa-times mr-1"></i> Cancelar
          </button>
          <button type="submit" class="btn btn-primary btn-modern btn-guardar-producto">
            <i class="fas fa-save mr-1"></i> Guardar producto
          </button>
        </div>

        <?php
          $crearProducto = new ControladorProductos();
          $crearProducto -> ctrCrearProducto();
        ?>
      </form>
    </div>
  </div>
</div>

<!--------------------- MODAL: EDITAR PRODUCTO------------------------>
<div class="modal fade" id="modalEditarProducto" tabindex="-1" role="dialog" aria-labelledby="modalEditarProductoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content shadow-lg border-0 rounded-xl overflow-hidden">

      <form role="form" method="post" enctype="multipart/form-data">
        <!-- Header -->
        <div class="modal-header modal-header-custom text-white">
          <div class="d-flex align-items-center">
            <div class="modal-icon mr-2">
              <i class="fas fa-box-open"></i>
            </div>
            <h5 class="modal-title mb-0">Editar producto</h5>
          </div>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <!-- Body -->
        <div class="modal-body p-4">

          <!-- Mensaje informativo -->
          <div class="alert alert-danger py-2 mb-4" style="font-size: 0.9rem;">
            <i class="fas fa-info-circle mr-1"></i>
            No es necesario modificar todos los campos, solo cambia lo que quieras actualizar.
          </div>

          <div class="row">
            <!-- Nombre -->
            <div class="col-md-6 mb-3">
              <label class="font-weight-semibold" for="editarNombreProducto">Nombre del producto</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-tag"></i></span>
                </div>
                <input type="text" class="form-control" id="editarNombreProducto" name="editarNombreProducto" placeholder="Ej: Pisco Mistral 35°">
              </div>
            </div>

            <!-- Código -->
            <div class="col-md-6 mb-3">
              <label class="font-weight-semibold" for="editarCodigo">Código / Código de barras</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                </div>
                <input type="text" class="form-control" id="editarCodigo" name="editarCodigo" placeholder="Ej: 7804320950023">
              </div>
            </div>

            <!-- Tamaño -->
            <div class="col-md-4 mb-3">
              <label class="font-weight-semibold" for="editarTamano">Tamaño</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                </div>
                <input type="text" class="form-control" id="editarTamano" name="editarTamano" placeholder="Ej: 700ml, 1L, 350cc">
              </div>
            </div>

            <!-- Formato -->
            <div class="col-md-4 mb-3">
              <label class="font-weight-semibold" for="editarFormato">Formato</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-box-open"></i></span>
                </div>
                <input type="text" class="form-control" id="editarFormato" name="editarFormato"
                       placeholder="Ej: Botella, Lata, Caja, Pack 6 unid">
              </div>
            </div>

            <!-- Marca -->
            <div class="col-md-4 mb-3">
              <label class="font-weight-semibold" for="editarMarca">Marca</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-industry"></i></span>
                </div>
                <input type="text" class="form-control" id="editarMarca" name="editarMarca" placeholder="Ej: Mistral">
              </div>
            </div>

            <!-- Cantidad -->
            <div class="col-md-4 mb-3">
              <label class="font-weight-semibold" for="editarCantidad">Cantidad</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-cubes"></i></span>
                </div>
                <input type="number" class="form-control" id="editarCantidad" name="editarCantidad" min="0" placeholder="Ej: 12">
              </div>
            </div>

            <!-- Precio compra -->
            <div class="col-md-6 mb-3">
              <label class="font-weight-semibold" for="editarPrecioCompra">Precio de compra (CLP)</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-hand-holding-usd"></i></span>
                </div>
                <input type="number" class="form-control" id="editarPrecioCompra" name="editarPrecioCompra" placeholder="Ej: 3500" min="0" step="any" required>
              </div>
              <small class="text-muted">Solo números, sin puntos ni comas.</small>
            </div>

            <!-- Precio venta -->
            <div class="col-md-6 mb-3">
              <label class="font-weight-semibold" for="editarPrecioVenta">Precio de venta (CLP)</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-cash-register"></i></span>
                </div>
                <input type="number" class="form-control" id="editarPrecioVenta" name="editarPrecioVenta" placeholder="Ej: 4990" min="0" step="any" required>
              </div>
            </div>

            <!-- Proveedor -->
            <div class="col-md-12 mb-2">
              <label class="font-weight-semibold" for="editarProveedor">Proveedor</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-truck-loading"></i></span>
                </div>
                <input type="text" class="form-control" id="editarProveedor" name="editarProveedor" placeholder="Ej: Distribuidora Los Andes">
              </div>
            </div>

            <!-- Fecha de vencimiento -->
            <div class="col-md-6 mb-3">
              <label class="font-weight-semibold" for="editarFechaVencimiento">Fecha de vencimiento</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                </div>
                <input type="date" class="form-control" id="editarFechaVencimiento" name="editarFechaVencimiento">
              </div>
            </div>

            <!-- Subir Foto (EDITAR) -->
            <div class="col-md-12 mb-3">
              <div class="row align-items-center">
                <!-- Vista previa -->
                <div class="col-md-3 text-center">
                  <img src="vistas/imagenes/sinfoto.png"
                       id="previewEditar"
                       class="img-thumbnail shadow-sm rounded"
                       style="max-width:120px; max-height:120px; object-fit:cover;"
                       alt="Foto producto"
                       onerror="this.onerror=null;this.src='vistas/imagenes/sinfoto.png';">
                </div>

                <!-- Input de archivo -->
                <div class="col-md-9">
                  <label class="font-weight-semibold mb-2" for="editarImagen">Cambiar foto</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-image"></i></span>
                    </div>
                    <input type="file"
                           class="form-control"
                           id="editarImagen"
                           name="editarImagen"
                           accept="image/*">
                  </div>
                  <small class="text-muted">Formatos permitidos: JPG, PNG. Peso máximo 2 MB.</small>
                </div>
              </div>
            </div>
          </div>

          <!-- Hidden (dentro del body, fuera del row) -->
          <input type="hidden" id="imagenActual" name="imagenActual" value="">
          <input type="hidden" name="editarProducto" value="1">
          <input type="hidden" id="idProducto" name="idProducto">

        </div>

        <!-- Footer -->
       <div class="modal-footer d-flex justify-content-between">
          <button type="button" class="btn btn-cancelar" data-dismiss="modal">
            <i class="fas fa-times mr-1"></i> Cancelar
          </button>
          <button type="submit" class="btn btn-guardar-producto">
            <i class="fas fa-save mr-1"></i> Guardar cambios
          </button>
        </div>

        <!-- Llamar controlador para editar producto -->
        <?php
          $editarProducto = new ControladorProductos();
          $editarProducto->ctrEditarProducto();
        ?>
      </form>
    </div>
  </div>
</div>

<!-- Llamar controlador para eliminar producto -->
<?php
  $eliminarProducto = new ControladorProductos();
  $eliminarProducto -> ctrEliminarProducto();
?>

<!-- JS -->
<script src="vistas/js/productos.js"></script>
<script src="vistas/js/crear-producto.js"></script>
<!-- CSS -->
<link rel="stylesheet" href="vistas/css/crear-producto.css">
