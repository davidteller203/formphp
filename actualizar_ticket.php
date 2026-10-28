<?php
// actualizar_ticket.php — cambia el estado respetando el flujo:
// Recibida -> Asignada -> En proceso -> Resuelta (y luego Archivar / Restaurar)
require __DIR__ . '/config/db.php';
requiereTecnico();
$titulo = 'Panel técnico';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin.php');
    exit;
}

$id     = (int)($_POST['id'] ?? 0);
$accion = $_POST['accion'] ?? '';
$ok     = false;

if ($id > 0) {
    switch ($accion) {

        case 'asignar':
            $tecnico_id = (int)($_POST['tecnico_id'] ?? 0);
            if ($tecnico_id > 0) {
                $stmt = $pdo->prepare(
                    "UPDATE tickets
                     SET estado='Asignada', tecnico_id=:te, fecha_asignacion=NOW()
                     WHERE id=:id AND estado='Recibida'"
                );
                $stmt->execute([':te' => $tecnico_id, ':id' => $id]);
                $ok = $stmt->rowCount() > 0;
            }
            break;

        case 'iniciar':
            $stmt = $pdo->prepare("UPDATE tickets SET estado='En proceso' WHERE id=:id AND estado='Asignada'");
            $stmt->execute([':id' => $id]);
            $ok = $stmt->rowCount() > 0;
            break;

        case 'resolver':
            $stmt = $pdo->prepare(
                "UPDATE tickets SET estado='Resuelta', fecha_cierre=NOW()
                 WHERE id=:id AND estado='En proceso'"
            );
            $stmt->execute([':id' => $id]);
            $ok = $stmt->rowCount() > 0;
            break;

        case 'archivar':
            // Solo se pueden archivar tickets ya resueltos
            $stmt = $pdo->prepare("UPDATE tickets SET archivado=1 WHERE id=:id AND estado='Resuelta'");
            $stmt->execute([':id' => $id]);
            $ok = $stmt->rowCount() > 0;
            break;

        case 'restaurar':
            $stmt = $pdo->prepare("UPDATE tickets SET archivado=0 WHERE id=:id AND archivado=1");
            $stmt->execute([':id' => $id]);
            $ok = $stmt->rowCount() > 0;
            break;
    }
}

$_SESSION['flash'] = $ok
    ? ['tipo' => 'success', 'msg' => 'Ticket actualizado correctamente.']
    : ['tipo' => 'danger',  'msg' => 'No se pudo actualizar (acción inválida o estado ya cambiado).'];

header('Location: admin.php');
exit;