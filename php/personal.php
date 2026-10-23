<?php
require_once __DIR__ . '/conexion.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campos = [
        'nro_legajo' => 20,
        'nombre' => 50,
        'apellido' => 50,
        'dni' => 15,
        'domicilio' => 100,
        'telefono' => 20,
        'email' => 100,
        'cargo' => 50,
        'area' => 50,
    ];
    $datos = [];
    foreach ($campos as $campo => $maximo) {
        $valor = isset($_POST[$campo]) && is_string($_POST[$campo]) ? trim($_POST[$campo]) : '';
        $largo = function_exists('mb_strlen') ? mb_strlen($valor, 'UTF-8') : strlen($valor);
        if ($largo > $maximo) {
            $mensaje = 'El campo ' . $campo . ' supera el largo permitido.';
            break;
        }
        $datos[$campo] = $valor;
    }
    $fecha_nacimiento = isset($_POST['fecha_nacimiento']) && is_string($_POST['fecha_nacimiento'])
        ? trim($_POST['fecha_nacimiento'])
        : '';
    $fecha_ingreso = isset($_POST['fecha_ingreso']) && is_string($_POST['fecha_ingreso'])
        ? trim($_POST['fecha_ingreso'])
        : '';
    $estado = isset($_POST['estado']) && is_string($_POST['estado']) ? trim($_POST['estado']) : '';
    $fecha_valida = static function (string $fecha): bool {
        if ($fecha === '') {
            return true;
        }
        $partes = explode('-', $fecha);
        return count($partes) === 3
            && checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0])
            && sprintf('%04d-%02d-%02d', (int) $partes[0], (int) $partes[1], (int) $partes[2]) === $fecha;
    };

    if ($mensaje === '' && (
        $datos['nro_legajo'] === ''
        || $datos['nombre'] === ''
        || $datos['apellido'] === ''
        || $datos['dni'] === ''
        || !in_array($estado, ['Activo', 'Pendiente', 'Inactivo'], true)
    )) {
        $mensaje = 'Completa legajo, nombre, apellido y DNI, y selecciona un estado válido.';
    }

    if ($mensaje === '' && (!$fecha_valida($fecha_nacimiento) || !$fecha_valida($fecha_ingreso))) {
        $mensaje = 'Ingresa fechas válidas.';
    }

    if ($mensaje === '') {
        $guardar = $conexion->prepare(
            'INSERT INTO personal (
                nro_legajo, nombre, apellido, dni, fecha_nacimiento, domicilio, telefono,
                email, cargo, area, fecha_ingreso, estado
            ) VALUES (
                :nro_legajo, :nombre, :apellido, :dni, :fecha_nacimiento, :domicilio, :telefono,
                :email, :cargo, :area, :fecha_ingreso, :estado
            )'
        );
        $guardar->execute(array_replace($datos, [
            'fecha_nacimiento' => $fecha_nacimiento !== '' ? $fecha_nacimiento : null,
            'fecha_ingreso' => $fecha_ingreso !== '' ? $fecha_ingreso : null,
            'domicilio' => $datos['domicilio'] !== '' ? $datos['domicilio'] : null,
            'telefono' => $datos['telefono'] !== '' ? $datos['telefono'] : null,
            'email' => $datos['email'] !== '' ? $datos['email'] : null,
            'cargo' => $datos['cargo'] !== '' ? $datos['cargo'] : null,
            'area' => $datos['area'] !== '' ? $datos['area'] : null,
            'estado' => $estado,
        ]));

        header('Location: personal.php?guardado=1');
        exit;
    }
}

if (isset($_GET['guardado'])) {
    $mensaje = 'El registro de personal se guardó correctamente.';
}

$empleados = $conexion->query(
    'SELECT nro_legajo, nombre, apellido, dni, fecha_nacimiento, domicilio, telefono,
            email, cargo, area, fecha_ingreso, estado
     FROM personal
     ORDER BY apellido, nombre'
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
            <p>Administración de legajos y datos personales del equipo.</p>
        </div>

        <?php if ($mensaje !== ''): ?>
            <p role="status" aria-live="polite"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <div class="formularios">
            <section class="tarjeta">
                <h3>Registrar empleado</h3>
                <form method="post" action="personal.php">
                    <div class="entrada">
                        <label for="nroLegajo">Número de legajo</label>
                        <input id="nroLegajo" name="nro_legajo" type="text" maxlength="20" required>
                    </div>
                    <div class="entrada">
                        <label for="nombre">Nombre</label>
                        <input id="nombre" name="nombre" type="text" maxlength="50" required>
                    </div>
                    <div class="entrada">
                        <label for="apellido">Apellido</label>
                        <input id="apellido" name="apellido" type="text" maxlength="50" required>
                    </div>
                    <div class="entrada">
                        <label for="dni">DNI</label>
                        <input id="dni" name="dni" type="text" maxlength="15" required>
                    </div>
                    <div class="entrada">
                        <label for="fechaNacimiento">Fecha de nacimiento</label>
                        <input id="fechaNacimiento" name="fecha_nacimiento" type="date">
                    </div>
                    <div class="entrada">
                        <label for="domicilio">Domicilio</label>
                        <input id="domicilio" name="domicilio" type="text" maxlength="100">
                    </div>
                    <div class="entrada">
                        <label for="telefono">Teléfono</label>
                        <input id="telefono" name="telefono" type="tel" maxlength="20">
                    </div>
                    <div class="entrada">
                        <label for="email">Correo electrónico</label>
                        <input id="email" name="email" type="email" maxlength="100">
                    </div>
                    <div class="entrada">
                        <label for="cargo">Cargo</label>
                        <input id="cargo" name="cargo" type="text" maxlength="50">
                    </div>
                    <div class="entrada">
                        <label for="area">Área</label>
                        <input id="area" name="area" type="text" maxlength="50">
                    </div>
                    <div class="entrada">
                        <label for="fechaIngreso">Fecha de ingreso</label>
                        <input id="fechaIngreso" name="fecha_ingreso" type="date">
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
                        <button type="submit" class="boton boton-primario">Guardar personal</button>
                    </div>
                </form>
            </section>

            <section class="tarjeta">
                <h3>Personal registrado</h3>
                <div class="tabla-contenedor">
                    <table class="tabla">
                        <thead><tr><th>Legajo</th><th>Nombre</th><th>Apellido</th><th>DNI</th><th>Nacimiento</th><th>Domicilio</th><th>Teléfono</th><th>Email</th><th>Cargo</th><th>Área</th><th>Ingreso</th><th>Estado</th></tr></thead>
                        <tbody>
                            <?php foreach ($empleados as $empleado): ?>
                                <?php $estado = $empleado['estado'] ?? 'Sin estado'; ?>
                                <?php $estado_css = strtolower(str_replace(' ', '-', $estado)); ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($empleado['nro_legajo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['apellido'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['dni'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['fecha_nacimiento'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['domicilio'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['cargo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['area'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['fecha_ingreso'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><span class="estado <?php echo htmlspecialchars($estado_css, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($estado, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($empleados === []): ?>
                                <tr><td colspan="12">No hay personal registrado.</td></tr>
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
