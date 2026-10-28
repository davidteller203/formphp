<?php
// admin.php — panel del técnico: lista de tickets y acciones por estado
require __DIR__ . '/config/db.php';
$titulo = 'Panel técnico';
requiereTecnico();

$estados = [               // estado => color de Bootstrap
    'Recibida'   => 'secondary',
    'Asignada'   => 'primary',
    'En proceso' => 'warning',
    'Resuelta'   => 'success',
];

$filtro = $_GET['estado'] ?? '';
$sql = "SELECT t.id,
               CONCAT(c.prefijo,'-',LPAD(t.id,3,'0')) AS codigo,
               t.solicitante, t.correo, c.nombre AS categoria, t.descripcion,
               t.estado, te.nombre AS responsable, t.fecha_creacion, t.fecha_cierre
        FROM tickets t
        JOIN categorias c     ON c.id  = t.categoria_id
        LEFT JOIN tecnicos te ON te.id = t.tecnico_id";
$verArchivados = isset($_GET['archivados']);
$where  = [$verArchivados ? "t.archivado = 1" : "t.archivado = 0"];
$params = [];
if (isset($estados[$filtro])) {
    $where[]           = "t.estado = :estado";
    $params[':estado'] = $filtro;
}
$sql .= " WHERE " . implode(' AND ', $where) . " ORDER BY t.fecha_creacion DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

$tecnicos = $pdo->query("SELECT id, nombre FROM tecnicos WHERE activo = 1 ORDER BY nombre")->fetchAll();
$conteo   = $pdo->query("SELECT estado, COUNT(*) FROM tickets WHERE archivado = 0 GROUP BY estado")->fetchAll(PDO::FETCH_KEY_PAIR);

require __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Gestión de solicitudes</h4>
 <div class="d-flex gap-2">
  <?php if ($verArchivados): ?>
    <a href="admin.php" class="btn btn-outline-primary btn-sm">Volver a activos</a>
  <?php else: ?>
    <a href="admin.php?archivados=1" class="btn btn-outline-primary btn-sm">Ver archivados</a>
  <?php endif; ?>
  <a href="exportar_csv.php" class="btn btn-outline-secondary btn-sm">Exportar CSV (indicadores)</a>
</div>
</div>

<!-- Tarjetas por estado (también funcionan como filtro) -->
<div class="row g-3 mb-4">
  <?php foreach ($estados as $est => $color): ?>
    <div class="col-6 col-md-3">
      <a href="admin.php?estado=<?= urlencode($est) ?>" class="text-decoration-none">
        <div class="card border-<?= $color ?> shadow-sm">
          <div class="card-body text-center py-2">
            <div class="fs-3 fw-bold text-<?= $color === 'warning' ? 'dark' : $color ?>"><?= (int)($conteo[$est] ?? 0) ?></div>
            <div class="small text-muted"><?= e($est) ?></div>
          </div>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>
<?php if ($filtro): ?>
  <p>Filtrando por: <strong><?= e($filtro) ?></strong> — <a href="admin.php">ver todos</a></p>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Código</th><th>Solicitante</th><th>Categoría</th><th>Descripción</th>
          <th>Estado</th><th>Responsable</th><th>Creada</th><th style="min-width:230px">Acción</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($tickets as $t): ?>
        <tr>
          <td class="fw-bold"><?= e($t['codigo']) ?></td>
          <td><?= e($t['solicitante']) ?><br><small class="text-muted"><?= e($t['correo']) ?></small></td>
          <td><?= e($t['categoria']) ?></td>
          <td style="max-width:260px"><?= e($t['descripcion']) ?></td>
          <td><span class="badge text-bg-<?= $estados[$t['estado']] ?>"><?= e($t['estado']) ?></span></td>
          <td><?= e($t['responsable'] ?? '—') ?></td>
          <td class="small"><?= e($t['fecha_creacion']) ?></td>
          <td>
            <form action="actualizar_ticket.php" method="POST" class="d-flex gap-2">
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <?php if ($t['estado'] === 'Recibida'): ?>
                <select name="tecnico_id" class="form-select form-select-sm" required>
                  <option value="">Responsable...</option>
                  <?php foreach ($tecnicos as $te): ?>
                    <option value="<?= $te['id'] ?>"><?= e($te['nombre']) ?></option>
                  <?php endforeach; ?>
                </select>
                <button name="accion" value="asignar" class="btn btn-primary btn-sm">Asignar</button>
              <?php elseif ($t['estado'] === 'Asignada'): ?>
                <button name="accion" value="iniciar" class="btn btn-warning btn-sm">Iniciar atención</button>
              <?php elseif ($t['estado'] === 'En proceso'): ?>
                <button name="accion" value="resolver" class="btn btn-success btn-sm">Marcar resuelta</button>
              <?php else: ?>
             <span class="text-muted small">Cerrada <?= e($t['fecha_cierre']) ?></span>
<?php if ($verArchivados): ?>
  <button name="accion" value="restaurar" class="btn btn-outline-secondary btn-sm ms-auto">Restaurar</button>
<?php else: ?>
  <button name="accion" value="archivar" class="btn btn-outline-secondary btn-sm ms-auto"
          onclick="return confirm('¿Archivar este ticket? Dejará de verse en el panel, pero seguirá contando en los indicadores.');">
    <i class="bi bi-archive"></i> Archivar
  </button>
<?php endif; ?>
              <?php endif; ?>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$tickets): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No hay solicitudes.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
