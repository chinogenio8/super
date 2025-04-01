<?php
require_once 'config.php';
verificarSesion();

$error = '';
$success = '';
$usuario_id = $_SESSION['usuario_id'];

// Obtener la familia actual del usuario
$familia_actual = obtenerFamiliaUsuario($usuario_id);

// Si el usuario no pertenece a ninguna familia, redirigir a la página de familias
if (!$familia_actual) {
    header("Location: familias.php");
    exit();
}

// Agregar producto
if (isset($_POST['agregar_producto'])) {
    $nombre_producto = limpiarDato($_POST['nombre_producto']);
    $cantidad = isset($_POST['cantidad']) ? limpiarDato($_POST['cantidad']) : '';
    
    if (empty($nombre_producto)) {
        $error = 'Por favor, ingrese el nombre del producto.';
    } else {
        $query = "INSERT INTO productos (familia_id, nombre, cantidad, usuario_id) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "issi", $familia_actual['id'], $nombre_producto, $cantidad, $usuario_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Producto agregado exitosamente.';
        } else {
            $error = 'Error al agregar el producto: ' . mysqli_error($conn);
        }
    }
}

// Marcar producto como comprado/pendiente
if (isset($_GET['marcar']) && is_numeric($_GET['marcar']) && isset($_GET['estado']) && ($_GET['estado'] == '0' || $_GET['estado'] == '1')) {
    $producto_id = $_GET['marcar'];
    $estado = $_GET['estado'];
    
    // Verificar que el producto pertenece a la familia del usuario
    $query = "SELECT * FROM productos WHERE id = ? AND familia_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $producto_id, $familia_actual['id']);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($resultado) > 0) {
        $query = "UPDATE productos SET comprado = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $estado, $producto_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Estado del producto actualizado exitosamente.';
        } else {
            $error = 'Error al actualizar el estado del producto: ' . mysqli_error($conn);
        }
    } else {
        $error = 'Este producto no pertenece a tu familia.';
    }
}

// Eliminar producto
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $producto_id = $_GET['eliminar'];
    
    // Verificar que el producto pertenece a la familia del usuario
    $query = "SELECT * FROM productos WHERE id = ? AND familia_id = ?";
$stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $producto_id, $familia_actual['id']);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($resultado) > 0) {
        $query = "DELETE FROM productos WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $producto_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Producto eliminado exitosamente.';
        } else {
            $error = 'Error al eliminar el producto: ' . mysqli_error($conn);
        }
    } else {
        $error = 'Este producto no pertenece a tu familia.';
    }
}

// Obtener productos de la familia
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todos';
$where_clause = "WHERE p.familia_id = ?";

if ($filtro === 'pendientes') {
    $where_clause .= " AND p.comprado = 0";
} elseif ($filtro === 'comprados') {
    $where_clause .= " AND p.comprado = 1";
}

$query = "SELECT p.*, u.nombre as agregado_por 
          FROM productos p 
          JOIN usuarios u ON p.usuario_id = u.id 
          $where_clause 
          ORDER BY p.comprado ASC, p.fecha_creacion DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $familia_actual['id']);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$productos = [];
while ($row = mysqli_fetch_assoc($resultado)) {
    $productos[] = $row;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Compras - <?php echo htmlspecialchars($familia_actual['nombre']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="app-header">
            <h1>Lista de Compras: <?php echo htmlspecialchars($familia_actual['nombre']); ?></h1>
            <nav>
                <a href="familias.php" class="btn">Gestionar Familia</a>
                <a href="logout.php" class="btn logout">Cerrar Sesión</a>
            </nav>
        </header>
        
        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="lista-container">
            <div class="agregar-producto">
                <h2>Agregar Producto</h2>
                
                <form method="POST" action="lista.php" class="form-inline">
                    <div class="form-group">
                        <label for="nombre_producto">Nombre:</label>
                        <input type="text" id="nombre_producto" name="nombre_producto" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cantidad">Cantidad:</label>
                        <input type="text" id="cantidad" name="cantidad" placeholder="Opcional">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="agregar_producto" class="btn primary">Agregar</button>
                    </div>
                </form>
            </div>
            
            <div class="filtros">
                <a href="lista.php?filtro=todos" class="btn <?php echo $filtro === 'todos' ? 'active' : ''; ?>">Todos</a>
                <a href="lista.php?filtro=pendientes" class="btn <?php echo $filtro === 'pendientes' ? 'active' : ''; ?>">Pendientes</a>
                <a href="lista.php?filtro=comprados" class="btn <?php echo $filtro === 'comprados' ? 'active' : ''; ?>">Comprados</a>
            </div>
            
            <div class="productos-lista">
                <h2>Productos (<?php echo count($productos); ?>)</h2>
                
                <?php if (empty($productos)): ?>
                    <p class="empty-message">No hay productos en la lista.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Agregado por</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                                <tr class="<?php echo $producto['comprado'] ? 'comprado' : ''; ?>">
                                    <td>
                                        <?php if ($producto['comprado']): ?>
                                            <a href="lista.php?marcar=<?php echo $producto['id']; ?>&estado=0" class="estado-link">✅</a>
                                        <?php else: ?>
                                            <a href="lista.php?marcar=<?php echo $producto['id']; ?>&estado=1" class="estado-link">⬜</a>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($producto['cantidad']); ?></td>
                                    <td><?php echo htmlspecialchars($producto['agregado_por']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($producto['fecha_creacion'])); ?></td>
                                    <td>
                                        <a href="lista.php?eliminar=<?php echo $producto['id']; ?>" class="btn small danger" onclick="return confirm('¿Estás seguro de querer eliminar este producto?')">Eliminar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>