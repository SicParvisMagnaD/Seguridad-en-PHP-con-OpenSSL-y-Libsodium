<?php
/**
 * Encriptacion.php - Cifrado simétrico con openssl_encrypt() / openssl_decrypt().
 * Versión con formulario: el usuario escribe el texto, elige el algoritmo y (opcional) la clave.
 */
require __DIR__ . '/config_openssl.php';

// Algoritmo => longitud de clave en bytes
$algoritmos = [
    'AES-128-CBC' => 16,  // el del script original
    'AES-256-CBC' => 32,
    'AES-256-GCM' => 32,  // cifrado autenticado (confidencialidad + integridad)
];

$texto = $_POST['texto'] ?? 'Mensaje secreto';
$alg   = $_POST['algoritmo'] ?? 'AES-128-CBC';
$clave = $_POST['clave'] ?? '';
$r = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($algoritmos[$alg])) {
            throw new RuntimeException('Algoritmo no permitido.');
        }
        if ($texto === '') {
            throw new RuntimeException('Escribe el texto a cifrar.');
        }
        $largo = $algoritmos[$alg];

        // Clave: si el usuario no escribe una, se genera una aleatoria segura.
        $claveGenerada = false;
        if ($clave === '') {
            $clave = random_bytes($largo);
            $claveGenerada = true;
        } elseif (strlen($clave) !== $largo) {
            throw new RuntimeException("Para $alg la clave debe tener exactamente $largo caracteres (tiene " . strlen($clave) . ').');
        }

        // IV aleatorio, de la longitud que exige el algoritmo
        $iv  = random_bytes(openssl_cipher_iv_length($alg));
        $tag = '';

        // Cifrar (flags = 0: el resultado sale en Base64)
        if ($alg === 'AES-256-GCM') {
            $cifrado = openssl_encrypt($texto, $alg, $clave, 0, $iv, $tag);
        } else {
            $cifrado = openssl_encrypt($texto, $alg, $clave, 0, $iv);
        }
        if ($cifrado === false) {
            throw new RuntimeException(errorOpenssl('Falló el cifrado.'));
        }

        // Descifrar para comprobar
        if ($alg === 'AES-256-GCM') {
            $descifrado = openssl_decrypt($cifrado, $alg, $clave, 0, $iv, $tag);
        } else {
            $descifrado = openssl_decrypt($cifrado, $alg, $clave, 0, $iv);
        }

        $r = [
            'alg'     => $alg,
            'clave'   => $claveGenerada ? base64_encode($clave) . ' (generada al azar, en Base64)' : $clave,
            'iv'      => base64_encode($iv),
            'tag'     => $tag !== '' ? base64_encode($tag) : null,
            'cifrado' => $cifrado,
            'plano'   => $descifrado,
        ];
    } catch (RuntimeException $ex) {
        $error = $ex->getMessage();
    }
}

paginaInicio('Encriptación simple con OpenSSL (AES)');
?>
<form method="post">
    <label for="t">Texto a cifrar</label>
    <textarea id="t" name="texto" rows="3" required><?= e($texto) ?></textarea>

    <label for="a">Algoritmo</label>
    <select id="a" name="algoritmo">
        <?php foreach ($algoritmos as $nombre => $bytes): ?>
            <option value="<?= e($nombre) ?>" <?= $nombre === $alg ? 'selected' : '' ?>>
                <?= e($nombre) ?> (clave de <?= $bytes ?> caracteres)
            </option>
        <?php endforeach; ?>
    </select>

    <label for="c">Clave <small>(opcional: déjala vacía para generar una aleatoria)</small></label>
    <input type="text" id="c" name="clave" autocomplete="off" value="">

    <button type="submit">Cifrar y descifrar</button>
</form>

<?php if ($error): ?>
    <div class="caja"><span class="mal">Error:</span> <?= e($error) ?></div>
<?php endif; ?>

<?php if ($r): ?>
    <div class="caja">
        <p><strong>Algoritmo:</strong> <?= e($r['alg']) ?></p>
        <p><strong>Clave:</strong> <?= e($r['clave']) ?></p>
        <p><strong>IV (vector de inicialización, Base64):</strong> <?= e($r['iv']) ?></p>
        <?php if ($r['tag']): ?>
            <p><strong>Etiqueta de autenticación (tag GCM, Base64):</strong> <?= e($r['tag']) ?></p>
        <?php endif; ?>

        <h2>Resultado del cifrado</h2>
        <pre><?= e($r['cifrado']) ?></pre>

        <h2>Resultado del descifrado</h2>
        <?php if ($r['plano'] === false): ?>
            <p class="mal">✘ No se pudo descifrar (clave, IV o tag incorrectos).</p>
        <?php else: ?>
            <p class="ok"><?= e($r['plano']) ?></p>
        <?php endif; ?>
    </div>
<?php endif;
paginaFin();
