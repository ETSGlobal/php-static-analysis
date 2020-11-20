<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Helpers;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Helpers\TokenHelper;

class ArgumentHelper
{
    /**
     * Find an argument passed to a function call.
     *
     * @param int   $openParenthesisPointer The pointer to the function call opening parenthesis.
     * @param int   $argumentNumber         The position number of the argument looked for.
     *
     * @return int|null                     The pointer to desired argument, null if argument does not exist.
     */
    public static function findArgumentPointer(File $phpcsFile, int $openParenthesisPointer, int $argumentNumber = 1): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $openParenthesis = $tokens[$openParenthesisPointer];
        $closeParenthesisPointer = $openParenthesis['parenthesis_closer'];

        $currentPointer = $openParenthesisPointer + 1;
        $currentArgumentNumber = 1;
        while ($currentPointer < $closeParenthesisPointer) {
            $nextDelimiterPointer = $phpcsFile->findEndOfStatement($currentPointer, [T_DOUBLE_ARROW]);
            if ($currentArgumentNumber === $argumentNumber) {
                $argumentPointer = TokenHelper::findNextEffective($phpcsFile, $currentPointer, $nextDelimiterPointer);
                if (!$argumentPointer) {
                    // Cannot find any valid argument
                    return null;
                }

                return $argumentPointer;
            }

            if ($nextDelimiterPointer === $closeParenthesisPointer) {
                // Next delimiter is the closing parenthesis, there is no more argument.
                break;
            }

            $currentPointer = $nextDelimiterPointer + 1;
            $currentArgumentNumber++;
        }

        return null;
    }
}
