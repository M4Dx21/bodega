<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar-caja'])) {
    $id = $_POST['id'];
    
    if (empty($id)) {
        echo "El ID de la caja es inválido.";
        exit();
    }
    
    $sql_delete = "DELETE FROM cajas WHERE id = ?";

    if ($stmt = $conn->prepare($sql_delete)) {
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo "Caja eliminada correctamente.";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Error al eliminar la caja: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error en la preparación de la consulta: " . $conn->error;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['meter'])) {
    $codigo = $_POST['codigo'];
    $nombre_caja = $_POST['nombre_caja'];
    $descripcion = $_POST['descripcion'];

    $sql_insert = "INSERT INTO cajas (codigo, nombre_caja, descripcion) 
                   VALUES ('$codigo', '$nombre_caja', '$descripcion')";

    if ($conn->query($sql_insert) === TRUE) {
        echo "Equipo registrado correctamente.";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error: " . $sql_insert . "<br>" . $conn->error;
    }
}

$sql2 = "SELECT nombre_caja, descripcion, codigo, fecha_creacion FROM cajas";
$result2 = $conn->query($sql2);
$solicitudes_result2 = [];
if ($result2->num_rows > 0) {
    while ($row = $result2->fetch_assoc()) {
        $solicitudes_result2[] = $row;
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
            <div class="main-title">Agregar cajas de insumos medicos</div>
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
        <form method="POST" action="">
            <input type="text" name="codigo" placeholder="Codigo de caja" required id="codigo">
            <input type="text" name="nombre_caja" placeholder="Nombre de caja" required id="nombre_caja">
            <input type="text" name="descripcion" placeholder="Descripcion" required id="descripcion">
            <button type="submit" name="meter">Agregar Caja</button>
        </form>
        <?php if (!empty($solicitudes_result2)): ?>
            <h3>Cajas Disponibles:</h3>
            <table class="tabla-admin">
                <thead>
                    <tr>
                        <th>Codigo</th>
                        <th>Nombre</th>
                        <th>Descripcion</th>
                        <th>Fecha creacion</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes_result2 as $solicitud): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($solicitud['codigo']);?></td>
                            <td><?php echo htmlspecialchars($solicitud['nombre_caja']); ?></td>
                            <td><?php echo htmlspecialchars($solicitud['descripcion']); ?></td>
                            <td><?php echo htmlspecialchars($solicitud['fecha_creacion']); ?></td>
                            <td>
                                <a href="?editar=<?= $componente['id'] ?>" class="btn">Editar</a>
                                <a href="?eliminar=<?= $componente['id'] ?>" class="btn" onclick="return confirm('¿Estás seguro de eliminar este componente?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
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