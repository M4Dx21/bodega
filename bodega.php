<?php
session_start();
include 'db.php';

$consulta = "SELECT * FROM componentes ORDER BY fecha_ingreso DESC";
$resultado = mysqli_query($conn, $consulta);
$personas_dentro = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
$nombre_usuario_filtro = '';
$resolucion_filtro = '';

if (isset($_POST['limpiar_filtros'])) {
    $resolucion_filtro = '';
    $nombre_usuario_filtro = '';
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

if (isset($_GET['query'])) {
    $query = $conn->real_escape_string($_GET['query']);

    $sql = "SELECT codigo, insumo FROM componentes 
            WHERE codigo LIKE '%$query%' OR insumo LIKE '%$query%' 
            LIMIT 10";

    $result = $conn->query($sql);

    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['codigo'] . " - " . $row['insumo'];
    }

    header('Content-Type: application/json');
    echo json_encode($suggestions);
    exit();
}


$sql_check = "SELECT id, codigo, especialidad, insumo, formato, stock, ubicacion, fecha_ingreso FROM componentes WHERE 1";

if ($resolucion_filtro) {
    $sql_check .= " AND estado = '$resolucion_filtro'";
}

if ($nombre_usuario_filtro) {
    $nombre_usuario_filtro = $conn->real_escape_string($nombre_usuario_filtro);
    $sql_check .= " AND (codigo LIKE '%$nombre_usuario_filtro%' OR insumo LIKE '%$nombre_usuario_filtro%')";
}

$sql_check .= " ORDER BY fecha_ingreso DESC";
$resultado = mysqli_query($conn, $sql_check);
$personas_dentro = mysqli_fetch_all($resultado, MYSQLI_ASSOC);


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="asset/styles.css">
    <meta charset="UTF-8">
    <title>Administracion de insumos del Hospital Clinico Félix Bulnes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <div class="header">
        <img src="asset/logo.png" alt="Logo">
        <div class="header-text">
            <div class="main-title">Solicitar insumos de TI</div>
            <div class="sub-title">Hospital Clínico Félix Bulnes</div>
        </div>
        <button id="cuenta-btn" onclick="toggleAccountInfo()"><?php echo $_SESSION['nombre']; ?></button>
        <div id="accountInfo" style="display: none;">
            <p><strong>Usuario: </strong><?php echo $_SESSION['nombre']; ?></p>
            <form action="logout.php" method="POST">
                <button type="submit" class="logout-btn">Salir</button>
            </form>
        </div>
    </div>
</head>
<body>
    <div class="container">
        <div class="filters">
            <form method="POST" action="">
                <label for="codigo">Insumo:</label>
                <div class="input-sugerencias-wrapper">
                    <input type="text" id="codigo" name="codigo" autocomplete="off"
                        placeholder="Escribe el insumo para buscar..."
                        value="<?php echo htmlspecialchars($nombre_usuario_filtro); ?>">
                    <div id="sugerencias" class="sugerencias-box"></div>
                </div>
                <div class="botones-filtros">
                    <button type="submit">Filtrar</button>
                    <button type="submit" name="limpiar_filtros" class="limpiar-filtros-btn">Limpiar Filtros</button>
                </div>
            </form>
        </div>
        <form action="agregarcomp.php" method="post">
            <button type="submit">Agregar Insumos</button>
        </form>
        <?php if (!empty($personas_dentro)): ?>
            <h2>Lista de Componentes</h2>
            <table>
            <tr>
                <th>Código</th>
                <th>Insumo</th>
                <th>Stock</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($personas_dentro as $componente): ?>
                <tr>
                    <td><?= htmlspecialchars($componente['codigo']) ?></td>
                    <td><?= htmlspecialchars($componente['insumo']) ?></td>
                    <td><?= htmlspecialchars($componente['stock']) ?></td>
                    <td><?= date('d-m-y H:i', strtotime($componente['fecha_ingreso'])) ?></td>
                    <td>
                        <a href="?editar=<?= $componente['id'] ?>" class="btn">Editar</a>
                        <a href="?eliminar=<?= $componente['id'] ?>" class="btn" onclick="return confirm('¿Estás seguro de eliminar este componente?');">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <?php if (empty($personas_dentro)): ?>
            <p>No se encontraron resultados para tu búsqueda.</p>
        <?php endif; ?>
    </div>

    <script>

        window.onload = function() {
            const successMsg = document.getElementById("success-msg");
            const errorMsg = document.getElementById("error-rut");

            if (successMsg) {
                successMsg.style.display = 'flex';
                setTimeout(function() {
                    successMsg.style.opacity = 1;
                    setTimeout(function() {
                        successMsg.style.display = 'none';
                    }, 3000);
                }, 70);
            }

            if (errorMsg) {
                errorMsg.style.display = 'flex';
                setTimeout(function() {
                    errorMsg.style.opacity = 1;
                    setTimeout(function() {
                        errorMsg.style.display = 'none';
                    }, 3000);
                }, 70);
            }
        };
        document.addEventListener("DOMContentLoaded", function() {
            const input = document.getElementById("codigo");
            const sugerenciasBox = document.getElementById("sugerencias");

            input.addEventListener("input", function() {
                const query = input.value;

                if (query.length < 2) {
                    sugerenciasBox.innerHTML = "";
                    sugerenciasBox.style.display = "none";
                    return;
                }

                fetch(`bodega.php?query=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        sugerenciasBox.innerHTML = "";
                        if (data.length === 0) {
                            sugerenciasBox.style.display = "none";
                            return;
                        }

                        data.forEach(item => {
                            const div = document.createElement("div");
                            div.textContent = item;
                            div.addEventListener("click", () => {
                                input.value = item.split(" - ")[0]; // Solo deja el código
                                sugerenciasBox.innerHTML = "";
                                sugerenciasBox.style.display = "none";
                            });
                            sugerenciasBox.appendChild(div);
                        });
                        sugerenciasBox.style.display = "block";
                    });
            });

            // Ocultar si se hace clic fuera
            document.addEventListener("click", function(e) {
                if (!sugerenciasBox.contains(e.target) && e.target !== input) {
                    sugerenciasBox.style.display = "none";
                }
            });
        });
        function toggleAccountInfo() {
            const info = document.getElementById('accountInfo');
            info.style.display = info.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>