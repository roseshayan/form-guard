# Iranian validation rules

FormGuard includes a dedicated set of `ir_*` rules for common fields in Iranian websites and applications.

The goal of these rules is to validate locally and deterministically without requiring a network request. They validate format and, where an official check digit exists, mathematical consistency. They do not perform identity, ownership, account-status, or address lookups.

## Digit normalization

Iranian users frequently enter values using Persian or Arabic-Indic digits. Iranian rules normalize both forms before validation:

```text
ASCII:        0123456789
Persian:      ۰۱۲۳۴۵۶۷۸۹
Arabic-Indic: ٠١٢٣٤٥٦٧٨٩
```

For example, both values below pass `ir_mobile`:

```text
09121234567
۰۹۱۲۱۲۳۴۵۶۷
```

The original input is not mutated. `validated()` still returns the value that your application received.

## Mobile numbers — `ir_mobile`

Accepted examples include:

```text
09121234567
9121234567
+989121234567
989121234567
00989121234567
۰۹۱۲۱۲۳۴۵۶۷
```

Spaces, parentheses, and hyphens are ignored for phone validation. After normalization the value must be an Iranian `09` mobile number with 11 domestic digits.

This is structural validation. It does not check the operator, allocation status, SIM ownership, or whether the number is active.

## Landlines — `ir_landline`

Examples:

```text
02112345678
2112345678
+982112345678
00982112345678
۰۲۱۱۲۳۴۵۶۷۸
```

After international-prefix normalization, FormGuard expects the domestic structure `0` + area code + subscriber number and intentionally excludes `09...` mobile numbers.

Area-code allocation and line existence are not queried.

Use `ir_phone` when a field may contain either an Iranian mobile or landline number.

## Natural-person national code — `ir_national_code`

`ir_national_code` validates a 10-digit Iranian national code (کد ملی) using its check digit.

Examples used by the test suite:

```text
0013542419
3240175800
۰۰۱۳۵۴۲۴۱۹
```

Repeated placeholder values such as `1111111111` are rejected even if a naive checksum implementation would accept them.

A mathematically valid national code does not prove that the code has been issued or belongs to the user. Identity-sensitive workflows must verify it with the appropriate authoritative service.

## Legal-entity national ID — `ir_legal_id`

`ir_legal_id` validates the 11-digit national identifier used for Iranian legal entities (شناسه ملی اشخاص حقوقی) and its check digit.

Examples:

```text
10380284790
14007650912
۱۴۰۰۷۶۵۰۹۱۲
```

`ir_company_id` is an alias of `ir_legal_id` for developer convenience.

As with national codes, checksum validity is not proof that an entity currently exists or is active.

## Postal code — `ir_postal_code`

Iranian postal codes are validated as 10-digit values. Persian and Arabic-Indic digits are accepted and normalized for checking.

Example:

```text
1619735744
۱۶۱۹۷۳۵۷۴۴
```

The rule rejects wrong lengths and obvious repeated-digit placeholders. It does not query GNAF or verify that the postal code is assigned to a real address.

If address existence matters, validate locally first and then call the appropriate postal/address service separately.

## Sheba / Iranian IBAN — `ir_sheba`

Iranian Sheba values must contain the `IR` country prefix and 24 digits. FormGuard validates the checksum using the IBAN MOD-97 procedure.

Accepted examples:

```text
IR062960000000100324200001
ir06 2960 0000 0010 0324 2000 01
IR۰۶۲۹۶۰۰۰۰۰۰۰۱۰۰۳۲۴۲۰۰۰۰۱
```

Spaces and hyphens are ignored and the `IR` prefix is case-insensitive.

`ir_iban` is an alias of `ir_sheba`.

A passing MOD-97 check only proves mathematical consistency. It does not prove that the bank account exists, is open, or belongs to a specific person.

## Iranian bank card — `ir_bank_card`

`ir_bank_card` validates a 16-digit card number using the card check-digit algorithm. Spaces and hyphens are allowed for display-formatted input.

Examples:

```text
6274129005473742
6274-1290-0547-3742
۶۲۷۴ ۱۲۹۰ ۰۵۴۷ ۳۷۴۲
```

`ir_bank_card_number` is an alias of `ir_bank_card`.

Obvious repeated placeholders such as `0000000000000000` are rejected.

Checksum validation does not prove that a card was issued, is active, or belongs to the user. Never use this rule as a substitute for a payment gateway or bank-side verification.

## Example Iranian registration form

```php
<?php

use RoseShayan\FormGuard\Validator;

$validator = Validator::make($_POST, [
    'full_name' => 'required|string|min:2|max:100',
    'mobile' => 'required|ir_mobile',
    'national_code' => 'required|ir_national_code',
    'postal_code' => 'nullable|ir_postal_code',
    'phone' => 'nullable|ir_phone',
    'sheba' => 'nullable|ir_sheba',
    'card_number' => 'nullable|ir_bank_card',
]);

if ($validator->fails()) {
    $errors = $validator->errors();
}
```

For a company form:

```php
$validator = Validator::make($_POST, [
    'company_name' => 'required|string|max:200',
    'national_id' => 'required|ir_legal_id',
    'phone' => 'required|ir_phone',
    'postal_code' => 'required|ir_postal_code',
    'sheba' => 'nullable|ir_sheba',
]);
```

## Security boundary

Local validators are ideal for rejecting typos early and avoiding unnecessary API requests. They are not authoritative verification systems.

Use server-side authoritative checks when your business process requires any of the following:

- proving a national code belongs to a specific person;
- proving a mobile number belongs to that person;
- confirming a company is registered and active;
- resolving a postal code to an address;
- checking a bank card or Sheba owner;
- confirming a financial account is active.

FormGuard should be the first validation layer, not the final identity-verification layer.
