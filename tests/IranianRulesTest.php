<?php
declare(strict_types=1);

namespace RoseShayan\FormGuard\Tests;

use PHPUnit\Framework\TestCase;
use RoseShayan\FormGuard\Validator;

final class IranianRulesTest extends TestCase
{
    public function testIranianMobileAcceptsDomesticInternationalAndPersianDigits(): void
    {
        foreach (['09121234567', '+989121234567', '00989121234567', '9121234567', '۰۹۱۲۱۲۳۴۵۶۷'] as $mobile) {
            $validator = Validator::make(['mobile' => $mobile], ['mobile' => 'required|ir_mobile']);

            self::assertTrue($validator->passes(), $mobile);
        }

        self::assertTrue(
            Validator::make(['mobile' => '02112345678'], ['mobile' => 'ir_mobile'])->fails()
        );
    }

    public function testIranianLandlineAndGenericPhoneRules(): void
    {
        foreach (['02112345678', '+982112345678', '00982112345678', '2112345678', '۰۲۱۱۲۳۴۵۶۷۸'] as $phone) {
            self::assertTrue(
                Validator::make(['phone' => $phone], ['phone' => 'ir_landline'])->passes(),
                $phone
            );
        }

        self::assertTrue(
            Validator::make(['phone' => '09121234567'], ['phone' => 'ir_landline'])->fails()
        );
        self::assertTrue(
            Validator::make(['phone' => '09121234567'], ['phone' => 'ir_phone'])->passes()
        );
        self::assertTrue(
            Validator::make(['phone' => '02112345678'], ['phone' => 'ir_phone'])->passes()
        );
    }

    public function testIranianNationalCodeChecksumAndPersianDigits(): void
    {
        foreach (['0013542419', '3240175800', '۰۰۱۳۵۴۲۴۱۹'] as $nationalCode) {
            self::assertTrue(
                Validator::make(['code' => $nationalCode], ['code' => 'required|ir_national_code'])->passes(),
                $nationalCode
            );
        }

        foreach (['0013542418', '1111111111', '123456789'] as $nationalCode) {
            self::assertTrue(
                Validator::make(['code' => $nationalCode], ['code' => 'ir_national_code'])->fails(),
                $nationalCode
            );
        }
    }

    public function testIranianLegalIdAndCompanyIdAlias(): void
    {
        foreach (['10380284790', '14007650912', '۱۴۰۰۷۶۵۰۹۱۲'] as $legalId) {
            self::assertTrue(
                Validator::make(['id' => $legalId], ['id' => 'ir_legal_id'])->passes(),
                $legalId
            );
        }

        self::assertTrue(
            Validator::make(['id' => '10380284790'], ['id' => 'ir_company_id'])->passes()
        );
        self::assertTrue(
            Validator::make(['id' => '10380284791'], ['id' => 'ir_legal_id'])->fails()
        );
    }

    public function testIranianShebaUsesMod97AndSupportsFormattedInput(): void
    {
        $valid = [
            'IR062960000000100324200001',
            'ir06 2960 0000 0010 0324 2000 01',
            'IR۰۶۲۹۶۰۰۰۰۰۰۰۱۰۰۳۲۴۲۰۰۰۰۱',
        ];

        foreach ($valid as $sheba) {
            self::assertTrue(
                Validator::make(['sheba' => $sheba], ['sheba' => 'ir_sheba'])->passes(),
                $sheba
            );
        }

        self::assertTrue(
            Validator::make(
                ['sheba' => 'IR062960000000100324200001'],
                ['sheba' => 'ir_iban']
            )->passes()
        );
        self::assertTrue(
            Validator::make(
                ['sheba' => 'IR072960000000100324200001'],
                ['sheba' => 'ir_sheba']
            )->fails()
        );
    }

    public function testIranianBankCardChecksumAliasesAndSeparators(): void
    {
        foreach (['6274129005473742', '6274-1290-0547-3742', '۶۲۷۴ ۱۲۹۰ ۰۵۴۷ ۳۷۴۲'] as $card) {
            self::assertTrue(
                Validator::make(['card' => $card], ['card' => 'ir_bank_card'])->passes(),
                $card
            );
        }

        self::assertTrue(
            Validator::make(
                ['card' => '6274129005473742'],
                ['card' => 'ir_bank_card_number']
            )->passes()
        );
        self::assertTrue(
            Validator::make(['card' => '6274129005473743'], ['card' => 'ir_bank_card'])->fails()
        );
        self::assertTrue(
            Validator::make(['card' => '0000000000000000'], ['card' => 'ir_bank_card'])->fails()
        );
    }

    public function testIranianPostalCodeIsTenDigitsAndAcceptsPersianDigits(): void
    {
        foreach (['1619735744', '۱۶۱۹۷۳۵۷۴۴'] as $postalCode) {
            self::assertTrue(
                Validator::make(['postal_code' => $postalCode], ['postal_code' => 'ir_postal_code'])->passes(),
                $postalCode
            );
        }

        foreach (['161973574', '1111111111', '16197-35744'] as $postalCode) {
            self::assertTrue(
                Validator::make(['postal_code' => $postalCode], ['postal_code' => 'ir_postal_code'])->fails(),
                $postalCode
            );
        }
    }

    public function testIranianRulesRejectNonScalarInput(): void
    {
        $validator = Validator::make(
            ['code' => ['0013542419']],
            ['code' => 'ir_national_code']
        );

        self::assertTrue($validator->fails());
    }
}
