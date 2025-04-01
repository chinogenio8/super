<?php
require_once 'config.php';
verificarSesion();

$error = '';
$success = '';
$usuario_id = $_SESSION['usuario_id'];

// Verificar parámetros
if (!isset($_GET['id']) || !isset($_GET['familia']) || !is_numeric($_GET['id']) || !is_numeric($_GET['familia'])) {
    header("Location: familias.php");
    exit();
}

$miembro_id = $_GET['id'];
$familia_id = $_GET['familia'];

// Verificar que el usuario actual es administrador de la familia
if (!esAdministrador($usuario_id, $familia_id)) {
    header("Location: familias.php");
    exit();
}

// No permitir eliminar a sí mismo
if ($miembro_id == $usuario_id) {
    header("Location: familias.php");
    exit();
}

// Verificar que el miembro pertenece a la familia
$query = "SELECT u.nombre, mf.es_admin FROM usuarios u 
          JOIN miembros_familia mf ON u.id = mf.usuario_id 
          WHERE u.id = ? AND mf.familia_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $miembro_id, $familia_id);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($resultado) == 0) {
    header("Location: familias.php");
    exit();
}

$miembro = mysqli_fetch_assoc($resultado);

// Cambiar rol de administrador
if (isset($_GET['admin']) && ($_GET['admin'] == 0 || $_GET['admin'] == 1)) {
    $nuevo_rol = $_GET['admin'];
    
    // Si se va a quitar el rol de administrador, verificar que no sea el último
    if ($nuevo_rol == 0 && $miembro['es_admin'] == 1) {
        $query = "SELECT COUNT(*) as total FROM miembros_familia WHERE familia_id = ? AND es_admin = 1";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $familia_id);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($resultado);
        
        if ($row['total'] <= 1) {
            $error = 'No se puede quitar el rol de administrador porque es el último administrador de la familia.';
        } else {
            // Actualizar rol
            $query = "UPDATE miembros_familia SET es_admin = ? WHERE usuario_id = ? AND familia_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "iii", $nuevo_rol, $miembro_id, $familia_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Rol actualizado exitosamente.';
                // Redirigir después de 2 segundos
                header("refresh:2;url=familias.php");
            } else {
                $error = 'Error al actualizar el rol: ' . mysqli_error($conn);
            }
        }
    } else {
        // Actualizar rol
        $query = "UPDATE miembros_familia SET es_admin = ? WHERE usuario_id = ? AND familia_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iii", $nuevo_rol, $miembro_id, $familia_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Rol actualizado exitosamente.';
            // Redirigir después de 2 segundos
            header("refresh:2;url=familias.php");
        } else {
            $error = 'Error al actualizar el rol: ' . mysqli_error($conn);
        }
    }
} else {
    // Eliminar miembro
    $query = "DELETE FROM miembros_familia WHERE usuario_id = ? AND familia_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $miembro_id, $familia_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $success = 'Miembro eliminado exitosamente.';
        // Redirigir después de 2 segundos
        header("refresh:2;url=familias.php");
    } else {
        $error = 'Error al eliminar el miembro: ' . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Miembro - Lista de Compras Familiar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="app-header">
            <h1>Eliminar Miembro</h1>
            <nav>
                <a href="familias.php" class="btn">Volver a Familia</a>
            </nav>
        </header>
        
        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert success"><?php echo $success; ?></div>
            <p>Redirigiendo a la página de familias...</p>
        <?php endif; ?>
    </div>
</body>
</html>