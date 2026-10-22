<?php
require_once __DIR__ . '/conexion.php';

$mensaje = '';
$estados_validos = ['En curso', 'Pendiente', 'Finalizado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente = isset($_POST['cliente']) && is_string($_POST['cliente']) ? trim($_POST['cliente']) : '';
    $titulo = isset($_POST['titulo']) && is_string($_POST['titulo']) ? trim($_POST['titulo']) : '';
    $descripcion = isset($_POST['descripcion']) && is_string($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $tipo = isset($_POST['tipo']) && is_string($_POST['tipo']) ? trim($_POST['tipo']) : '';
    $fecha_solicitud = isset($_POST['fecha_solicitud']) && is_string($_POST['fecha_solicitud'])
        ? trim($_POST['fecha_solicitud'])
        : '';
    $fecha_vencimiento = isset($_POST['fecha_vencimiento']) && is_string($_POST['fecha_vencimiento'])
        ? trim($_POST['fecha_vencimiento'])
        : '';
    $estado = isset($_POST['estado']) && is_string($_POST['estado']) ? trim($_POST['estado']) : '';
    $prioridad = isset($_POST['prioridad']) && is_string($_POST['prioridad']) ? trim($_POST['prioridad']) : '';
    $id_creador = isset($_POST['id_creador']) && $_POST['id_creador'] !== ''
        ? filter_var($_POST['id_creador'], FILTER_VALIDATE_INT)
        : null;
    $id_personal = isset($_POST['id_personal']) && $_POST['id_personal'] !== ''
        ? filter_var($_POST['id_personal'], FILTER_VALIDATE_INT)
        : null;
    $largo_cliente = function_exists('mb_strlen') ? mb_strlen($cliente, 'UTF-8') : strlen($cliente);
    $largo_titulo = function_exists('mb_strlen') ? mb_strlen($titulo, 'UTF-8') : strlen($titulo);
    $largo_tipo = function_exists('mb_strlen') ? mb_strlen($tipo, 'UTF-8') : strlen($tipo);
    $largo_prioridad = function_exists('mb_strlen') ? mb_strlen($prioridad, 'UTF-8') : strlen($prioridad);
    $fecha_valida = static function (string $fecha): bool {
        if ($fecha === '') {
            return true;
        }
        $partes = explode('-', $fecha);
        return count($partes) === 3
            && checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0])
            && sprintf('%04d-%02d-%02d', (int) $partes[0], (int) $partes[1], (int) $partes[2]) === $fecha;
    };

    if ($cliente === '' || $titulo === '') {
        $mensaje = 'Completa el cliente y el título de la tarea.';
    } elseif ($largo_cliente > 100 || $largo_titulo > 100 || $largo_tipo > 20 || $largo_prioridad > 20) {
        $mensaje = 'Uno o más campos superan el largo permitido.';
    } elseif (!in_array($estado, $estados_validos, true)) {
        $mensaje = 'Selecciona un estado válido.';
    } elseif (!$fecha_valida($fecha_solicitud) || !$fecha_valida($fecha_vencimiento)) {
        $mensaje = 'Ingresa fechas válidas.';
    } elseif (
        $fecha_solicitud !== ''
        && $fecha_vencimiento !== ''
        && $fecha_vencimiento < $fecha_solicitud
    ) {
        $mensaje = 'La fecha de vencimiento no puede ser anterior a la fecha de solicitud.';
    } elseif ($id_creador === false || $id_personal === false) {
        $mensaje = 'Selecciona un creador y un responsable válidos.';
    } else {
        $conexion->beginTransaction();
        try {
            $guardar = $conexion->prepare(
                'INSERT INTO tareas (
                    cliente, titulo, descripcion, tipo, fecha_solicitud,
                    fecha_vencimiento, estado, prioridad, id_creador
                ) VALUES (
                    :cliente, :titulo, :descripcion, :tipo, :fecha_solicitud,
                    :fecha_vencimiento, :estado, :prioridad, :id_creador
                )'
            );
            $guardar->execute([
                'cliente' => $cliente,
                'titulo' => $titulo,
                'descripcion' => $descripcion !== '' ? $descripcion : null,
                'tipo' => $tipo !== '' ? $tipo : null,
                'fecha_solicitud' => $fecha_solicitud !== '' ? $fecha_solicitud : null,
                'fecha_vencimiento' => $fecha_vencimiento !== '' ? $fecha_vencimiento : null,
                'estado' => $estado,
                'prioridad' => $prioridad !== '' ? $prioridad : null,
                'id_creador' => $id_creador ?: null,
            ]);
            $id_tarea = (int) $conexion->lastInsertId();

            if ($id_personal) {
                $asignar = $conexion->prepare(
                    'INSERT INTO asignacion_tarea (id_tarea, id_personal, fecha_asignacion)
                     VALUES (:id_tarea, :id_personal, CURRENT_DATE)'
                );
                $asignar->execute([
                    'id_tarea' => $id_tarea,
                    'id_personal' => $id_personal,
                ]);
            }
            $conexion->commit();
            header('Location: tareas.php?guardada=1');
            exit;
        } catch (Throwable $error) {
            $conexion->rollBack();
            throw $error;
        }
    }
}

if (isset($_GET['guardada'])) {
    $mensaje = 'La tarea se guardó correctamente.';
}

$personal = $conexion->query(
    'SELECT id_personal, nombre, apellido FROM personal ORDER BY apellido, nombre'
)->fetchAll();
$empleados = $conexion->query(
    'SELECT e.id_empleado, p.nombre, p.apellido
     FROM empleados e
     INNER JOIN personal p ON p.id_personal = e.id_personal
     ORDER BY p.apellido, p.nombre'
)->fetchAll();
$tareas = $conexion->query(
    'SELECT t.id_tarea, t.titulo, t.cliente, t.descripcion, t.tipo,
            t.fecha_solicitud, t.fecha_vencimiento, t.estado, t.prioridad,
            GROUP_CONCAT(DISTINCT CONCAT(p.nombre, " ", p.apellido) SEPARATOR ", ") AS responsables
     FROM tareas t
     LEFT JOIN asignacion_tarea a ON a.id_tarea = t.id_tarea
     LEFT JOIN personal p ON p.id_personal = a.id_personal
     GROUP BY t.id_tarea
     ORDER BY t.fecha_solicitud DESC, t.id_tarea DESC'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tareas | quequetech</title>
    <link rel="stylesheet" href="../css/Base.css">
    <link rel="stylesheet" href="../css/Layout.css">
    <link rel="stylesheet" href="../css/Formularios.css">
    <link rel="stylesheet" href="../css/Tareas.css">
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
                <a href="acceso.php">Acceso</a>
                <a class="activo" href="tareas.php">Tareas</a>
            </nav>
        </div>
    </header>

    <main class="contenedor seccion">
        <div class="seccion-titulo">
            <h2>Seguimiento de tareas</h2>
            <p>Registro de solicitudes, fechas, prioridad y asignación de responsables.</p>
        </div>

        <?php if ($mensaje !== ''): ?>
            <p role="status" aria-live="polite"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <section class="tarjeta">
            <h3>Registrar tarea</h3>
            <form method="post" action="tareas.php">
                <div class="formularios">
                    <div class="entrada">
                        <label for="cliente">Cliente</label>
                        <input id="cliente" name="cliente" type="text" maxlength="100" required>
                    </div>
                    <div class="entrada">
                        <label for="titulo">Título</label>
                        <input id="titulo" name="titulo" type="text" maxlength="100" required>
                    </div>
                    <div class="entrada">
                        <label for="tipo">Tipo</label>
                        <input id="tipo" name="tipo" type="text" maxlength="20">
                    </div>
                    <div class="entrada">
                        <label for="prioridad">Prioridad</label>
                        <input id="prioridad" name="prioridad" type="text" maxlength="20">
                    </div>
                    <div class="entrada">
                        <label for="fechaSolicitud">Fecha de solicitud</label>
                        <input id="fechaSolicitud" name="fecha_solicitud" type="date">
                    </div>
                    <div class="entrada">
                        <label for="fechaVencimiento">Fecha de vencimiento</label>
                        <input id="fechaVencimiento" name="fecha_vencimiento" type="date">
                    </div>
                    <div class="entrada">
                        <label for="estadoTarea">Estado</label>
                        <select id="estadoTarea" name="estado" required>
                            <option value="Pendiente">Pendiente</option>
                            <option value="En curso">En curso</option>
                            <option value="Finalizado">Finalizado</option>
                        </select>
                    </div>
                    <div class="entrada">
                        <label for="creador">Creador (empleado)</label>
                        <select id="creador" name="id_creador">
                            <option value="">Sin especificar</option>
                            <?php foreach ($empleados as $empleado): ?>
                                <option value="<?php echo (int) $empleado['id_empleado']; ?>">
                                    <?php echo htmlspecialchars($empleado['apellido'] . ', ' . $empleado['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="entrada">
                        <label for="responsable">Asignar a personal</label>
                        <select id="responsable" name="id_personal">
                            <option value="">Sin asignar</option>
                            <?php foreach ($personal as $persona): ?>
                                <option value="<?php echo (int) $persona['id_personal']; ?>">
                                    <?php echo htmlspecialchars($persona['apellido'] . ', ' . $persona['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="entrada">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion"></textarea>
                    </div>
                </div>
                <div class="acciones">
                    <button type="submit" class="boton boton-primario">Guardar tarea</button>
                </div>
            </form>
        </section>

        <div class="lista-filtro" aria-label="Filtros de tareas">
            <button class="filtro activo" data-filtro="todos">Todos</button>
            <button class="filtro" data-filtro="en curso">En curso</button>
            <button class="filtro" data-filtro="pendiente">Pendiente</button>
            <button class="filtro" data-filtro="finalizado">Finalizado</button>
        </div>

        <div class="tabla-contenedor">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Tarea</th>
                        <th>Cliente</th>
                        <th>Descripción</th>
                        <th>Tipo</th>
                        <th>Responsables</th>
                        <th>Estado</th>
                        <th>Prioridad</th>
                        <th>Solicitud</th>
                        <th>Vencimiento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tareas as $tarea): ?>
                        <?php
                        $estado_tarea = $tarea['estado'] ?? 'Sin estado';
                        $estado_filtro = function_exists('mb_strtolower')
                            ? mb_strtolower($estado_tarea, 'UTF-8')
                            : strtolower($estado_tarea);
                        ?>
                        <tr data-estado="<?php echo htmlspecialchars($estado_filtro, ENT_QUOTES, 'UTF-8'); ?>">
                            <td><?php echo htmlspecialchars($tarea['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($tarea['cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($tarea['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($tarea['tipo'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($tarea['responsables'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="estado <?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $estado_tarea)), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($estado_tarea, ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo htmlspecialchars($tarea['prioridad'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php
                                echo $tarea['fecha_solicitud']
                                    ? htmlspecialchars(date('d/m/Y', strtotime($tarea['fecha_solicitud'])), ENT_QUOTES, 'UTF-8')
                                    : 'Sin fecha';
                            ?></td>
                            <td>
                                <?php
                                echo $tarea['fecha_vencimiento']
                                    ? htmlspecialchars(date('d/m/Y', strtotime($tarea['fecha_vencimiento'])), ENT_QUOTES, 'UTF-8')
                                    : 'Sin fecha';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($tareas === []): ?>
                        <tr>
                            <td colspan="9">No hay tareas registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer class="pie">
        <div class="contenedor">
            <p>quequetech Technologies inc. • Seguimiento y control de tareas.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const botonesFiltro = document.querySelectorAll('.filtro');
            const filasTareas = document.querySelectorAll('tr[data-estado]');

            botonesFiltro.forEach(function(boton) {
                boton.addEventListener('click', function() {
                    const filtro = boton.dataset.filtro;
                    botonesFiltro.forEach(function(elemento) {
                        elemento.classList.toggle('activo', elemento === boton);
                    });
                    filasTareas.forEach(function(fila) {
                        const estado = fila.dataset.estado;
                        fila.style.display = filtro === 'todos' || estado === filtro ? '' : 'none';
                    });
                });
            });
        });
    </script>
</body>

</html>