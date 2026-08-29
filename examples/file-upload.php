<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use RoseShayan\FormGuard\Validator;

$input = array_merge($_POST, [
    'avatar' => $_FILES['avatar'] ?? null,
]);

$validator = Validator::make($input, [
    'avatar' => 'required|file|image|max_file:2048|mimetypes:image/jpeg,image/png|extensions:jpg,jpeg,png',
]);

if ($validator->fails()) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['errors' => $validator->errorBag()->toArray()], JSON_PRETTY_PRINT);
    exit;
}

// Move/store the trusted $_FILES entry using your application's upload policy.
$validated = $validator->validated();
