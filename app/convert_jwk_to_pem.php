<?php

/**
 * Convert base64url to base64
 */
function base64url_decode($data)
{
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $data .= str_repeat('=', $padlen);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Convert modulus (n) and exponent (e) to PEM format
 */
function createPemFromModulusAndExponent($n, $e)
{
    $modulus = base64url_decode($n);
    $exponent = base64url_decode($e);

    $components = [
        'modulus' => $modulus,
        'publicExponent' => $exponent
    ];

    $sequence = encodeSequence([
        encodeInteger($components['modulus']),
        encodeInteger($components['publicExponent']),
    ]);

    $bitString = chr(0x00) . $sequence;
    $encoded = encodeSequence([
        encodeSequence([
            encodeObjectIdentifier('1.2.840.113549.1.1.1'),
            encodeNull()
        ]),
        encodeBitString($bitString)
    ]);

    $pem = "-----BEGIN PUBLIC KEY-----\n";
    $pem .= chunk_split(base64_encode($encoded), 64, "\n");
    $pem .= "-----END PUBLIC KEY-----\n";

    return $pem;
}

function encodeLength($length)
{
    if ($length <= 0x7F) return chr($length);

    $temp = ltrim(pack('N', $length), chr(0));
    return chr(0x80 | strlen($temp)) . $temp;
}

function encodeInteger($value)
{
    if (ord($value[0]) > 0x7F) $value = chr(0) . $value;
    return chr(0x02) . encodeLength(strlen($value)) . $value;
}

function encodeBitString($value)
{
    return chr(0x03) . encodeLength(strlen($value)) . $value;
}

function encodeNull()
{
    return chr(0x05) . chr(0x00);
}

function encodeObjectIdentifier($oid)
{
    $parts = explode('.', $oid);
    $first = 40 * (int)$parts[0] + (int)$parts[1];
    $other = array_slice($parts, 2);

    $encoded = chr($first);
    foreach ($other as $part) {
        $encoded .= encodeOIDPart($part);
    }

    return chr(0x06) . encodeLength(strlen($encoded)) . $encoded;
}

function encodeOIDPart($part)
{
    $part = (int)$part;
    $result = '';
    do {
        $byte = $part & 0x7F;
        $part >>= 7;
        if ($result !== '') {
            $byte |= 0x80;
        }
        $result = chr($byte) . $result;
    } while ($part);

    return $result;
}

function encodeSequence($encodedElements)
{
    $data = implode('', $encodedElements);
    return chr(0x30) . encodeLength(strlen($data)) . $data;
}

// Fetch the JWKs from Keycloak
$jwkUrl = 'http://10.10.1.164:30976/realms/sara-realm/protocol/openid-connect/certs';
$jwkData = file_get_contents($jwkUrl);
if ($jwkData === false) {
    die("Failed to fetch JWK from Keycloak.\n");
}

$jwk = json_decode($jwkData, true);
if (!isset($jwk['keys'][0]['n']) || !isset($jwk['keys'][0]['e'])) {
    die("Invalid JWK structure.\n");
}

$n = $jwk['keys'][0]['n'];
$e = $jwk['keys'][0]['e'];

$pem = createPemFromModulusAndExponent($n, $e);

echo "✅ Public Key (PEM format):\n\n";
echo $pem;
