<?php
// registro.php — crea cuentas nuevas. SIEMPRE con rol "solicitante".
// Los técnicos se habilitan manualmente en la base de datos (ver guía).
require __DIR__ . '/config/db.php';
$titulo = 'Crear cuenta';

if (estaLogueado()) {
    header('Location: index.php');
    exit;
}

// Opcional: limitar el registro a correos institucionales, ej. '@uniminuto.edu.co'
// Déjalo vacío ('') para permitir cualquier correo.
$dominioPermitido = '@uniminuto.edu.co';

$error  = '';
$nombre = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $clave  = $_POST['clave'] ?? '';
    $clave2 = $_POST['clave2'] ?? '';

    if ($nombre === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Escribe tu nombre y un correo válido.';
    } elseif ($dominioPermitido !== '' && !str_ends_with(strtolower($correo), $dominioPermitido)) {
        $error = "Solo se permiten correos $dominioPermitido";
    } elseif (strlen($clave) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($clave !== $clave2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $existe = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
        $existe->execute([$correo]);
        if ($existe->fetch()) {
            $error = 'Ya existe una cuenta con ese correo.';
        } else {
            // El rol NO viene del formulario: siempre queda como solicitante
            $stmt = $pdo->prepare(
                "INSERT INTO usuarios (nombre, correo, clave_hash, rol) VALUES (?, ?, ?, 'solicitante')"
            );
            $stmt->execute([$nombre, $correo, password_hash($clave, PASSWORD_DEFAULT)]);

            session_regenerate_id(true);
            $_SESSION['usuario'] = [
                'id'     => (int)$pdo->lastInsertId(),
                'nombre' => $nombre,
                'correo' => $correo,
                'rol'    => 'solicitante',
            ];
            $_SESSION['flash'] = ['tipo' => 'success', 'msg' => '¡Cuenta creada! Ya puedes registrar tu solicitud.'];
            header('Location: index.php');
            exit;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-7 col-lg-5">
    <div class="card card-form">
      <div class="card-body p-4">
        <h4 class="fw-bold mb-4 text-center"><i class="bi bi-person-plus-fill text-primary"></i> Crear cuenta</h4>

        <?php if ($error): ?>
          <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
          <label class="form-label fw-semibold">Nombre completo</label>
          <div class="input-group mb-3">
            <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
            <input type="text" name="nombre" class="form-control" maxlength="100" value="<?= e($nombre) ?>" required>
          </div>

          <label class="form-label fw-semibold">Correo</label>
          <div class="input-group mb-3">
            <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
            <input type="email" name="correo" class="form-control" maxlength="120" value="<?= e($correo) ?>" required>
          </div>

          <label class="form-label fw-semibold">Contraseña <small class="text-muted">(mínimo 8 caracteres)</small></label>
          <div class="input-group mb-3">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input type="password" name="clave" class="form-control" minlength="8" required>
          </div>

          <label class="form-label fw-semibold">Repite la contraseña</label>
          <div class="input-group mb-4">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input type="password" name="clave2" class="form-control" minlength="8" required>
          </div>

          <button class="btn btn-uni w-100">Crear cuenta</button>
        </form>

        <p class="text-center mt-3 mb-0 small">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
