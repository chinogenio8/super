    // Función para ocultar las alertas después de 3 segundos
    document.addEventListener('DOMContentLoaded', function() {
        const alertas = document.querySelectorAll('.alert');
        if (alertas.length > 0) {
            setTimeout(function() {
                alertas.forEach(function(alerta) {
                    alerta.style.display = 'none';
                });
            }, 3000); // 3000 milisegundos = 3 segundos
        }
    });
