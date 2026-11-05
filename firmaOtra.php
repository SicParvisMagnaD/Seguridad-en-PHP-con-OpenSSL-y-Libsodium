<?php
/**
 * firmaOtra.php - Genera llave privada, CSR y un certificado X.509 autofirmado.
 * Versión con formulario: los datos del certificado (DN) los escribe el usuario.
 * Guarda: keysCert/certout.csr (certificado) y keysCert/privkey.pem (llave privada).
 * Estos archivos los usa después FirmarMesaje.php.
 */
require __DIR__ . '/config_openssl.php';

$dirCert = asegurarCarpeta(__DIR__ . '/keysCert');
$f = [
    'pais'         => $_POST['pais'] ?? 'PA',
    'localidad'    => $_POST['localidad'] ?? 'Panamá',
    'organizacion' => $_POST['organizacion'] ?? 'UTP',
    'nombre'       => $_POST['nombre'] ?? '',
    'email'        => $_POST['email'] ?? '',
    'dias'         => $_POST['dias'] ?? '365',
];
$r = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validación de entradas (nunca confiar en lo que llega del formulario)
        if (!preg_match('/^[A-Za-z]{2}$/', $f['pais'])) {
            throw new RuntimeException('El país debe ser un código de 2 letras (ej. PA).');
        }
        if (trim($f['nombre']) === '' || trim($f['organizacion']) === '' || trim($f['localidad']) === '') {
            throw new RuntimeException('Completa localidad, organización y nombre.');
        }
        if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El correo electrónico no es válido.');
        }
        $dias = filter_var($f['dias'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 3650]]);
        if ($dias === false) {
            throw new RuntimeException('Los días de validez deben estar entre 1 y 3650.');
        }

        $configArgs = opensslConfigArgs();
        $dn = [
            'countryName'      => strtoupper($f['pais']),
            'localityName'     => $f['localidad'],
            'organizationName' => $f['organizacion'],
            'commonName'       => $f['nombre'],
            'emailAddress'     => $f['email'],
        ];

        // 1. Par de llaves
        $resourceNewKeyPair = openssl_pkey_new($configArgs);
        if (!$resourceNewKeyPair) {
            throw new RuntimeException(errorOpenssl('Error al generar la clave. Revisa la ruta del openssl.cnf.'));
        }
        // 2. Exportar la privada como PEM
        if (!openssl_pkey_export($resourceNewKeyPair, $privkey, null, $configArgs)) {
            throw new RuntimeException(errorOpenssl('Error al exportar la clave privada.'));
        }
        // 3. CSR (solicitud de certificado). Se pasa el RECURSO, no el string.
        $csr = openssl_csr_new($dn, $resourceNewKeyPair, $configArgs);
        if (!$csr) {
            throw new RuntimeException(errorOpenssl('Error al crear la CSR.'));
        }
        // 4. Autofirmar el CSR con la misma llave (CA = null)
        $sscert = openssl_csr_sign($csr, null, $resourceNewKeyPair, $dias, $configArgs);
        if (!$sscert) {
            throw new RuntimeException(errorOpenssl('Error al firmar la CSR.'));
        }
        // 5. Exportar el certificado a PEM
        openssl_x509_export($sscert, $certout);

        file_put_contents($dirCert . '/certout.csr', $certout);
        file_put_contents($dirCert . '/privkey.pem', $privkey);

        $info = openssl_x509_parse($sscert);
        $r = [
            'cert'    => $certout,
            'private' => $privkey,
            'sujeto'  => $info['name'] ?? '',
            'desde'   => date('Y-m-d H:i', $info['validFrom_time_t']),
            'hasta'   => date('Y-m-d H:i', $info['validTo_time_t']),
            'serie'   => $info['serialNumberHex'] ?? '',
        ];
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
    }
}

paginaInicio('firmaOtra: certificado X.509 autofirmado');
?>
<form method="post">
    <label>País (2 letras)</label>
    <input type="text" name="pais" maxlength="2" value="<?= e($f['pais']) ?>" required>
    <label>Localidad</label>
    <input type="text" name="localidad" value="<?= e($f['localidad']) ?>" required>
    <label>Organización</label>
    <input type="text" name="organizacion" value="<?= e($f['organizacion']) ?>" required>
    <label>Nombre común (CN)</label>
    <input type="text" name="nombre" value="<?= e($f['nombre']) ?>" placeholder="Nombre(s) de los estudiantes" required>
    <label>Correo electrónico</label>
    <input type="email" name="email" value="<?= e($f['email']) ?>" required>
    <label>Días de validez</label>
    <input type="number" name="dias" min="1" max="3650" value="<?= e($f['dias']) ?>" required>
    <button type="submit">Generar certificado</button>
</form>

<?php if ($error): ?>
    <div class="caja"><span class="mal">Error:</span> <?= e($error) ?></div>
<?php endif; ?>

<?php if ($r): ?>
    <div class="caja">
        <p class="ok">✔ Certificado creado y firmado correctamente.</p>
        <p><strong>Sujeto:</strong> <?= e($r['sujeto']) ?><br>
           <strong>Válido desde:</strong> <?= e($r['desde']) ?><br>
           <strong>Válido hasta:</strong> <?= e($r['hasta']) ?><br>
           <strong>N.º de serie:</strong> <?= e($r['serie']) ?></p>
        <p><small>Guardado en keysCert/: certout.csr (certificado) y privkey.pem (llave privada)</small></p>

        <h2>Certificado X.509 (PEM)</h2>
        <pre><?= e($r['cert']) ?></pre>

        <h2>Llave privada</h2>
        <div class="aviso">Solo para fines educativos. Nunca publiques una llave privada real.</div>
        <details><summary>Mostrar llave privada</summary><pre><?= e($r['private']) ?></pre></details>
    </div>
<?php endif;
paginaFin();
