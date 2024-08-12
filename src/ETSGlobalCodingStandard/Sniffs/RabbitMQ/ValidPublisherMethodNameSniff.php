<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Sniffs\RabbitMQ;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\ClassHelper;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\StringHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;

/**
 * Checks the publisher methods follow naming conventions.
 *
 * The public methods must be a verb in the part form, describing the event that just happened.
 */
class ValidPublisherMethodNameSniff implements Sniff
{
    public const string CODE_INVALID_PUBLISHER_METHOD_NAME = 'InvalidPublisherMethodName';

    public function register(): array
    {
        return [
            T_CLASS,
        ];
    }

    /** {@inheritDoc} */
    public function process(File $phpcsFile, $stackPointer)
    {
        $className = ClassHelper::getName($phpcsFile, $stackPointer);

        if (!StringHelper::endsWith($className, 'Publisher')) {
            return;
        }

        $nextFunctionPointer = TokenHelper::findNext($phpcsFile, T_FUNCTION, $stackPointer + 1);
        while ($nextFunctionPointer !== false) {
            if ($nextFunctionPointer === null) {
                return;
            }

            $this->checkFunction($phpcsFile, $nextFunctionPointer);

            $nextFunctionPointer = TokenHelper::findNext($phpcsFile, T_FUNCTION, $nextFunctionPointer + 1);
        }
    }

    private function checkFunction(File $phpcsFile, int $functionPointer): void
    {
        if (!FunctionHelper::isMethod($phpcsFile, $functionPointer)) {
            // We are not in presence of a method but rather a normal function.
            return;
        }

        $visibilityPointer = $phpcsFile->findPrevious([T_PROTECTED, T_PRIVATE], $functionPointer - 1);
        if ($visibilityPointer !== false) {
            // Visibility keyword is either protected or private, skipping
            return;
        }

        $methodNamePointer = $phpcsFile->findNext([T_STRING], $functionPointer + 1);
        if ($methodNamePointer === false) {
            // Method name not found
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $methodName = $tokens[$methodNamePointer]['content'];

        if (StringHelper::startsWith($methodName, '__')) {
            // Magic method
            return;
        }

        if (StringHelper::endsWith($methodName, 'ed')) {
            // Method name is valid
            return;
        }

        $phpcsFile->addErrorOnLine(
            'Invalid publisher method name. It should be a verb in the past form (engind with -ed)',
            $tokens[$methodNamePointer]['line'],
            self::CODE_INVALID_PUBLISHER_METHOD_NAME,
        );
    }
}
