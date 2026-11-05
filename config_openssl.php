<?php
/**
 * config_openssl.php
 * Funciones compartidas por los 4 scripts del laboratorio.
 * - opensslConfigArgs(): arma el arreglo de configuración y busca el openssl.cnf
 *   automáticamente (Solución 2 de la guía, adaptada a WampServer).
 * - e(): escapa texto para mostrarlo en HTML (previene XSS, OWASP).
 * - paginaInicio()/paginaFin(): encabezado y pie comunes con menú.
 */

function opensslConfigArgs(): array
{
    $args = [
        'digest_alg'       => 'sha256',
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];

    // Rutas candidatas, en orden de prioridad. Se usa la primera que exista.
    $candidatas = [
        getenv('OPENSSL_CONF') ?: '',
        'C:/wamp64/bin/apache/apache2.4.65/conf/openssl.cnf', // WampServer (Apache)
        dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf',      // PHP de WAMP/XAMPP en CLI
        'C:/xampp/php/extras/ssl/openssl.cnf',                // XAMPP
        'C:/OpenSSL-Win64/bin/cnf/openssl.cnf',               // OpenSSL instalado aparte
    ];
    foreach ($candidatas as $ruta) {
        if ($ruta !== '' && is_file($ruta)) {
            $args['config'] = $ruta;
            break;
        }
    }
    return $args;
}

function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function asegurarCarpeta(string $ruta): string
{
    if (!is_dir($ruta)) {
        mkdir($ruta, 0775, true);
    }
    return $ruta;
}

/** Devuelve el último error de OpenSSL (o un texto por defecto). */
function errorOpenssl(string $porDefecto): string
{
    $msgs = [];
    while (($m = openssl_error_string()) !== false) {
        $msgs[] = $m;
    }
    return $porDefecto . ($msgs ? ' | ' . implode(' | ', $msgs) : '');
}

function paginaInicio(string $titulo): void
{
    $menu = [
        'firma7.php'         => 'firma7',
        'firmaOtra.php'      => 'firmaOtra',
        'FirmarMesaje.php'   => 'FirmarMesaje',
        'Encriptacion.php'   => 'Encriptación',
    ];
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($titulo) . '</title><style>
        body{font-family:Segoe UI,Arial,sans-serif;max-width:900px;margin:24px auto;padding:0 16px;color:#222;background:#f6f7f9}
        nav a{display:inline-block;margin:0 6px 8px 0;padding:6px 12px;background:#4b2a6b;color:#fff;border-radius:6px;text-decoration:none;font-size:14px}
        h1{font-size:22px;color:#4b2a6b} h2{font-size:17px;margin-top:22px}
        form,.caja{background:#fff;border:1px solid #d9dce1;border-radius:8px;padding:16px;margin:12px 0}
        label{display:block;font-weight:600;margin:10px 0 4px}
        input[type=text],input[type=email],input[type=number],textarea,select{width:100%;padding:8px;border:1px solid #bbb;border-radius:6px;box-sizing:border-box;font-size:14px}
        button{margin-top:14px;padding:9px 18px;background:#1a7f37;color:#fff;border:0;border-radius:6px;font-size:15px;cursor:pointer}
        pre{background:#1e1e1e;color:#d4d4d4;padding:12px;border-radius:6px;overflow-x:auto;white-space:pre-wrap;word-break:break-all;font-size:13px}
        .ok{color:#1a7f37;font-weight:700} .mal{color:#c62828;font-weight:700}
        .aviso{background:#fff8e1;border-left:4px solid #f9a825;padding:8px 12px;font-size:13px;margin:8px 0}
        small{color:#666}
    </style></head><body><nav>';
    foreach ($menu as $archivo => $nombre) {
        echo '<a href="' . e($archivo) . '">' . e($nombre) . '</a>';
    }
    echo '</nav><h1>' . e($titulo) . '</h1>';
}

function paginaFin(): void
{
    echo '</body></html>';
}
