<?php
declare(strict_types=1);

namespace RoseShayan\FormGuard\Rules;

final class IranianRules
{
    /** @var list<string> */
    private const NAMES = [
        'ir_mobile',
        'ir_landline',
        'ir_phone',
        'ir_national_code',
        'ir_legal_id',
        'ir_company_id',
        'ir_postal_code',
        'ir_sheba',
        'ir_iban',
        'ir_bank_card',
        'ir_bank_card_number',
    ];

    /** @var array<string, string> */
    private const DIGIT_MAP = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    private function __construct()
    {
    }

    public static function exists(string $rule): bool
    {
        return in_array($rule, self::NAMES, true);
    }

    public static function validate(string $rule, mixed $value): bool
    {
        return match ($rule) {
            'ir_mobile' => self::mobile($value),
            'ir_landline' => self::landline($value),
            'ir_phone' => self::mobile($value) || self::landline($value),
            'ir_national_code' => self::nationalCode($value),
            'ir_legal_id', 'ir_company_id' => self::legalId($value),
            'ir_postal_code' => self::postalCode($value),
            'ir_sheba', 'ir_iban' => self::sheba($value),
            'ir_bank_card', 'ir_bank_card_number' => self::bankCard($value),
            default => false,
        };
    }

    private static function mobile(mixed $value): bool
    {
        $phone = self::normalizePhone($value);

        return $phone !== null && preg_match('/^09\d{9}$/D', $phone) === 1;
    }

    private static function landline(mixed $value): bool
    {
        $phone = self::normalizePhone($value);

        // Structural validation only: 0 + two-digit area code + eight-digit subscriber number.
        // 09... is intentionally reserved for mobile numbers.
        return $phone !== null && preg_match('/^0[1-8]\d{9}$/D', $phone) === 1;
    }

    private static function nationalCode(mixed $value): bool
    {
        $code = self::normalizePlainDigits($value);
        if ($code === null || preg_match('/^\d{10}$/D', $code) !== 1 || self::allDigitsEqual($code)) {
            return false;
        }

        $sum = 0;
        for ($index = 0; $index < 9; $index++) {
            $sum += ((int) $code[$index]) * (10 - $index);
        }

        $remainder = $sum % 11;
        $expectedCheckDigit = $remainder < 2 ? $remainder : 11 - $remainder;

        return $expectedCheckDigit === (int) $code[9];
    }

    private static function legalId(mixed $value): bool
    {
        $id = self::normalizePlainDigits($value);
        if ($id === null || preg_match('/^\d{11}$/D', $id) !== 1 || self::allDigitsEqual($id)) {
            return false;
        }

        $weights = [29, 27, 23, 19, 17, 29, 27, 23, 19, 17];
        $factor = ((int) $id[9]) + 2;
        $sum = 0;

        for ($index = 0; $index < 10; $index++) {
            $sum += (((int) $id[$index]) + $factor) * $weights[$index];
        }

        $remainder = $sum % 11;
        $expectedCheckDigit = $remainder === 10 ? 0 : $remainder;

        return $expectedCheckDigit === (int) $id[10];
    }

    private static function postalCode(mixed $value): bool
    {
        $postalCode = self::normalizePlainDigits($value);

        return $postalCode !== null
            && preg_match('/^\d{10}$/D', $postalCode) === 1
            && !self::allDigitsEqual($postalCode);
    }

    private static function sheba(mixed $value): bool
    {
        $sheba = self::normalizeText($value);
        if ($sheba === null) {
            return false;
        }

        $sheba = strtoupper(self::stripSeparators($sheba));
        if (preg_match('/^IR\d{24}$/D', $sheba) !== 1) {
            return false;
        }

        // ISO 13616 / MOD-97: move IRxx to the end and replace I/R with 18/27.
        $numeric = substr($sheba, 4) . '1827' . substr($sheba, 2, 2);

        return self::mod97($numeric) === 1;
    }

    private static function bankCard(mixed $value): bool
    {
        $card = self::normalizeText($value);
        if ($card === null) {
            return false;
        }

        $card = self::stripSeparators($card);
        if (preg_match('/^\d{16}$/D', $card) !== 1 || self::allDigitsEqual($card)) {
            return false;
        }

        $sum = 0;
        for ($index = 0; $index < 16; $index++) {
            $digit = (int) $card[$index];
            $weighted = $digit * ($index % 2 === 0 ? 2 : 1);
            $sum += $weighted > 9 ? $weighted - 9 : $weighted;
        }

        return $sum % 10 === 0;
    }

    private static function normalizePhone(mixed $value): ?string
    {
        $phone = self::normalizeText($value);
        if ($phone === null) {
            return null;
        }

        $phone = self::stripPhoneSeparators($phone);

        if (str_starts_with($phone, '+98')) {
            $phone = '0' . substr($phone, 3);
        } elseif (str_starts_with($phone, '0098')) {
            $phone = '0' . substr($phone, 4);
        } elseif (str_starts_with($phone, '98')) {
            $phone = '0' . substr($phone, 2);
        } elseif (preg_match('/^[1-9]\d{9}$/D', $phone) === 1) {
            $phone = '0' . $phone;
        }

        return $phone;
    }

    private static function normalizePlainDigits(mixed $value): ?string
    {
        $text = self::normalizeText($value);

        return $text === null ? null : trim($text);
    }

    private static function normalizeText(mixed $value): ?string
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        return strtr((string) $value, self::DIGIT_MAP);
    }

    private static function stripSeparators(string $value): string
    {
        $result = preg_replace('/[\s-]+/u', '', $value);

        return $result ?? $value;
    }

    private static function stripPhoneSeparators(string $value): string
    {
        $result = preg_replace('/[\s()-]+/u', '', $value);

        return $result ?? $value;
    }

    private static function allDigitsEqual(string $digits): bool
    {
        return $digits !== '' && strspn($digits, $digits[0]) === strlen($digits);
    }

    private static function mod97(string $numeric): int
    {
        $remainder = 0;

        $length = strlen($numeric);
        for ($index = 0; $index < $length; $index++) {
            $remainder = (($remainder * 10) + (int) $numeric[$index]) % 97;
        }

        return $remainder;
    }
}
