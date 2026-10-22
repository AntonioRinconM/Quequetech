<?php
session_start();
require_once __DIR__ . '/conexion.php';

if (!isset($_SESSION['token_formulario'])) {
    $_SESSION['token_formulario'] = bin2hex(random_bytes(32));
}

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['token']) && is_string($_POST['token']) ? $_POST['token'] : '';
    if (!hash_equals($_SESSION['token_formulario'], $token)) {
        http_response_code(400);
        $mensaje = 'La solicitud expiró. Actualiza la página e inténtalo nuevamente.';
    } elseif (isset($_POST['accion']) && $_POST['accion'] === 'salir') {
        if (isset($_SESSION['id_empleado'])) {
            $registrar_salida = $conexion->prepare(
                "INSERT INTO registro_acceso (id_empleado, fecha_hora, tipo)
                 VALUES (:id_empleado, CURRENT_TIMESTAMP, 'Salida')"
            );
            $registrar_salida->execute(['id_empleado' => $_SESSION['id_empleado']]);
        }
        $_SESSION = [];
        session_destroy();
        header('Location: acceso.php?salio=1');
        exit;
    } elseif (isset($_POST['accion']) && $_POST['accion'] === 'registrar') {
        $id_personal = filter_input(INPUT_POST, 'id_personal', FILTER_VALIDATE_INT);
        $nombre_usuario = isset($_POST['usuario_nuevo']) && is_string($_POST['usuario_nuevo'])
            ? trim($_POST['usuario_nuevo'])
            : '';
        $clave = isset($_POST['clave_nueva']) && is_string($_POST['clave_nueva'])
            ? $_POST['clave_nueva']
            : '';
        $confirmacion = isset($_POST['confirmar_clave']) && is_string($_POST['confirmar_clave'])
            ? $_POST['confirmar_clave']
            : '';
        $rol = isset($_POST['rol']) && is_string($_POST['rol']) ? trim($_POST['rol']) : '';
        $estado = isset($_POST['estado']) && is_string($_POST['estado']) ? trim($_POST['estado']) : '';
        $largo_usuario = function_exists('mb_strlen')
            ? mb_strlen($nombre_usuario, 'UTF-8')
            : strlen($nombre_usuario);
        $largo_rol = function_exists('mb_strlen') ? mb_strlen($rol, 'UTF-8') : strlen($rol);

        if (isset($_SESSION['id_empleado'])) {
            $mensaje = 'Cierra la sesión para registrar una cuenta nueva.';
        } elseif (!$id_personal || $nombre_usuario === '' || $clave === '' || $confirmacion === '' || $rol === '') {
            $mensaje = 'Completa todos los campos del registro.';
        } elseif ($largo_usuario > 50 || $largo_rol > 30) {
            $mensaje = 'El usuario o el rol superan el largo permitido.';
        } elseif (strlen($clave) < 8 || strlen($clave) > 72) {
            $mensaje = 'La contraseña debe tener entre 8 y 72 bytes.';
        } elseif ($clave !== $confirmacion) {
            $mensaje = 'Las contraseñas no coinciden.';
        } elseif (!in_array($estado, ['Activo', 'Inactivo'], true)) {
            $mensaje = 'Selecciona un estado válido.';
        } else {
            $buscar_usuario = $conexion->prepare(
                'SELECT id_empleado FROM empleados WHERE nombre_usuario = :usuario LIMIT 1'
            );
            $buscar_usuario->execute(['usuario' => $nombre_usuario]);

            $buscar_personal = $conexion->prepare(
                'SELECT id_personal FROM personal
                 WHERE id_personal = :id_personal
                 AND NOT EXISTS (
                     SELECT 1 FROM empleados WHERE empleados.id_personal = personal.id_personal
                 )'
            );
            $buscar_personal->execute(['id_personal' => $id_personal]);

            if ($buscar_usuario->fetch()) {
                $mensaje = 'Ese nombre de usuario ya está registrado.';
            } elseif (!$buscar_personal->fetch()) {
                $mensaje = 'Selecciona una persona sin cuenta de acceso.';
            } else {
                $registrar_usuario = $conexion->prepare(
                    'INSERT INTO empleados (id_personal, nombre_usuario, contrasenia, rol, estado)
                     VALUES (:id_personal, :usuario, :contrasenia, :rol, :estado)'
                );
                $registrar_usuario->execute([
                    'id_personal' => $id_personal,
                    'usuario' => $nombre_usuario,
                    'contrasenia' => password_hash($clave, PASSWORD_DEFAULT),
                    'rol' => $rol,
                    'estado' => $estado,
                ]);
                header('Location: acceso.php?registrado=1');
                exit;
            }
        }
    } else {
        $nombre_usuario = isset($_POST['usuario']) && is_string($_POST['usuario']) ? trim($_POST['usuario']) : '';
        $clave = isset($_POST['clave']) && is_string($_POST['clave']) ? $_POST['clave'] : '';

        if ($nombre_usuario === '' || $clave === '') {
            $mensaje = 'Ingresa tu usuario y contraseña.';
        } else {
            $consulta = $conexion->prepare(
                'SELECT e.id_empleado, e.id_personal, e.nombre_usuario, e.contrasenia,
                        e.estado, e.rol, p.nombre, p.apellido
                 FROM empleados e
                 INNER JOIN personal p ON p.id_personal = e.id_personal
                 WHERE e.nombre_usuario = :usuario
                 LIMIT 1'
            );
            $consulta->execute(['usuario' => $nombre_usuario]);
            $cuenta = $consulta->fetch();

            if (
                $cuenta
                && $cuenta['estado'] === 'Activo'
                && password_verify($clave, $cuenta['contrasenia'])
            ) {
                $registrar_acceso = $conexion->prepare(
                    "INSERT INTO registro_acceso (id_empleado, fecha_hora, tipo)
                     VALUES (:id_empleado, CURRENT_TIMESTAMP, 'Ingreso')"
                );
                $registrar_acceso->execute(['id_empleado' => $cuenta['id_empleado']]);
                $actualizar_acceso = $conexion->prepare(
                    'UPDATE empleados SET ultimo_acceso = CURRENT_TIMESTAMP
                     WHERE id_empleado = :id_empleado'
                );
                $actualizar_acceso->execute(['id_empleado' => $cuenta['id_empleado']]);
                session_regenerate_id(true);
                $_SESSION['id_empleado'] = (int) $cuenta['id_empleado'];
                $_SESSION['nombre_usuario'] = $cuenta['nombre_usuario'];
                $_SESSION['nombre_completo'] = $cuenta['nombre'] . ' ' . $cuenta['apellido'];
                $_SESSION['rol_usuario'] = $cuenta['rol'] ?? 'Sin rol asignado';
                header('Location: acceso.php?ingreso=1');
                exit;
            }

            $mensaje = 'Usuario o contraseña incorrectos, o cuenta inactiva.';
        }
    }
}

if (isset($_GET['ingreso'])) {
    $mensaje = 'Sesión iniciada correctamente.';
}
if (isset($_GET['registrado'])) {
    $mensaje = 'La cuenta se registró correctamente. Ya puedes iniciar sesión.';
}
if (isset($_GET['salio'])) {
    $mensaje = 'Sesión cerrada.';
}

$personal_disponible = $conexion->query(
    'SELECT p.id_personal, p.nombre, p.apellido, p.nro_legajo
     FROM personal p
     WHERE NOT EXISTS (
         SELECT 1 FROM empleados e WHERE e.id_personal = p.id_personal
     )
     ORDER BY p.apellido, p.nombre'
)->fetchAll();
$empleados = $conexion->query(
    'SELECT e.nombre_usuario, e.estado, e.rol, p.nombre, p.apellido
     FROM empleados e
     INNER JOIN personal p ON p.id_personal = e.id_personal
     ORDER BY p.apellido, p.nombre'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso | quequetech</title>
    <link rel="stylesheet" href="../css/Base.css">
    <link rel="stylesheet" href="../css/Layout.css">
    <link rel="stylesheet" href="../css/Formularios.css">
</head>
<body>
    <header class="encabezado">
        <div class="contenedor">
            <div class="logo">
                <img src="../images/ico.png" alt="Logo quequetech">
                <div class="logo-texto"><strong>quequetech</strong><span>Technologies inc.</span></div>
            </div>
            <nav class="menu">
                <a href="index.php">Inicio</a>
                <a href="../servicios.html">Servicios</a>
                <a href="personal.php">Personal</a>
                <a class="activo" href="acceso.php">Acceso</a>
                <a href="tareas.php">Tareas</a>
            </nav>
        </div>
    </header>

    <main class="contenedor seccion">
        <div class="seccion-titulo">
            <h2>Control de acceso</h2>
            <p>Inicio de sesión de empleados y registro de ingresos y salidas.</p>
        </div>

        <?php if ($mensaje !== ''): ?>
            <p role="status" aria-live="polite"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <div class="formularios">
            <section class="tarjeta">
                <?php if (isset($_SESSION['id_empleado'])): ?>
                    <h3>Sesión activa</h3>
                    <p>Empleado: <?php echo htmlspecialchars($_SESSION['nombre_completo'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p>Usuario: <?php echo htmlspecialchars($_SESSION['nombre_usuario'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p>Rol: <?php echo htmlspecialchars($_SESSION['rol_usuario'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <form method="post" action="acceso.php">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token_formulario'], ENT_QUOTES, 'UTF-8'); ?>">
                        <button class="boton boton-primario" type="submit" name="accion" value="salir">Cerrar sesión</button>
                    </form>
                <?php else: ?>
                    <h3>Inicio de sesión</h3>
                    <form method="post" action="acceso.php">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token_formulario'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="entrada">
                            <label for="usuario">Usuario</label>
                            <input id="usuario" name="usuario" type="text" maxlength="50" autocomplete="username" required>
                        </div>
                        <div class="entrada">
                            <label for="contrasena">Contraseña</label>
                            <input id="contrasena" name="clave" type="password" autocomplete="current-password" required>
                        </div>
                        <div class="acciones">
                            <button type="submit" class="boton boton-primario">Ingresar</button>
                        </div>
                    </form>
                    <br>    
                    <h3>Crear cuenta de empleado</h3>
                    <p>La cuenta se vincula a una persona registrada en el legajo.</p>
                    <form method="post" action="acceso.php">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token_formulario'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="entrada">
                            <label for="personalCuenta">Personal</label>
                            <select id="personalCuenta" name="id_personal" required>
                                <option value="">Selecciona una persona</option>
                                <?php foreach ($personal_disponible as $persona): ?>
                                    <option value="<?php echo (int) $persona['id_personal']; ?>">
                                        <?php echo htmlspecialchars($persona['apellido'] . ', ' . $persona['nombre'] . ' — Legajo ' . $persona['nro_legajo'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="entrada">
                            <label for="usuarioNuevo">Nombre de usuario</label>
                            <input id="usuarioNuevo" name="usuario_nuevo" type="text" maxlength="50" autocomplete="username" required>
                        </div>
                        <div class="entrada">
                            <label for="rolCuenta">Rol</label>
                            <input id="rolCuenta" name="rol" type="text" maxlength="30" required>
                        </div>
                        <div class="entrada">
                            <label for="estadoCuenta">Estado</label>
                            <select id="estadoCuenta" name="estado" required>
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div class="entrada">
                            <label for="claveNueva">Contraseña (mínimo 8 caracteres)</label>
                            <input id="claveNueva" name="clave_nueva" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                        </div>
                        <div class="entrada">
                            <label for="confirmarClave">Confirmar contraseña</label>
                            <input id="confirmarClave" name="confirmar_clave" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                        </div>
                        <div class="acciones">
                            <button type="submit" name="accion" value="registrar" class="boton boton-secundario" <?php echo $personal_disponible === [] ? 'disabled' : ''; ?>>Registrar cuenta</button>
                        </div>
                    </form>
                    <?php if ($personal_disponible === []): ?>
                        <p>Primero registra una persona en la sección Personal para poder crear su cuenta.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <section class="tarjeta">
                <h3>Cuentas de acceso</h3>
                <div class="tabla-contenedor">
                    <table class="tabla">
                        <thead><tr><th>Personal</th><th>Usuario</th><th>Rol</th><th>Estado</th></tr></thead>
                        <tbody>
                            <?php foreach ($empleados as $empleado): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($empleado['apellido'] . ', ' . $empleado['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['nombre_usuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['rol'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['estado'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($empleados === []): ?>
                                <tr><td colspan="4">No hay cuentas registradas en la base de datos.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <footer class="pie"><div class="contenedor"><p>quequetech Technologies inc. • Control de cuentas y registro de accesos.</p></div></footer>
</body>
</html>
