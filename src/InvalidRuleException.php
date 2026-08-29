<?php
declare(strict_types=1);

namespace RoseShayan\FormGuard;

use InvalidArgumentException;

final class InvalidRuleException extends InvalidArgumentException
{
    public static function unknown(string $rule): self
    {
        return new self(sprintf('Unknown validation rule "%s".', $rule));
    }

    public static function malformed(string $rule, string $reason): self
    {
        return new self(sprintf('Malformed validation rule "%s": %s', $rule, $reason));
    }
}
