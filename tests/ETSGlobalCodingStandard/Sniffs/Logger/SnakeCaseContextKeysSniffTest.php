<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Sniffs\Logger;

use SlevomatCodingStandard\Sniffs\TestCase;

class SnakeCaseContextKeysSniffTest extends TestCase
{
    public function testNoErrors(): void
    {
        $report = self::checkFile(__DIR__ . '/data/snakeCaseContextKeysSniffNoErrors.php');
        self::assertNoSniffErrorInFile($report);
    }

    public function testErrors(): void
    {
        $report = self::checkFile(__DIR__ . '/data/snakeCaseContextKeysSniffErrors.php');

        self::assertSame(4, $report->getErrorCount());

        self::assertSniffError($report, 9, SnakeCaseContextKeysSniff::CODE_CONTEXT_KEY_NOT_SET);

        self::assertSniffError($report, 12, SnakeCaseContextKeysSniff::CODE_NO_SNAKE_CASE_CONTEXT_KEY);
        self::assertSniffError($report, 13, SnakeCaseContextKeysSniff::CODE_NO_SNAKE_CASE_CONTEXT_KEY);
        self::assertSniffError($report, 17, SnakeCaseContextKeysSniff::CODE_NO_SNAKE_CASE_CONTEXT_KEY);
    }
}
