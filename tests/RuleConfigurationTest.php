<?php
declare(strict_types=1);

namespace RoseShayan\FormGuard\Tests;

use PHPUnit\Framework\TestCase;
use RoseShayan\FormGuard\InvalidRuleException;
use RoseShayan\FormGuard\Validator;

final class RuleConfigurationTest extends TestCase
{
    public function testUnknownRuleThrowsEvenWhenFieldIsMissing(): void
    {
        $this->expectException(InvalidRuleException::class);

        Validator::make([], ['optional_field' => 'does_not_exist']);
    }

    public function testInvalidRegexThrowsConfigurationException(): void
    {
        $this->expectException(InvalidRuleException::class);

        Validator::make(['code' => 'ABC'], ['code' => ['regex:/[invalid/']]);
    }

    public function testRegexContainingPipeWorksInArraySyntax(): void
    {
        $validator = Validator::make(
            ['code' => 'XYZ'],
            ['code' => ['required', 'regex:/^(ABC|XYZ)$/']]
        );

        self::assertTrue($validator->passes());
    }
}
