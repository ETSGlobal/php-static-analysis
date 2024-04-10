<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Helpers;

use PHPUnit\Framework\TestCase;

class WildcardHelperTest extends TestCase
{
    public static function provideTestcases(): array
    {
        return [
            // Match
            ['App\Repository\*', 'App\Repository\FooRepository', true],
            ['App\Repository\*', 'App\Repository\RepositoryGetter', true],
            ['App\Repository\*\*', 'App\Repository\Foo\Foo', true],
            ['App\Repository\*\*Repository', 'App\Repository\Foo\FooRepository', true],
            ['App\Domain\*\Repository\*', 'App\Domain\Foo\Repository\Foo', true],
            ['App\*Bundle\Repository\*Repository', 'App\FooBundle\Repository\FooRepository', true],
            ['Tests\*', 'Tests\Repository\FooRepository', true],

            // Don't match
            ['App\Repository\*Repository', 'App\Repository\RepositoryGetter', false],
            ['App\Domain\*', 'App\Repository\FooRepository', false],
            ['*Repository', 'App\Repository\Foo', false],
            ['App\Domain\*\Repository\*', 'App\Domain\Foo\FooRepository', false],
        ];
    }

    /**
     * @dataProvider provideTestcases
     */
    public function testWildcard(string $pattern, string $subject, bool $expected): void
    {
        self::assertSame($expected, WildcardHelper::match($pattern, $subject));
    }
}
