<?php
// index.php — formulario público para registrar un ticket
require __DIR__ . '/config/db.php';
requiereLogin();
$titulo = 'Registrar solicitud de soporte';
$categorias = $pdo->query("SELECT id, nombre, area FROM categorias ORDER BY area, nombre")->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card shadow-sm">
      <div class="card-header text-white" style="background:#255a91">
        <h5 class="mb-0">Registrar solicitud de soporte</h5>
      </div>
      <div class="card-body">
        <form action="guardar_ticket.php" method="POST">
          <div class="mb-3">
            <label class="form-label">Nombre completo</label>
            <input type="text" name="solicitante" class="form-control" maxlength="100" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Correo institucional</label>
            <input type="email" name="correo" class="form-control" maxlength="120" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Categoría</label>
            <select name="categoria_id" class="form-select" required>
              <option value="">Seleccione...</option>
              <?php foreach ($categorias as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['area']) ?> — <?= e($c['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Descripción del problema o solicitud, número del aula y computadora</label>
            <textarea name="descripcion" class="form-control" rows="4" required></textarea>
          </div>
          <button class="btn btn-primary w-100">Enviar solicitud</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
