<?php
/**
 * firma7.php - Genera un par de llaves RSA, firma un texto y verifica la firma.
 * Versión con formulario: el texto a firmar lo escribe el usuario.
 */
require __DIR__ . '/config_openssl.php';

$dirKeys = asegurarCarpeta(__DIR__ . '/keys');
$datos           = $_POST['datos'] ?? 'Este texto será firmado. Thanks for your attention :)';
$datosVerificar  = $_POST['datos_verificar'] ?? '';
$r = null;      // resultados
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (trim($datos) === '') {
            throw new RuntimeException('Escribe el texto que se quiere firmar.');
        }
        $configArgs = opensslConfigArgs();

        // 1. Crear el par de llaves (pública y privada)
        $resourceNewKeyPair = openssl_pkey_new($configArgs);
        if (!$resourceNewKeyPair) {
            throw new RuntimeException(errorOpenssl('No se pudo crear el par de llaves. Revisa la ruta del openssl.cnf.'));
        }

        // 2. Obtener la clave pública como string PEM
        $details      = openssl_pkey_get_details($resourceNewKeyPair);
        $publicKeyPem = $details['key'];

        // 3. Exportar la clave privada como string PEM (por referencia)
        if (!openssl_pkey_export($resourceNewKeyPair, $privateKeyPem, null, $configArgs)) {
            throw new RuntimeException(errorOpenssl('No se pudo exportar la clave privada.'));
        }

        // 4. Guardar ambas llaves en disco
        file_put_contents($dirKeys . '/private_key.pem', $privateKeyPem);
        file_put_contents($dirKeys . '/public_key.pem', $publicKeyPem);

        // 5. Releer la privada desde el archivo para comprobar que se guardó bien
        $privateKeyPem   = file_get_contents($dirKeys . '/private_key.pem');
        $resourcePrivate = openssl_get_privatekey($privateKeyPem);
        if (!$resourcePrivate) {
            throw new RuntimeException(errorOpenssl('No se pudo cargar la clave privada guardada.'));
        }

        // 6. Firmar (SHA-256 + RSA). $firma se llena por referencia.
        if (!openssl_sign($datos, $firma, $resourcePrivate, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException(errorOpenssl('No se pudo firmar.'));
        }
        file_put_contents($dirKeys . '/signature.dat', $firma);

        // 7. Verificar con la clave pública. Si el usuario escribió otro texto, se verifica ese.
        $textoAVerificar = $datosVerificar !== '' ? $datosVerificar : $datos;
        $resultado = openssl_verify($textoAVerificar, $firma, $publicKeyPem, OPENSSL_ALGO_SHA256);

        $r = [
            'public'  => $publicKeyPem,
            'private' => $privateKeyPem,
            'firma'   => base64_encode($firma),
            'bytes'   => strlen($firma),
            'verif'   => $resultado,
            'texto'   => $textoAVerificar,
        ];
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
    }
}

paginaInicio('firma7: generar llaves, firmar y verificar');
?>
<form method="post">
    <label for="datos">Texto a firmar</label>
    <textarea id="datos" name="datos" rows="3" required><?= e($datos) ?></textarea>

    <label for="dv">Texto a verificar <small>(opcional: cámbialo para simular que alguien alteró el mensaje)</small></label>
    <textarea id="dv" name="datos_verificar" rows="2"><?= e($datosVerificar) ?></textarea>

    <button type="submit">Generar llaves, firmar y verificar</button>
</form>

<?php if ($error): ?>
    <div class="caja"><span class="mal">Error:</span> <?= e($error) ?></div>
<?php endif; ?>

<?php if ($r): ?>
    <div class="caja">
        <h2>Resultado de la verificación</h2>
        <p>Texto verificado: <em><?= e($r['texto']) ?></em></p>
        <?php if ($r['verif'] === 1): ?>
            <p class="ok">✔ La firma es válida y los datos son confiables.</p>
        <?php elseif ($r['verif'] === 0): ?>
            <p class="mal">✘ La firma es inválida y/o los datos fueron alterados.</p>
        <?php else: ?>
            <p class="mal">⚠ Error al verificar: <?= e(openssl_error_string()) ?></p>
        <?php endif; ?>
        <p><small>Archivos guardados en keys/: private_key.pem, public_key.pem, signature.dat</small></p>
    </div>

    <div class="caja">
        <h2>Clave pública (se puede compartir)</h2>
        <pre><?= e($r['public']) ?></pre>

        <h2>Firma digital (<?= (int) $r['bytes'] ?> bytes, mostrada en Base64)</h2>
        <pre><?= e($r['firma']) ?></pre>

        <h2>Clave privada</h2>
        <div class="aviso">Solo para fines educativos del laboratorio. En un sistema real la clave privada nunca se muestra ni se sube a un repositorio.</div>
        <details><summary>Mostrar clave privada</summary><pre><?= e($r['private']) ?></pre></details>
    </div>
<?php endif;
paginaFin();
