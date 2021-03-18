<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Sniffs\Doctrine;

use SlevomatCodingStandard\Sniffs\TestCase;

class ForbidRepositoryInjectionSniffTest extends TestCase
{
    public function testNoErrors(): void
    {
        $report = self::checkFile(__DIR__ . '/data/forbidRepositoryInjectionNoErrors.php');
        self::assertNoSniffErrorInFile($report);
    }

    public function testErrors(): void
    {
        $report = self::checkFile(__DIR__ . '/data/forbidRepositoryInjectionErrors.php');

        self::assertSame(3, $report->getErrorCount());

        self::assertSniffError($report, 8, ForbidRepositoryInjectionSniff::FORBIDDEN_REPOSITORY_PROPERTY);
        self::assertSniffError($report, 11, ForbidRepositoryInjectionSniff::FORBIDDEN_REPOSITORY_PROPERTY);
        self::assertSniffError($report, 16, ForbidRepositoryInjectionSniff::FORBIDDEN_REPOSITORY_INJECTION);
    }
}
