BRASA — GESTIÓN DE PEDIDOS
===========================

REQUISITOS
- PHP 8.0 o superior (con PDO MySQL habilitado).
- MySQL/MariaDB.
- Un servidor web como Apache o el hosting PHP de Alwaysdata.

INSTALACIÓN
1. Descomprime el ZIP.
2. Sube index.php, config.php y styles.css a la carpeta pública de tu hosting.
3. Revisa la configuración de conexión en config.php.
4. Abre la URL donde subiste index.php.
5. Al conectarse, config.php crea automáticamente la tabla `pedidos` si no existe.

BASE DE DATOS CONFIGURADA
Host: mysql-bomboclatsito.alwaysdata.net
Base de datos: bomboclatsito_burgir
Usuario: bomboclatsito

FUNCIONES
- Crear pedidos con dirección, estado y cantidades de hamburguesas sencillas, dobles y mixtas.
- Modificar dirección, estado, cantidades y precios de cada pedido.
- Cambiar rápidamente el estado entre Preparando y Despachado.
- Eliminar pedidos.
- Ver el total de cada pedido y un resumen de pedidos/unidades.
- Diseño adaptable a móviles, con CSS de efecto vidrio, transparencias y degradados.

PRECIOS INICIALES
Sencilla: $10.000
Doble: $15.000
Mixta: $18.000
Los precios son valores iniciales editables desde el formulario.

SEGURIDAD
- Usa consultas preparadas PDO y token CSRF para las operaciones de formulario.
- La contraseña de la base de datos está en config.php porque así se solicitó. Evita publicar el archivo o compartir estas credenciales.
- Si la contraseña se ha compartido en un lugar público, cámbiala desde el panel de hosting y actualiza config.php.
- En un entorno de producción conviene ocultar los detalles técnicos de errores de conexión y configurar HTTPS.

NOTA
La aplicación gestiona pedidos y guarda el precio unitario aplicado a cada pedido. No descuenta inventario de ingredientes automáticamente, ya que no se proporcionaron cantidades de ingredientes ni existencias iniciales.
