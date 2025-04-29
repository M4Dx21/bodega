<?php
include 'db.php';
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

function validarRUT($rut) {
    $rut = str_replace(".", "", $rut);

    if (strpos($rut, '-') === false) {
        $rut = substr($rut, 0, -1) . '-' . substr($rut, -1);
    }

    if (!preg_match("/^[0-9]{7,8}-[0-9kK]{1}$/", $rut)) {
        return false;
    }

    list($rut_numeros, $rut_dv) = explode("-", $rut);

    $suma = 0;
    $factor = 2;
    for ($i = strlen($rut_numeros) - 1; $i >= 0; $i--) {
        $suma += $rut_numeros[$i] * $factor;
        $factor = ($factor == 7) ? 2 : $factor + 1;
    }

    $dv_calculado = 11 - ($suma % 11);
    if ($dv_calculado == 11) {
        $dv_calculado = '0';
    } elseif ($dv_calculado == 10) {
        $dv_calculado = 'K';
    }

    return strtoupper($dv_calculado) == strtoupper($rut_dv);
}

function formatearRUT($rut) {
    $rut = str_replace(array("."), "", $rut);
    $dv = strtoupper(substr($rut, -1));
    $rut = substr($rut, 0, -1);
    $rut = strrev(implode(".", str_split(strrev($rut), 3)));
    return $rut . '-' . $dv;
}

function rutExists($rut, $conn) {
    $sql_check = "SELECT 1 FROM usuarios WHERE rut = ?";
    if ($stmt = $conn->prepare($sql_check)) {
        $stmt->bind_param("s", $rut);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    } else {
        echo "Error en la preparación de la consulta: " . $conn->error;
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ingresar'])) {
    $rut = $_POST['rut'];

    if (!validarRUT($rut)) {
        echo "El RUT ingresado no es válido.";
        exit();
    }

    $nombre = $_POST['nombre'];
    $pass = $_POST['pass'];
    $rol = $_POST['rol'];
    $correo = $_POST['correo'];

    if (rutExists($rut, $conn)) {
        $sql_update = "UPDATE usuarios 
                       SET nombre = ?, pass = ?, rol = ?, correo = ?
                       WHERE rut = ?";

        if ($stmt = $conn->prepare($sql_update)) {
            $stmt->bind_param("sssss", $nombre, $pass, $rol, $correo, $rut);

            if ($stmt->execute()) {
                echo "Usuario actualizado correctamente.";
                header("Location: ".$_SERVER['PHP_SELF']);
                exit();
            } else {
                echo "Error: " . $sql_update . "<br>" . $conn->error;
            }

            $stmt->close();
        } else {
            echo "Error en la preparación de la consulta: " . $conn->error;
        }
    } else {
        $sql_insert = "INSERT INTO usuarios (rut, nombre, pass, rol, correo) 
                       VALUES ('$rut', '$nombre', '$pass', '$rol', '$correo')";

        if ($conn->query($sql_insert) === TRUE) {
            echo "Usuario registrado correctamente.";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Error: " . $sql_insert . "<br>" . $conn->error;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['insertar'])) {
    $num_serie = $_POST['num_serie'];
    $codigo = $_POST['codigo'];
    $nombre = $_POST['nombre'];
    $estado = $_POST['estado'];
    $lote = $_POST['lote'];
    $cantidad = $_POST['cantidad'];
    $fecha_ingreso = date('Y-m-d H:i:s');

    $sql_insert = "INSERT INTO componentes (codigo, estado, nombre, num_serie, lote, cantidad, fecha_ingreso) 
                   VALUES ('$codigo', '$estado', '$nombre', '$num_serie', '$lote', '$cantidad', '$fecha_ingreso')";

    if ($conn->query($sql_insert) === TRUE) {
        echo "Equipo registrado correctamente.";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error: " . $sql_insert . "<br>" . $conn->error;
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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambiar_estado'])) {
    $num_serie = $_POST['num_serie'];    
    $estado_actual = $_POST['estado_actual'];

    $nuevo_estado = ($estado_actual == 'bueno') ? 'malo' : 'bueno';

    $sql_update = "UPDATE componentes SET estado = ? WHERE num_serie = ?";

    if ($stmt = $conn->prepare($sql_update)) {
        $stmt->bind_param("si", $nuevo_estado, $nro_serie);

        if ($stmt->execute()) {
        } else {
            echo "Error al actualizar el estado del equipo: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error en la preparación de la consulta: " . $conn->error;
    }
}

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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar-usuario'])) {
    $rut = $_POST['rut'];    
    $sql_delete = "DELETE FROM usuarios WHERE rut = ?";

    if ($stmt = $conn->prepare($sql_delete)) {
        $stmt->bind_param("s", $rut);

        if ($stmt->execute()) {
        } else {
            echo "Error al eliminar el usuario: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error en la preparación de la consulta: " . $conn->error;
    }
}

$sql = "SELECT nombre, num_serie, estado FROM componentes";
$result = $conn->query($sql);
$solicitudes_result = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $solicitudes_result[] = $row;
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

$sql1 = "SELECT nombre, rut, correo FROM usuarios WHERE rol = 'bodeguero'";
$result1 = $conn->query($sql1);
$solicitudes_result1 = [];
if ($result1->num_rows > 0) {
    while ($row = $result1->fetch_assoc()) {
        $solicitudes_result1[] = $row;
    }
}

$sql3 = "SELECT nombre, rut, rol FROM usuarios WHERE rol = 'admin'";
$result3 = $conn->query($sql3);
$solicitudes_result3 = [];
if ($result3->num_rows > 0) {
    while ($row = $result3->fetch_assoc()) {
        $solicitudes_result3[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="stylesheet" href="asset/styles.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <div class="header">
        <img src="asset/logo.png" alt="Logo">
        <div class="header-text">
            <div class="main-title">Administrador bodega Insumos</div>
            <div class="sub-title">Hospital Clínico Félix Bulnes</div>
        </div>
        <form action="logout.php" method="POST">
            <button type="submit" class="logout-btn">Salir</button>
        </form>
    </div>
    <script>
        function toggleCorreo() {
            var rol = document.getElementById('rol').value;
            var correoInput = document.getElementById('correo');
            
            if (rol === 'solicitante') {
                correoInput.disabled = true;
            }
            else if (rol === 'admin') {
                correoInput.disabled = true;
            } else {
                correoInput.disabled = false;
            }
        }

        function limpiarRut() {
            const rutInput = document.getElementById("rut");
            let rut = rutInput.value;
            rut = rut.replace(/\./g, "");
            rutInput.value = rut;
        }

        window.onload = function() {
            toggleCorreo();
        };
    </script>
</head>
<body>
    <div class="container">
    <form method="POST" action="">
            <input type="text" name="codigo" placeholder="Codigo de caja" required id="codigo">
            <input type="text" name="nombre_caja" placeholder="Nombre de caja" required id="nombre_caja">
            <input type="text" name="descripcion" placeholder="Descripcion" required id="descripcion">
            <button type="submit" name="meter">Agregar Caja</button>
        </form>

        <form method="POST" action="">
            <input type="text" name="codigo" placeholder="Codigo de caja" required id="codigo">
            <input type="text" name="nombre" placeholder="Nombre del Equipo" required id="nombre">
            <input type="text" name="num_serie" placeholder="Número de Serie" required id="num_serie">
            <input type="number" name="cantidad" placeholder="cantidad" required id="cantidad">
            <input type="text" name="lote" placeholder="lote" required id="lote">
            <select name="estado" required id="estado">
                <option value="bueno">Bueno</option>
                <option value="malo">Malo</option>
                <option value="en reparacion">En reparacion</option>
            </select>

            <button type="submit" name="insertar">Agregar Equipo</button>
        </form>
        
        <form method="POST" action="">
            <select name="rol" required id="rol" onchange="toggleCorreo()">
                <option value="">Selecciona una opcion</option>
                <option value="bodeguero">Bodeguero</option>
                <option value="admin">Admin</option>
            </select>
            <input type="text" name="rut" placeholder="RUT (sin puntos ni guion, solo con guion para ingresar usuario tipo administrador)" required id="rut" onblur="validarRUTInput()" oninput="limpiarRut()">
            <input type="text" name="nombre" placeholder="Nombre" required id="nombre">
            <input type="password" name="pass" placeholder="Contraseña" required id="pass">

            <input type="email" name="correo" placeholder="Correo" required id="correo">
            <button type="submit" name="ingresar">Registrar Usuario</button>
        </form>

        <?php if (!empty($solicitudes_result)): ?>
            <h3>Insumos Disponibles:</h3>
            <table class="tabla-admin">
                <thead>
                    <tr>
                        <th>Equipos</th>
                        <th>Nro° Serie</th>
                        <th>Estado</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes_result as $solicitud): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($solicitud['nombre']);?></td>
                            <td><?php echo htmlspecialchars($solicitud['num_serie']); ?></td>
                            <td><?php echo htmlspecialchars($solicitud['estado']); ?></td>
                            <td>
                                <a href="?editar=<?= $componente['id'] ?>" class="btn">Editar</a>
                                <a href="?eliminar=<?= $componente['id'] ?>" class="btn" onclick="return confirm('¿Estás seguro de eliminar este componente?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

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

        <?php if (!empty($solicitudes_result1)): ?>
            <h3>Bodegueros:</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>RUT</th>
                        <th>Correo</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes_result1 as $solicitud1): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($solicitud1['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($solicitud1['rut']); ?></td>
                            <td><?php echo htmlspecialchars($solicitud1['correo']); ?></td>
                            <td>
                                <form method="POST" action="">
                                    <input type="hidden" name="rut" value="<?php echo $solicitud1['rut']; ?>">
                                    <button type="submit" name="eliminar-usuario" class="rechazar-btn-table">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($solicitudes_result3)): ?>
            <h3>Administradores:</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>RUT</th>
                        <th>Rol</th>
                        <th>Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes_result3 as $solicitud3): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($solicitud3['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($solicitud3['rut']); ?></td>
                            <td><?php echo htmlspecialchars($solicitud3['rol']); ?></td>
                            <td>
                                <form method="POST" action="">
                                    <input type="hidden" name="rut" value="<?php echo $solicitud3['rut']; ?>">
                                    <button type="submit" name="eliminar-usuario" class="rechazar-btn-table">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>


    </div>
</body>
</html>

<?php
$conn->close();
?>