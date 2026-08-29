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
| `uuid` | RFC/IETF-variant UUID versions 1 through 8, including UUIDv7. Reserved version values are rejected. |
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

## Iranian rules

Iranian rules normalize both Persian digits (`۰۱۲۳۴۵۶۷۸۹`) and Arabic-Indic digits (`٠١٢٣٤٥٦٧٨٩`) to ASCII before validation where appropriate.

| Rule | Meaning |
| --- | --- |
| `ir_mobile` | Iranian mobile number. Accepts common domestic and international forms such as `0912...`, `912...`, `+98912...`, `98912...`, and `0098912...`. |
| `ir_landline` | Structural Iranian landline number validation. Accepts domestic/international prefixes and common spaces, parentheses, and hyphens. |
| `ir_phone` | Accepts either a valid `ir_mobile` or `ir_landline`. |
| `ir_national_code` | 10-digit Iranian natural-person national code with checksum validation. Obvious repeated-digit values are rejected. |
| `ir_legal_id` | 11-digit national ID for Iranian legal entities with check-digit validation. |
| `ir_company_id` | Alias of `ir_legal_id`. |
| `ir_postal_code` | Structurally validates a 10-digit Iranian postal code and rejects repeated-digit placeholders. It does not verify that the code is assigned to an address. |
| `ir_sheba` | Iranian 26-character Sheba/IBAN beginning with `IR`, validated using MOD-97. Spaces and hyphens are accepted for formatted input. |
| `ir_iban` | Alias of `ir_sheba`. |
| `ir_bank_card` | 16-digit Iranian bank card number with checksum validation. Spaces and hyphens are accepted. |
| `ir_bank_card_number` | Alias of `ir_bank_card`. |

Example:

```php
$validator = Validator::make($_POST, [
    'mobile' => 'required|ir_mobile',
    'national_code' => 'required|ir_national_code',
    'postal_code' => 'required|ir_postal_code',
    'sheba' => 'nullable|ir_sheba',
    'card_number' => 'nullable|ir_bank_card',
]);
```

These rules validate syntax and/or checksums only. A valid checksum does **not** prove that a national code, legal ID, bank card, Sheba, postal code, or phone number exists, is active, or belongs to the person submitting the form. Use the appropriate authoritative verification service when ownership or existence matters.

See [iranian-validation.md](iranian-validation.md) for accepted formats, examples, normalization behavior, and security boundaries.

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
        if (!is_string($value)) {
            return 'The :attribute must be a string.';
        }

        return preg_match('/^[a-z0-9-]+$/', $value) === 1
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
