<?php
require_once '../config/config.php';
verificarSesion();

$error = '';
$success = '';
$usuario_id = $_SESSION['usuario_id'];

// Obtener la familia actual del usuario
$familia_actual = obtenerFamiliaUsuario($usuario_id);

// Si el usuario no pertenece a ninguna familia, redirigir a la página de familias
if (!$familia_actual) {
    header("Location: ../modules/familias.php");
    exit();
}

// Verificar si el usuario es administrador
if (!esAdministrador($usuario_id, $familia_actual['id'])) {
    header("Location: ../modules/familias.php");
    exit();
}

// Enviar invitación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = limpiarDato($_POST['email']);
    
    if (empty($email)) {
        $error = 'Por favor, ingrese un correo electrónico.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } else {
        // Verificar si el correo ya está en la familia
        $query = "SELECT u.* FROM usuarios u 
                  JOIN miembros_familia mf ON u.id = mf.usuario_id 
                  WHERE u.email = ? AND mf.familia_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $email, $familia_actual['id']);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($resultado) > 0) {
            $error = 'Este usuario ya es miembro de la familia.';
        } else {
            // Verificar si ya hay una invitación pendiente
            $query = "SELECT * FROM invitaciones WHERE email = ? AND familia_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "si", $email, $familia_actual['id']);
            mysqli_stmt_execute($stmt);
            $resultado = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($resultado) > 0) {
                $error = 'Ya existe una invitación pendiente para este correo.';
            } else {
                // Generar token único
                $token = bin2hex(random_bytes(32));
                
                // Guardar la invitación
                $query = "INSERT INTO invitaciones (familia_id, email, token) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "iss", $familia_actual['id'], $email, $token);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Invitación enviada exitosamente.';
                    
                    // Generar el enlace de invitación
                    $enlace = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]/../familias.php?token=" . $token;
                    
                    // En una aplicación real, aquí enviarías un correo electrónico con el enlace
                    $mensaje_invitacion = "Has sido invitado a unirte a la familia '" . $familia_actual['nombre'] . "' en la aplicación de Lista de Compras Familiar. Para aceptar la invitación, haz clic en el siguiente enlace o cópialo en tu navegador:\n\n" . $enlace;
                } else {
                    $error = 'Error al enviar la invitación: ' . mysqli_error($conn);
                }
            }
        }
    }
}

// Obtener invitaciones pendientes
$query = "SELECT * FROM invitaciones WHERE familia_id = ? ORDER BY fecha_invitacion DESC";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $familia_actual['id']);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$invitaciones = [];
while ($row = mysqli_fetch_assoc($resultado)) {
    $invitaciones[] = $row;
}

// Eliminar invitación
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $invitacion_id = $_GET['eliminar'];
    
    // Verificar que la invitación pertenece a la familia del usuario
    $query = "SELECT * FROM invitaciones WHERE id = ? AND familia_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $invitacion_id, $familia_actual['id']);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($resultado) > 0) {
        $query = "DELETE FROM invitaciones WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $invitacion_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = 'Invitación eliminada exitosamente.';
            // Recargar la página para actualizar la lista de invitaciones
            header("Location: ../modules/invitar.php");
            exit();
        } else {
            $error = 'Error al eliminar la invitación: ' . mysqli_error($conn);
        }
    } else {
        $error = 'Esta invitación no pertenece a tu familia.';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitar Miembros - <?php echo htmlspecialchars($familia_actual['nombre']); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" href="../assets/icono_carrito.png">
</head>
<body>
    <div class="container">
        <header class="app-header">
            <h1>Invitar Miembros: <?php echo htmlspecialchars($familia_actual['nombre']); ?></h1>
            <nav>
                <a href="../modules/familias.php" class="btn">Volver a Familia</a>
                <a href="../modules/lista.php" class="btn">Ver Lista de Compras</a>
                <a href="../auth/logout.php" class="btn logout">Cerrar Sesión</a>
            </nav>
        </header>
        
        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert success"><?php echo $success; ?></div>
            
            <?php if (isset($mensaje_invitacion)): ?>
                <div class="invitacion-info">
                    <h3>Enlace de Invitación</h3>
                    <p>Comparte este enlace con la persona invitada:</p>
                    <input type="text" value="<?php echo htmlspecialchars($enlace); ?>" readonly onclick="this.select();">
                    
                    <div class="invitacion-mensaje">
                        <h4>Mensaje para enviar:</h4>
                        <textarea rows="6" readonly onclick="this.select();"><?php echo htmlspecialchars($mensaje_invitacion); ?></textarea>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="invitar-container">
            <div class="nueva-invitacion">
                <h2>Enviar Nueva Invitación</h2>
                
                <form method="POST" action="../modules/invitar.php">
                    <div class="form-group">
                        <label for="email">Correo electrónico:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn primary">Enviar Invitación</button>
                    </div>
                </form>
            </div>
            
            <div class="invitaciones-pendientes">
                <h2>Invitaciones Pendientes</h2>
                
                <?php if (empty($invitaciones)): ?>
                    <p class="empty-message">No hay invitaciones pendientes.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Correo</th>
                                <th>Fecha</th>
                                <th>Enlace</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invitaciones as $invitacion): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($invitacion['email']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($invitacion['fecha_invitacion'])); ?></td>
                                    <td>
                                        <?php 
                                        $enlace = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]/../familias.php?token=" . $invitacion['token'];
                                        ?>
                                        <input type="text" value="<?php echo htmlspecialchars($enlace); ?>" readonly onclick="this.select();" class="enlace-input">
                                    </td>
                                    <td>
                                        <a href="../modules/invitar.php?eliminar=<?php echo $invitacion['id']; ?>" class="btn small danger" onclick="return confirm('¿Estás seguro de querer eliminar esta invitación?')">Eliminar</a>
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