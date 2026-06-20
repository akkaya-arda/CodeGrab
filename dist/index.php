<?php
/**
 * Raven Guard Helper Routing Fallback - Recommended For Shared Hostings
 */

$envPath = __DIR__ . '/.env';

if (!file_exists($envPath)) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'];
    $baseUrl = rtrim($protocol . $domain, '/');

    if (file_exists(__DIR__ . '/.env.example')) {
        $envContent = file_get_contents(__DIR__ . '/.env.example');
        $envContent = preg_replace('/APP_URL=(.*)/', "APP_URL={$baseUrl}", $envContent);
        file_put_contents($envPath, $envContent);
    } else {
        $defaultEnv = "APP_NAME=\"Raven Guard Helper\"\nAPP_ENV=local\nAPP_KEY=\nAPP_DEBUG=true\nAPP_URL={$baseUrl}\nDB_CONNECTION=mysql";
        file_put_contents($envPath, $defaultEnv);
    }
    chmod($envPath, 0644);
}

$envContent = file_get_contents($envPath);
if (preg_match('/APP_KEY=\s*$/m', $envContent) || !str_contains($envContent, 'APP_KEY=')) {
    $randomKey = 'base64:' . base64_encode(random_bytes(32));
    if (str_contains($envContent, 'APP_KEY=')) {
        $envContent = preg_replace('/APP_KEY=.*/', "APP_KEY={$randomKey}", $envContent);
    } else {
        $envContent = "APP_KEY={$randomKey}\n" . $envContent;
    }
    file_put_contents($envPath, $envContent);
}

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($request === '/' && !file_exists(__DIR__ . '/storage/installed')) {
    header("Location: /install");
    exit;
}

if (str_starts_with($request, '/install') || str_starts_with($request, '/api')) {
    require __DIR__ . '/public/index.php';
    exit;
}

if (str_starts_with($request, '/uploads/')) {
    $uploadFile = __DIR__ . '/public' . $request;
    if (file_exists($uploadFile) && !is_dir($uploadFile)) {
        serveStaticFile($uploadFile);
    }
    header("HTTP/1.1 404 Not Found");
    exit;
}

$uiFile = __DIR__ . '/public/ui/browser' . $request;

if ($request !== '/' && file_exists($uiFile) && !is_dir($uiFile)) {
    serveStaticFile($uiFile);
}

require __DIR__ . '/public/ui/browser/index.html';
exit;

function serveStaticFile(string $filePath): void
{
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
    ];

    $contentType = $mimeTypes[$extension] ?? 'application/octet-stream';
    header("Content-Type: " . $contentType);
    header("Content-Length: " . filesize($filePath));
    header("Cache-Control: public, max-age=2592000");
    readfile($filePath);
    exit;
}