<?php
include 'db.php';
session_start();
$editando = false;
$componente_edit = null;

$consulta_estados = "SELECT estado FROM componentes";
$resultado_estados = mysqli_query($conn, $consulta_estados);
$estados = mysqli_fetch_all($resultado_estados, MYSQLI_ASSOC);

$consulta = "SELECT * FROM componentes ORDER BY fecha_ingreso DESC";
$resultado = mysqli_query($conn, $consulta);
$personas_dentro = mysqli_fetch_all($resultado, MYSQLI_ASSOC);

if (isset($_POST['agregar'])) {
    $nombre = $_POST["nombre"];
    $codigo = $_POST["codigo"];
    $cantidad = $_POST["cantidad"];
    $estado = $_POST["estado"];
    $lote = $_POST["lote"];
    $num_serie = $_POST["num_serie"];

    $fecha_ingreso = date('Y-m-d H:i:s');

    $insert = "INSERT INTO componentes (codigo, nombre, cantidad, estado, lote, fecha_ingreso, num_serie) 
               VALUES ('$codigo', '$nombre', '$cantidad', '$estado', '$lote', '$fecha_ingreso', '$num_serie')";
    mysqli_query($conn, $insert);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_GET['eliminar'])) {
    $id = $_GET['eliminar'];
    mysqli_query($conn, "DELETE FROM componentes WHERE id = $id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_GET['editar'])) {
    $editando = true;
    $id = $_GET['editar'];
    $resultado = mysqli_query($conn, "SELECT * FROM componentes WHERE id = $id");
    $componente_edit = mysqli_fetch_assoc($resultado);
}

if (isset($_POST['guardar_cambios'])) {
    $id = $_POST["id"];
    $nombre = $_POST["nombre"];
    $codigo = $_POST["codigo"];
    $cantidad = $_POST["cantidad"];
    $estado = $_POST["estado"];
    $lote = $_POST["lote"];
    $num_serie = $_POST["num_serie"];
    $fecha_ingreso = $_POST["fecha_ingreso"];

    $update = "UPDATE componentes SET 
                codigo = '$codigo',
                nombre = '$nombre',
                cantidad = '$cantidad',
                estado = '$estado',
                lote = '$lote',
                fecha_ingreso = '$fecha_ingreso',
                num_serie = '$num_serie'
               WHERE id = $id";
    mysqli_query($conn, $update);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$sql = "SELECT * FROM componentes";
$result = $conn->query($sql);
$solicitudes_result = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $solicitudes_result[] = $row;
    }
}

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
            <div class="main-title">Agregar insumos medicos</div>
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
        <div id="mensaje-container">
            <?php if (isset($mensaje)) echo $mensaje; ?>
        </div>
        <h2><?= $editando ? 'Editar Componente' : 'Agregar Componente' ?></h2>
        <form action="" method="post">
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?= $componente_edit['id'] ?>">
            <?php endif; ?>
            <input type="text" name="nombre" placeholder="Nombre del componente" required
                value="<?= $editando ? $componente_edit['nombre'] : '' ?>">
            <input type="text" name="num_serie" placeholder="Número de Serie" required
                value="<?= $editando ? $componente_edit['num_serie'] : '' ?>"> <!-- Nuevo campo -->
            <input type="text" name="codigo" placeholder="Código" required
                value="<?= $editando ? $componente_edit['codigo'] : '' ?>">
            <input type="number" name="cantidad" placeholder="Cantidad" required
                value="<?= $editando ? $componente_edit['cantidad'] : '' ?>">
            <select name="estado" required>
                <option value="bueno" <?= $editando && $componente_edit['estado'] == 'bueno' ? 'selected' : '' ?>>Bueno</option>
                <option value="malo" <?= $editando && $componente_edit['estado'] == 'malo' ? 'selected' : '' ?>>Malo</option>
                <option value="de baja" <?= $editando && $componente_edit['estado'] == 'de baja' ? 'selected' : '' ?>>De Baja</option>
            </select>
            <input type="text" name="lote" placeholder="Lote" required
                value="<?= $editando ? $componente_edit['lote'] : '' ?>">

            <?php if ($editando): ?>
                <button type="submit" name="guardar_cambios">Guardar Cambios</button>
                <a href="<?= $_SERVER['PHP_SELF'] ?>">Cancelar</a>
            <?php else: ?>
                <button type="submit" name="agregar">Agregar Componente</button>
            <?php endif; ?>
        </form>
        <?php if (!empty($personas_dentro)): ?>
            <h2>Lista de Componentes</h2>
            <table>
            <tr>
                <th>ID</th>
                <th>Código</th>
                <th>Nombre</th>
                <th>Número de Serie</th>
                <th>Cantidad</th>
                <th>Estado</th>
                <th>Lote</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($personas_dentro as $componente): ?>
                <tr>
                    <td><?= $componente['id'] ?></td>
                    <td><?= htmlspecialchars($componente['codigo']) ?></td>
                    <td><?= htmlspecialchars($componente['nombre']) ?></td>
                    <td><?= htmlspecialchars($componente['num_serie']) ?></td>
                    <td><?= htmlspecialchars($componente['cantidad']) ?></td>
                    <td><?= htmlspecialchars($componente['estado']) ?></td>
                    <td><?= htmlspecialchars($componente['lote']) ?></td>
                    <td><?= date('d-m-y H:i', strtotime($componente['fecha_ingreso'])) ?></td>
                    <td>
                        <a href="?editar=<?= $componente['id'] ?>" class="btn">Editar</a>
                        <a href="?eliminar=<?= $componente['id'] ?>" class="btn" onclick="return confirm('¿Estás seguro de eliminar este componente?');">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    <script>
        function toggleAccountInfo() {
            const info = document.getElementById('accountInfo');
            info.style.display = info.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>