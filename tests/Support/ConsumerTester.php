<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Tests\Support\_generated\ConsumerTesterActions;

class ConsumerTester extends AbstractTester
{
    use ConsumerTesterActions;

    public function runConsumer(string $consumerName, string $queue, string $message): string
    {
        $this->pushToExchange('', $message, $queue);

        $command = sprintf('swarrot:consume:%s', $consumerName);

        return $this->runSymfonyConsoleCommand($command, ['queue' => $queue, '--max-messages' => 1]);
    }
}
