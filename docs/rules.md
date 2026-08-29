# Built-in validation rules

Rules can be written as a pipe-delimited string:

```php
'email' => 'bail|required|email|max_length:255'
```

or as an array. Array syntax is required when a regex itself contains `|` and is also the syntax used for inline callable rules.

## Presence and flow control

| Rule | Meaning |
| --- | --- |
| `required` | Field must exist and may not be `null`, an empty string, an empty array, or a missing upload. `0`, `'0'`, and `false` are valid values. |
| `present` | Field key must exist, even if its value is empty. |
| `filled` | If the field exists, it must not be empty. |
| `required_if:other,value,...` | Field is required when `other` matches one of the configured values. |
| `required_with:field,...` | Field is required when at least one listed field exists and is not empty. |
| `required_without:field,...` | Field is required when at least one listed field is missing or empty. |
| `sometimes` | Skip the entire field when its key is not present. |
| `nullable` | Skip non-implicit rules when the value is `null`, an empty string, or a `UPLOAD_ERR_NO_FILE` upload. |
| `bail` | Stop evaluating further rules for the field after its first failure. |

## Type and format rules

| Rule | Meaning |
| --- | --- |
| `string` | Value must be a PHP string. |
| `integer` | Accepts integers and integer strings such as `'42'` or `'-5'`. |
| `numeric` | Value must satisfy PHP's `is_numeric()`. |
| `boolean` | Accepts `true`, `false`, `0`, `1`, `'0'`, `'1'`, `'true'`, and `'false'`. |
| `array` | Value must be an array. |
| `email` | Uses PHP's `FILTER_VALIDATE_EMAIL`. |
| `url` | Uses PHP's `FILTER_VALIDATE_URL`. This validates URL syntax, not whether a scheme is safe for your output context. |
| `uuid` | UUID versions 1 through 5 with RFC variant bits. |
| `ip` | IPv4 or IPv6. |
| `ipv4` | IPv4 only. |
| `ipv6` | IPv6 only. |
| `alpha` | Unicode letters only. |
| `alpha_num` | Unicode letters and numbers only. |
| `alpha_dash` | Unicode letters, numbers, `_`, and `-`. |
| `json` | Value must be a valid JSON string. |
| `date` | Value must be parseable by PHP `strtotime()`. |
| `date_format:Y-m-d` | Value must exactly match the supplied `DateTime` format. |
| `regex:/pattern/` | Value must match the PCRE expression. Use array rule syntax if the pattern contains `|`. |
| `ir_mobile` | Iranian mobile number in common `09...`, `98...`, `+98...`, or `0098...` forms. |

## Size and numeric rules

`min`, `max`, `between`, and `size` use these semantics:

- when the field also has `numeric` or `integer`, the numeric value is compared;
- arrays are measured with `count()`;
- strings are measured with Unicode-aware `mb_strlen()`;
- native PHP integers/floats are compared numerically.

| Rule | Meaning |
| --- | --- |
| `min:3` | Minimum numeric value, array count, or string length depending on the field type/rules. |
| `max:100` | Maximum numeric value, array count, or string length. |
| `between:3,10` | Measure must be between both bounds, inclusive. |
| `size:5` | Measure must equal the supplied value. |
| `min_length:3` | String must contain at least the specified number of Unicode characters. |
| `max_length:100` | String must contain no more than the specified number of Unicode characters. |
| `length:10` | String length must exactly equal the specified number of Unicode characters. |
| `min_value:0` | Explicit numeric minimum independent of sibling rules. |
| `max_value:1000` | Explicit numeric maximum independent of sibling rules. |

For HTML form numeric values, using `integer|min:18` or `numeric|min:18` is recommended because `$_POST` values normally arrive as strings.

## Choice and comparison rules

| Rule | Meaning |
| --- | --- |
| `in:a,b,c` | String representation of the value must be in the allowlist. |
| `not_in:a,b,c` | String representation of the value must not be in the list. |
| `same:other` | Value must strictly equal the other field. |
| `matches:other` | Alias of `same`. |
| `different:other` | Value must be strictly different from the other field. |
| `confirmed` | Value must strictly equal `<field>_confirmation`. |
| `accepted` | Accepts common checkbox values: `yes`, `on`, `1`, `true` and equivalent boolean/integer forms. |
| `declined` | Accepts `no`, `off`, `0`, `false` and equivalent boolean/integer forms. |
| `starts_with:a,b` | String must begin with one of the values. |
| `ends_with:a,b` | String must end with one of the values. |
| `contains:text` | String must contain the supplied text. |

## Upload rules

Upload rules accept either a standard single-file `$_FILES['field']` array or a `RoseShayan\FormGuard\UploadedFile` instance.

| Rule | Meaning |
| --- | --- |
| `file` | Upload must have `UPLOAD_ERR_OK` and a real temporary file path. |
| `image` | Detected MIME type must be JPEG, PNG, GIF, WebP, or AVIF. |
| `max_file:2048` | Maximum upload size in KiB. |
| `mimetypes:image/jpeg,image/png` | Server-detected MIME type must be in the list. Detection uses `finfo`; the browser's `type` value is not trusted. |
| `extensions:jpg,jpeg,png` | Original filename extension must be in the list. Never use this rule as the only upload security check. |

Example:

```php
$input = array_merge($_POST, [
    'document' => $_FILES['document'] ?? null,
]);

$validator = Validator::make($input, [
    'document' => 'required|file|max_file:5120|mimetypes:application/pdf|extensions:pdf',
]);
```

FormGuard validates upload metadata and MIME type. Your application is still responsible for generating a safe destination filename, storing uploads outside executable paths where appropriate, permissions, malware scanning when needed, and enforcing authorization.

## Nested fields and wildcards

Dot notation accesses nested arrays:

```php
'user.email' => 'required|email'
```

A `*` validates each existing array element:

```php
'items.*.sku' => 'required|string'
'items.*.quantity' => 'required|integer|min:1'
```

If an item exists but its leaf key is missing, implicit rules such as `required` still run for that concrete leaf.

## Custom callable rules

```php
'slug' => [
    'required',
    static function (string $field, mixed $value, array $data): bool|string {
        return preg_match('/^[a-z0-9-]+$/', (string) $value) === 1
            ? true
            : 'The :attribute must be a lowercase slug.';
    },
]
```

Return values:

- `true` or `null`: pass;
- `false`: fail with FormGuard's generic custom-rule message;
- string: fail using that string as the error message.

Callable rules are skipped for absent/blank optional fields, just like normal non-implicit rules.

## Invalid rule configuration

Unknown built-in rule names throw `RoseShayan\FormGuard\InvalidRuleException` immediately, even if the target field is missing. Malformed parameters also throw when the affected rule is evaluated.

This is intentional: a typo in validation configuration must not silently disable validation.
