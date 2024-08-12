<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Sniffs\RabbitMQ;

use ETSGlobalCodingStandard\Helpers\ArgumentHelper;
use ETSGlobalCodingStandard\Helpers\ArrayHelper;
use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Sniffs\Functions\AbstractLineCall;

/**
 * Checks the body of the message has the right structure.
 *
 * The second argument passed to the publisher must be an array with following structure:
 * [
 *   "data" => [],
 *   "meta" => [], // Optional
 * ]
 * It must not contain other elements at the root, everything must be inside the "data" or "meta" sections.
 */
class ValidMessageStructureSniff extends AbstractLineCall
{
    public const string CODE_INVALID_STRUCTURE = 'InvalidMessageStructure';

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

        $dataArgumentPointer = $this->getDataArgumentPointer($phpcsFile, $stackPointer);
        if ($dataArgumentPointer === null) {
            // THe argument was not found or is not resolvable, skipping.
            return;
        }

        $this->checkDataStructure($phpcsFile, $dataArgumentPointer);
    }

    private function checkDataStructure(File $phpcsFile, int $startPointer): void
    {
        $tokens = $phpcsFile->getTokens();

        if (!ArrayHelper::hasKey($phpcsFile, $startPointer, 'data')) {
            $phpcsFile->addErrorOnLine(
                'Missing "data" property in message structure.',
                $tokens[$startPointer]['line'],
                self::CODE_INVALID_STRUCTURE,
            );

            return;
        }

        $count = ArrayHelper::count($phpcsFile, $startPointer);
        if ($count === 1) {
            // The message only has a "data" property.
            return;
        }

        if ($count === 2 && ArrayHelper::hasKey($phpcsFile, $startPointer, 'meta')) {
            // The message has both "data" and "meta" properties.
            return;
        }

        $phpcsFile->addErrorOnLine(
            'Properties other than "data" or "meta" are not allowed in message structure.',
            $tokens[$startPointer]['line'],
            self::CODE_INVALID_STRUCTURE,
        );
    }

    private function getDataArgumentPointer(File $phpcsFile, int $stackPointer): ?int
    {
        $openParenthesisPointer = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPointer);
        if ($openParenthesisPointer === false) {
            return null;
        }

        $dataArgumentPointer = ArgumentHelper::findArgumentPointer($phpcsFile, $openParenthesisPointer, 2);
        if ($dataArgumentPointer === null) {
            return null;
        }

        if (!ArrayHelper::isArray($phpcsFile, $dataArgumentPointer)) {
            return null;
        }

        return $dataArgumentPointer;
    }
}
