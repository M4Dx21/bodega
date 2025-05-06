<?php
session_start();
include 'db.php';

$consulta = "SELECT * FROM componentes ORDER BY fecha_ingreso DESC";
$resultado = mysqli_query($conn, $consulta);
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

    function toggleAccountInfo() {
        const info = document.getElementById('accountInfo');
        info.style.display = info.style.display === 'none' ? 'block' : 'none';
    }
    </script>
</body>
</html>