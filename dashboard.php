<?php
session_start();
require 'db.php';
include 'funciones.php';

$filtros = [
    'especialidad' => $_GET['especialidad'] ?? '',
    'fecha_inicio' => $_GET['fecha_inicio'] ?? '',
    'fecha_fin' => $_GET['fecha_fin'] ?? ''
];

$insumosBajos = obtenerInsumosBajoStock();
$datosPorMes = obtenerDatosAgrupadosPorMes($conn, $filtros);
$datosHeatmap = obtenerDatosParaHeatmap($conn, $filtros);
$datosPorMes = obtenerDatosAgrupadosPorMes($conn, $filtros);
$topEspecialidad = obtenerTopInsumosPorEspecialidad($conn, $filtros);
//echo '<pre>'; print_r($topEspecialidad); echo '</pre>';


// Consultas para el dashboard 
$total_insumos = $conn->query("SELECT COUNT(*) FROM componentes")->fetch_row()[0];
$stock_critico = $conn->query("SELECT COUNT(*) FROM componentes WHERE stock <= 5")->fetch_row()[0];
$ultimas_reposiciones = $conn->query("SELECT * FROM movimientos WHERE tipo = 'entrada' ORDER BY fecha DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

//actualizar la tabla de ultimas reposiciones

// Construye condiciones WHERE basadas en los filtros
$whereConditions = [];
$params = [];

// Filtro por especialidad
if (!empty($_GET['especialidad'])) {
    $whereConditions[] = "c.especialidad = ?";
    $params[] = $_GET['especialidad'];
}

// Filtro por fecha
if (!empty($_GET['fecha_inicio']) && !empty($_GET['fecha_fin'])) {
    $whereConditions[] = "m.fecha BETWEEN ? AND ?";
    $params[] = $_GET['fecha_inicio'];
    $params[] = $_GET['fecha_fin'];
}



// Consulta modificada para el gráfico de consumo
$sqlConsumo = "
    SELECT c.especialidad, SUM(m.cantidad) as total 
    FROM movimientos m
    JOIN componentes c ON m.componente_id = c.id
    WHERE m.tipo = 'salida'
";

// Añade condiciones WHERE si existen
if (!empty($whereConditions)) {
    $sqlConsumo .= " AND " . implode(" AND ", $whereConditions);
}

$sqlConsumo .= " GROUP BY c.especialidad";

// Prepara y ejecuta la consulta
$stmt = $conn->prepare($sqlConsumo);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$consumo_especialidad = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="asset/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MediTrack</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/regression@2.0.1/dist/regression.min.js"></script>

    <style>
                .card { 
            border-radius: 10px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            
        }
        .card { transform: translateY(-5px); }
        .grid-3-col {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 20px;
        }
        .card-header {
        padding: 15px;
        border-bottom: 1px solid rgba(0,0,0,0.1);
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 10px;
        }

        .card-header i {
            font-size: 1.2em;
        }
        /* Agrega esto en tu sección de estilos CSS */
        .card.mb-4 {
            margin-bottom: 1.7rem !important; /* 32px si estás usando Bootstrap base 16px */
        }

        .kpi-number {
            font-size: 2.5rem;
            font-weight: bold;
        }

        .grid-2-col {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .grid-3-col, .grid-2-col {
                grid-template-columns: 1fr;
            }
        }
        /* Filtros */
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            margin-bottom: 12px; /* Aumentado de 5px */
            font-weight: 500;
            margin-top: 12px;
        }

        .date-range {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .date-range span {
            color: #666;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            align-self: flex-end;
        }

        .btn-filter {
            background-color: #4e73df;
            color: white;
        }

        .btn-clear {
            background-color: #e74a3b;
            color: white;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }


    </style>
    <div class="header">
        <img src="asset/logo.png" alt="Logo">
        <div class="header-text">
            <div class="main-title">Gestion de insumos medicos</div>
            <div class="sub-title">Hospital Clínico Félix Bulnes</div>
        </div>
        <button id="cuenta-btn" onclick="toggleAccountInfo()"><?php echo $_SESSION['nombre']; ?></button>
        <div id="accountInfo" style="display: none;">
            <p><strong>Usuario: </strong><?php echo $_SESSION['nombre']; ?></p>
            <form action="logout.php" method="POST">
                <button type="submit" class="logout-btn">Salir</button>
            </form>
            <button type="button" class="volver-btn" onclick="window.location.href='bodega.php'">Volver</button>
        </div>
    </div>
</head>
<body>
    
    <div class="container">
        <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter"></i> Filtros Avanzados
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="especialidad"><i class="fas fa-tag"></i> Especialidad:</label>
                        <select name="especialidad" id="especialidad" class="form-control">
                            <option value="">Todas las áreas</option>
                            <?php
                            $especialidades = $conn->query("SELECT DISTINCT especialidad FROM componentes");
                            while ($esp = $especialidades->fetch_assoc()):
                                $selected = ($_GET['especialidad'] ?? '') == $esp['especialidad'] ? 'selected' : '';
                            ?>
                                <option value="<?= $esp['especialidad'] ?>" <?= $selected ?>>
                                    <?= ucfirst($esp['especialidad']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <!-- Filtro por Fecha -->
                    <div class="filter-group">
                        <label for="fecha_inicio"><i class="fas fa-calendar-alt"></i> Rango de Fechas:</label>
                        <div class="date-range">
                            <input type="date" name="fecha_inicio" id="fecha_inicio" 
                                   value="<?= $_GET['fecha_inicio'] ?? '' ?>" class="form-control">
                            <span>a</span>
                            <input type="date" name="fecha_fin" id="fecha_fin" 
                                   value="<?= $_GET['fecha_fin'] ?? '' ?>" class="form-control">
                        </div>
                    </div>
                    
                    <!-- Botones -->
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-filter">
                            <i class="fas fa-filter"></i> Aplicar Filtros
                        </button>
                        <a href="dashboard.php" class="btn btn-clear">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
        <!-- Sección de KPIs -->
        <div class="grid-3-col">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-boxes"></i> Total Insumos
                </div>
                <div class="card-body text-center">
                    <div class="kpi-number text-primary"><?= $total_insumos ?></div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-warning">
                    <i class="fas fa-exclamation-triangle"></i> Stock Crítico
                </div>
                <div class="card-body text-center">
                    <div class="kpi-number text-warning"><?= $stock_critico ?></div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-success">
                    <i class="fas fa-history"></i> Última Reposición
                </div>
                <div class="card-body text-center">
                    <div class="kpi-number text-success">
                        <?= !empty($ultimas_reposiciones) ? date('d/m/Y', strtotime($ultimas_reposiciones[0]['fecha'])) : 'N/A' ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- <form action="actualizar_datos.php" method="POST">
            <button type="submit"> Actualizar datos y gráficos</button>
        </form> -->
        <!-- Gráficos desde imágenes generadas por Python -->
        <div class="grid-2-col">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Predicción Insumos
                </div>
                <div class="card-body text-center">
                    <canvas id="graficoPrediccionTodos" ></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i> Top insumos más solicitados
                </div>
                <div class="card-body text-center">
            <canvas id="graficoTopInsumosMes"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie"></i> Insumos según especialidad
                </div>
                <div class="card-body text-center">
                    <canvas id="graficoTortaEspecialidad"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i> Mapa de calor
                </div>
                <div class="card-body text-center">
                    <canvas id="graficoHeatmap"></canvas>
                </div>
            </div>
        </div>


        <!-- Tabla de Insumos Críticos -->
        <div class="card">
            <div class="card-header bg-danger text-white">
                <i class="fas fa-exclamation-circle"></i> Insumos con Stock Crítico
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Insumo</th>
                                <th>Código</th>
                                <th>Stock</th>
                                <th>Ubicación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($insumosBajos)): ?>
                                <?php foreach ($insumosBajos as $insumo): ?>
                                <tr>
                                    <td><?= htmlspecialchars($insumo['insumo']) ?></td>
                                    <td><?= htmlspecialchars($insumo['codigo']) ?></td>
                                    <td class="text-danger font-weight-bold"><?= $insumo['stock'] ?></td>
                                    <td><?= htmlspecialchars($insumo['ubicacion']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-success">
                                        <i class="fas fa-check-circle"></i> No hay insumos con stock crítico
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

   <script>
    // Función para mostrar/ocultar el menú de usuario
    function toggleAccountInfo() {
        const info = document.getElementById('accountInfo');
        info.style.display = info.style.display === 'none' ? 'block' : 'none';
    }

    // Cerrar el menú al hacer clic fuera de él
    document.addEventListener('click', function(event) {
        const accountBtn = document.getElementById('cuenta-btn');
        const accountInfo = document.getElementById('accountInfo');
        
        if (!accountBtn.contains(event.target) && !accountInfo.contains(event.target)){
            accountInfo.style.display = 'none';
        }
    });

    const datos = <?= json_encode($datosPorMes) ?>;

    // Agrupar por insumo (sumar cantidades de todos los meses)
    const resumen = {};
    datos.forEach(d => {
        if (!resumen[d.insumo]) resumen[d.insumo] = 0;
        resumen[d.insumo] += d.cantidad;
    });

    // Obtener los 10 más usados
    const top10 = Object.entries(resumen)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 10);

    const etiquetas = top10.map(([nombre]) => nombre);
    const cantidades = top10.map(([_, cantidad]) => cantidad);

    new Chart(document.getElementById('graficoTopInsumosMes'), {
        type: 'bar',
        data: {
            labels: etiquetas,
            datasets: [{
                label: 'Total consumido',
                data: cantidades,
                backgroundColor: 'rgba(54, 162, 235, 0.7)'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: '10 Insumos más usados (total)'
                }
            }
        }
    });


    document.addEventListener('DOMContentLoaded', function () {
    const raw = <?= json_encode($datosHeatmap) ?>;

    const insumos = [...new Set(raw.map(r => r.insumo))];
    const meses = [...new Set(raw.map(r => r.mes))].sort();

    // Crear matriz vacía
    const matriz = insumos.map(ins => {
        return meses.map(m => {
            const item = raw.find(r => r.insumo === ins && r.mes === m);
            return item ? item.cantidad : 0;
        });
    });

    // Dataset para cada insumo
    const datasets = insumos.map((insumo, i) => ({
        label: insumo,
        data: matriz[i],
        backgroundColor: matriz[i].map(val => `rgba(${255 - val * 10},${255 - val * 5},255,0.7)`),
        borderWidth: 1
    }));

    new Chart(document.getElementById('graficoHeatmap'), {
        type: 'bar',
        data: {
            labels: meses,
            datasets: datasets
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Consumo mensual por insumo'
                },
                legend: { display: false }
            },
            scales: {
                x: { stacked: true },
                y: { stacked: true }
            }
        }
    });
});
// Usamos datos ya disponibles
const agrupados = datos;

// Agrupar por insumo
const agrupadoPorInsumo = {};
agrupados.forEach((d) => {
  if (!agrupadoPorInsumo[d.insumo]) agrupadoPorInsumo[d.insumo] = [];
  agrupadoPorInsumo[d.insumo].push(d);
});

const prediccionesFinales = [];

// Recorremos cada insumo para aplicar regresión
Object.keys(agrupadoPorInsumo).forEach((insumo) => {
  const registros = agrupadoPorInsumo[insumo];
  if (registros.length < 3) return; // omitir si hay pocos datos

  registros.sort((a, b) => a.mes.localeCompare(b.mes));
  const puntos = registros.map((d, i) => [i, d.cantidad]);

  const resultado = regression.linear(puntos);
  const valor = resultado.predict(registros.length)[1]; // predicción próximo mes
  prediccionesFinales.push({ insumo, cantidad: Math.round(valor) });
});

// Ordenar por cantidad descendente
prediccionesFinales.sort((a, b) => b.cantidad - a.cantidad);

// Separar etiquetas y valores
const etiquetasPred = prediccionesFinales.map((d) => d.insumo);
const cantidadesPred = prediccionesFinales.map((d) => d.cantidad);

// Graficar
new Chart(document.getElementById('graficoPrediccionTodos'), {
  type: 'bar',
  data: {
    labels: etiquetasPred,
    datasets: [{
      label: 'Cantidad estimada (próximo mes)',
      data: cantidadesPred,
      backgroundColor: 'rgba(0, 123, 255, 0.7)'
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    plugins: {
      title: {
        display: true,
        text: 'Predicción general de insumos para el próximo mes'
      }
    }
  }
});

const topEspecialidad = <?= json_encode($topEspecialidad) ?>;
const etiquetasPie = topEspecialidad.map(e => e.especialidad);
const valoresPie = topEspecialidad.map(e => e.cantidad);
const detalles = topEspecialidad.map(e => e.insumo);

new Chart(document.getElementById('graficoTortaEspecialidad'), {
    type: 'pie',
    data: {
        labels: etiquetasPie,
        datasets: [{
            data: valoresPie,
            backgroundColor: [
                '#FF6384', '#36A2EB', '#FFCE56',
                '#4BC0C0', '#9966FF', '#FF9F40',
                '#8AC24A', '#7E57C2', '#EF5350', '#66BB6A'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const index = context.dataIndex;
                        return `${detalles[index]}: ${valoresPie[index]}`;
                    }
                }
            },
            title: {
                display: true,
                text: 'Insumo más usado por especialidad'
            }
        }
    }
});


</script>
</body>
</html>