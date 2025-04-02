# Lista del super para una familia

Este proyecto se basa en una problemática que puede haber en las familias, que es la lista de las compras. Capaz que se olvidan las cosas, o la misma lista. Para eso está este programa.

* **Agregar Productos:** Permite añadir nuevos productos a la lista de compras.
* **Gestionar Familias:** Permite gestionar las diferentes familias y sus listas de compras.
* **Eliminar Productos:** Permite eliminar productos de la lista de compras.
* **Login:** Sistema de autenticación para que cada miembro de la familia pueda acceder a su lista personalizada.

## Tecnologías Utilizadas

Este proyecto ha sido desarrollado utilizando las siguientes tecnologías web fundamentales:

* **PHP:** Lenguaje de programación del lado del servidor utilizado para la lógica del backend.
* **CSS:** Define el estilo visual, incluyendo el diseño, la presentación y la implementación del modo oscuro/claro.
* **MYSQL:** Base de datos relacional utilizada para almacenar la información de los productos y usuarios.

## Cómo Utilizar

Para ejecutar esta lista del super en tu entorno local, sigue estos sencillos pasos:

1. **Clonar el Repositorio:** Clona el repositorio del proyecto en tu máquina local utilizando Git.
    ```sh
    git clone [URL_del_repositorio]
    ```
2. **Configurar la Base de Datos:** Importa el archivo SQL proporcionado para crear la estructura de la base de datos en MySQL.
    ```sh
    mysql -u tu_usuario -p tu_base_de_datos < archivo.sql
    ```
3. **Configurar el Archivo de Configuración:** Edita el archivo de configuración (por ejemplo, `config.php`) con los detalles de tu base de datos.
    ```php
    $servername = "localhost";
    $username = "tu_usuario";
    $password = "tu_contraseña";
    $dbname = "tu_base_de_datos";
    ```
4. **Iniciar el Servidor:** Inicia un servidor local (por ejemplo, utilizando XAMPP, WAMP o cualquier otro servidor PHP).
5. **Acceder a la Aplicación:** Abre tu navegador web y accede a la aplicación a través de `http://localhost/nombre_del_proyecto`.
6. **Registrar y Loguear:** Regístrate como nuevo usuario o inicia sesión con tus credenciales existentes para empezar a utilizar la lista del super.

![image](https://github.com/user-attachments/assets/27a48868-7dfb-4b12-a265-853965e769fe)
