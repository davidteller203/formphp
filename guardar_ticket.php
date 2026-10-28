<?php
// guardar_ticket.php — recibe el formulario y guarda el ticket en MySQL
require __DIR__ . '/config/db.php';
requiereLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$solicitante  = trim($_POST['solicitante'] ?? '');
$correo       = trim($_POST['correo'] ?? '');
$categoria_id = (int)($_POST['categoria_id'] ?? 0);
$descripcion  = trim($_POST['descripcion'] ?? '');

// Validación básica
if ($solicitante === '' || $descripcion === '' || $categoria_id <= 0 ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash'] = ['tipo' => 'danger', 'msg' => 'Revisa los datos: hay campos vacíos o inválidos.'];
    header('Location: index.php');
    exit;
}

// Consultas preparadas = protección contra inyección SQL
$stmt = $pdo->prepare(
    "INSERT INTO tickets (solicitante, correo, categoria_id, descripcion)
     VALUES (:s, :c, :cat, :d)"
);
$stmt->execute([
    ':s' => $solicitante, ':c' => $correo, ':cat' => $categoria_id, ':d' => $descripcion,
]);
$id = (int)$pdo->lastInsertId();

// Armamos el código visible (ej. ST-006)
$prefijo = $pdo->prepare("SELECT prefijo FROM categorias WHERE id = ?");
$prefijo->execute([$categoria_id]);
$codigo = $prefijo->fetchColumn() . '-' . str_pad($id, 3, '0', STR_PAD_LEFT);

$_SESSION['flash'] = ['tipo' => 'success', 'msg' => "Solicitud registrada. Tu código es $codigo."];
header('Location: index.php');
exit;
