<?php
declare(strict_types=1);

namespace RoseShayan\FormGuard\Support;

final class Arr
{
    private function __construct()
    {
    }

    /** @param array<array-key, mixed> $data */
    public static function has(array $data, string $path): bool
    {
        if ($path === '') {
            return false;
        }

        $current = $data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }
            $current = $current[$segment];
        }

        return true;
    }

    /** @param array<array-key, mixed> $data */
    public static function get(array $data, string $path, mixed $default = null): mixed
    {
        if ($path === '') {
            return $default;
        }

        $current = $data;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /** @param array<array-key, mixed> $data */
    public static function set(array &$data, string $path, mixed $value): void
    {
        $segments = explode('.', $path);
        $current = &$data;

        foreach ($segments as $index => $segment) {
            if ($index === array_key_last($segments)) {
                $current[$segment] = $value;
                return;
            }

            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }
    }

    /**
     * Expand a wildcard path such as users.*.email to concrete paths that exist
     * at the wildcard level. Missing leaf keys are intentionally retained so
     * rules such as required still work for every existing array item.
     *
     * @param array<array-key, mixed> $data
     * @return list<string>
     */
    public static function expandWildcardPaths(array $data, string $pattern): array
    {
        if (!str_contains($pattern, '*')) {
            return [$pattern];
        }

        return self::walkWildcard($data, explode('.', $pattern), '');
    }

    /**
     * @param list<string> $segments
     * @return list<string>
     */
    private static function walkWildcard(mixed $current, array $segments, string $prefix): array
    {
        if ($segments === []) {
            return $prefix === '' ? [] : [$prefix];
        }

        $segment = array_shift($segments);

        if ($segment === '*') {
            if (!is_array($current)) {
                return [];
            }

            $paths = [];
            foreach ($current as $key => $value) {
                $nextPrefix = $prefix === '' ? (string) $key : $prefix . '.' . $key;
                array_push($paths, ...self::walkWildcard($value, $segments, $nextPrefix));
            }

            return $paths;
        }

        $nextPrefix = $prefix === '' ? $segment : $prefix . '.' . $segment;
        $nextValue = is_array($current) && array_key_exists($segment, $current)
            ? $current[$segment]
            : null;

        return self::walkWildcard($nextValue, $segments, $nextPrefix);
    }
}
