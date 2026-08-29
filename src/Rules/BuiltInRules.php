<?php
declare(strict_types=1);

namespace RoseShayan\FormGuard\Rules;

use DateTimeImmutable;
use RoseShayan\FormGuard\InvalidRuleException;
use RoseShayan\FormGuard\Support\Arr;
use RoseShayan\FormGuard\UploadedFile;

final class BuiltInRules
{
    /** @var list<string> */
    private const NAMES = [
        'required', 'required_if', 'required_with', 'required_without', 'present', 'filled',
        'string', 'integer', 'numeric', 'boolean', 'array',
        'file', 'image', 'max_file', 'mimetypes', 'extensions',
        'email', 'url', 'uuid', 'ip', 'ipv4', 'ipv6',
        'alpha', 'alpha_num', 'alpha_dash',
        'min', 'max', 'between', 'size',
        'min_length', 'max_length', 'length', 'min_value', 'max_value',
        'in', 'not_in', 'same', 'matches', 'different', 'confirmed',
        'regex', 'date', 'date_format', 'json', 'accepted', 'declined',
        'starts_with', 'ends_with', 'contains', 'ir_mobile',
    ];

    private function __construct()
    {
    }

    public static function exists(string $rule): bool
    {
        return in_array($rule, self::NAMES, true);
    }

    /**
     * @param list<string> $params
     * @param array<string, mixed> $data
     * @param list<string> $fieldRuleNames
     */
    public static function validate(
        string $rule,
        mixed $value,
        array $params,
        string $field,
        array $data,
        array $fieldRuleNames
    ): bool {
        return match ($rule) {
            'required' => Arr::has($data, $field) && !self::isEmpty($value),
            'required_if' => self::requiredIf($value, $params, $field, $data),
            'required_with' => self::requiredWith($value, $params, $field, $data),
            'required_without' => self::requiredWithout($value, $params, $field, $data),
            'present' => Arr::has($data, $field),
            'filled' => !Arr::has($data, $field) || !self::isEmpty($value),
            'string' => is_string($value),
            'integer' => self::isInteger($value),
            'numeric' => is_numeric($value),
            'boolean' => in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true),
            'array' => is_array($value),
            'file' => self::file($value)?->isSuccessful() === true,
            'image' => self::image($value),
            'max_file' => self::maxFile($value, $params),
            'mimetypes' => self::mimeTypes($value, $params),
            'extensions' => self::extensions($value, $params),
            'email' => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false,
            'uuid' => is_string($value)
                && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD', $value) === 1,
            'ip' => is_string($value) && filter_var($value, FILTER_VALIDATE_IP) !== false,
            'ipv4' => is_string($value)
                && filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false,
            'ipv6' => is_string($value)
                && filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false,
            'alpha' => is_string($value) && preg_match('/^\p{L}+$/uD', $value) === 1,
            'alpha_num' => is_string($value) && preg_match('/^[\p{L}\p{N}]+$/uD', $value) === 1,
            'alpha_dash' => is_string($value) && preg_match('/^[\p{L}\p{N}_-]+$/uD', $value) === 1,
            'min' => self::compareMeasure($value, $params, $fieldRuleNames, 'min'),
            'max' => self::compareMeasure($value, $params, $fieldRuleNames, 'max'),
            'between' => self::between($value, $params, $fieldRuleNames),
            'size' => self::size($value, $params, $fieldRuleNames),
            'min_length' => is_string($value)
                && mb_strlen($value, 'UTF-8') >= self::numericParam($params, 'min_length'),
            'max_length' => is_string($value)
                && mb_strlen($value, 'UTF-8') <= self::numericParam($params, 'max_length'),
            'length' => is_string($value)
                && mb_strlen($value, 'UTF-8') === (int) self::numericParam($params, 'length'),
            'min_value' => is_numeric($value)
                && (float) $value >= self::numericParam($params, 'min_value'),
            'max_value' => is_numeric($value)
                && (float) $value <= self::numericParam($params, 'max_value'),
            'in' => self::in($value, $params, false),
            'not_in' => self::in($value, $params, true),
            'same', 'matches' => $value === Arr::get($data, self::fieldParam($params, $rule)),
            'different' => $value !== Arr::get($data, self::fieldParam($params, 'different')),
            'confirmed' => Arr::has($data, $field . '_confirmation')
                && $value === Arr::get($data, $field . '_confirmation'),
            'regex' => self::regex($value, $params),
            'date' => self::date($value),
            'date_format' => self::dateFormat($value, $params),
            'json' => self::json($value),
            'accepted' => in_array($value, ['yes', 'on', '1', 1, true, 'true'], true),
            'declined' => in_array($value, ['no', 'off', '0', 0, false, 'false'], true),
            'starts_with' => self::startsWith($value, $params),
            'ends_with' => self::endsWith($value, $params),
            'contains' => self::contains($value, $params),
            'ir_mobile' => self::iranMobile($value),
            default => throw InvalidRuleException::unknown($rule),
        };
    }

    public static function isBlank(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return self::file($value)?->isMissing() === true;
    }

    private static function isEmpty(mixed $value): bool
    {
        return self::isBlank($value) || $value === [];
    }

    private static function isInteger(mixed $value): bool
    {
        return is_int($value)
            || (is_string($value) && preg_match('/^[+-]?\d+$/D', $value) === 1);
    }

    /**
     * @param list<string> $params
     * @param array<string, mixed> $data
     */
    private static function requiredIf(mixed $value, array $params, string $field, array $data): bool
    {
        if (count($params) < 2) {
            throw InvalidRuleException::malformed(
                'required_if',
                'expected other field and at least one value'
            );
        }

        $other = $params[0];
        $expectedValues = array_slice($params, 1);
        $otherValue = self::toComparableString(Arr::get($data, $other));
        $required = $otherValue !== null && in_array($otherValue, $expectedValues, true);

        return !$required || (Arr::has($data, $field) && !self::isEmpty($value));
    }

    /**
     * @param list<string> $params
     * @param array<string, mixed> $data
     */
    private static function requiredWith(mixed $value, array $params, string $field, array $data): bool
    {
        if ($params === []) {
            throw InvalidRuleException::malformed('required_with', 'expected at least one field');
        }

        foreach ($params as $other) {
            if (Arr::has($data, $other) && !self::isEmpty(Arr::get($data, $other))) {
                return Arr::has($data, $field) && !self::isEmpty($value);
            }
        }

        return true;
    }

    /**
     * @param list<string> $params
     * @param array<string, mixed> $data
     */
    private static function requiredWithout(mixed $value, array $params, string $field, array $data): bool
    {
        if ($params === []) {
            throw InvalidRuleException::malformed('required_without', 'expected at least one field');
        }

        foreach ($params as $other) {
            if (!Arr::has($data, $other) || self::isEmpty(Arr::get($data, $other))) {
                return Arr::has($data, $field) && !self::isEmpty($value);
            }
        }

        return true;
    }

    private static function file(mixed $value): ?UploadedFile
    {
        if ($value instanceof UploadedFile) {
            return $value;
        }

        return is_array($value) ? UploadedFile::fromArray($value) : null;
    }

    private static function image(mixed $value): bool
    {
        $file = self::file($value);
        if ($file === null || !$file->isSuccessful()) {
            return false;
        }

        return in_array(
            $file->detectedMimeType(),
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'],
            true
        );
    }

    /** @param list<string> $params */
    private static function maxFile(mixed $value, array $params): bool
    {
        $file = self::file($value);
        if ($file === null || !$file->isSuccessful()) {
            return false;
        }

        return $file->size() <= (int) (self::numericParam($params, 'max_file') * 1024);
    }

    /** @param list<string> $params */
    private static function mimeTypes(mixed $value, array $params): bool
    {
        if ($params === []) {
            throw InvalidRuleException::malformed('mimetypes', 'expected at least one MIME type');
        }

        $file = self::file($value);

        return $file !== null
            && $file->isSuccessful()
            && in_array($file->detectedMimeType(), $params, true);
    }

    /** @param list<string> $params */
    private static function extensions(mixed $value, array $params): bool
    {
        if ($params === []) {
            throw InvalidRuleException::malformed('extensions', 'expected at least one extension');
        }

        $file = self::file($value);
        if ($file === null || !$file->isSuccessful()) {
            return false;
        }

        $allowed = array_map(
            static fn (string $extension): string => strtolower(ltrim($extension, '.')),
            $params
        );

        return in_array($file->extension(), $allowed, true);
    }

    /**
     * @param list<string> $params
     * @param list<string> $fieldRuleNames
     */
    private static function compareMeasure(
        mixed $value,
        array $params,
        array $fieldRuleNames,
        string $rule
    ): bool {
        $measure = self::measure($value, $fieldRuleNames);
        if ($measure === null) {
            return false;
        }

        $target = self::numericParam($params, $rule);

        return $rule === 'min' ? $measure >= $target : $measure <= $target;
    }

    /**
     * @param list<string> $params
     * @param list<string> $fieldRuleNames
     */
    private static function between(mixed $value, array $params, array $fieldRuleNames): bool
    {
        if (count($params) !== 2 || !is_numeric($params[0]) || !is_numeric($params[1])) {
            throw InvalidRuleException::malformed('between', 'expected two numeric parameters');
        }

        $measure = self::measure($value, $fieldRuleNames);

        return $measure !== null
            && $measure >= (float) $params[0]
            && $measure <= (float) $params[1];
    }

    /**
     * @param list<string> $params
     * @param list<string> $fieldRuleNames
     */
    private static function size(mixed $value, array $params, array $fieldRuleNames): bool
    {
        $measure = self::measure($value, $fieldRuleNames);

        return $measure !== null && (float) $measure === self::numericParam($params, 'size');
    }

    /** @param list<string> $fieldRuleNames */
    private static function measure(mixed $value, array $fieldRuleNames): float|int|null
    {
        if (
            (in_array('numeric', $fieldRuleNames, true) || in_array('integer', $fieldRuleNames, true))
            && is_numeric($value)
        ) {
            return (float) $value;
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_array($value)) {
            return count($value);
        }

        return is_string($value) ? mb_strlen($value, 'UTF-8') : null;
    }

    /** @param list<string> $params */
    private static function in(mixed $value, array $params, bool $negated): bool
    {
        if ($params === []) {
            throw InvalidRuleException::malformed(
                $negated ? 'not_in' : 'in',
                'expected at least one value'
            );
        }

        $comparable = self::toComparableString($value);
        $found = $comparable !== null && in_array($comparable, $params, true);

        return $negated ? !$found : $found;
    }

    /** @param list<string> $params */
    private static function regex(mixed $value, array $params): bool
    {
        if (count($params) !== 1 || $params[0] === '') {
            throw InvalidRuleException::malformed('regex', 'expected one regular expression');
        }

        if (!is_string($value)) {
            return false;
        }

        $result = @preg_match($params[0], $value);
        if ($result === false) {
            throw InvalidRuleException::malformed('regex', 'invalid regular expression');
        }

        return $result === 1;
    }

    private static function date(mixed $value): bool
    {
        if (!is_string($value) && !is_int($value)) {
            return false;
        }

        return strtotime((string) $value) !== false;
    }

    /** @param list<string> $params */
    private static function dateFormat(mixed $value, array $params): bool
    {
        if (count($params) !== 1 || $params[0] === '') {
            throw InvalidRuleException::malformed('date_format', 'expected one date format');
        }

        if (!is_string($value)) {
            return false;
        }

        $format = $params[0];
        $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
        $errors = DateTimeImmutable::getLastErrors();

        return $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format($format) === $value;
    }

    private static function json(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /** @param list<string> $params */
    private static function startsWith(mixed $value, array $params): bool
    {
        if (!is_string($value) || $params === []) {
            return false;
        }

        foreach ($params as $prefix) {
            if ($prefix !== '' && str_starts_with($value, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $params */
    private static function endsWith(mixed $value, array $params): bool
    {
        if (!is_string($value) || $params === []) {
            return false;
        }

        foreach ($params as $suffix) {
            if ($suffix !== '' && str_ends_with($value, $suffix)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $params */
    private static function contains(mixed $value, array $params): bool
    {
        if (count($params) !== 1 || $params[0] === '') {
            throw InvalidRuleException::malformed('contains', 'expected one non-empty string');
        }

        return is_string($value) && str_contains($value, $params[0]);
    }

    private static function iranMobile(mixed $value): bool
    {
        if (!is_string($value) && !is_int($value)) {
            return false;
        }

        $normalized = preg_replace('/[\s()-]+/', '', (string) $value);

        return $normalized !== null
            && preg_match('/^(?:\+98|0098|98|0)?9\d{9}$/D', $normalized) === 1;
    }

    private static function toComparableString(mixed $value): ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return null;
    }

    /** @param list<string> $params */
    private static function numericParam(array $params, string $rule): float
    {
        if (count($params) !== 1 || !is_numeric($params[0])) {
            throw InvalidRuleException::malformed($rule, 'expected one numeric parameter');
        }

        return (float) $params[0];
    }

    /** @param list<string> $params */
    private static function fieldParam(array $params, string $rule): string
    {
        if (count($params) !== 1 || $params[0] === '') {
            throw InvalidRuleException::malformed($rule, 'expected one field name');
        }

        return $params[0];
    }
}
