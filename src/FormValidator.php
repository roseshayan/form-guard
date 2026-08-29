<?php
declare(strict_types=1);

namespace RoseShayan\FormGuard;

use DateTimeImmutable;
use RoseShayan\FormGuard\Support\Arr;

class FormValidator
{
    /** @var array<string, mixed> */
    private array $data;

    /** @var array<string, string|array<int, string|callable>> */
    private array $rules;

    /** @var array<string, string> */
    private array $messages;

    /** @var array<string, string> */
    private array $attributes;

    private ErrorBag $errorBag;
    private bool $hasValidated = false;

    /** @var list<string> */
    private array $currentRuleNames = [];

    /** @var array<string, string> */
    private const DEFAULT_MESSAGES = [
        'required' => 'The :attribute field is required.',
        'required_if' => 'The :attribute field is required when :other is :param.',
        'required_with' => 'The :attribute field is required when :other is present.',
        'required_without' => 'The :attribute field is required when :other is not present.',
        'present' => 'The :attribute field must be present.',
        'filled' => 'The :attribute field must have a value.',
        'string' => 'The :attribute field must be a string.',
        'integer' => 'The :attribute field must be an integer.',
        'numeric' => 'The :attribute field must be numeric.',
        'boolean' => 'The :attribute field must be true or false.',
        'array' => 'The :attribute field must be an array.',
        'email' => 'The :attribute field must be a valid email address.',
        'url' => 'The :attribute field must be a valid URL.',
        'uuid' => 'The :attribute field must be a valid UUID.',
        'ip' => 'The :attribute field must be a valid IP address.',
        'ipv4' => 'The :attribute field must be a valid IPv4 address.',
        'ipv6' => 'The :attribute field must be a valid IPv6 address.',
        'alpha' => 'The :attribute field may only contain letters.',
        'alpha_num' => 'The :attribute field may only contain letters and numbers.',
        'alpha_dash' => 'The :attribute field may only contain letters, numbers, dashes, and underscores.',
        'min' => 'The :attribute field must be at least :param.',
        'max' => 'The :attribute field must not be greater than :param.',
        'between' => 'The :attribute field must be between :param and :param2.',
        'size' => 'The :attribute field must have a size of :param.',
        'min_length' => 'The :attribute field must be at least :param characters.',
        'max_length' => 'The :attribute field must not be greater than :param characters.',
        'length' => 'The :attribute field must be exactly :param characters.',
        'min_value' => 'The :attribute field must be at least :param.',
        'max_value' => 'The :attribute field must not be greater than :param.',
        'in' => 'The selected :attribute is invalid.',
        'not_in' => 'The selected :attribute is invalid.',
        'same' => 'The :attribute field must match :other.',
        'matches' => 'The :attribute field must match :other.',
        'different' => 'The :attribute field and :other must be different.',
        'confirmed' => 'The :attribute confirmation does not match.',
        'regex' => 'The :attribute field format is invalid.',
        'date' => 'The :attribute field must be a valid date.',
        'date_format' => 'The :attribute field must match the format :param.',
        'json' => 'The :attribute field must be a valid JSON string.',
        'accepted' => 'The :attribute field must be accepted.',
        'declined' => 'The :attribute field must be declined.',
        'starts_with' => 'The :attribute field must start with one of: :params.',
        'ends_with' => 'The :attribute field must end with one of: :params.',
        'contains' => 'The :attribute field must contain :param.',
        'ir_mobile' => 'The :attribute field must be a valid Iranian mobile number.',
    ];

    /** @var list<string> */
    private const DIRECTIVE_RULES = ['sometimes', 'nullable', 'bail'];

    /** @var list<string> */
    private const IMPLICIT_RULES = [
        'required',
        'required_if',
        'required_with',
        'required_without',
        'present',
        'filled',
    ];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string|callable>> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     */
    public function __construct(
        array $data = [],
        array $rules = [],
        array $messages = [],
        array $attributes = []
    ) {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
        $this->attributes = $attributes;
        $this->errorBag = new ErrorBag();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|array<int, string|callable>> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     */
    public static function make(
        array $data,
        array $rules,
        array $messages = [],
        array $attributes = []
    ): static {
        $instance = new static($data, $rules, $messages, $attributes);
        $instance->validate();

        return $instance;
    }

    public function validate(): bool
    {
        $this->errorBag = new ErrorBag();
        $this->hasValidated = true;

        foreach ($this->rules as $pattern => $definition) {
            $fieldRules = $this->normalizeRuleDefinition($definition);
            $this->currentRuleNames = $this->extractRuleNames($fieldRules);

            $paths = str_contains($pattern, '*')
                ? Arr::expandWildcardPaths($this->data, $pattern)
                : [$pattern];

            foreach ($paths as $field) {
                $this->validateField($field, $pattern, $fieldRules);
            }
        }

        $this->currentRuleNames = [];

        return $this->errorBag->isEmpty();
    }

    public function passes(): bool
    {
        $this->ensureValidated();

        return $this->errorBag->isEmpty();
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        $this->ensureValidated();

        return $this->errorBag->firstOfEach();
    }

    public function errorBag(): ErrorBag
    {
        $this->ensureValidated();

        return $this->errorBag;
    }

    public function firstError(?string $field = null): ?string
    {
        $this->ensureValidated();

        return $this->errorBag->first($field);
    }

    /**
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function validated(): array
    {
        $this->ensureValidated();

        if (!$this->errorBag->isEmpty()) {
            throw new ValidationException($this->errorBag);
        }

        $result = [];

        foreach ($this->rules as $pattern => $_definition) {
            $paths = str_contains($pattern, '*')
                ? Arr::expandWildcardPaths($this->data, $pattern)
                : [$pattern];

            foreach ($paths as $field) {
                if (Arr::has($this->data, $field)) {
                    Arr::set($result, $field, Arr::get($this->data, $field));
                }
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function validateOrFail(): array
    {
        $this->validate();

        return $this->validated();
    }

    /** @param list<string|callable> $fieldRules */
    private function validateField(string $field, string $pattern, array $fieldRules): void
    {
        $exists = Arr::has($this->data, $field);
        $value = Arr::get($this->data, $field);

        if (in_array('sometimes', $this->currentRuleNames, true) && !$exists) {
            return;
        }

        $isNullable = in_array('nullable', $this->currentRuleNames, true)
            && ($value === null || $value === '');

        $bail = in_array('bail', $this->currentRuleNames, true);

        foreach ($fieldRules as $ruleDefinition) {
            if (is_callable($ruleDefinition)) {
                if (!$exists || $value === null || $value === '') {
                    continue;
                }

                $result = $ruleDefinition($field, $value, $this->data);
                if ($result === true || $result === null) {
                    continue;
                }

                $message = is_string($result)
                    ? $result
                    : 'The :attribute field is invalid.';

                $this->errorBag->add(
                    $field,
                    $this->replacePlaceholders($message, $field, $pattern, [])
                );

                if ($bail) {
                    break;
                }

                continue;
            }

            [$rule, $params] = $this->parseRule($ruleDefinition);

            if (in_array($rule, self::DIRECTIVE_RULES, true)) {
                continue;
            }

            $isImplicit = in_array($rule, self::IMPLICIT_RULES, true);

            if (!$exists && !$isImplicit) {
                continue;
            }

            if ($isNullable && !$isImplicit) {
                continue;
            }

            if (($value === null || $value === '') && !$isImplicit) {
                continue;
            }

            $method = 'validate' . str_replace(' ', '', ucwords(str_replace('_', ' ', $rule)));
            if (!method_exists($this, $method)) {
                throw InvalidRuleException::unknown($rule);
            }

            /** @var bool $valid */
            $valid = $this->{$method}($value, $params, $field);
            if ($valid) {
                continue;
            }

            $this->addError($field, $pattern, $rule, $params);

            if ($bail) {
                break;
            }
        }
    }

    /**
     * @param string|array<int, string|callable> $definition
     * @return list<string|callable>
     */
    private function normalizeRuleDefinition(string|array $definition): array
    {
        if (is_string($definition)) {
            if ($definition === '') {
                return [];
            }

            return explode('|', $definition);
        }

        return array_values($definition);
    }

    /**
     * @param list<string|callable> $rules
     * @return list<string>
     */
    private function extractRuleNames(array $rules): array
    {
        $names = [];

        foreach ($rules as $rule) {
            if (!is_string($rule)) {
                continue;
            }

            [$name] = $this->parseRule($rule);
            $names[] = $name;
        }

        return $names;
    }

    /** @return array{0:string,1:list<string>} */
    private function parseRule(string $definition): array
    {
        $definition = trim($definition);
        if ($definition === '') {
            throw InvalidRuleException::malformed($definition, 'rule name cannot be empty');
        }

        if (!str_contains($definition, ':')) {
            return [strtolower($definition), []];
        }

        [$name, $parameterString] = explode(':', $definition, 2);
        $name = strtolower(trim($name));

        if ($name === '') {
            throw InvalidRuleException::malformed($definition, 'rule name cannot be empty');
        }

        if ($name === 'regex') {
            return [$name, [$parameterString]];
        }

        $params = array_map(
            static fn (string $param): string => trim($param),
            explode(',', $parameterString)
        );

        return [$name, $params];
    }

    /** @param list<string> $params */
    private function addError(string $field, string $pattern, string $rule, array $params): void
    {
        $message = $this->resolveMessage($field, $pattern, $rule);
        $this->errorBag->add(
            $field,
            $this->replacePlaceholders($message, $field, $pattern, $params)
        );
    }

    private function resolveMessage(string $field, string $pattern, string $rule): string
    {
        foreach ([$field . '.' . $rule, $pattern . '.' . $rule, $rule] as $key) {
            if (isset($this->messages[$key])) {
                return $this->messages[$key];
            }
        }

        return self::DEFAULT_MESSAGES[$rule] ?? 'The :attribute field is invalid.';
    }

    /** @param list<string> $params */
    private function replacePlaceholders(
        string $message,
        string $field,
        string $pattern,
        array $params
    ): string {
        $attribute = $this->resolveAttribute($field, $pattern);
        $other = $params[0] ?? '';

        return strtr($message, [
            ':attribute' => $attribute,
            ':field' => $field,
            ':param' => $params[0] ?? '',
            ':param2' => $params[1] ?? '',
            ':params' => implode(', ', $params),
            ':other' => $other === '' ? '' : $this->resolveAttribute($other, $other),
        ]);
    }

    private function resolveAttribute(string $field, string $pattern): string
    {
        if (isset($this->attributes[$field])) {
            return $this->attributes[$field];
        }

        if (isset($this->attributes[$pattern])) {
            return $this->attributes[$pattern];
        }

        return str_replace(['.', '_'], ' ', $field);
    }

    private function ensureValidated(): void
    {
        if (!$this->hasValidated) {
            $this->validate();
        }
    }

    /** @param list<string> $params */
    private function validateRequired(mixed $value, array $params, string $field): bool
    {
        return Arr::has($this->data, $field) && !$this->isEmpty($value);
    }

    /** @param list<string> $params */
    private function validateRequiredIf(mixed $value, array $params, string $field): bool
    {
        if (count($params) < 2) {
            throw InvalidRuleException::malformed('required_if', 'expected other field and at least one value');
        }

        $other = array_shift($params);
        if ($other === null) {
            return true;
        }

        $otherValue = Arr::get($this->data, $other);
        $required = in_array((string) $otherValue, $params, true);

        return !$required || $this->validateRequired($value, [], $field);
    }

    /** @param list<string> $params */
    private function validateRequiredWith(mixed $value, array $params, string $field): bool
    {
        if ($params === []) {
            throw InvalidRuleException::malformed('required_with', 'expected at least one field');
        }

        $required = false;
        foreach ($params as $other) {
            if (Arr::has($this->data, $other) && !$this->isEmpty(Arr::get($this->data, $other))) {
                $required = true;
                break;
            }
        }

        return !$required || $this->validateRequired($value, [], $field);
    }

    /** @param list<string> $params */
    private function validateRequiredWithout(mixed $value, array $params, string $field): bool
    {
        if ($params === []) {
            throw InvalidRuleException::malformed('required_without', 'expected at least one field');
        }

        $required = false;
        foreach ($params as $other) {
            if (!Arr::has($this->data, $other) || $this->isEmpty(Arr::get($this->data, $other))) {
                $required = true;
                break;
            }
        }

        return !$required || $this->validateRequired($value, [], $field);
    }

    /** @param list<string> $params */
    private function validatePresent(mixed $value, array $params, string $field): bool
    {
        return Arr::has($this->data, $field);
    }

    /** @param list<string> $params */
    private function validateFilled(mixed $value, array $params, string $field): bool
    {
        return !Arr::has($this->data, $field) || !$this->isEmpty($value);
    }

    /** @param list<string> $params */
    private function validateString(mixed $value, array $params, string $field): bool
    {
        return is_string($value);
    }

    /** @param list<string> $params */
    private function validateInteger(mixed $value, array $params, string $field): bool
    {
        if (is_int($value)) {
            return true;
        }

        return is_string($value) && preg_match('/^[+-]?\d+$/D', $value) === 1;
    }

    /** @param list<string> $params */
    private function validateNumeric(mixed $value, array $params, string $field): bool
    {
        return is_numeric($value);
    }

    /** @param list<string> $params */
    private function validateBoolean(mixed $value, array $params, string $field): bool
    {
        return in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true);
    }

    /** @param list<string> $params */
    private function validateArray(mixed $value, array $params, string $field): bool
    {
        return is_array($value);
    }

    /** @param list<string> $params */
    private function validateEmail(mixed $value, array $params, string $field): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /** @param list<string> $params */
    private function validateUrl(mixed $value, array $params, string $field): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /** @param list<string> $params */
    private function validateUuid(mixed $value, array $params, string $field): bool
    {
        return is_string($value)
            && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/iD', $value) === 1;
    }

    /** @param list<string> $params */
    private function validateIp(mixed $value, array $params, string $field): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /** @param list<string> $params */
    private function validateIpv4(mixed $value, array $params, string $field): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /** @param list<string> $params */
    private function validateIpv6(mixed $value, array $params, string $field): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /** @param list<string> $params */
    private function validateAlpha(mixed $value, array $params, string $field): bool
    {
        return is_string($value) && preg_match('/^\p{L}+$/uD', $value) === 1;
    }

    /** @param list<string> $params */
    private function validateAlphaNum(mixed $value, array $params, string $field): bool
    {
        return is_string($value) && preg_match('/^[\p{L}\p{N}]+$/uD', $value) === 1;
    }

    /** @param list<string> $params */
    private function validateAlphaDash(mixed $value, array $params, string $field): bool
    {
        return is_string($value) && preg_match('/^[\p{L}\p{N}_-]+$/uD', $value) === 1;
    }

    /** @param list<string> $params */
    private function validateMin(mixed $value, array $params, string $field): bool
    {
        $limit = $this->numericParameter($params, 'min');
        $measure = $this->measure($value);

        return $measure !== null && $measure >= $limit;
    }

    /** @param list<string> $params */
    private function validateMax(mixed $value, array $params, string $field): bool
    {
        $limit = $this->numericParameter($params, 'max');
        $measure = $this->measure($value);

        return $measure !== null && $measure <= $limit;
    }

    /** @param list<string> $params */
    private function validateBetween(mixed $value, array $params, string $field): bool
    {
        if (count($params) !== 2 || !is_numeric($params[0]) || !is_numeric($params[1])) {
            throw InvalidRuleException::malformed('between', 'expected two numeric parameters');
        }

        $measure = $this->measure($value);
        if ($measure === null) {
            return false;
        }

        return $measure >= (float) $params[0] && $measure <= (float) $params[1];
    }

    /** @param list<string> $params */
    private function validateSize(mixed $value, array $params, string $field): bool
    {
        $target = $this->numericParameter($params, 'size');
        $measure = $this->measure($value);

        return $measure !== null && $measure === $target;
    }

    /** @param list<string> $params */
    private function validateMinLength(mixed $value, array $params, string $field): bool
    {
        return is_string($value)
            && $this->stringLength($value) >= $this->numericParameter($params, 'min_length');
    }

    /** @param list<string> $params */
    private function validateMaxLength(mixed $value, array $params, string $field): bool
    {
        return is_string($value)
            && $this->stringLength($value) <= $this->numericParameter($params, 'max_length');
    }

    /** @param list<string> $params */
    private function validateLength(mixed $value, array $params, string $field): bool
    {
        return is_string($value)
            && $this->stringLength($value) === (int) $this->numericParameter($params, 'length');
    }

    /** @param list<string> $params */
    private function validateMinValue(mixed $value, array $params, string $field): bool
    {
        return is_numeric($value) && (float) $value >= $this->numericParameter($params, 'min_value');
    }

    /** @param list<string> $params */
    private function validateMaxValue(mixed $value, array $params, string $field): bool
    {
        return is_numeric($value) && (float) $value <= $this->numericParameter($params, 'max_value');
    }

    /** @param list<string> $params */
    private function validateIn(mixed $value, array $params, string $field): bool
    {
        if ($params === []) {
            throw InvalidRuleException::malformed('in', 'expected at least one allowed value');
        }

        return in_array((string) $value, $params, true);
    }

    /** @param list<string> $params */
    private function validateNotIn(mixed $value, array $params, string $field): bool
    {
        if ($params === []) {
            throw InvalidRuleException::malformed('not_in', 'expected at least one disallowed value');
        }

        return !in_array((string) $value, $params, true);
    }

    /** @param list<string> $params */
    private function validateSame(mixed $value, array $params, string $field): bool
    {
        $other = $this->fieldParameter($params, 'same');

        return $value === Arr::get($this->data, $other);
    }

    /** @param list<string> $params */
    private function validateMatches(mixed $value, array $params, string $field): bool
    {
        return $this->validateSame($value, $params, $field);
    }

    /** @param list<string> $params */
    private function validateDifferent(mixed $value, array $params, string $field): bool
    {
        $other = $this->fieldParameter($params, 'different');

        return $value !== Arr::get($this->data, $other);
    }

    /** @param list<string> $params */
    private function validateConfirmed(mixed $value, array $params, string $field): bool
    {
        $confirmationField = $field . '_confirmation';

        return Arr::has($this->data, $confirmationField)
            && $value === Arr::get($this->data, $confirmationField);
    }

    /** @param list<string> $params */
    private function validateRegex(mixed $value, array $params, string $field): bool
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

    /** @param list<string> $params */
    private function validateDate(mixed $value, array $params, string $field): bool
    {
        return (is_string($value) || is_int($value)) && strtotime((string) $value) !== false;
    }

    /** @param list<string> $params */
    private function validateDateFormat(mixed $value, array $params, string $field): bool
    {
        if (count($params) !== 1 || $params[0] === '') {
            throw InvalidRuleException::malformed('date_format', 'expected one date format');
        }

        if (!is_string($value)) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!' . $params[0], $value);
        $errors = DateTimeImmutable::getLastErrors();

        return $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format($params[0]) === $value;
    }

    /** @param list<string> $params */
    private function validateJson(mixed $value, array $params, string $field): bool
    {
        if (!is_string($value)) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }

    /** @param list<string> $params */
    private function validateAccepted(mixed $value, array $params, string $field): bool
    {
        return in_array($value, ['yes', 'on', '1', 1, true, 'true'], true);
    }

    /** @param list<string> $params */
    private function validateDeclined(mixed $value, array $params, string $field): bool
    {
        return in_array($value, ['no', 'off', '0', 0, false, 'false'], true);
    }

    /** @param list<string> $params */
    private function validateStartsWith(mixed $value, array $params, string $field): bool
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
    private function validateEndsWith(mixed $value, array $params, string $field): bool
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
    private function validateContains(mixed $value, array $params, string $field): bool
    {
        if (!is_string($value) || count($params) !== 1 || $params[0] === '') {
            return false;
        }

        return str_contains($value, $params[0]);
    }

    /** @param list<string> $params */
    private function validateIrMobile(mixed $value, array $params, string $field): bool
    {
        if (!is_string($value) && !is_int($value)) {
            return false;
        }

        $normalized = preg_replace('/[\s()-]+/', '', (string) $value);
        if ($normalized === null) {
            return false;
        }

        return preg_match('/^(?:\+98|0098|98|0)?9\d{9}$/D', $normalized) === 1;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    private function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function measure(mixed $value): float|int|null
    {
        if (
            (in_array('numeric', $this->currentRuleNames, true)
                || in_array('integer', $this->currentRuleNames, true))
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

        if (is_string($value)) {
            return $this->stringLength($value);
        }

        return null;
    }

    /** @param list<string> $params */
    private function numericParameter(array $params, string $rule): float
    {
        if (count($params) !== 1 || !is_numeric($params[0])) {
            throw InvalidRuleException::malformed($rule, 'expected one numeric parameter');
        }

        return (float) $params[0];
    }

    /** @param list<string> $params */
    private function fieldParameter(array $params, string $rule): string
    {
        if (count($params) !== 1 || $params[0] === '') {
            throw InvalidRuleException::malformed($rule, 'expected one field name');
        }

        return $params[0];
    }
}
