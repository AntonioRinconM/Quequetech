<?php
require_once __DIR__ . '/conexion.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = isset($_POST['nombre']) && is_string($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $rol = isset($_POST['rol']) && is_string($_POST['rol']) ? trim($_POST['rol']) : '';
    $telefono = isset($_POST['telefono']) && is_string($_POST['telefono']) ? trim($_POST['telefono']) : '';
    $estado = isset($_POST['estado']) && is_string($_POST['estado']) ? trim($_POST['estado']) : '';

    if ($nombre === '' || $rol === '' || !in_array($estado, ['Activo', 'Pendiente', 'Inactivo'], true)) {
        $mensaje = 'Completa el nombre y el rol, y selecciona un estado válido.';
    } elseif (
        (function_exists('mb_strlen') ? mb_strlen($nombre, 'UTF-8') : strlen($nombre)) > 120
        || (function_exists('mb_strlen') ? mb_strlen($rol, 'UTF-8') : strlen($rol)) > 80
        || (function_exists('mb_strlen') ? mb_strlen($telefono, 'UTF-8') : strlen($telefono)) > 40
    ) {
        $mensaje = 'Uno o más campos superan el largo permitido.';
    } else {
        $guardar = $conexion->prepare(
            'INSERT INTO empleados (nombre, rol, telefono, estado) VALUES (:nombre, :rol, :telefono, :estado)'
        );
        $guardar->execute([
            'nombre' => $nombre,
            'rol' => $rol,
            'telefono' => $telefono !== '' ? $telefono : null,
            'estado' => $estado,
        ]);

        header('Location: personal.php?guardado=1');
        exit;
    }
}

if (isset($_GET['guardado'])) {
    $mensaje = 'El empleado se guardó correctamente.';
}

$empleados = $conexion->query(
    'SELECT nombre, rol, telefono, estado FROM empleados ORDER BY nombre'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal | quequetech</title>
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
                <a class="activo" href="personal.php">Personal</a>
                <a href="acceso.php">Acceso</a>
                <a href="tareas.php">Tareas</a>
            </nav>
        </div>
    </header>

    <main class="contenedor seccion">
        <div class="seccion-titulo">
            <h2>Administración del personal</h2>
            <p>Gestión del legajo, roles y situación del equipo de trabajo.</p>
        </div>

        <?php if ($mensaje !== ''): ?>
            <p role="status" aria-live="polite"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <div class="formularios">
            <section class="tarjeta">
                <h3>Registrar empleado</h3>
                <form method="post" action="personal.php">
                    <div class="entrada">
                        <label for="nombreEmpleado">Nombre completo</label>
                        <input id="nombreEmpleado" name="nombre" type="text" maxlength="120" required placeholder="Ej: Martín Ortega">
                    </div>
                    <div class="entrada">
                        <label for="rolEmpleado">Rol</label>
                        <input id="rolEmpleado" name="rol" type="text" maxlength="80" required placeholder="Ej: Técnico">
                    </div>
                    <div class="entrada">
                        <label for="telefonoEmpleado">Teléfono</label>
                        <input id="telefonoEmpleado" name="telefono" type="tel" maxlength="40" placeholder="Ej: +54 11 2222-3333">
                    </div>
                    <div class="entrada">
                        <label for="estadoEmpleado">Estado</label>
                        <select id="estadoEmpleado" name="estado" required>
                            <option value="Activo">Activo</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                    <div class="acciones">
                        <button type="submit" class="boton boton-primario">Guardar legajo</button>
                    </div>
                </form>
            </section>

            <section class="tarjeta">
                <h3>Equipo actual</h3>
                <div class="tabla-contenedor">
                    <table class="tabla">
                        <thead><tr><th>Nombre</th><th>Rol</th><th>Teléfono</th><th>Estado</th></tr></thead>
                        <tbody>
                            <?php foreach ($empleados as $empleado): ?>
                                <?php $estado_css = strtolower(str_replace(' ', '-', $empleado['estado'])); ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($empleado['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['rol'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="estado <?php echo htmlspecialchars($estado_css, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($empleado['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($empleados === []): ?>
                                <tr><td colspan="4">No hay empleados registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <footer class="pie"><div class="contenedor"><p>quequetech Technologies inc. • Administración del personal.</p></div></footer>
</body>
</html>
