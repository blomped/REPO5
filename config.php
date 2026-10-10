<?php
declare(strict_types=1);

/*
 * Configuración de base de datos.
 * Por seguridad, cambia la contraseña si fue compartida públicamente.
 */
$host = 'mysql-bomboclatsito.alwaysdata.net';
$db   = 'bomboclatsito_burgir';
$user = 'bomboclatsito';
$pass = 'ju1337an';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pedidos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            direccion VARCHAR(255) NOT NULL,
            estado ENUM('Preparando', 'Despachado') NOT NULL DEFAULT 'Preparando',
            cantidad_sencilla INT UNSIGNED NOT NULL DEFAULT 0,
            cantidad_doble INT UNSIGNED NOT NULL DEFAULT 0,
            cantidad_mixta INT UNSIGNED NOT NULL DEFAULT 0,
            precio_sencilla DECIMAL(10,2) NOT NULL DEFAULT 10000.00,
            precio_doble DECIMAL(10,2) NOT NULL DEFAULT 15000.00,
            precio_mixta DECIMAL(10,2) NOT NULL DEFAULT 18000.00,
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
} catch (PDOException $e) {
    http_response_code(500);
    exit('No se pudo conectar con la base de datos. Revisa los datos de conexión y que el servidor permita conexiones. Detalle: ' .
        htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
