<?php

declare(strict_types=1);

namespace Consumer;

use App\Document\EInvoice;
use App\Tests\Support\ConsumerTester;
use Codeception\Util\HttpCode;

use function Mcustiel\Phiremock\Client\getRequest;
use function Mcustiel\Phiremock\Client\isEqualTo;
use function Mcustiel\Phiremock\Client\on;
use function Mcustiel\Phiremock\Client\postRequest;
use function Mcustiel\Phiremock\Client\respond;

class TestCest
{
    public function _before(ConsumerTester $I): void
    {
        $I->listenRoutingKeys(['test.test.*.test']);
    }

    public function _after(ConsumerTester $I): void
    {
        $I->purgeAllQueues();
    }

    public function testOk(ConsumerTester $I): void
    {
        $message = [
            'data' => [
                'id' => 1,
            ],
        ];

        $I->runConsumer('test', 'queue_test', \json_encode($message));

        $I->dontExpectRequestsInRemoteService();

        $I->dontSeeMessageWithRoutingKey('test.test.*.test');

        $I->seeQueueIsEmpty('queue_test');
    }
}
