<?php

declare(strict_types=1);

namespace ETSGlobal\Codeception\Support;

use Codeception\Actor;
use Codeception\PHPUnit\Constraint\JsonContains;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\Assert;
use Symfony\Component\Cache\Adapter\AdapterInterface;

use function Mcustiel\Phiremock\Client\contains;
use function Mcustiel\Phiremock\Client\getRequest;
use function Mcustiel\Phiremock\Client\isEqualTo;
use function Mcustiel\Phiremock\Client\on;
use function Mcustiel\Phiremock\Client\postRequest;
use function Mcustiel\Phiremock\Client\respond;

abstract class AbstractTester extends Actor
{
    private const int USLEEP_VALUE = 100000;

    private const string QUEUE_PREFIX = 'test_';

    abstract public function getExchange(): string;

    public function listenRoutingKeys(array $routingKeys, ?string $exchange = null): void
    {
        foreach ($routingKeys as $routingKey) {
            if (!is_string($routingKey)) {
                continue;
            }

            $queueName = self::getQueue($routingKey);

            $this->declareQueue($queueName);

            $this->bindQueueToExchange($queueName, $exchange ?? $this->getExchange(), $routingKey);

            $this->scheduleQueueCleanup($queueName);

            $this->purgeQueue($queueName);
        }
    }

    public function seeMessageWithRoutingKeyContainsText(string $routingKey, string $text): void
    {
        usleep(self::USLEEP_VALUE);

        $this->seeMessageInQueueContainsText(self::getQueue($routingKey), $text);
    }

    public function dontSeeMessageWithRoutingKey(string $routingKey): void
    {
        usleep(self::USLEEP_VALUE);

        $this->seeQueueIsEmpty(self::getQueue($routingKey));
    }

    public function seeMessageWithRoutingKeyContainsJson(string $routingKey, array $json): void
    {
        usleep(self::USLEEP_VALUE);

        $message = $this->grabMessageFromQueue(self::getQueue($routingKey));

        if (!$message instanceof AMQPMessage) {
            Assert::fail('Message was not received');
        }

        Assert::assertThat($message->getBody(), new JsonContains($json));
    }

    public function initializeMongoDb(): void
    {
        try {
            $this->runSymfonyConsoleCommand('doctrine:mongodb:schema:create');
        } catch (\Throwable $e) {
            // Don't fail if db already exists
        }
    }

    public function runConsumer(
        string $consumerName,
        string $queue,
        array $message,
        array $messageProperty = [],
        int $maxMessage = 1,
        int $expectedExitCode = 0,
    ): string {
        $this->pushToExchange('', new AMQPMessage(json_encode($message), $messageProperty), $queue);

        $command = sprintf('swarrot:consume:%s', $consumerName);

        return $this->runSymfonyConsoleCommand(
            $command,
            ['queue' => $queue, '--max-messages' => $maxMessage, [], $expectedExitCode],
        );
    }

    public function haveEmoLogin(string $emoUrl): void
    {
        $loginCheckRequest = postRequest()->andUrl(isEqualTo('/auth/ecom/login_check'));

        $this->expectARequestToRemoteServiceWithAResponse(
            on($loginCheckRequest)->then(
                respond(200)
                    ->andBody('{OK}')
                    ->andHeader('set-cookie', 'cookie')
                    ->andHeader('location', $emoUrl . '/auth'),
            ),
        );

        $loginRequest = getRequest()->andUrl(isEqualTo('/emo/auth'));

        $this->expectARequestToRemoteServiceWithAResponse(
            on($loginRequest)->then(respond(200)->andBody('{"token":"token"}')),
        );
    }

    public function seeEmoLogin(string $user, string $password): void
    {
        $loginCheckRequest = postRequest()->andUrl(isEqualTo('/auth/ecom/login_check'));

        $this->seeRemoteServiceReceived(
            1,
            $loginCheckRequest->andBody(contains(sprintf('_username=%s&_password=%s', $user, $password))),
        );

        $loginRequest = getRequest()->andUrl(isEqualTo('/emo/auth'));

        $this->seeRemoteServiceReceived(1, $loginRequest->andHeader('Cookie', isEqualTo('cookie')));

        /** @var ?AdapterInterface $emoAuthenticationCache */
        $emoAuthenticationCache = $this->grabService('emo_authentication.cache');

        if (!$emoAuthenticationCache) {
            return;
        }

        $emoAuthenticationCache->clear();
    }

    private static function getQueue(string $routingKey): string
    {
        return self::QUEUE_PREFIX . $routingKey;
    }
}
