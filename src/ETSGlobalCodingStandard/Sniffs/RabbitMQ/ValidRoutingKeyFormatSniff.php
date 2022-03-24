<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Sniffs\RabbitMQ;

use ETSGlobalCodingStandard\Helpers\ArgumentHelper;
use ETSGlobalCodingStandard\Helpers\ArrayHelper;
use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Helpers\StringHelper;
use SlevomatCodingStandard\Sniffs\Functions\AbstractLineCall;

/**
 * Checks routing key is correctly formatted.
 *
 * It must follow the naming convention: <app name>.<entity>.<id>.<event>
 * The sniff will check all `$this->publish` method calls, as we have currently
 * no other way to recognize calls to RabbitMQ publishes.
 */
class ValidRoutingKeyFormatSniff extends AbstractLineCall
{
    public const CODE_MISSING_ROUTING_KEY = 'MissingRoutingKey';
    public const CODE_INVALID_ROUTING_KEY_FORMAT = 'InvalidRoutingKeyFormat';

    /** {@inheritDoc} */
    public function process(File $phpcsFile, $stackPointer)
    {
        if (!$this->isCall($phpcsFile, $stackPointer)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPointer - 1]['type'] !== 'T_OBJECT_OPERATOR') {
            return;
        }

        if ($tokens[$stackPointer]['content'] !== 'publish') {
            return;
        }

        if ($tokens[$stackPointer - 2]['type'] !== 'T_VARIABLE' || $tokens[$stackPointer - 2]['content'] !== '$this') {
            return;
        }

        $messagePropertiesPointer = $this->getMessagePropertiesArgumentPointer($phpcsFile, $stackPointer);

        if ($messagePropertiesPointer === null) {
            $phpcsFile->addErrorOnLine(
                'Missing routing_key configuration on publish call.',
                $tokens[$stackPointer]['line'],
                self::CODE_MISSING_ROUTING_KEY,
            );

            return;
        }

        $this->checkMessageProperties($phpcsFile, $messagePropertiesPointer);
    }

    private function checkMessageProperties(File $phpcsFile, int $startPointer): void
    {
        $tokens = $phpcsFile->getTokens();

        if (!ArrayHelper::hasKey($phpcsFile, $startPointer, 'routing_key')) {
            $phpcsFile->addErrorOnLine(
                'Missing routing_key configuration on publish call.',
                $tokens[$startPointer]['line'],
                self::CODE_MISSING_ROUTING_KEY,
            );

            return;
        }

        $routingKeyPointer = ArrayHelper::getValuePointerAtKey($phpcsFile, $startPointer, 'routing_key');
        if (!$routingKeyPointer) {
            return;
        }

        if (!in_array($tokens[$routingKeyPointer]['type'], ['T_CONSTANT_ENCAPSED_STRING', 'T_STRING'], true)) {
            // Unsupported routing key value
            return;
        }

        $routingKey = $this->extractRoutingKeyToken($phpcsFile, $routingKeyPointer);

        if ($routingKey === null) {
            $phpcsFile->addErrorOnLine(
                'The rounting_key was not found.',
                $tokens[$routingKeyPointer]['line'],
                self::CODE_MISSING_ROUTING_KEY,
            );

            return;
        }

        $parts = explode('.', str_replace('\'', '', $routingKey['content']));
        if (count($parts) !== 4) {
            $phpcsFile->addErrorOnLine(
                sprintf(
                    'The rounting_key "%s" is not formatted correctly.' .
                    ' Expected format is "application.entity.id.event"',
                    $routingKey['content'],
                ),
                $tokens[$routingKeyPointer]['line'],
                self::CODE_INVALID_ROUTING_KEY_FORMAT,
            );

            return;
        }

        if ($this->usesSimplePastTense($parts[3])) {
            return;
        }

        $phpcsFile->addErrorOnLine(
            sprintf(
                'Invalid rounting_key "%s" with format "application.entity.id.event":' .
                ' The event must be a verb in past form (ending with -ed)',
                $routingKey['content'],
            ),
            $tokens[$routingKeyPointer]['line'],
            self::CODE_INVALID_ROUTING_KEY_FORMAT,
        );
    }

    private function usesSimplePastTense(string $action): bool
    {
        if (StringHelper::endsWith($action, 'ed')) {
            return true;
        }

        // Irregular verbs have custom endings for the simple past tense
        return StringHelper::endsWith($action, 'found') || StringHelper::endsWith($action, 'sent');
    }

    private function getMessagePropertiesArgumentPointer(File $phpcsFile, int $stackPointer): ?int
    {
        $openParenthesisPointer = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPointer);
        if ($openParenthesisPointer === false) {
            return null;
        }

        $messagePropertiesArgumentPointer = ArgumentHelper::findArgumentPointer($phpcsFile, $openParenthesisPointer, 4);
        if ($messagePropertiesArgumentPointer === null) {
            return null;
        }

        if (!ArrayHelper::isArray($phpcsFile, $messagePropertiesArgumentPointer)) {
            return null;
        }

        return $messagePropertiesArgumentPointer;
    }

    private function extractRoutingKeyToken(File $phpcsFile, int $startPointer): ?array
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$startPointer]['type'] === 'T_CONSTANT_ENCAPSED_STRING') {
            // We have the routing key directly as constant string

            return $tokens[$startPointer];
        }

        if ($tokens[$startPointer]['type'] === 'T_STRING' && $tokens[$startPointer]['content'] === 'sprintf') {
            // We are using sprintf to build the routing key
            $openingParenthesisPointer = $phpcsFile->findNext([T_OPEN_PARENTHESIS], $startPointer);
            if ($openingParenthesisPointer === false) {
                return null;
            }

            $firstArgPointer = ArgumentHelper::findArgumentPointer($phpcsFile, $openingParenthesisPointer);
            if (!$firstArgPointer) {
                return null;
            }

            $routingKeyPointer = $phpcsFile->findNext([T_CONSTANT_ENCAPSED_STRING], $firstArgPointer);
            if ($routingKeyPointer === false) {
                return null;
            }

            return $tokens[$routingKeyPointer];
        }

        return null;
    }
}
