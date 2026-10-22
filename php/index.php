<?php
require_once __DIR__ . '/conexion.php';

$estado_base = 'Conectado';
$total_personal = (int) $conexion->query('SELECT COUNT(*) FROM empleados')->fetchColumn();
$total_tareas = (int) $conexion->query('SELECT COUNT(*) FROM tareas')->fetchColumn();
$total_roles = (int) $conexion->query('SELECT COUNT(*) FROM roles')->fetchColumn();
$empleados = $conexion->query(
    'SELECT nombre, rol, estado, telefono FROM empleados ORDER BY nombre LIMIT 4'
)->fetchAll();
$proyectos = $conexion->query(
    'SELECT t.titulo, c.nombre AS cliente, t.estado, t.fecha
     FROM tareas t
     INNER JOIN clientes c ON c.id = t.cliente_id
     ORDER BY t.fecha DESC, t.id DESC
     LIMIT 3'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>quequetech Technologies inc.</title>
    <link rel="stylesheet" href="../css/Base.css">
    <link rel="stylesheet" href="../css/Layout.css">
    <link rel="stylesheet" href="../css/Inicio.css">
    <link rel="stylesheet" href="../css/Servicios.css">
    <link rel="stylesheet" href="../css/Tareas.css">
</head>
<body>
    <header class="encabezado">
        <div class="contenedor">
            <div class="logo">
                <img src="../images/ico.png" alt="Logo de quequetech">
                <div class="logo-texto">
                    <strong>Quequetech</strong>
                    <span>Technologies inc.</span>
                </div>
            </div>
            <nav class="menu">
                <a class="activo" href="index.php">Inicio</a>
                <a href="../servicios.html">Servicios</a>
                <a href="personal.php">Personal</a>
                <a href="acceso.php">Acceso</a>
                <a href="tareas.php">Tareas</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="contenedor">
                <div class="hero-izquierda">
                    <span class="etiqueta">Soluciones tecnológicas</span>
                    <h1>Administración digital para <span>servicios técnicos</span> y seguridad.</h1>
                    <p>
                        quequetech Technologies inc. combina soporte técnico a domicilio con protección antivirus para hogares,
                        pymes y equipos corporativos. Aquí presentamos la gestión integral de personal, accesos y tareas.
                    </p>
                    <div class="acciones">
                        <a href="#gestion" class="boton boton-primario">Ver gestión</a>
                        <a href="../servicios.html" class="boton boton-secundario">Ver servicios</a>
                    </div>
                </div>

                <aside class="hero-panel">
                    <div class="panel-miniatura">
                        <div class="miniatura">
                            <img src="../images/quequetechlogo.png" alt="Logo de quequetech">
                        </div>
                        <div class="lista-metrica">
                            <div class="metric-card">
                                <strong><?php echo $total_personal; ?></strong>
                                <span>Empleados</span>
                            </div>
                            <div class="metric-card">
                                <strong><?php echo $total_tareas; ?></strong>
                                <span>Tareas</span>
                            </div>
                            <div class="metric-card">
                                <strong><?php echo $total_roles; ?></strong>
                                <span>Roles</span>
                            </div>
                            <div class="metric-card">
                                <strong>24/7</strong>
                                <span>Soporte</span>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="seccion">
            <div class="contenedor">
                <div class="seccion-titulo">
                    <h2>Servicios que ofrecemos</h2>
                    <p>Soluciones prácticas para mantenimiento, protección digital y control operativo.</p>
                </div>

                <div class="grilla-servicios">
                    <article class="tarjeta">
                        <div class="icono">🛠️</div>
                        <h3>Servicio técnico a domicilio</h3>
                        <p>Diagnóstico y reparación de hardware, instalación de equipos, optimización de rendimiento y soporte para hogares y pequeñas empresas.</p>
                    </article>

                    <article class="tarjeta">
                        <div class="icono">🛡️</div>
                        <h3>Software antivirus</h3>
                        <p>Protección preventiva contra malware, ransomware, spyware y amenazas web, con monitoreo y control de seguridad.</p>
                    </article>

                    <article class="tarjeta">
                        <div class="icono">📊</div>
                        <h3>Gestión integral</h3>
                        <p>Visualización de personal, autorizaciones y seguimiento de tareas para mantener cada servicio bajo control.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="gestion" class="seccion">
            <div class="contenedor">
                <div class="seccion-titulo">
                    <h2>Gestión integral de la empresa</h2>
                    <p>Panel funcional para administrar cada aspecto crítico del negocio.</p>
                </div>

                <div class="grilla-modulos">
                    <article class="tarjeta modulo">
                        <h3>Administración del personal</h3>
                        <p>Control del legajo, datos del equipo y estado de cada colaborador.</p>
                        <ul>
                            <li>Legajos activos</li>
                            <li>Contactos y roles</li>
                            <li>Estado del personal</li>
                        </ul>
                    </article>

                    <article class="tarjeta modulo">
                        <h3>Control de acceso</h3>
                        <p>Registro de usuarios y permisos con roles definidos para ingreso y seguridad.</p>
                        <ul>
                            <li>Autenticación</li>
                            <li>Roles por área</li>
                            <li>Control de ingreso</li>
                        </ul>
                    </article>

                    <article class="tarjeta modulo">
                        <h3>Seguimiento de tareas</h3>
                        <p>Monitoreo completo del avance de proyectos y servicios solicitados por clientes.</p>
                        <ul>
                            <li>Estado actual</li>
                            <li>Fechas de trabajo</li>
                            <li>Prioridades</li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <section class="seccion">
            <div class="contenedor">
                <div class="seccion-titulo">
                    <h2>Resumen del equipo</h2>
                    <p>Vista básica del personal y el estado operativo del negocio.</p>
                </div>

                <div class="tabla-contenedor">
                    <table class="tabla">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Teléfono</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empleados as $empleado): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($empleado['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($empleado['rol']); ?></td>
                                    <td><span class="estado <?php echo strtolower(str_replace(' ', '-', $empleado['estado'])); ?>"><?php echo htmlspecialchars($empleado['estado']); ?></span></td>
                                    <td><?php echo htmlspecialchars($empleado['telefono']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="seccion">
            <div class="contenedor">
                <div class="seccion-titulo">
                    <h2>Panel de tareas</h2>
                    <p>Estado de proyectos y servicios disponibles para seguimiento.</p>
                </div>

                <div class="lista-filtro" aria-label="Filtro de tareas">
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
                                <th>Estado</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proyectos as $tarea): ?>
                                <tr data-estado="<?php echo strtolower($tarea['estado']); ?>">
                                    <td><?php echo htmlspecialchars($tarea['titulo']); ?></td>
                                    <td><?php echo htmlspecialchars($tarea['cliente']); ?></td>
                                    <td><span class="estado <?php echo strtolower(str_replace(' ', '-', $tarea['estado'])); ?>"><?php echo htmlspecialchars($tarea['estado']); ?></span></td>
                                    <td><?php echo htmlspecialchars($tarea['fecha']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <footer class="pie">
        <div class="contenedor">
            <p>quequetech Technologies inc. • Base de datos MySQL + PHP • Estado: <?php echo htmlspecialchars($estado_base); ?></p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const botonesFiltro = document.querySelectorAll('.filtro');
            const filasTareas = document.querySelectorAll('tr[data-estado]');

            botonesFiltro.forEach(function (boton) {
                boton.addEventListener('click', function () {
                    const filtro = this.dataset.filtro;
                    botonesFiltro.forEach(function (elemento) {
                        elemento.classList.toggle('activo', elemento === boton);
                    });

                    filasTareas.forEach(function (fila) {
                        const estado = fila.dataset.estado;
                        const debeMostrar = filtro === 'todos' || estado === filtro;
                        fila.style.display = debeMostrar ? '' : 'none';
                    });
                });
            });
        });
    </script>
</body>
</html>
