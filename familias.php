<?php
require_once 'config.php';
verificarSesion();

$error = '';
$success = '';
$usuario_id = $_SESSION['usuario_id'];

// Obtener la familia actual del usuario
$familia_actual = obtenerFamiliaUsuario($usuario_id);

// Crear una nueva familia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_familia'])) {
    $nombre_familia = limpiarDato($_POST['nombre_familia']);
    
    if (empty($nombre_familia)) {
        $error = 'Por favor, ingrese un nombre para la familia.';
    } else {
        // Iniciar transacción
        mysqli_begin_transaction($conn);
        
        try {
            // Crear la familia
            $query = "INSERT INTO familias (nombre) VALUES (?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "s", $nombre_familia);
            mysqli_stmt_execute($stmt);
            
            $familia_id = mysqli_insert_id($conn);
            
            // Agregar al usuario como administrador de la familia
            $query = "INSERT INTO miembros_familia (usuario_id, familia_id, es_admin) VALUES (?, ?, 1)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $usuario_id, $familia_id);
            mysqli_stmt_execute($stmt);
            
            // Confirmar transacción
            mysqli_commit($conn);
            
            $success = 'Familia creada exitosamente.';
            // Redirigir a la lista de compras
            header("refresh:1;url=lista.php");
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            mysqli_rollback($conn);
            $error = 'Error al crear la familia: ' . $e->getMessage();
        }
    }
}

// Salir de una familia
if (isset($_GET['salir']) && is_numeric($_GET['salir'])) {
    $familia_id = $_GET['salir'];
    
    // Verificar que el usuario pertenece a esa familia
    $query = "SELECT * FROM miembros_familia WHERE usuario_id = ? AND familia_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $usuario_id, $familia_id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($resultado) > 0) {
        $miembro = mysqli_fetch_assoc($resultado);
        
        // Verificar si es el último administrador
        if ($miembro['es_admin']) {
            $query = "SELECT COUNT(*) as total FROM miembros_familia WHERE familia_id = ? AND es_admin = 1";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $familia_id);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($resultado);
            
            if ($row['total'] <= 1) {
                // Es el último administrador, preguntar si desea eliminar la familia por completo
                if (isset($_GET['confirmar']) && $_GET['confirmar'] == 1) {
                    // Eliminar la familia
                    $query = "DELETE FROM familias WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $familia_id);
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $success = 'Has eliminado la familia por completo.';
                        // Actualizar la familia actual
                        $familia_actual = obtenerFamiliaUsuario($usuario_id);
                    } else {
                        $error = 'Error al eliminar la familia: ' . mysqli_error($conn);
                    }
                } else {
                    // Mostrar confirmación
                    $mensaje_confirmacion = 'Eres el último administrador. ¿Quieres eliminar la familia por completo? 
                                           <a href="familias.php?salir=' . $familia_id . '&confirmar=1" class="btn danger">Sí, eliminar</a> 
                                           <a href="familias.php" class="btn">Cancelar</a>';
                }
            } else {
                // Eliminar solo al miembro
                $query = "DELETE FROM miembros_familia WHERE usuario_id = ? AND familia_id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ii", $usuario_id, $familia_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Has salido de la familia.';
                    // Actualizar la familia actual
                    $familia_actual = obtenerFamiliaUsuario($usuario_id);
                } else {
                    $error = 'Error al salir de la familia: ' . mysqli_error($conn);
                }
            }
        } else {
            // No es administrador, solo eliminar al miembro
            $query = "DELETE FROM miembros_familia WHERE usuario_id = ? AND familia_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $usuario_id, $familia_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Has salido de la familia.';
                // Actualizar la familia actual
                $familia_actual = obtenerFamiliaUsuario($usuario_id);
            } else {
                $error = 'Error al salir de la familia: ' . mysqli_error($conn);
            }
        }
    } else {
        $error = 'No perteneces a esta familia.';
    }
}

// Aceptar invitación
if (isset($_GET['token'])) {
    $token = limpiarDato($_GET['token']);
    
    // Buscar la invitación
    $query = "SELECT * FROM invitaciones WHERE token = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($resultado) > 0) {
        $invitacion = mysqli_fetch_assoc($resultado);
        
        // Verificar que el correo de la invitación coincide con el del usuario
        if ($invitacion['email'] == $_SESSION['usuario_email']) {
            // Verificar si ya es miembro de la familia
            $query = "SELECT * FROM miembros_familia WHERE usuario_id = ? AND familia_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $usuario_id, $invitacion['familia_id']);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($resultado) == 0) {
                // Agregar al usuario a la familia
                $query = "INSERT INTO miembros_familia (usuario_id, familia_id, es_admin) VALUES (?, ?, 0)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "ii", $usuario_id, $invitacion['familia_id']);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Eliminar la invitación
                    $query = "DELETE FROM invitaciones WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "i", $invitacion['id']);
                    mysqli_stmt_execute($stmt);
                    
                    $success = 'Has sido agregado a la familia.';
                    // Redirigir a la lista de compras
                    header("refresh:1;url=lista.php");
                } else {
                    $error = 'Error al unirse a la familia: ' . mysqli_error($conn);
                }
            } else {
                $error = 'Ya eres miembro de esta familia.';
            }
        } else {
            $error = 'Esta invitación no es para ti.';
        }
    } else {
        $error = 'Invitación no válida o expirada.';
    }
}

// Obtener lista de miembros si pertenece a una familia
$miembros = [];
if ($familia_actual) {
    $query = "SELECT u.id, u.nombre, u.email, mf.es_admin 
              FROM usuarios u 
              JOIN miembros_familia mf ON u.id = mf.usuario_id 
              WHERE mf.familia_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $familia_actual['id']);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($resultado)) {
        $miembros[] = $row;
    }
    
    // Verificar si el usuario actual es administrador
    $es_admin = esAdministrador($usuario_id, $familia_actual['id']);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Familias - Lista de Compras Familiar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="app-header">
            <h1>Gestión de Familias</h1>
            <nav>
                <?php if ($familia_actual): ?>
                    <a href="lista.php" class="btn">Ver Lista de Compras</a>
                <?php endif; ?>
                <a href="logout.php" class="btn logout">Cerrar Sesión</a>
            </nav>
        </header>
        
        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($mensaje_confirmacion)): ?>
            <div class="alert warning"><?php echo $mensaje_confirmacion; ?></div>
        <?php endif; ?>
        
        <?php if ($familia_actual): ?>
            <div class="familia-info">
                <h2>Familia: <?php echo htmlspecialchars($familia_actual['nombre']); ?></h2>
                
                <?php if ($es_admin): ?>
                    <a href="invitar.php" class="btn primary">Invitar Miembros</a>
                <?php endif; ?>
                
                <a href="familias.php?salir=<?php echo $familia_actual['id']; ?>" class="btn danger" onclick="return confirm('¿Estás seguro de querer salir de esta familia?')">Salir de la Familia</a>
            </div>
            
            <div class="miembros-lista">
                <h3>Miembros de la Familia</h3>
                
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <?php if ($es_admin): ?>
                                <th>Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($miembros as $miembro): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($miembro['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($miembro['email']); ?></td>
                                <td><?php echo $miembro['es_admin'] ? 'Administrador' : 'Miembro'; ?></td>
                                <?php if ($es_admin && $miembro['id'] != $usuario_id): ?>
                                    <td>
                                        <a href="eliminar_miembro.php?id=<?php echo $miembro['id']; ?>&familia=<?php echo $familia_actual['id']; ?>" class="btn small danger" onclick="return confirm('¿Estás seguro de querer eliminar a este miembro?')">Eliminar</a>
                                        
                                        <?php if ($miembro['es_admin']): ?>
                                            <a href="eliminar_miembro.php?id=<?php echo $miembro['id']; ?>&familia=<?php echo $familia_actual['id']; ?>&admin=0" class="btn small">Quitar Admin</a>
                                        <?php else: ?>
                                            <a href="eliminar_miembro.php?id=<?php echo $miembro['id']; ?>&familia=<?php echo $familia_actual['id']; ?>&admin=1" class="btn small">Hacer Admin</a>
                                        <?php endif; ?>
                                    </td>
                                <?php elseif ($es_admin): ?>
                                    <td>Tú</td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="crear-familia">
                <h2>Crear una Nueva Familia</h2>
                
                <form method="POST" action="familias.php">
                    <div class="form-group">
                        <label for="nombre_familia">Nombre de la Familia:</label>
                        <input type="text" id="nombre_familia" name="nombre_familia" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="crear_familia" class="btn primary">Crear Familia</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>