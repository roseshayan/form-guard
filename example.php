<?php
require_once 'FormValidator.php';

use RoseShayan\FormGuard\FormValidator;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validator = FormValidator::make($_POST, [
        'username' => 'required|min:4|max:20',
        'email'    => 'required|email',
        'mobile'   => 'required|phone',
        'password' => 'required|min:8',
        'confirm'  => 'required|matches:password',
    ]);

    if ($validator->fails()) {
        // Get all errors or the first error
        $errors = $validator->errors();
        echo $validator->firstError();
    } else {
        // Receive secure and validated data
        $safeData = $validator->validated();
        // Continue processing and storing in the database with secure data
    }
}