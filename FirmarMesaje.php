<?php
/**
 * FirmarMesaje.php - Firma un mensaje con la llave privada (keysCert/privkey.pem)
 * y verifica la firma con el certificado (keysCert/certout.csr).
 * Requisito: haber ejecutado antes firmaOtra.php.
 */
require __DIR__ . '/config_openssl.php';

$rutaPriv = __DIR__ . '/keysCert/privkey.pem';
$rutaCert = __DIR__ . '/keysCert/certout.csr';

$mensaje   = $_POST['mensaje'] ?? 'Este es un mensaje confidencial';
$recibido  = $_POST['recibido'] ?? '';
$r = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!is_file($rutaPriv) || !is_file($rutaCert)) {
            throw new RuntimeException('No existen privkey.pem / certout.csr. Ejecuta primero firmaOtra.php.');
        }
        if (trim($mensaje) === '') {
            throw new RuntimeException('Escribe el mensaje a firmar.');
        }

        // PASO 1: firmar con la llave privada
        $privKeyResource = openssl_pkey_get_private(file_get_contents($rutaPriv));
        if (!$privKeyResource) {
            throw new RuntimeException(errorOpenssl('No se pudo cargar la llave privada.'));
        }
        if (!openssl_sign($mensaje, $firmaBinaria, $privKeyResource, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException(errorOpenssl('No se pudo firmar el mensaje.'));
        }
        $firmaBase64 = base64_encode($firmaBinaria); // Base64 para guardarla o enviarla como texto

        // PASO 2: verificar con la clave pública contenida en el certificado
        $pubKeyResource = openssl_pkey_get_public(file_get_contents($rutaCert));
        if (!$pubKeyResource) {
            throw new RuntimeException(errorOpenssl('No se pudo leer la clave pública del certificado.'));
        }
        $firmaRecibida   = base64_decode($firmaBase64, true);
        $mensajeRecibido = $recibido !== '' ? $recibido : $mensaje;
        $ok = openssl_verify($mensajeRecibido, $firmaRecibida, $pubKeyResource, OPENSSL_ALGO_SHA256);

        $r = ['firma' => $firmaBase64, 'ok' => $ok, 'recibido' => $mensajeRecibido];
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
    }
}

paginaInicio('FirmarMesaje: firmar y verificar con certificado');
?>
<form method="post">
    <label for="m">Mensaje a firmar (lado del emisor)</label>
    <textarea id="m" name="mensaje" rows="3" required><?= e($mensaje) ?></textarea>

    <label for="rc">Mensaje recibido <small>(lado del receptor; opcional: modifícalo para ver que la firma deja de ser válida)</small></label>
    <textarea id="rc" name="recibido" rows="2"><?= e($recibido) ?></textarea>

    <button type="submit">Firmar y verificar</button>
</form>

<?php if ($error): ?>
    <div class="caja"><span class="mal">Error:</span> <?= e($error) ?></div>
<?php endif; ?>

<?php if ($r): ?>
    <div class="caja">
        <h2>Paso 1. Firma en Base64</h2>
        <pre><?= e($r['firma']) ?></pre>

        <h2>Paso 2. Verificación con el certificado</h2>
        <p>Mensaje verificado: <em><?= e($r['recibido']) ?></em></p>
        <?php if ($r['ok'] === 1): ?>
            <p class="ok">✔ Firma válida. El mensaje no fue alterado.</p>
        <?php elseif ($r['ok'] === 0): ?>
            <p class="mal">✘ Firma inválida. El mensaje pudo haber sido modificado.</p>
        <?php else: ?>
            <p class="mal">⚠ Error al verificar la firma: <?= e(openssl_error_string()) ?></p>
        <?php endif; ?>
    </div>
<?php endif;
paginaFin();
