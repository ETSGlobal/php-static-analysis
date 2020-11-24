<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Sniffs\RabbitMQ;

use SlevomatCodingStandard\Sniffs\TestCase;

class ValidMessageStructureSniffTest extends TestCase
{
    public function testNoErrors(): void
    {
        $report = self::checkFile(__DIR__ . '/data/validMessageStructureSniffNoErrors.php');
        self::assertNoSniffErrorInFile($report);
    }

    public function testErrors(): void
    {
        $report = self::checkFile(__DIR__ . '/data/validMessageStructureSniffErrors.php');

        self::assertSame(3, $report->getErrorCount());

        self::assertSniffError($report, 9, ValidMessageStructureSniff::CODE_INVALID_STRUCTURE);
        self::assertSniffError($report, 14, ValidMessageStructureSniff::CODE_INVALID_STRUCTURE);
        self::assertSniffError($report, 22, ValidMessageStructureSniff::CODE_INVALID_STRUCTURE);
    }
}
