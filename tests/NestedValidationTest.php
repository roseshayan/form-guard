<?php
declare(strict_types=1);

namespace RoseShayan\FormGuard\Tests;

use PHPUnit\Framework\TestCase;
use RoseShayan\FormGuard\Validator;

final class NestedValidationTest extends TestCase
{
    public function testDotNotationValidatesNestedData(): void
    {
        $validator = Validator::make(
            ['user' => ['email' => 'person@example.com']],
            ['user.email' => 'required|email']
        );

        self::assertTrue($validator->passes());
    }

    public function testWildcardRulesValidateEveryExistingArrayItem(): void
    {
        $validator = Validator::make(
            [
                'users' => [
                    ['email' => 'first@example.com'],
                    ['email' => 'invalid'],
                    [],
                ],
            ],
            ['users.*.email' => 'required|email']
        );

        self::assertTrue($validator->fails());
        self::assertArrayHasKey('users.1.email', $validator->errors());
        self::assertArrayHasKey('users.2.email', $validator->errors());
    }

    public function testWildcardSpecificCustomMessageAndAttribute(): void
    {
        $validator = Validator::make(
            ['items' => [['sku' => '']]],
            ['items.*.sku' => 'required'],
            ['items.*.sku.required' => ':attribute is required for every item.'],
            ['items.*.sku' => 'SKU']
        );

        self::assertSame('SKU is required for every item.', $validator->firstError('items.0.sku'));
    }

    public function testValidatedReturnsOnlyWhitelistedNestedFields(): void
    {
        $validator = Validator::make(
            [
                'user' => [
                    'name' => 'Shayan',
                    'email' => 'person@example.com',
                    'is_admin' => true,
                ],
                'debug' => true,
            ],
            [
                'user.name' => 'required|string',
                'user.email' => 'required|email',
            ]
        );

        self::assertSame(
            [
                'user' => [
                    'name' => 'Shayan',
                    'email' => 'person@example.com',
                ],
            ],
            $validator->validated()
        );
    }

    public function testValidatedRebuildsWildcardArrays(): void
    {
        $validator = Validator::make(
            [
                'items' => [
                    ['name' => 'One', 'secret' => 'a'],
                    ['name' => 'Two', 'secret' => 'b'],
                ],
            ],
            ['items.*.name' => 'required|string']
        );

        self::assertSame(
            [
                'items' => [
                    ['name' => 'One'],
                    ['name' => 'Two'],
                ],
            ],
            $validator->validated()
        );
    }
}
