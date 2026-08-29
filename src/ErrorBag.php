<?php
declare(strict_types=1);

namespace RoseShayan\FormGuard;

final class ErrorBag
{
    /** @var array<string, list<string>> */
    private array $messages = [];

    public function add(string $field, string $message): void
    {
        $this->messages[$field] ??= [];
        $this->messages[$field][] = $message;
    }

    public function has(string $field): bool
    {
        return isset($this->messages[$field]) && $this->messages[$field] !== [];
    }

    public function isEmpty(): bool
    {
        return $this->messages === [];
    }

    public function first(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->messages[$field][0] ?? null;
        }

        foreach ($this->messages as $messages) {
            if ($messages !== []) {
                return $messages[0];
            }
        }

        return null;
    }

    /** @return list<string> */
    public function get(string $field): array
    {
        return $this->messages[$field] ?? [];
    }

    /** @return list<string> */
    public function all(): array
    {
        $all = [];
        foreach ($this->messages as $messages) {
            array_push($all, ...$messages);
        }

        return $all;
    }

    /** @return array<string, list<string>> */
    public function toArray(): array
    {
        return $this->messages;
    }

    /** @return array<string, string> */
    public function firstOfEach(): array
    {
        $result = [];
        foreach ($this->messages as $field => $messages) {
            if ($messages !== []) {
                $result[$field] = $messages[0];
            }
        }

        return $result;
    }
}
