<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Sniffs\RabbitMQ;

use SlevomatCodingStandard\Sniffs\TestCase;

class ValidPublisherMethodNameSniffTest extends TestCase
{
    public function testNoErrors(): void
    {
        $report = self::checkFile(__DIR__ . '/data/validPublisherMethodNameSniffNoErrors.php');
        self::assertNoSniffErrorInFile($report);
    }

    public function testErrors(): void
    {
        $report = self::checkFile(__DIR__ . '/data/validPublisherMethodNameSniffErrors.php');

        self::assertSame(2, $report->getErrorCount());

        self::assertSniffError($report, 5, ValidPublisherMethodNameSniff::CODE_INVALID_PUBLISHER_METHOD_NAME);
        self::assertSniffError($report, 7, ValidPublisherMethodNameSniff::CODE_INVALID_PUBLISHER_METHOD_NAME);
    }
}
