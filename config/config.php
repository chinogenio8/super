<?php
session_start();

// Configuración de la base de datos
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'lista_compras';

// Conexión a la base de datos
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Verificar conexión
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Función para limpiar entradas
function limpiarDato($dato) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($dato)));
}

// Función para verificar si el usuario ha iniciado sesión
function verificarSesion() {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Función para obtener la familia actual del usuario
function obtenerFamiliaUsuario($usuario_id) {
    global $conn;
    
    $query = "SELECT f.id, f.nombre 
              FROM familias f 
              JOIN miembros_familia mf ON f.id = mf.familia_id 
              WHERE mf.usuario_id = ?";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $usuario_id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($resultado) > 0) {
        return mysqli_fetch_assoc($resultado);
    }
    
    return false;
}

// Función para verificar si el usuario es administrador de la familia
function esAdministrador($usuario_id, $familia_id) {
    global $conn;
    
    $query = "SELECT * FROM miembros_familia 
              WHERE usuario_id = ? AND familia_id = ? AND es_admin = 1";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $usuario_id, $familia_id);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    return mysqli_num_rows($resultado) > 0;
}

