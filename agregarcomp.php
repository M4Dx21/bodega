<?php
include 'db.php';
session_start();
$editando = false;
$componente_edit = null;

$consulta = "SELECT * FROM componentes ORDER BY fecha_ingreso DESC";
$resultado = mysqli_query($conn, $consulta);
$personas_dentro = mysqli_fetch_all($resultado, MYSQLI_ASSOC);

function obtenerValoresEnum($conn, $tabla, $columna) {
    $query = "SHOW COLUMNS FROM $tabla LIKE '$columna'";
    $resultado = mysqli_query($conn, $query);
    $fila = mysqli_fetch_assoc($resultado);
    
    if (preg_match("/^enum\(\'(.*)\'\)$/", $fila['Type'], $matches)) {
        $valores = explode("','", $matches[1]);
        return $valores;
    }
    return [];
}
$enum_especialidades = obtenerValoresEnum($conn, 'componentes', 'especialidad');
$enum_formatos = obtenerValoresEnum($conn, 'componentes', 'formato');
$enum_ubicaciones = obtenerValoresEnum($conn, 'componentes', 'ubicacion');

if (isset($_POST['agregar'])) {
    $nombre = $_POST["insumo"];
    $codigo = $_POST["codigo"];
    $stock = $_POST["stock"];
    $especialidad = $_POST["especialidad"];
    $formato = $_POST["formato"];
    $ubicacion = $_POST["ubicacion"];

    $fecha_ingreso = date('Y-m-d H:i:s');

    $insert = "INSERT INTO componentes (codigo, insumo, stock, especialidad, formato, ubicacion, fecha_ingreso) 
               VALUES ('$codigo', '$nombre', '$stock', '$especialidad', '$formato', '$ubicacion', '$fecha_ingreso')";
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
    $nombre = $_POST["insumo"];
    $codigo = $_POST["codigo"];
    $cantidad = $_POST["stock"];
    $especialidad = $_POST["especialidad"];
    $formato = $_POST["formato"];
    $ubicacion = $_POST["ubicacion"];
    $fecha_ingreso = date('Y-m-d H:i:s');

    $update = "UPDATE componentes SET 
                codigo = '$codigo',
                insumo = '$nombre',
                stock = '$cantidad',
                especialidad = '$especialidad',
                formato = '$formato',
                ubicacion = '$ubicacion',
                fecha_ingreso = '$fecha_ingreso'
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
        <form action="importar_excel.php" method="post" enctype="multipart/form-data">
            <label for="archivo_excel">Subir Excel:</label>
            <input type="file" name="archivo_excel" accept=".xlsx, .xls">
            <button type="submit">Importar</button>
        </form>
        <form action="" method="post">
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?= $componente_edit['id'] ?>">
            <?php endif; ?>
            <input type="text" name="insumo" placeholder="Insumo" required
                value="<?= $editando ? $componente_edit['insumo'] : '' ?>">
            <input type="text" name="codigo" placeholder="Código Ad" required
                value="<?= $editando ? $componente_edit['codigo'] : '' ?>">
                <select name="especialidad" required>
                    <option value="">Seleccione especialidad</option>
                    <?php foreach ($enum_especialidades as $valor): ?>
                        <option value="<?= $valor ?>" <?= $editando && $componente_edit['especialidad'] == $valor ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($valor)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="formato" required>
                    <option value="">Seleccione formato</option>
                    <?php foreach ($enum_formatos as $valor): ?>
                        <option value="<?= $valor ?>" <?= $editando && $componente_edit['formato'] == $valor ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($valor)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="ubicacion" required>
                    <option value="">Seleccione ubicación</option>
                    <?php foreach ($enum_ubicaciones as $valor): ?>
                        <option value="<?= $valor ?>" <?= $editando && $componente_edit['ubicacion'] == $valor ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($valor)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <input type="number" name="stock" placeholder="Cantidad" required
                value="<?= $editando ? $componente_edit['stock'] : '' ?>">
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
                <th>Stock</th>
                <th>Especialidad</th>
                <th>Formato</th>
                <th>Ubicacion</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($personas_dentro as $componente): ?>
                <tr>
                    <td><?= $componente['id'] ?></td>
                    <td><?= htmlspecialchars($componente['codigo']) ?></td>
                    <td><?= htmlspecialchars($componente['insumo']) ?></td>
                    <td><?= htmlspecialchars($componente['stock']) ?></td>
                    <td><?= htmlspecialchars($componente['especialidad']) ?></td>
                    <td><?= htmlspecialchars($componente['formato']) ?></td>
                    <td><?= htmlspecialchars($componente['ubicacion']) ?></td>
                    <td><?= date('d-m-y H:i', strtotime($componente['fecha_ingreso'])) ?></td>
                    <td>
                        <a href="?editar=<?= $componente['id'] ?>" class="btn">Editar</a>
                        <a href="?eliminar=<?= $componente['id'] ?>" class="btn" onclick="return confirm('¿Estás seguro de eliminar este componente?');">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </table>
        <?php endif; ?>
                <?php if (isset($_GET['importado'])): ?>
            <div id="success-msg">¡Archivo importado correctamente!</div>
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