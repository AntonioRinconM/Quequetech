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
        $_SESSION = [];
        session_destroy();
        header('Location: acceso.php?salio=1');
        exit;
    } elseif (isset($_POST['accion']) && $_POST['accion'] === 'registrar') {
        $nombre_usuario = isset($_POST['usuario_nuevo']) && is_string($_POST['usuario_nuevo'])
            ? trim($_POST['usuario_nuevo'])
            : '';
        $clave = isset($_POST['clave_nueva']) && is_string($_POST['clave_nueva'])
            ? $_POST['clave_nueva']
            : '';
        $confirmacion = isset($_POST['confirmar_clave']) && is_string($_POST['confirmar_clave'])
            ? $_POST['confirmar_clave']
            : '';
        $largo_usuario = function_exists('mb_strlen')
            ? mb_strlen($nombre_usuario, 'UTF-8')
            : strlen($nombre_usuario);

        if (isset($_SESSION['usuario_id'])) {
            $mensaje = 'Cierra la sesión para registrar una cuenta nueva.';
        } elseif ($nombre_usuario === '' || $clave === '' || $confirmacion === '') {
            $mensaje = 'Completa todos los campos del registro.';
        } elseif ($largo_usuario > 80) {
            $mensaje = 'El nombre de usuario no puede superar los 80 caracteres.';
        } elseif (strlen($clave) < 8 || strlen($clave) > 72) {
            $mensaje = 'La contraseña debe tener entre 8 y 72 bytes.';
        } elseif ($clave !== $confirmacion) {
            $mensaje = 'Las contraseñas no coinciden.';
        } else {
            $buscar_usuario = $conexion->prepare(
                'SELECT id FROM usuarios WHERE usuario = :usuario LIMIT 1'
            );
            $buscar_usuario->execute(['usuario' => $nombre_usuario]);

            if ($buscar_usuario->fetch()) {
                $mensaje = 'Ese nombre de usuario ya está registrado.';
            } else {
                $buscar_rol = $conexion->prepare(
                    'SELECT id FROM roles WHERE nombre = :nombre LIMIT 1'
                );
                $buscar_rol->execute(['nombre' => 'Técnico']);
                $rol_id = $buscar_rol->fetchColumn();

                if ($rol_id === false) {
                    throw new RuntimeException('No se puede registrar usuarios: falta el rol Técnico en la tabla roles.');
                }

                try {
                    $registrar_usuario = $conexion->prepare(
                        'INSERT INTO usuarios (usuario, clave, rol_id, estado)
                         VALUES (:usuario, :clave, :rol_id, :estado)'
                    );
                    $registrar_usuario->execute([
                        'usuario' => $nombre_usuario,
                        'clave' => password_hash($clave, PASSWORD_DEFAULT),
                        'rol_id' => (int) $rol_id,
                        'estado' => 'Activo',
                    ]);
                } catch (PDOException $error) {
                    if ($error->getCode() !== '23000' || (int) ($error->errorInfo[1] ?? 0) !== 1062) {
                        throw $error;
                    }

                    $mensaje = 'Ese nombre de usuario ya está registrado.';
                }

                if ($mensaje === '') {
                    header('Location: acceso.php?registrado=1');
                    exit;
                }
            }
        }
    } else {
        $nombre_usuario = isset($_POST['usuario']) && is_string($_POST['usuario']) ? trim($_POST['usuario']) : '';
        $clave = isset($_POST['clave']) && is_string($_POST['clave']) ? $_POST['clave'] : '';

        if ($nombre_usuario === '' || $clave === '') {
            $mensaje = 'Ingresa tu usuario y contraseña.';
        } else {
            $consulta = $conexion->prepare(
                'SELECT u.id, u.usuario, u.clave, u.estado, r.nombre AS rol
                 FROM usuarios u
                 LEFT JOIN roles r ON r.id = u.rol_id
                 WHERE u.usuario = :usuario
                 LIMIT 1'
            );
            $consulta->execute(['usuario' => $nombre_usuario]);
            $cuenta = $consulta->fetch();

            if (
                $cuenta
                && $cuenta['estado'] === 'Activo'
                && password_verify($clave, $cuenta['clave'])
            ) {
                session_regenerate_id(true);
                $_SESSION['usuario_id'] = (int) $cuenta['id'];
                $_SESSION['nombre_usuario'] = $cuenta['usuario'];
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

$usuarios = $conexion->query(
    'SELECT u.usuario, u.estado, r.nombre AS rol
     FROM usuarios u
     LEFT JOIN roles r ON r.id = u.rol_id
     ORDER BY u.usuario'
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
            <p>Inicio de sesión y permisos consultados desde la base de datos.</p>
        </div>

        <?php if ($mensaje !== ''): ?>
            <p role="status" aria-live="polite"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <div class="formularios">
            <section class="tarjeta">
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <h3>Sesión activa</h3>
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
                            <input id="usuario" name="usuario" type="text" maxlength="80" autocomplete="username" required>
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
                    <h3>Crear usuario</h3>
                    <p>Las cuentas nuevas se registran con el rol Técnico.</p>
                    <form method="post" action="acceso.php">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token_formulario'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="entrada">
                            <label for="usuarioNuevo">Nombre de usuario</label>
                            <input id="usuarioNuevo" name="usuario_nuevo" type="text" maxlength="80" autocomplete="username" required>
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
                            <button type="submit" name="accion" value="registrar" class="boton boton-secundario">Registrar usuario</button>
                        </div>
                    </form>
                <?php endif; ?>
            </section>

            <section class="tarjeta">
                <h3>Usuarios y permisos</h3>
                <div class="tabla-contenedor">
                    <table class="tabla">
                        <thead><tr><th>Usuario</th><th>Rol</th><th>Acceso</th></tr></thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usuario['usuario'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['rol'] ?? 'Sin rol asignado', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['estado'], ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($usuarios === []): ?>
                                <tr><td colspan="3">No hay usuarios registrados en la base de datos.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <footer class="pie"><div class="contenedor"><p>quequetech Technologies inc. • Control de acceso de usuarios y roles.</p></div></footer>
</body>
</html>
