<?php
function pdo_connect_mysql()
{
    $DATABASE_HOST = 'localhost';
    $DATABASE_USER = 'root';
    $DATABASE_PASS = '';
    $DATABASE_NAME = 'guzel_ayakkabi1';

    try {
        return new PDO(
            'mysql:host=' . $DATABASE_HOST . ';dbname=' . $DATABASE_NAME . ';charset=utf8mb4',
            $DATABASE_USER,
            $DATABASE_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        exit('Failed to connect to database!');
    }
}


function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function csrf_token()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    echo '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf_token()
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function upload_product_image($file, $directory)
{
    if (!isset($file) || !is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new RuntimeException('The image could not be uploaded. Please try again.');
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('The image must be smaller than 5 MB.');
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new RuntimeException('Please select a valid image file.');
    }

    $mimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];
    $mimeType = $imageInfo['mime'] ?? '';

    if (!isset($mimeTypes[$mimeType])) {
        throw new RuntimeException('Only JPG, PNG, WEBP and GIF images are allowed.');
    }

    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        throw new RuntimeException('The image folder is not available.');
    }

    $fileName = 'p_' . bin2hex(random_bytes(12)) . '.' . $mimeTypes[$mimeType];
    $destination = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('The image could not be saved.');
    }

    return $fileName;
}
