<?php
declare(strict_types=1);

namespace RoseShayan\FormGuard;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    public function __construct(
        private readonly ErrorBag $errors,
        string $message = 'The given data failed validation.'
    ) {
        parent::__construct($message);
    }

    public function errors(): ErrorBag
    {
        return $this->errors;
    }
}
