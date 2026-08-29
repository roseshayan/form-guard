<?php
declare(strict_types=1);

namespace RoseShayan\FormGuard\Tests;

use PHPUnit\Framework\TestCase;
use RoseShayan\FormGuard\Validator;

final class BuiltInRulesTest extends TestCase
{
    public function testSizeAndRangeRulesUseCorrectSemanticsForStringsArraysAndNumbers(): void
    {
        $validator = Validator::make(
            [
                'title' => 'شایان',
                'tags' => ['php', 'validation'],
                'score' => '18',
            ],
            [
                'title' => 'string|min:5|max:5|between:5,5|size:5',
                'tags' => 'array|min:2|max:2|between:2,2|size:2',
                'score' => 'numeric|min:18|max:18|between:18,18|size:18|min_value:18|max_value:18',
            ]
        );

        self::assertTrue($validator->passes(), json_encode($validator->errors()) ?: 'validation failed');
    }

    public function testModernUuidVersionsAreAcceptedAndReservedVersionIsRejected(): void
    {
        $valid = Validator::make(
            ['id' => '017f22e2-79b0-7cc3-98c4-dc0c0c07398f'],
            ['id' => 'required|uuid']
        );

        $reserved = Validator::make(
            ['id' => '017f22e2-79b0-9cc3-98c4-dc0c0c07398f'],
            ['id' => 'required|uuid']
        );

        self::assertTrue($valid->passes());
        self::assertTrue($reserved->fails());
    }

    public function testTypeAndFormatRulesAcceptRepresentativeValidValues(): void
    {
        $validator = Validator::make(
            [
                'string' => 'hello',
                'integer' => '-42',
                'numeric' => '42.5',
                'boolean' => 'false',
                'array' => ['one'],
                'email' => 'person@example.com',
                'url' => 'https://example.com/path?x=1',
                'ip' => '2001:db8::1',
                'ipv4' => '192.0.2.10',
                'ipv6' => '2001:db8::2',
                'alpha' => 'شایان',
                'alpha_num' => 'Shayan123',
                'alpha_dash' => 'form_guard-2',
                'json' => '{"ok":true}',
                'date' => '2026-08-29',
                'formatted_date' => '2026-08-29',
            ],
            [
                'string' => 'string',
                'integer' => 'integer',
                'numeric' => 'numeric',
                'boolean' => 'boolean',
                'array' => 'array',
                'email' => 'email',
                'url' => 'url',
                'ip' => 'ip',
                'ipv4' => 'ipv4',
                'ipv6' => 'ipv6',
                'alpha' => 'alpha',
                'alpha_num' => 'alpha_num',
                'alpha_dash' => 'alpha_dash',
                'json' => 'json',
                'date' => 'date',
                'formatted_date' => 'date_format:Y-m-d',
            ]
        );

        self::assertTrue($validator->passes(), json_encode($validator->errors()) ?: 'validation failed');
    }

    public function testChoiceComparisonAndStringRules(): void
    {
        $validator = Validator::make(
            [
                'role' => 'admin',
                'status' => 'active',
                'password' => 'secret',
                'password_copy' => 'secret',
                'different_value' => 'other',
                'code' => 'FG-2026-END',
                'terms' => 'yes',
                'marketing' => 'no',
            ],
            [
                'role' => 'in:admin,editor',
                'status' => 'not_in:blocked,deleted',
                'password_copy' => 'same:password|matches:password',
                'different_value' => 'different:password',
                'code' => 'starts_with:FG-|ends_with:-END|contains:2026',
                'terms' => 'accepted',
                'marketing' => 'declined',
            ]
        );

        self::assertTrue($validator->passes(), json_encode($validator->errors()) ?: 'validation failed');
    }

    public function testExplicitStringLengthRules(): void
    {
        $validator = Validator::make(
            ['code' => 'ABCDE'],
            ['code' => 'min_length:5|max_length:5|length:5']
        );

        self::assertTrue($validator->passes());
    }

    public function testConditionalRequiredRulesFailWhenTheirConditionRequiresAValue(): void
    {
        $with = Validator::make(
            ['email' => 'person@example.com', 'phone' => ''],
            ['phone' => 'required_with:email']
        );

        $without = Validator::make(
            ['phone' => ''],
            ['phone' => 'required_without:email']
        );

        $if = Validator::make(
            ['type' => 'company', 'company_name' => ''],
            ['company_name' => 'required_if:type,company']
        );

        self::assertTrue($with->fails());
        self::assertTrue($without->fails());
        self::assertTrue($if->fails());
    }

    public function testPresentAndFilledHaveDifferentSemantics(): void
    {
        $present = Validator::make(
            ['token' => ''],
            ['token' => 'present']
        );

        $filled = Validator::make(
            ['token' => ''],
            ['token' => 'filled']
        );

        self::assertTrue($present->passes());
        self::assertTrue($filled->fails());
    }

    public function testRepresentativeInvalidFormatsFail(): void
    {
        $validator = Validator::make(
            [
                'email' => 'bad',
                'ipv4' => '999.1.1.1',
                'json' => '{bad}',
                'date' => 'not-a-date',
                'alpha' => 'abc123',
            ],
            [
                'email' => 'email',
                'ipv4' => 'ipv4',
                'json' => 'json',
                'date' => 'date',
                'alpha' => 'alpha',
            ]
        );

        self::assertTrue($validator->fails());
        self::assertSame(
            ['email', 'ipv4', 'json', 'date', 'alpha'],
            array_keys($validator->errors())
        );
    }
}
