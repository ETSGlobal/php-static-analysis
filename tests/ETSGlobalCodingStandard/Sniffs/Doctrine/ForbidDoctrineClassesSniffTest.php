<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Sniffs\Doctrine;

use SlevomatCodingStandard\Sniffs\TestCase;

class ForbidDoctrineClassesSniffTest extends TestCase
{
    public function testNoErrors(): void
    {
        $report = self::checkFile(__DIR__ . '/data/forbidDoctrineClassesNoErrors.php');
        self::assertNoSniffErrorInFile($report);
    }

    public function testErrors(): void
    {
        $report = self::checkFile(__DIR__ . '/data/forbidDoctrineClassesErrors.php');

        self::assertSame(6, $report->getErrorCount());

        self::assertSniffError($report, 16, ForbidDoctrineClassesSniff::FORBIDDEN_CLASS_FOUND);
        self::assertSniffError($report, 17, ForbidDoctrineClassesSniff::FORBIDDEN_CLASS_FOUND);
        self::assertSniffError($report, 18, ForbidDoctrineClassesSniff::FORBIDDEN_CLASS_FOUND);
        self::assertSniffError($report, 19, ForbidDoctrineClassesSniff::FORBIDDEN_CLASS_FOUND);
        self::assertSniffError($report, 20, ForbidDoctrineClassesSniff::FORBIDDEN_CLASS_FOUND);
        self::assertSniffError($report, 24, ForbidDoctrineClassesSniff::FORBIDDEN_CLASS_FOUND);
    }
}
