<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = limpiarDato($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        $query = "SELECT * FROM usuarios WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $resultado = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($resultado) > 0) {
            $usuario = mysqli_fetch_assoc($resultado);
            
            // Verificar contraseña
            if (password_verify($password, $usuario['password'])) {
                // Iniciar sesión
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_email'] = $usuario['email'];
                
                // Verificar si el usuario pertenece a alguna familia
                $familia = obtenerFamiliaUsuario($usuario['id']);
                
                if ($familia) {
                    // Si tiene familia, redirigir a la lista de compras
                    header("Location: lista.php");
                } else {
                    // Si no tiene familia, redirigir a la página de familias
                    header("Location: familias.php");
                }
                exit();
            } else {
                $error = 'Contraseña incorrecta.';
            }
        } else {
            $error = 'El correo electrónico no está registrado.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Lista de Compras Familiar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Iniciar Sesión</h1>
        
        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="email">Correo electrónico:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn primary">Iniciar Sesión</button>
            </div>
        </form>
        
        <div class="links">
            <p>¿No tienes cuenta? <a href="register.php">Registrarse</a></p>
        </div>
    </div>
</body>
</html>