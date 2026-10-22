<?php
require_once __DIR__ . '/conexion.php';

$tareas = $conexion->query(
    'SELECT t.titulo, c.nombre AS cliente, t.responsable, t.estado, t.fecha
    FROM tareas t
    INNER JOIN clientes c ON c.id = t.cliente_id
    ORDER BY t.fecha DESC, t.id DESC'
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
            <p>Monitoreo del estado de proyectos, trabajos y servicios solicitados.</p>
        </div>

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
                        <th>Responsable</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tareas as $tarea): ?>
                        <?php
                        $estado_filtro = function_exists('mb_strtolower')
                            ? mb_strtolower($tarea['estado'], 'UTF-8')
                            : strtolower($tarea['estado']);
                        ?>
                        <tr data-estado="<?php echo htmlspecialchars($estado_filtro, ENT_QUOTES, 'UTF-8'); ?>">
                            <td><?php echo htmlspecialchars($tarea['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($tarea['cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($tarea['responsable'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="estado <?php echo htmlspecialchars(strtolower(str_replace(' ', '-', $tarea['estado'])), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($tarea['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td>
                                <?php
                                echo $tarea['fecha']
                                    ? htmlspecialchars(date('d/m/Y', strtotime($tarea['fecha'])), ENT_QUOTES, 'UTF-8')
                                    : 'Sin fecha';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($tareas === []): ?>
                        <tr>
                            <td colspan="5">No hay tareas registradas.</td>
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