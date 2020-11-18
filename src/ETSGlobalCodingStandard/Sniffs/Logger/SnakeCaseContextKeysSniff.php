<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Sniffs\Logger;

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
    public function process(File $phpcsFile, $stackPtr)
    {
        if (!$this->isCall($phpcsFile, $stackPtr)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr - 1]['type'] !== 'T_OBJECT_OPERATOR') {
            return;
        }

        if (!in_array($tokens[$stackPtr]['content'], self::$loggerMethods, true)) {
            return;
        }

        if (!$this->isLoggerPropertyCall($phpcsFile, $stackPtr - 2) && !$this->isLoggerVariableCall($phpcsFile, $stackPtr - 2)) {
            return;
        }

        $contextArrayStartPtr = $this->getContextParameterPtr($phpcsFile, $stackPtr);
        if ($contextArrayStartPtr === null) {
            return;
        }

        $contextArrayStart = $tokens[$contextArrayStartPtr];
        $contextArrayEndPtr = $contextArrayStart['bracket_closer'];

        $this->checkContextArray($phpcsFile, $contextArrayStartPtr, $contextArrayEndPtr);
    }

    private function checkContextArray(File $phpcsFile, int $startPtr, int $endPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $currentPtr = $startPtr;
        while ($currentPtr < $endPtr) {
            $entryEndPtr = $phpcsFile->findNext([T_COMMA, T_CLOSE_SHORT_ARRAY, T_CLOSE_PARENTHESIS], $currentPtr, $endPtr + 1);
            if ($entryEndPtr === false) {
                break;
            }

            $entryPtr = $phpcsFile->findNext([T_CONSTANT_ENCAPSED_STRING, T_VARIABLE], $currentPtr, $entryEndPtr);
            if ($entryPtr === false) {
                break;
            }

            $doubleArrowPtr = $phpcsFile->findNext([T_DOUBLE_ARROW], $entryPtr, $entryEndPtr);
            if ($doubleArrowPtr === false) {
                $phpcsFile->addErrorOnLine('The context array passed as argument 2 must have all keys set.', $tokens[$entryPtr]['line'], self::CODE_CONTEXT_KEY_NOT_SET);
                $currentPtr = $entryEndPtr + 1;

                continue;
            }

            if ($tokens[$entryPtr]['type'] === 'T_CONSTANT_ENCAPSED_STRING') {
                $value = str_replace('\'', '', $tokens[$entryPtr]['content']);

                if (!$this->isSnakeCase($value)) {
                    $phpcsFile->addErrorOnLine(sprintf('The key "%s" of context array does not match the snake_case format.', $value), $tokens[$entryPtr]['line'], self::CODE_NO_SNAKE_CASE_CONTEXT_KEY);
                }
            }

            $currentPtr = $entryEndPtr + 1;
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

    private function getContextParameterPtr(File $phpcsFile, int $stackPtr): ?int
    {
        $openParenthesisPtr = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr);
        if ($openParenthesisPtr === false) {
            return null;
        }

        $tokens = $phpcsFile->getTokens();
        $openParenthesis = $tokens[$openParenthesisPtr];
        $closeParenthesisPtr = $openParenthesis['parenthesis_closer'];

        $contextArrayStartPtr = $phpcsFile->findNext([T_ARRAY, T_OPEN_SHORT_ARRAY], $openParenthesisPtr, $closeParenthesisPtr);
        if ($contextArrayStartPtr === false) {
            // No context argument
            return null;
        }

        return $contextArrayStartPtr;
    }

    private function isLoggerPropertyCall(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['type'] !== 'T_STRING') {
            return false;
        }

        if ($tokens[$stackPtr]['content'] !== 'logger') {
            return false;
        }

        if ($tokens[$stackPtr - 1]['type'] !== 'T_OBJECT_OPERATOR') {
            return false;
        }

        return $tokens[$stackPtr - 2]['type'] === 'T_VARIABLE' && $tokens[$stackPtr - 2]['content'] === '$this';
    }

    private function isLoggerVariableCall(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();

        return $tokens[$stackPtr]['type'] === 'T_VARIABLE' && $tokens[$stackPtr]['content'] === '$logger';
    }
}
