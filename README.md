# 🔐 Seguridad en PHP con OpenSSL y Libsodium

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Apache](https://img.shields.io/badge/Apache-D22128?style=for-the-badge&logo=apache&logoColor=white)
![WampServer](https://img.shields.io/badge/WampServer-FF0040?style=for-the-badge&logo=windows&logoColor=white)
![OpenSSL](https://img.shields.io/badge/OpenSSL-771C2E?style=for-the-badge&logo=letsencrypt&logoColor=white)
![Libsodium](https://img.shields.io/badge/Libsodium-1EA362?style=for-the-badge&logo=letsencrypt&logoColor=white)
![OWASP](https://img.shields.io/badge/OWASP-Cryptographic%20Failures-1EA362?style=for-the-badge)

Laboratorio de **Programación Segura según OWASP: Protege los Datos**, de la materia *Desarrollo Web VII: PHP y MySQL* (Licenciatura en Ciberseguridad, Universidad Tecnológica de Panamá).

El repositorio parte del material de la profesora Irina Fong ([Salomon2514/SeguridadOpenSSL](https://github.com/Salomon2514/SeguridadOpenSSL)). Sobre esa base agregué formularios para recibir los datos, corregí varios problemas de seguridad y sumé un ejemplo con Libsodium.

## 📌 ¿De qué trata?

Los datos sensibles (contraseñas, tarjetas, información personal) nunca deben guardarse ni enviarse a la vista de cualquiera. Cuando eso ocurre, o cuando se usan métodos de protección débiles, OWASP lo llama *Cryptographic Failures*. Cada técnica de este laboratorio protege algo distinto:

| Qué se protege | Técnica | Script |
|---|---|---|
| **Confidencialidad**: que nadie lea el dato | Cifrado simétrico AES | `Encriptacion.php` |
| **Integridad**: que nadie lo cambie | Firma digital RSA con SHA-256 | `firma7.php`, `FirmarMesaje.php` |
| **Autenticidad**: saber quién lo envió | Certificado X.509 autofirmado | `firmaOtra.php` |
| **Las tres, con una biblioteca moderna** | Libsodium (secretbox y Ed25519) | `sodium_ejemplo.php` |

## 📁 Contenido del repositorio

| Archivo | Qué hace |
|---|---|
| `config_openssl.php` | Busca el `openssl.cnf` en varias rutas posibles y usa la primera que exista. Lo usan los demás scripts. |
| `firma7.php` | Genera un par de llaves RSA de 2048 bits, firma un texto con la llave privada y verifica la firma con la pública. |
| `firmaOtra.php` | Crea un certificado X.509 autofirmado a partir de los datos de un formulario (país, localidad, organización, nombre, correo y días de validez). |
| `FirmarMesaje.php` | Firma un mensaje con la llave privada del certificado y lo verifica con la clave pública del certificado. Hay que ejecutar antes `firmaOtra.php`. |
| `Encriptacion.php` | Cifra y descifra un texto con AES-128-CBC, AES-256-CBC o AES-256-GCM. |
| `sodium_ejemplo.php` | Cifrado autenticado (XSalsa20-Poly1305) y firma digital (Ed25519) con Libsodium. |
| `keys/` y `keysCert/` | Carpetas donde los scripts guardan las llaves y certificados que generan. |

## 🛠️ Requisitos

- Windows con **WampServer** (Apache + PHP 8.3) o XAMPP.
- Extensiones `openssl` y `sodium` habilitadas en PHP.
- Un navegador. Opcionalmente, Visual Studio Code y PowerShell.

### Verificar que las extensiones estén activas

Crea un archivo `info.php` con este contenido y ábrelo desde el navegador:

```php
<?php phpinfo(); ?>
```

Busca las secciones **openssl** y **sodium**. Conviene revisarlo desde el navegador y no solo desde la terminal, porque la terminal y Apache pueden usar versiones distintas de PHP, y los scripts corren con la de Apache.

<!-- 📸 CAPTURA 1: phpinfo() en el navegador mostrando la sección openssl (y sodium si la tienes). -->
![phpinfo con OpenSSL activo](capturas/01_phpinfo_openssl.png)

<!-- 📸 CAPTURA 2: WampServer con los servicios activos (ícono verde). -->
![WampServer con los servicios activos](capturas/02_wampserver.png)

Si `openssl` no aparece (sobre todo en XAMPP), abre `php.ini`, busca la línea `;extension=openssl`, quita el punto y coma, guarda y reinicia los servicios:

```ini
extension=openssl
```

## ⚙️ Configuración del `openssl.cnf`

Al generar llaves, PHP necesita encontrar el archivo `openssl.cnf`. La ruta original del laboratorio (`C:\OpenSSL-Win64\bin\cnf\openssl.cnf`) solo existe si OpenSSL se instaló por separado, y en WampServer el archivo está en otro lugar. Por eso aparecía un error al generar las llaves.

La solución que apliqué fue `config_openssl.php`: prueba varias rutas conocidas y usa la primera que exista. Si en tu equipo el archivo está en otra carpeta, agrégala a la lista de ese archivo. Rutas habituales:

```text
C:\wamp64\bin\php\php8.x.x\extras\ssl\openssl.cnf   (WampServer)
C:\xampp\php\extras\ssl\openssl.cnf                 (XAMPP)
C:\OpenSSL-Win64\bin\cnf\openssl.cnf                (OpenSSL instalado aparte)
```

Para encontrar el archivo en tu equipo, desde PowerShell:

```powershell
Get-ChildItem -Path C:\ -Filter openssl.cnf -Recurse -ErrorAction SilentlyContinue | Select-Object FullName
```

<!-- 📸 CAPTURA 3 (opcional): el error original antes de corregir la ruta del openssl.cnf. -->

## 🚀 Cómo usar este repositorio

1. Clona el repositorio dentro de la carpeta `www` (WampServer) o `htdocs` (XAMPP):

   ```bash
   git clone https://github.com/SicParvisMagnaD/SeguridadOpenSSL.git
   ```

2. Inicia Apache desde WampServer o XAMPP.
3. Abre cada script desde el navegador, por ejemplo:

   ```text
   http://localhost/SeguridadOpenSSL/firma7.php
   ```

4. Orden recomendado: `firma7.php`, `firmaOtra.php`, `FirmarMesaje.php`, `Encriptacion.php` y `sodium_ejemplo.php`. `FirmarMesaje.php` necesita el certificado y la llave que crea `firmaOtra.php`, así que ese debe ejecutarse antes.

> **Importante:** las llaves privadas no están en el repositorio a propósito (ver [Notas de seguridad](#-notas-de-seguridad)). Se generan solas la primera vez que ejecutas los scripts.

## 🧪 Resultados por script

### 1. `firma7.php`: llaves RSA, firma digital y verificación

Genera un par de llaves RSA de 2048 bits, firma el texto con la privada y lo verifica con la pública. La firma mide 256 bytes, que es el tamaño que corresponde a RSA de 2048 bits. Al cambiar un solo carácter del texto a verificar, `openssl_verify` devuelve `0` y la firma deja de ser válida: esa es la prueba de integridad.

<!-- 📸 CAPTURA 4: firma7 con una firma válida. -->
![firma7 con una firma válida](capturas/04_firma7_valida.png)

<!-- 📸 CAPTURA 5: firma7 detectando un mensaje alterado. -->
![firma7 detectando un mensaje alterado](capturas/05_firma7_alterado.png)

<!-- 📸 CAPTURA 6: clave pública y firma generadas. -->
![Clave pública y firma generadas](capturas/06_firma7_claves.png)

### 2. `firmaOtra.php`: certificado X.509 autofirmado

El formulario valida que el país tenga dos letras, que el correo sea válido y que los días estén entre 1 y 3650. El certificado queda **autofirmado**: la misma llave lo solicita y lo firma. Sirve para el laboratorio, pero un navegador no lo aceptaría como confiable; en un sistema real lo firmaría una autoridad certificadora (CA).

<!-- 📸 CAPTURA 7: formulario con los datos del certificado. -->
![Formulario del certificado](capturas/07_firmaotra_formulario.png)

<!-- 📸 CAPTURA 8: certificado X.509 generado. -->
![Certificado X.509 autofirmado](capturas/08_firmaotra_certificado.png)

### 3. `FirmarMesaje.php`: firma y verificación con certificado

El emisor firma con `privkey.pem` y el receptor verifica usando solo la clave pública que viene en el certificado. La firma se convierte a Base64 para poder guardarla o enviarla como texto (esto no es cifrado, solo cambia la representación).

<!-- 📸 CAPTURA 9: FirmarMesaje con una firma válida. -->
![FirmarMesaje con firma válida](capturas/09_firmarmesaje_valida.png)

<!-- 📸 CAPTURA 10: FirmarMesaje detectando un mensaje modificado. -->
![FirmarMesaje detectando un mensaje modificado](capturas/10_firmarmesaje_alterado.png)

### 4. `Encriptacion.php`: cifrado simétrico AES

La misma clave cifra y descifra. El IV se genera al azar en cada cifrado, por eso el mismo texto da un resultado distinto cada vez. El formulario comprueba que la clave tenga el largo exacto que pide el algoritmo (16 caracteres para AES-128 y 32 para AES-256) y, si se deja vacía, genera una al azar con `random_bytes()`. **AES-256-GCM** agrega una etiqueta de autenticación: si alguien altera el texto cifrado, el descifrado falla. AES-CBC no tiene esa protección.

<!-- 📸 CAPTURA 11: cifrado y descifrado con AES-128-CBC. -->
![AES-128-CBC](capturas/11_aes128cbc.png)

<!-- 📸 CAPTURA 12: cifrado con AES-256-CBC. -->
![AES-256-CBC](capturas/12_aes256cbc.png)

<!-- 📸 CAPTURA 13: cifrado autenticado con AES-256-GCM. -->
![AES-256-GCM](capturas/13_aes256gcm.png)

<!-- 📸 CAPTURA 14: validación del largo de la clave (mensaje de error). -->
![Validación del largo de la clave](capturas/14_validacion_clave.png)

### 5. `sodium_ejemplo.php`: Libsodium

Hace dos cosas con el mismo mensaje:

1. **Cifrado autenticado** con `sodium_crypto_secretbox()` (XSalsa20-Poly1305). La etiqueta de autenticación viene incluida, así que cualquier cambio en el texto cifrado hace que `sodium_crypto_secretbox_open()` devuelva `false`. La casilla *Alterar el texto cifrado* simula ese ataque.
2. **Firma digital** con Ed25519: la clave pública mide 32 bytes y la firma 64 bytes (frente a los 256 bytes de una firma RSA de 2048 bits). Se verifica el mensaje original (válida) y uno alterado (inválida).

Los datos que se muestran en pantalla pasan por `htmlspecialchars()` para evitar XSS.

<!-- 📸 CAPTURA 15: formulario de Libsodium. -->
![Formulario de Libsodium](capturas/15_sodium_formulario.png)

<!-- 📸 CAPTURA 16: resultado con el mensaje sin alterar. -->
![Libsodium con el mensaje sin alterar](capturas/16_sodium_valido.png)

<!-- 📸 CAPTURA 17: resultado con el texto cifrado alterado. -->
![Libsodium con el texto cifrado alterado](capturas/17_sodium_alterado.png)

## 🛡️ OpenSSL vs. Libsodium

| Característica | OpenSSL | Libsodium |
|---|---|---|
| Propósito principal | Protocolos TLS, certificados X.509 y criptografía general | Criptografía moderna para aplicaciones |
| Complejidad | Alta: hay que elegir algoritmo, modo, IV, relleno y longitudes | Baja: funciones simples con valores seguros por defecto |
| Cifrado autenticado | Solo con modos como GCM | Por defecto |
| Firma digital | RSA (llave y firma grandes) | Ed25519 (llave y firma pequeñas) |
| Contraseñas | No ofrece un hash de contraseñas dedicado | Argon2id |
| Uso recomendado | HTTPS y certificados | Cifrar datos sensibles y guardar contraseñas |

## 🔒 Notas de seguridad

Durante el laboratorio encontré varios detalles que no hacen fallar el programa, pero sí lo debilitan:

- **Llaves privadas publicadas.** El repositorio original traía llaves privadas incluidas. En este repositorio están en el `.gitignore` y no se suben: una llave privada nunca debe publicarse (OWASP, *Cryptographic Failures*). Se generan de nuevo al ejecutar los scripts.
- **SHA-1 por omisión.** `openssl_sign()` usa SHA-1 si no se indica otro algoritmo. Aquí se usa `OPENSSL_ALGO_SHA256` al firmar y al verificar.
- **Clave de 17 caracteres.** El script original usaba una clave de 17 caracteres para AES-128 (que exige 16) y OpenSSL la recortaba sin avisar. Ahora se valida el largo exacto.
- **AES-CBC no detecta alteraciones.** Por eso se agregó AES-256-GCM.
- **Nombre engañoso.** `certout.csr` guarda en realidad el certificado, no una solicitud (CSR).

## 📚 Referencias

- [OWASP Top 10: A02:2021 Cryptographic Failures](https://owasp.org/Top10/A02_2021-Cryptographic_Failures/)
- [OWASP Password Storage Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html)
- [Manual de PHP: OpenSSL](https://www.php.net/manual/es/book.openssl.php)
- [Manual de PHP: Sodium](https://www.php.net/manual/es/book.sodium.php)
- [Documentación de Libsodium](https://doc.libsodium.org)
- [Repositorio base de la profesora](https://github.com/Salomon2514/SeguridadOpenSSL)

## 👨‍💻 Autor

**Javier Vallarino** (8-1040-1488), grupo 1S3122
Licenciatura en Ciberseguridad, Facultad de Ingeniería de Sistemas Computacionales
Universidad Tecnológica de Panamá
📧 javier.vallarino@utp.ac.pa

**Facilitadora:** Ing. Irina Fong
