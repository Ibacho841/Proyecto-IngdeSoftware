<?php

class ControladorUsuarios {

    // ---------------------------- LOGIN USUARIO -----------------------------------------
    static public function ctrIngresoUsuario() {

        // Revisar si el formulario fue enviado
        if (isset($_POST["ingUsuario"])) {

            // Validar que el usuario y la contraseña solo tengan letras o números
            if (
                preg_match('/^[a-zA-Z0-9]+$/', $_POST["ingUsuario"]) &&
                preg_match('/^[a-zA-Z0-9]+$/', $_POST["ingPassword"])){

                $encriptar = crypt($_POST["ingPassword"], '$2a$07$usesomesillystringforsalt$' );

                // Nombre de la tabla que se va a consultar
                $tabla = "usuario";

                // Campo que se va a usar como filtro
                $item = "usuario";

                // Valor que ingresó el usuario
                $valor = $_POST["ingUsuario"];

                // Consultar en el modelo si existe el usuario
                $respuesta = ModeloUsuarios::MdlMostrarUsuarios($tabla, $item, $valor);

                // Verificar si se encontró el usuario
                if ($respuesta) {

                    // Verificar si los datos coinciden
                    if ($respuesta["usuario"] == $_POST["ingUsuario"] && $respuesta["password"] == $encriptar){
                        
                        $_SESSION["iniciarSesion"] = "ok";
                        $_SESSION["id"] = $respuesta["id"];
                        $_SESSION["nombre"] = $respuesta["nombre"];
                        $_SESSION["usuario"] = $respuesta["usuario"];
                        $_SESSION["rol"] = $respuesta["rol"];

                        // Ventana de avisos emergentes (CU21/CU22), abierta una sola vez por sesión
                        $_SESSION["avisosPendientes"] = true;

                        echo '<script>
                            window.location = "inicio";
                        </script>';
                    } 
                    else {
                        echo '<br><div class="alert alert-danger">Usuario o contraseña incorrectos</div><br>';
                    }

                } else {
                    echo '<br><div class="alert alert-warning">⚠️ Usuario no encontrado</div><br>';
                }
            }
        }
    }

    // ---------------------------- CREAR USUARIO -----------------------------------------
    public static function ctrCrearUsuario() {

        if (isset($_POST["nuevoUsuario"])) {

            // Validaciones de campos
            if (
                preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+$/u', $_POST["nuevoNombre"]) &&
                preg_match('/^[a-zA-Z0-9_.]+$/', $_POST["nuevoUsuario"]) &&
                preg_match('/^[a-zA-Z0-9]+$/', $_POST["nuevaPassword"])
            ) {

                $tabla = "usuario";
                $encriptar = crypt($_POST["nuevaPassword"], '$2a$07$usesomesillystringforsalt$');

                $datos = array(
                    "nombre" => $_POST["nuevoNombre"],
                    "usuario" => $_POST["nuevoUsuario"],
                    "password" => $encriptar,
                    "rol" => $_POST["nuevoRol"]
                );

                $respuesta = ModeloUsuarios::mdlIngresarUsuario($tabla, $datos);

                if ($respuesta == "ok") {

                    /* =======================================================
                    🔹 Registrar en historial
                    ======================================================= */
                    require_once "modelos/historial.modelo.php";
                    require_once "controladores/historial.controlador.php";

                    $usuario = $_SESSION["usuario"] ?? "desconocido";
                    $modulo  = "Usuarios";
                    $accion  = "INSERT";

                    // 🔹 Recuperar ID del último usuario creado
                    $nuevoUsuario = ModeloUsuarios::mdlObtenerUltimoUsuario($tabla);

                    $idRegistro = $nuevoUsuario["id"] ?? null;

                    // 🔹 Armar datos legibles
                    $valorAnterior = null;
                    $valorNuevo = json_encode([
                        "nombre"  => $_POST["nuevoNombre"],
                        "usuario" => $_POST["nuevoUsuario"],
                        "rol"     => $_POST["nuevoRol"],
                        "password" => "********" // no se guarda la clave en texto
                    ], JSON_UNESCAPED_UNICODE);

                    ControladorHistorial::ctrRegistrarCambio(
                        $usuario,
                        $modulo,
                        $accion,
                        $idRegistro,
                        $valorAnterior,
                        $valorNuevo
                    );
                    /* ======================================================= */

                    echo '<script>
                        Swal.fire({
                            icon: "success",
                            title: "¡Usuario registrado!",
                            text: "El usuario ha sido guardado correctamente.",
                            confirmButtonText: "Cerrar"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location = "usuarios";
                            }
                        });
                    </script>';
                }

            } else {
                echo '<script>
                    Swal.fire({
                        icon: "error",
                        title: "Error al registrar",
                        text: "El usuario no puede llevar caracteres especiales.",
                        confirmButtonText: "Cerrar"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location = "usuarios";
                        }
                    });
                </script>';
            }
        }
    }

    // ---------------------------- MOSTRAR USUARIOS --------------------------------------
    public static function ctrMostrarUsuarios($item, $valor) {

        $tabla = "usuario";

        $respuesta = ModeloUsuarios::mdlMostrarUsuarios($tabla, $item, $valor);

        return $respuesta;
    }

    // ---------------------------- EDITAR USUARIOS ---------------------------------------
    public static function ctrEditarUsuario(){

        if(isset($_POST["editarUsuario"])){

            // Validaciones de campos
            if (preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+$/u', $_POST["editarNombre"])){

                $tabla = "usuario";

                // 🔹 Obtener datos anteriores antes de actualizar (para historial)
                $usuarioAntes = ModeloUsuarios::mdlObtenerUsuarioPorUsername($tabla, $_POST["editarUsuario"]);

                // Manejo de contraseña
                if($_POST["editarPassword"] != ""){

                    if(preg_match('/^[a-zA-Z0-9]+$/', $_POST["editarPassword"])){

                        $encriptar = crypt($_POST["editarPassword"], '$2a$07$usesomesillystringforsalt$' );

                    } else {

                        echo '<script>
                            Swal.fire({
                                icon: "error",
                                title: "Error al registrar",
                                text: "La contraseña no puede llevar caracteres especiales.",
                                confirmButtonText: "Cerrar"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location = "usuarios";
                                }
                            });
                        </script>';
                        return; // salir para evitar que siga ejecutando
                    }

                } else {
                    // Si no se modifica la contraseña, se mantiene la actual
                    $encriptar = $_POST["passwordActual"];
                }

                $datos = array(
                    "nombre" => $_POST["editarNombre"],
                    "usuario" => $_POST["editarUsuario"],
                    "password" => $encriptar,
                    "rol" => $_POST["editarRol"]
                );

                $respuesta = ModeloUsuarios::mdlEditarUsuario($tabla, $datos);

                if ($respuesta == "ok") {

                    /* =======================================================
                    🔹 Registrar en historial
                    ======================================================= */
                    require_once "modelos/historial.modelo.php";
                    require_once "controladores/historial.controlador.php";

                    $usuario = $_SESSION["usuario"] ?? "desconocido";
                    $modulo  = "Usuarios";
                    $accion  = "UPDATE";

                    $usuarioDespues = ModeloUsuarios::mdlObtenerUsuarioPorUsername($tabla, $_POST["editarUsuario"]);
                    $idRegistro = $usuarioDespues["id"] ?? null;

                    // Crear arreglo de cambios
                    $cambios = [];

                    if ($usuarioAntes && $usuarioDespues) {
                        foreach (["nombre", "usuario", "rol"] as $campo) {
                            if ($usuarioAntes[$campo] != $usuarioDespues[$campo]) {
                                $cambios[$campo] = [
                                    "antes" => $usuarioAntes[$campo],
                                    "después" => $usuarioDespues[$campo]
                                ];
                            }
                        }

                        // Contraseña
                        if ($usuarioAntes["password"] != $usuarioDespues["password"]) {
                            $cambios["password"] = [
                                "antes" => "********",
                                "después" => "********"
                            ];
                        }
                    }

                    // Si no hay diferencias, igual se guarda el log vacío
                    $valorAnterior = json_encode($usuarioAntes, JSON_UNESCAPED_UNICODE);
                    $valorNuevo = json_encode($cambios ?: ["mensaje" => "Sin cambios detectados"], JSON_UNESCAPED_UNICODE);

                    ControladorHistorial::ctrRegistrarCambio(
                        $usuario,
                        $modulo,
                        $accion,
                        $idRegistro,
                        $valorAnterior,
                        $valorNuevo
                    );
                    /* ======================================================= */

                    echo '<script>
                        Swal.fire({
                            icon: "success",
                            title: "¡Usuario editado!",
                            text: "El usuario ha sido editado correctamente.",
                            confirmButtonText: "Cerrar"
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location = "usuarios";
                            }
                        });
                    </script>';
                }

            } else {

                echo '<script>
                    Swal.fire({
                        icon: "error",
                        title: "Error al registrar",
                        text: "El nombre no puede llevar caracteres especiales.",
                        confirmButtonText: "Cerrar"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location = "usuarios";
                        }
                    });
                </script>';
            }
        }
    }


    // ---------------------------- ELIMINAR USUARIO --------------------------------------
    public static function ctrBorrarUsuario() {

        if (isset($_GET["idUsuario"])) {

            $tabla = "usuario";
            $idUsuario = $_GET["idUsuario"];

            // 🔹 Obtener los datos del usuario antes de eliminar (para historial)
            $usuarioAntes = ModeloUsuarios::mdlObtenerUsuarioPorId($tabla, $idUsuario);

            // 🔹 Eliminar usuario
            $respuesta = ModeloUsuarios::mdlBorrarUsuario($tabla, $idUsuario);

            if ($respuesta == "ok") {

                /* =======================================================
                🔹 Registrar en historial
                ======================================================= */
                require_once "modelos/historial.modelo.php";
                require_once "controladores/historial.controlador.php";

                $usuario = $_SESSION["usuario"] ?? "desconocido";
                $modulo  = "Usuarios";
                $accion  = "DELETE";
                $idRegistro = (int)$idUsuario;

                // Guardar datos legibles
                $valorAnterior = json_encode([
                    "id"      => $usuarioAntes["id"] ?? "Sin información",
                    "nombre"  => $usuarioAntes["nombre"] ?? "Sin información",
                    "usuario" => $usuarioAntes["usuario"] ?? "Sin información",
                    "rol"     => $usuarioAntes["rol"] ?? "Sin información"
                ], JSON_UNESCAPED_UNICODE);

                $valorNuevo = null;

                ControladorHistorial::ctrRegistrarCambio(
                    $usuario,
                    $modulo,
                    $accion,
                    $idRegistro,
                    $valorAnterior,
                    $valorNuevo
                );
                /* ======================================================= */

                echo '<script>
                    Swal.fire({
                        icon: "success",
                        title: "¡Usuario Borrado!",
                        text: "El usuario ha sido borrado correctamente.",
                        confirmButtonText: "Cerrar"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location = "usuarios";
                        }
                    });
                </script>';
            }
        }
    }

}
