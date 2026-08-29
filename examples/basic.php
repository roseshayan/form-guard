<?php
declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use RoseShayan\FormGuard\Validator;

$input = [
    'name' => 'Shayan',
    'email' => 'shayan@example.com',
    'age' => '25',
    'password' => 'secret123',
    'password_confirmation' => 'secret123',
];

$validator = Validator::make($input, [
    'name' => 'bail|required|string|min:2|max:100',
    'email' => 'required|email',
    'age' => 'nullable|integer|min:18|max:120',
    'password' => 'required|string|min_length:8|confirmed',
]);

if ($validator->fails()) {
    print_r($validator->errors());
    exit(1);
}

print_r($validator->validated());
