<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = limpiarDato($_POST['nombre']);
    $email = limpiarDato($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validar campos
    if (empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Todos los campos son obligatorios';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        // Verificar que el email no existe
        $query = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($resultado) > 0) {
            $error = 'El correo electrónico ya está registrado';
        } else {
            // Encriptar contraseña
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertar usuario
            $query = "INSERT INTO usuarios (nombre, email, password) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sss", $nombre, $email, $password_hash);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Registro exitoso. Ahora puedes iniciar sesión.';
                // Redirigir después de 2 segundos
                header("refresh:2;url=login.php");
            } else {
                $error = 'Error al registrar el usuario: ' . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Lista de Compras Familiar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Registro de Usuario</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="nombre">Nombre completo:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            
            <div class="form-group">
                <label for="email">Correo electrónico:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmar contraseña:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn primary">Registrarse</button>
            </div>
        </form>
        
        <div class="links">
            <p>¿Ya tienes cuenta? <a href="login.php">Iniciar sesión</a></p>
        </div>
    </div>
</body>
</html>