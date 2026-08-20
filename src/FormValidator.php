<?php
declare(strict_types=1);

namespace RoseShayan\FormGuard;

class FormValidator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $sanitizedData = [];

    private array $customMessages = [
        'required' => 'The :field field is required.',
        'email'    => 'The email format entered for the :field is not valid.',
        'min'      => 'The length of :field must not be less than :param characters.',
        'max'      => 'The length of :field must not exceed :param characters.',
        'numeric'  => 'The value of :field must be a number.',
        'phone'    => 'The mobile number entered for :field is not valid.',
        'matches'  => 'The value of :field does not match the field :param.',
    ];

    public function __construct(array $data = [], array $rules = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->sanitize();
    }

    public static function make(array $data, array $rules): self
    {
        $instance = new self($data, $rules);
        $instance->validate();
        return $instance;
    }

    private function sanitize(): void
    {
        foreach ($this->data as $key => $value) {
            if (is_string($value)) {
                // پاکسازی فاصله‌های اضافی و جلوگیری از حملات XSS
                $clean = trim($value);
                $this->sanitizedData[$key] = htmlspecialchars($clean, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            } elseif (is_array($value)) {
                $this->sanitizedData[$key] = filter_var_array($value, FILTER_DEFAULT);
            } else {
                $this->sanitizedData[$key] = $value;
            }
        }
    }

    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $rules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $param = null;
                if (str_contains($rule, ':')) {
                    [$rule, $param] = explode(':', $rule, 2);
                }

                $method = 'validate' . ucfirst($rule);
                if (method_exists($this, $method)) {
                    if (!$this->$method($value, $param)) {
                        $this->addError($field, $rule, $param);
                        break;
                    }
                }
            }
        }

        return empty($this->errors);
    }

    private function validateRequired(mixed $value, ?string $param): bool
    {
        return !empty($value) || $value === '0' || $value === 0;
    }

    private function validateEmail(mixed $value, ?string $param): bool
    {
        return empty($value) || filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validateMin(mixed $value, ?string $param): bool
    {
        return empty($value) || mb_strlen((string)$value, 'UTF-8') >= (int)$param;
    }

    private function validateMax(mixed $value, ?string $param): bool
    {
        return empty($value) || mb_strlen((string)$value, 'UTF-8') <= (int)$param;
    }

    private function validateNumeric(mixed $value, ?string $param): bool
    {
        return empty($value) || is_numeric($value);
    }

    private function validatePhone(mixed $value, ?string $param): bool
    {
        // پشتیبانی از الگوی شماره موبایل استاندارد
        return empty($value) || preg_match('/^(09|\+989|9)[0-9]{9}$/', (string)$value) === 1;
    }

    private function validateMatches(mixed $value, ?string $param): bool
    {
        return $value === ($this->data[$param] ?? null);
    }

    private function addError(string $field, string $rule, ?string $param): void
    {
        $message = $this->customMessages[$rule] ?? "فیلد {$field} نامعتبر است.";
        $message = str_replace([':field', ':param'], [$field, (string)$param], $message);
        $this->errors[$field] = $message;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    public function validated(): array
    {
        return array_intersect_key($this->sanitizedData, $this->rules);
    }
}