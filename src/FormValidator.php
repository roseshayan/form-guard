<?php
declare(strict_types=1);

namespace RoseShayan\FormGuard;

use RoseShayan\FormGuard\Rules\BuiltInRules;
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

    /** @var array<string, string> */
    private const DEFAULT_MESSAGES = [
        'required' => 'The :attribute field is required.',
        'required_if' => 'The :attribute field is required when :other matches a required value.',
        'required_with' => 'The :attribute field is required when :other is present.',
        'required_without' => 'The :attribute field is required when :other is not present.',
        'present' => 'The :attribute field must be present.',
        'filled' => 'The :attribute field must have a value.',
        'string' => 'The :attribute field must be a string.',
        'integer' => 'The :attribute field must be an integer.',
        'numeric' => 'The :attribute field must be numeric.',
        'boolean' => 'The :attribute field must be true or false.',
        'array' => 'The :attribute field must be an array.',
        'file' => 'The :attribute field must be a valid uploaded file.',
        'image' => 'The :attribute field must be a valid image.',
        'max_file' => 'The :attribute file must not be larger than :param KiB.',
        'mimetypes' => 'The :attribute file type must be one of: :params.',
        'extensions' => 'The :attribute file extension must be one of: :params.',
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
        'ir_landline' => 'The :attribute field must be a valid Iranian landline number.',
        'ir_phone' => 'The :attribute field must be a valid Iranian phone number.',
        'ir_national_code' => 'The :attribute field must be a valid Iranian national code.',
        'ir_legal_id' => 'The :attribute field must be a valid Iranian legal entity national ID.',
        'ir_company_id' => 'The :attribute field must be a valid Iranian legal entity national ID.',
        'ir_postal_code' => 'The :attribute field must be a valid 10-digit Iranian postal code.',
        'ir_sheba' => 'The :attribute field must be a valid Iranian Sheba (IBAN).',
        'ir_iban' => 'The :attribute field must be a valid Iranian Sheba (IBAN).',
        'ir_bank_card' => 'The :attribute field must be a valid Iranian bank card number.',
        'ir_bank_card_number' => 'The :attribute field must be a valid Iranian bank card number.',
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
    final public function __construct(
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
        $validator = new static($data, $rules, $messages, $attributes);
        $validator->validate();

        return $validator;
    }

    public function validate(): bool
    {
        $this->errorBag = new ErrorBag();
        $this->hasValidated = true;

        foreach ($this->rules as $pattern => $definition) {
            $fieldRules = $this->normalizeRuleDefinition($definition);
            $ruleNames = $this->extractAndAssertRuleNames($fieldRules);

            $paths = str_contains($pattern, '*')
                ? Arr::expandWildcardPaths($this->data, $pattern)
                : [$pattern];

            foreach ($paths as $field) {
                $this->validateField($field, $pattern, $fieldRules, $ruleNames);
            }
        }

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

    /**
     * @param list<string|callable> $fieldRules
     * @param list<string> $ruleNames
     */
    private function validateField(
        string $field,
        string $pattern,
        array $fieldRules,
        array $ruleNames
    ): void {
        $exists = Arr::has($this->data, $field);
        $value = Arr::get($this->data, $field);

        if (in_array('sometimes', $ruleNames, true) && !$exists) {
            return;
        }

        $nullable = in_array('nullable', $ruleNames, true) && BuiltInRules::isBlank($value);
        $bail = in_array('bail', $ruleNames, true);

        foreach ($fieldRules as $definition) {
            if (!is_string($definition)) {
                if (!$exists || BuiltInRules::isBlank($value)) {
                    continue;
                }

                $result = $definition($field, $value, $this->data);
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

            [$rule, $params] = $this->parseRule($definition);

            if (in_array($rule, self::DIRECTIVE_RULES, true)) {
                continue;
            }

            $implicit = in_array($rule, self::IMPLICIT_RULES, true);

            if (!$exists && !$implicit) {
                continue;
            }

            if ($nullable && !$implicit) {
                continue;
            }

            if (BuiltInRules::isBlank($value) && !$implicit) {
                continue;
            }

            if (BuiltInRules::validate($rule, $value, $params, $field, $this->data, $ruleNames)) {
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
            return $definition === '' ? [] : explode('|', $definition);
        }

        return array_values($definition);
    }

    /**
     * @param list<string|callable> $rules
     * @return list<string>
     */
    private function extractAndAssertRuleNames(array $rules): array
    {
        $names = [];

        foreach ($rules as $definition) {
            if (!is_string($definition)) {
                continue;
            }

            [$name] = $this->parseRule($definition);

            if (
                !in_array($name, self::DIRECTIVE_RULES, true)
                && !BuiltInRules::exists($name)
            ) {
                throw InvalidRuleException::unknown($name);
            }

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

        return [
            $name,
            array_map(
                static fn (string $param): string => trim($param),
                explode(',', $parameterString)
            ),
        ];
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
        $other = $params[0] ?? '';

        return strtr($message, [
            ':attribute' => $this->resolveAttribute($field, $pattern),
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
}
