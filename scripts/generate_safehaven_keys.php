<?php
// Generates an RSA keypair (3072 bits) and prints:
// - PEMs to stdout
// - A suggested .env SAFEHAVEN_PRIVATE_KEY=... line with \n escaped
// It does NOT modify your files.

$bits = 3072;
$config = [
    'private_key_bits' => $bits,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];

$key = openssl_pkey_new($config);
if (!$key) {
    fwrite(STDERR, "Failed to generate key (check OpenSSL availability).\n");
    exit(1);
}

$privatePem = '';
openssl_pkey_export($key, $privatePem);
$details = openssl_pkey_get_details($key);
$publicPem = $details['key'] ?? '';

function esc_env($pem) {
    // Convert to an .env-safe one-liner with \n escapes
    $pem = str_replace(["\r\n", "\r"], "\n", $pem);
    $pem = trim($pem) . "\n"; // ensure trailing newline
    $escaped = str_replace("\n", "\\n", $pem);
    return $escaped;
}

$inlineEnv = 'SAFEHAVEN_PRIVATE_KEY="' . esc_env($privatePem) . '"';

// Print outputs
$out = [
    'bits' => $bits,
    'private_pem' => $privatePem,
    'public_pem' => $publicPem,
    'env_line' => $inlineEnv,
];

// Pretty print without escaping slashes
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
