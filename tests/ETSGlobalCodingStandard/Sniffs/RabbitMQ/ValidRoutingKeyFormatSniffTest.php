<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Sniffs\RabbitMQ;

use SlevomatCodingStandard\Sniffs\TestCase;

class ValidRoutingKeyFormatSniffTest extends TestCase
{
    public function testNoErrors(): void
    {
        $report = self::checkFile(__DIR__ . '/data/validRoutingKeyFormatSniffNoErrors.php');
        self::assertNoSniffErrorInFile($report);
    }

    public function testErrors(): void
    {
        $report = self::checkFile(__DIR__ . '/data/validRoutingKeyFormatSniffErrors.php');

        self::assertSame(9, $report->getErrorCount());

        self::assertSniffError($report, 7, ValidRoutingKeyFormatSniff::CODE_MISSING_ROUTING_KEY);
        self::assertSniffError($report, 8, ValidRoutingKeyFormatSniff::CODE_MISSING_ROUTING_KEY);
        self::assertSniffError($report, 9, ValidRoutingKeyFormatSniff::CODE_MISSING_ROUTING_KEY);
        self::assertSniffError($report, 10, ValidRoutingKeyFormatSniff::CODE_MISSING_ROUTING_KEY);
        self::assertSniffError($report, 12, ValidRoutingKeyFormatSniff::CODE_INVALID_ROUTING_KEY_FORMAT);
        self::assertSniffError($report, 13, ValidRoutingKeyFormatSniff::CODE_INVALID_ROUTING_KEY_FORMAT);
        self::assertSniffError($report, 14, ValidRoutingKeyFormatSniff::CODE_INVALID_ROUTING_KEY_FORMAT);
        self::assertSniffError($report, 15, ValidRoutingKeyFormatSniff::CODE_INVALID_ROUTING_KEY_FORMAT);
        self::assertSniffError($report, 18, ValidRoutingKeyFormatSniff::CODE_INVALID_ROUTING_KEY_FORMAT);
    }
}
