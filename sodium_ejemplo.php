<?php
// Ejemplo con Libsodium: cifrado autenticado (secretbox) y firma digital (Ed25519)
$mensaje = trim($_POST['mensaje'] ?? '');
$alterar = isset($_POST['alterar']);
$res = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mensaje !== '') {
    // ---------- 1) Cifrado autenticado ----------
    $clave  = sodium_crypto_secretbox_keygen();                    // clave de 32 bytes
    $nonce  = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);    // valor único de 24 bytes
    $cifrado = sodium_crypto_secretbox($mensaje, $nonce, $clave);

    $recibido = $cifrado;
    if ($alterar) {                       // simula que alguien cambió el dato en el camino
        $recibido[0] = $recibido[0] ^ "\x01";
    }
    $descifrado = sodium_crypto_secretbox_open($recibido, $nonce, $clave);

    // ---------- 2) Firma digital ----------
    $par     = sodium_crypto_sign_keypair();
    $privada = sodium_crypto_sign_secretkey($par);
    $publica = sodium_crypto_sign_publickey($par);
    $firma   = sodium_crypto_sign_detached($mensaje, $privada);
    $firmaOk = sodium_crypto_sign_verify_detached($firma, $mensaje, $publica);
    $firmaAlt = sodium_crypto_sign_verify_detached($firma, $mensaje . ' (alterado)', $publica);

    $res = compact('clave','nonce','cifrado','descifrado','publica','firma','firmaOk','firmaAlt');
    sodium_memzero($clave);               // borra la clave de la memoria
}
function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><title>Libsodium</title>
<style>body{font-family:Arial;max-width:760px;margin:30px auto}code{word-break:break-all}
.ok{color:#0a7d2c}.mal{color:#b00020}</style></head><body>
<h2>Libsodium: cifrado autenticado y firma digital</h2>
<form method="post">
  <label>Mensaje:</label><br>
  <input type="text" name="mensaje" size="60" value="<?= h($mensaje) ?>" required><br><br>
  <label><input type="checkbox" name="alterar" <?= $alterar ? 'checked' : '' ?>> Alterar el texto cifrado (simular un ataque)</label><br><br>
  <button type="submit">Ejecutar</button>
</form>
<?php if ($res): ?>
<h3>1) Cifrado autenticado (XSalsa20-Poly1305)</h3>
<p>Nonce: <code><?= bin2hex($res['nonce']) ?></code></p>
<p>Texto cifrado: <code><?= base64_encode($res['cifrado']) ?></code></p>
<?php if ($res['descifrado'] !== false): ?>
<p class="ok">Descifrado correcto: <?= h($res['descifrado']) ?></p>
<?php else: ?>
<p class="mal">No se pudo descifrar: el dato fue alterado o la clave no coincide.</p>
<?php endif; ?>
<h3>2) Firma digital (Ed25519)</h3>
<p>Clave pública (<?= strlen($res['publica']) ?> bytes): <code><?= bin2hex($res['publica']) ?></code></p>
<p>Firma (<?= strlen($res['firma']) ?> bytes): <code><?= bin2hex($res['firma']) ?></code></p>
<p class="<?= $res['firmaOk'] ? 'ok' : 'mal' ?>">Mensaje original: <?= $res['firmaOk'] ? 'firma válida' : 'firma inválida' ?></p>
<p class="<?= $res['firmaAlt'] ? 'ok' : 'mal' ?>">Mensaje alterado: <?= $res['firmaAlt'] ? 'firma válida' : 'firma inválida' ?></p>
<?php endif; ?>
</body></html>
