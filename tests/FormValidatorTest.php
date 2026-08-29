<?php
declare(strict_types=1);

namespace RoseShayan\FormGuard\Tests;

use PHPUnit\Framework\TestCase;
use RoseShayan\FormGuard\InvalidRuleException;
use RoseShayan\FormGuard\ValidationException;
use RoseShayan\FormGuard\Validator;

final class FormValidatorTest extends TestCase
{
    public function testRequiredAcceptsZeroValues(): void
    {
        $validator = Validator::make(
            ['string_zero' => '0', 'integer_zero' => 0],
            ['string_zero' => 'required', 'integer_zero' => 'required']
        );

        self::assertTrue($validator->passes());
    }

    public function testBasicRulesAndErrorAccessors(): void
    {
        $validator = Validator::make(
            ['name' => 'Shayan', 'email' => 'not-an-email'],
            ['name' => 'required|string|min:3', 'email' => 'required|email']
        );

        self::assertTrue($validator->fails());
        self::assertArrayHasKey('email', $validator->errors());
        self::assertSame($validator->errors()['email'], $validator->firstError('email'));
        self::assertNotEmpty($validator->errorBag()->all());
    }

    public function testOptionalBlankFieldsAreIgnored(): void
    {
        $validator = Validator::make(
            ['website' => '', 'bio' => null],
            ['website' => 'url', 'bio' => 'string|max:100']
        );

        self::assertTrue($validator->passes());
    }

    public function testNullableAndSometimesDirectives(): void
    {
        $validator = Validator::make(
            ['nickname' => null],
            [
                'nickname' => 'nullable|string|min:3',
                'missing' => 'sometimes|required|email',
            ]
        );

        self::assertTrue($validator->passes());
    }

    public function testBailStopsAfterFirstFailure(): void
    {
        $validator = Validator::make(
            ['email' => 'x'],
            ['email' => 'bail|email|min:10']
        );

        self::assertCount(1, $validator->errorBag()->get('email'));
    }

    public function testNumericRulesUseNumericSemanticsForHtmlFormValues(): void
    {
        $validator = Validator::make(
            ['age' => '18'],
            ['age' => 'required|integer|min:18|max:65']
        );

        self::assertTrue($validator->passes());
    }

    public function testStringLengthRulesSupportUnicode(): void
    {
        $validator = Validator::make(
            ['name' => 'شایان'],
            ['name' => 'required|string|min_length:5|max_length:5']
        );

        self::assertTrue($validator->passes());
    }

    public function testCrossFieldRules(): void
    {
        $validator = Validator::make(
            [
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'role' => 'company',
                'company_name' => 'Acme',
            ],
            [
                'password' => 'required|confirmed',
                'company_name' => 'required_if:role,company',
            ]
        );

        self::assertTrue($validator->passes());
    }

    public function testCustomMessagesAndAttributeNames(): void
    {
        $validator = Validator::make(
            ['email' => 'bad'],
            ['email' => 'required|email'],
            ['email.email' => ':attribute is not acceptable.'],
            ['email' => 'Email address']
        );

        self::assertSame('Email address is not acceptable.', $validator->firstError('email'));
    }

    public function testInlineClosureRule(): void
    {
        $validator = Validator::make(
            ['slug' => 'Admin'],
            [
                'slug' => [
                    'required',
                    static function (string $field, mixed $value, array $data): bool|string {
                        if (!is_string($value)) {
                            return 'The :attribute must be a string.';
                        }

                        return $value === strtolower($value)
                            ? true
                            : 'The :attribute must be lowercase.';
                    },
                ],
            ]
        );

        self::assertTrue($validator->fails());
        self::assertSame('The slug must be lowercase.', $validator->firstError('slug'));
    }

    public function testRegexRuleSupportsArraySyntaxWhenPatternContainsPipe(): void
    {
        $validator = Validator::make(
            ['code' => 'ABC'],
            ['code' => ['required', 'regex:/^(ABC|XYZ)$/']]
        );

        self::assertTrue($validator->passes());
    }

    public function testIranianMobileRuleSupportsCommonFormats(): void
    {
        foreach (['09121234567', '+989121234567', '989121234567', '00989121234567'] as $mobile) {
            self::assertTrue(
                Validator::make(['mobile' => $mobile], ['mobile' => 'required|ir_mobile'])->passes(),
                $mobile
            );
        }
    }

    public function testUnknownRuleThrowsInsteadOfSilentlyPassing(): void
    {
        $this->expectException(InvalidRuleException::class);

        Validator::make(['value' => 'x'], ['value' => 'required|does_not_exist']);
    }

    public function testMalformedRuleThrowsConfigurationException(): void
    {
        $this->expectException(InvalidRuleException::class);

        Validator::make(['value' => 'x'], ['value' => 'min:not-a-number']);
    }

    public function testValidatedDataCannotBeReadAfterFailedValidation(): void
    {
        $validator = Validator::make(['email' => 'bad'], ['email' => 'required|email']);

        $this->expectException(ValidationException::class);
        $validator->validated();
    }
}
