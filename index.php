<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/config.php';

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function money($value): string {
    return '$' . number_format((float)$value, 0, ',', '.');
}
function redirect(): never {
    header('Location: index.php');
    exit;
}

$error = '';
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
    }

    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM pedidos WHERE id = ?');
            $stmt->execute([(int)($_POST['id'] ?? 0)]);
            redirect();
        }

        if ($action === 'status') {
            $estado = $_POST['estado'] ?? '';
            if (!in_array($estado, ['Preparando', 'Despachado'], true)) {
                throw new RuntimeException('Estado no válido.');
            }
            $stmt = $pdo->prepare('UPDATE pedidos SET estado = ? WHERE id = ?');
            $stmt->execute([$estado, (int)($_POST['id'] ?? 0)]);
            redirect();
        }

        if ($action === 'save') {
            $id = (int)($_POST['id'] ?? 0);
            $direccion = trim((string)($_POST['direccion'] ?? ''));
            $estado = (string)($_POST['estado'] ?? 'Preparando');
            $qs = filter_var($_POST['cantidad_sencilla'] ?? 0, FILTER_VALIDATE_INT);
            $qd = filter_var($_POST['cantidad_doble'] ?? 0, FILTER_VALIDATE_INT);
            $qm = filter_var($_POST['cantidad_mixta'] ?? 0, FILTER_VALIDATE_INT);
            $ps = filter_var($_POST['precio_sencilla'] ?? null, FILTER_VALIDATE_FLOAT);
            $pd = filter_var($_POST['precio_doble'] ?? null, FILTER_VALIDATE_FLOAT);
            $pm = filter_var($_POST['precio_mixta'] ?? null, FILTER_VALIDATE_FLOAT);

            if ($direccion === '') throw new RuntimeException('La dirección es obligatoria.');
            if (!in_array($estado, ['Preparando', 'Despachado'], true)) throw new RuntimeException('Estado no válido.');
            foreach ([$qs, $qd, $qm] as $q) {
                if ($q === false || $q < 0 || $q > 10000) throw new RuntimeException('Las cantidades deben ser números enteros entre 0 y 10.000.');
            }
            foreach ([$ps, $pd, $pm] as $p) {
                if ($p === false || $p < 0 || $p > 100000000) throw new RuntimeException('Los precios deben ser valores positivos válidos.');
            }
            if (($qs + $qd + $qm) < 1) throw new RuntimeException('El pedido debe incluir al menos una hamburguesa.');

            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE pedidos SET direccion=?, estado=?, cantidad_sencilla=?, cantidad_doble=?, cantidad_mixta=?, precio_sencilla=?, precio_doble=?, precio_mixta=? WHERE id=?');
                $stmt->execute([$direccion, $estado, $qs, $qd, $qm, $ps, $pd, $pm, $id]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO pedidos (direccion, estado, cantidad_sencilla, cantidad_doble, cantidad_mixta, precio_sencilla, precio_doble, precio_mixta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$direccion, $estado, $qs, $qd, $qm, $ps, $pd, $pm]);
            }
            redirect();
        }
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo completar la operación. Revisa los datos e inténtalo otra vez.';
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM pedidos WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch() ?: null;
}

$pedidos = $pdo->query('SELECT * FROM pedidos ORDER BY creado_en DESC, id DESC')->fetchAll();
$totalPedidos = count($pedidos);
$preparando = count(array_filter($pedidos, fn($p) => $p['estado'] === 'Preparando'));
$despachados = $totalPedidos - $preparando;
$unidades = array_sum(array_map(fn($p) => (int)$p['cantidad_sencilla'] + (int)$p['cantidad_doble'] + (int)$p['cantidad_mixta'], $pedidos));
$sumTotal = fn($p) => (int)$p['cantidad_sencilla'] * (float)$p['precio_sencilla'] + (int)$p['cantidad_doble'] * (float)$p['precio_doble'] + (int)$p['cantidad_mixta'] * (float)$p['precio_mixta'];

$defaults = [
    'id' => 0, 'direccion' => '', 'estado' => 'Preparando',
    'cantidad_sencilla' => 1, 'cantidad_doble' => 0, 'cantidad_mixta' => 0,
    'precio_sencilla' => 10000, 'precio_doble' => 15000, 'precio_mixta' => 18000
];
$form = $editing ? array_merge($defaults, $editing) : $defaults;
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Brasa — Gestión de pedidos</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="ambient ambient-one"></div><div class="ambient ambient-two"></div>
<header class="topbar">
    <a class="brand" href="index.php"><span class="brand-mark">B</span><span>BRASA<small>HAMBURGUESERÍA</small></span></a>
    <span class="top-label"><span class="live-dot"></span> Panel de administración</span>
</header>
<main class="page">
    <section class="hero">
        <div>
            <p class="eyebrow">CENTRO DE OPERACIONES</p>
            <h1>Pedidos que dan<br><span>hambre.</span></h1>
            <p class="subtitle">Administra pedidos, precios y entregas desde un solo lugar.</p>
        </div>
        <a class="button button-primary hero-button" href="#formulario">＋ Nuevo pedido</a>
    </section>

    <section class="stats" aria-label="Resumen de pedidos">
        <article class="stat glass"><span class="stat-icon icon-red">▤</span><div><p>Pedidos totales</p><strong><?= $totalPedidos ?></strong></div></article>
        <article class="stat glass"><span class="stat-icon icon-yellow">◷</span><div><p>Preparando</p><strong><?= $preparando ?></strong></div></article>
        <article class="stat glass"><span class="stat-icon icon-pink">✓</span><div><p>Despachados</p><strong><?= $despachados ?></strong></div></article>
        <article class="stat glass"><span class="stat-icon icon-orange">♨</span><div><p>Hamburguesas</p><strong><?= $unidades ?></strong></div></article>
    </section>

    <?php if ($error !== ''): ?><div class="alert"><?= h($error) ?></div><?php endif; ?>

    <section class="content-grid">
        <section class="panel glass" id="formulario">
            <div class="section-heading"><div><p class="eyebrow">GESTIÓN</p><h2><?= $editing ? 'Editar pedido' : 'Crear pedido' ?></h2></div><span class="heading-mark">✳</span></div>
            <form method="post" class="order-form">
                <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= h($form['id']) ?>">
                <label>Dirección de entrega
                    <input type="text" name="direccion" maxlength="255" required placeholder="Calle, número, barrio..." value="<?= h($form['direccion']) ?>">
                </label>
                <label>Estado del pedido
                    <select name="estado">
                        <option value="Preparando" <?= $form['estado'] === 'Preparando' ? 'selected' : '' ?>>Preparando</option>
                        <option value="Despachado" <?= $form['estado'] === 'Despachado' ? 'selected' : '' ?>>Despachado</option>
                    </select>
                </label>
                <div class="form-subheading"><h3>Detalle del pedido</h3><span>Cantidad y precio unitario</span></div>
                <?php
                $items = [
                    ['sencilla', 'Sencilla', 'S', 'price-red'],
                    ['doble', 'Doble', 'D', 'price-yellow'],
                    ['mixta', 'Mixta', 'M', 'price-pink']
                ];
                foreach ($items as [$key, $name, $letter, $priceClass]):
                ?>
                <div class="burger-row">
                    <span class="burger-badge <?= $priceClass ?>"><?= $letter ?></span>
                    <div class="burger-info"><strong><?= $name ?></strong><small>Hamburguesa <?= strtolower($name) ?></small></div>
                    <label class="mini-field">Cant.
                        <input type="number" name="cantidad_<?= $key ?>" min="0" max="10000" step="1" required value="<?= h($form['cantidad_'.$key]) ?>">
                    </label>
                    <label class="mini-field">Precio $
                        <input type="number" name="precio_<?= $key ?>" min="0" max="100000000" step="100" required value="<?= h($form['precio_'.$key]) ?>">
                    </label>
                </div>
                <?php endforeach; ?>
                <button class="button button-primary submit-button" type="submit"><?= $editing ? 'Guardar cambios' : 'Registrar pedido' ?> <span>→</span></button>
                <?php if ($editing): ?><a class="cancel-link" href="index.php">Cancelar edición</a><?php endif; ?>
                <p class="form-note">Los precios se guardan en cada pedido; puedes modificarlos al editarlo.</p>
            </form>
        </section>

        <section class="panel glass orders-panel">
            <div class="section-heading"><div><p class="eyebrow">EN TIEMPO REAL</p><h2>Pedidos recientes</h2></div><span class="count-pill"><?= $totalPedidos ?> en total</span></div>
            <?php if (!$pedidos): ?>
                <div class="empty-state"><span>▤</span><h3>Aún no hay pedidos</h3><p>Registra el primero desde el formulario.</p></div>
            <?php else: ?>
            <div class="orders-list">
                <?php foreach ($pedidos as $p): ?>
                <article class="order-card">
                    <div class="order-top">
                        <div><span class="order-id">PEDIDO #<?= (int)$p['id'] ?></span><h3><?= h($p['direccion']) ?></h3></div>
                        <span class="status <?= $p['estado'] === 'Despachado' ? 'status-done' : 'status-preparing' ?>"><?= h($p['estado']) ?></span>
                    </div>
                    <div class="order-items">
                        <?php if ((int)$p['cantidad_sencilla']): ?><span><b><?= (int)$p['cantidad_sencilla'] ?>×</b> Sencilla</span><?php endif; ?>
                        <?php if ((int)$p['cantidad_doble']): ?><span><b><?= (int)$p['cantidad_doble'] ?>×</b> Doble</span><?php endif; ?>
                        <?php if ((int)$p['cantidad_mixta']): ?><span><b><?= (int)$p['cantidad_mixta'] ?>×</b> Mixta</span><?php endif; ?>
                    </div>
                    <div class="order-bottom"><div><small>Total del pedido</small><strong><?= money($sumTotal($p)) ?></strong></div><small><?= h(date('d/m/Y H:i', strtotime($p['creado_en']))) ?></small></div>
                    <div class="order-actions">
                        <a class="button button-soft" href="index.php?edit=<?= (int)$p['id'] ?>#formulario">Editar</a>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <input type="hidden" name="estado" value="<?= $p['estado'] === 'Preparando' ? 'Despachado' : 'Preparando' ?>">
                            <button class="button button-soft" type="submit"><?= $p['estado'] === 'Preparando' ? 'Despachar' : 'Volver a preparar' ?></button>
                        </form>
                        <form method="post" class="inline-form" onsubmit="return confirm('¿Seguro que deseas eliminar este pedido?')">
                            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button class="button button-delete" type="submit">Eliminar</button>
                        </form>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </section>
    <footer><span>BRASA © <?= date('Y') ?></span><span>Hecho para gestionar cada pedido con orden.</span></footer>
</main>
</body>
</html>
