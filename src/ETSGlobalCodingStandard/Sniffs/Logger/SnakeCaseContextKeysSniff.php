<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Sniffs\Logger;

use ETSGlobalCodingStandard\Helpers\ArgumentHelper;
use ETSGlobalCodingStandard\Helpers\ArrayHelper;
use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Sniffs\Functions\AbstractLineCall;

/**
 * Checks the array passed as second argument to Psr\LoggerInterface methods is valid.
 *
 * Every entry must be key/value, with snake_case keys.
 */
class SnakeCaseContextKeysSniff extends AbstractLineCall
{
    public const CODE_CONTEXT_KEY_NOT_SET = 'ContextKeyNotSet';
    public const CODE_NO_SNAKE_CASE_CONTEXT_KEY = 'NoSnakeCaseContextKey';

    /** @var string[] */
    private static $loggerMethods = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
        'log',
    ];

    /**
     * {@inheritDoc}
     */
    public function process(File $phpcsFile, $stackPointer)
    {
        if (!$this->isCall($phpcsFile, $stackPointer)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPointer - 1]['type'] !== 'T_OBJECT_OPERATOR') {
            return;
        }

        if (!in_array($tokens[$stackPointer]['content'], self::$loggerMethods, true)) {
            return;
        }

        if (!$this->isLoggerPropertyCall($phpcsFile, $stackPointer - 2) && !$this->isLoggerVariableCall($phpcsFile, $stackPointer - 2)) {
            return;
        }

        $contextArrayStartPointer = $this->getContextParameterPointer($phpcsFile, $stackPointer);
        if ($contextArrayStartPointer === null) {
            return;
        }

        $contextArrayStart = $tokens[$contextArrayStartPointer];
        $contextArrayEndPointer = $contextArrayStart['bracket_closer'];

        $this->checkContextArray($phpcsFile, $contextArrayStartPointer, $contextArrayEndPointer);
    }

    private function checkContextArray(File $phpcsFile, int $startPointer, int $endPointer): void
    {
        $tokens = $phpcsFile->getTokens();
        $currentPointer = $startPointer;
        while ($currentPointer < $endPointer) {
            $entryEndPointer = $phpcsFile->findNext([T_COMMA, T_CLOSE_SHORT_ARRAY, T_CLOSE_PARENTHESIS], $currentPointer, $endPointer + 1);
            if ($entryEndPointer === false) {
                break;
            }

            $entryPointer = $phpcsFile->findNext([T_CONSTANT_ENCAPSED_STRING, T_VARIABLE], $currentPointer, $entryEndPointer);
            if ($entryPointer === false) {
                break;
            }

            $doubleArrowPointer = $phpcsFile->findNext([T_DOUBLE_ARROW], $entryPointer, $entryEndPointer);
            if ($doubleArrowPointer === false) {
                $phpcsFile->addErrorOnLine('The context array passed as argument 2 must have all keys set.', $tokens[$entryPointer]['line'], self::CODE_CONTEXT_KEY_NOT_SET);
                $currentPointer = $entryEndPointer + 1;

                continue;
            }

            if ($tokens[$entryPointer]['type'] === 'T_CONSTANT_ENCAPSED_STRING') {
                $value = str_replace('\'', '', $tokens[$entryPointer]['content']);

                if (!$this->isSnakeCase($value)) {
                    $phpcsFile->addErrorOnLine(sprintf('The key "%s" of context array does not match the snake_case format.', $value), $tokens[$entryPointer]['line'], self::CODE_NO_SNAKE_CASE_CONTEXT_KEY);
                }
            }

            $currentPointer = $entryEndPointer + 1;
        }
    }

    private function isSnakeCase(string $string): bool
    {
        $result = preg_match('/[A-Z\s\-]/', $string);
        if ($result === false) {
            return false;
        }

        return $result === 0;
    }

    private function getContextParameterPointer(File $phpcsFile, int $stackPointer): ?int
    {
        $openParenthesisPointer = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPointer);
        if ($openParenthesisPointer === false) {
            return null;
        }

        $contextArgumentPointer = ArgumentHelper::findArgumentPointer($phpcsFile, $openParenthesisPointer, 2);
        if ($contextArgumentPointer === null) {
            // No context argument
            return null;
        }

        if (!ArrayHelper::isArray($phpcsFile, $contextArgumentPointer)) {
            // Context is not an array (maybe a variable), we can't resolve its value.
            return null;
        }

        return $contextArgumentPointer;
    }

    private function isLoggerPropertyCall(File $phpcsFile, int $stackPointer): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPointer]['type'] !== 'T_STRING') {
            return false;
        }

        if ($tokens[$stackPointer]['content'] !== 'logger') {
            return false;
        }

        if ($tokens[$stackPointer - 1]['type'] !== 'T_OBJECT_OPERATOR') {
            return false;
        }

        return $tokens[$stackPointer - 2]['type'] === 'T_VARIABLE' && $tokens[$stackPointer - 2]['content'] === '$this';
    }

    private function isLoggerVariableCall(File $phpcsFile, int $stackPointer): bool
    {
        $tokens = $phpcsFile->getTokens();

        return $tokens[$stackPointer]['type'] === 'T_VARIABLE' && $tokens[$stackPointer]['content'] === '$logger';
    }
}
