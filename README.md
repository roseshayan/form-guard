# FormGuard

A lightweight, framework-agnostic PHP validation library for forms, APIs, and plain PHP applications.

FormGuard gives you a small Laravel-style validation DSL without requiring Laravel, Symfony, or any other framework. It supports nested data, wildcard fields, custom messages, inline custom rules, uploaded files, and safe whitelisting through `validated()`.

## Why FormGuard?

- Framework-agnostic: use it in plain PHP, WordPress plugins, custom MVC projects, APIs, cron jobs, or legacy applications.
- Small public API: `Validator::make()`, `passes()`, `fails()`, `errors()`, `errorBag()`, and `validated()`.
- Strict configuration: unknown rules throw an `InvalidRuleException` instead of silently passing.
- Nested input: validate `user.email` and wildcard paths such as `items.*.sku`.
- Unicode-aware string validation through `mbstring`.
- Upload validation using server-side MIME detection through `fileinfo`.
- No framework runtime dependencies.
- Tested on PHP 8.2, 8.3, 8.4, and 8.5.

## Requirements

- PHP 8.2+
- `ext-mbstring`
- `ext-fileinfo`

## Installation

### Packagist

After the first tagged release is registered on Packagist:

```bash
composer require roseshayan/form-guard
```

### Install directly from GitHub before the Packagist release

```bash
composer config repositories.form-guard vcs https://github.com/roseshayan/form-guard.git
composer require roseshayan/form-guard:dev-main
```

## Quick start

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use RoseShayan\FormGuard\Validator;

$validator = Validator::make($_POST, [
    'name' => 'bail|required|string|min:2|max:100',
    'email' => 'required|email',
    'age' => 'nullable|integer|min:18|max:120',
    'password' => 'required|string|min_length:8|confirmed',
]);

if ($validator->fails()) {
    foreach ($validator->errors() as $field => $message) {
        echo $field . ': ' . $message . PHP_EOL;
    }

    exit;
}

$data = $validator->validated();

// $data contains only keys that had validation rules.
```

`confirmed` expects a sibling field named `<field>_confirmation`, for example `password_confirmation`.

## Nested arrays and wildcards

```php
$validator = Validator::make($payload, [
    'customer.email' => 'required|email',
    'items' => 'required|array|min:1',
    'items.*.sku' => 'required|string',
    'items.*.quantity' => 'required|integer|min:1',
]);
```

Errors use concrete field paths such as `items.1.quantity`.

## Custom messages and field names

```php
$validator = Validator::make(
    $_POST,
    ['email' => 'required|email'],
    ['email.email' => ':attribute is not a valid email address.'],
    ['email' => 'Work email']
);
```

Message lookup order is:

1. `field.rule`
2. wildcard pattern + rule, for example `items.*.sku.required`
3. global rule name, for example `required`
4. FormGuard's built-in message

Available placeholders include `:attribute`, `:field`, `:param`, `:param2`, `:params`, and `:other`.

## Inline custom rules

Use array syntax and return `true`/`null` to pass, `false` for the generic error, or a string for a custom error message:

```php
$validator = Validator::make($_POST, [
    'username' => [
        'required',
        'alpha_dash',
        static function (string $field, mixed $value, array $data): bool|string {
            return strtolower((string) $value) === $value
                ? true
                : 'The :attribute must be lowercase.';
        },
    ],
]);
```

This avoids global mutable custom-rule registries and works safely in long-running PHP processes.

## File uploads

Pass the trusted `$_FILES` entry alongside your normal form data:

```php
$input = array_merge($_POST, [
    'avatar' => $_FILES['avatar'] ?? null,
]);

$validator = Validator::make($input, [
    'avatar' => 'nullable|file|image|max_file:2048|mimetypes:image/jpeg,image/png|extensions:jpg,jpeg,png',
]);
```

`max_file` is measured in KiB. `mimetypes` uses `finfo` against the temporary file and does **not** trust the browser-provided MIME type. `extensions` checks the original filename and should never be used as the only upload security control.

## Error handling

For the first error of every field:

```php
$errors = $validator->errors();
```

For richer access:

```php
$bag = $validator->errorBag();

$bag->has('email');
$bag->first('email');
$bag->get('email');
$bag->all();
$bag->toArray();
```

If you prefer exceptions:

```php
use RoseShayan\FormGuard\ValidationException;

try {
    $data = Validator::make($input, $rules)->validateOrFail();
} catch (ValidationException $exception) {
    $errors = $exception->errors()->toArray();
}
```

Calling `validated()` after failed validation also throws `ValidationException`. This prevents accidentally consuming partially invalid input.

## Rule reference

See [docs/rules.md](docs/rules.md) for the complete built-in rule list and semantics.

## Important security boundary

FormGuard validates and **whitelists** input. It intentionally does not call `htmlspecialchars()`, strip HTML globally, escape SQL, or mutate your values.

Those operations belong at the output/storage boundary:

```php
// HTML output
$html = htmlspecialchars($data['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// SQL
$stmt = $pdo->prepare('INSERT INTO users (email) VALUES (:email)');
$stmt->execute(['email' => $data['email']]);
```

Validation is not a replacement for CSRF protection, authorization, prepared SQL statements, contextual HTML/JavaScript escaping, antivirus scanning, or secure upload storage.

## Regex note

String rules use `|` as the rule separator. If your regular expression contains a pipe, use array syntax:

```php
'code' => ['required', 'regex:/^(ABC|XYZ)$/']
```

## Development

```bash
composer install
composer check
```

`composer check` runs PHPUnit and PHPStan.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

See [SECURITY.md](SECURITY.md) before reporting a vulnerability.

## License

FormGuard is released under the MIT License. See [LICENSE](LICENSE).
