<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Helpers;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Helpers\TokenHelper;

class ArrayHelper
{
    /**
     * Checks whether the pointer is positioned on an array.
     *
     * @param int       $arrayPointer The pointer to the array.
     */
    public static function isArray(File $phpcsFile, int $arrayPointer): bool
    {
        $tokens = $phpcsFile->getTokens();

        return $tokens[$arrayPointer]['type'] === 'T_OPEN_SHORT_ARRAY';
    }

    /**
     * Counts the number of elements in the array.
     *
     * @param int   $arrayPointer The pointer to the array.
     * @return int                The size of the array.
     */
    public static function count(File $phpcsFile, int $arrayPointer): int
    {
        $tokens = $phpcsFile->getTokens();
        $array = $tokens[$arrayPointer];
        $closeBracketPointer = $array['bracket_closer'];

        $count = 0;
        $currentPointer = $arrayPointer + 1;
        while ($currentPointer < $closeBracketPointer) {
            $nextDelimiterPointer = $phpcsFile->findEndOfStatement($currentPointer, [T_DOUBLE_ARROW]);
            if (!$nextDelimiterPointer || $currentPointer === $nextDelimiterPointer) {
                break;
            }

            $item = TokenHelper::findNextEffective($phpcsFile, $currentPointer, $nextDelimiterPointer);
            if (!$item) {
                break;
            }

            $count++;

            if ($nextDelimiterPointer === $closeBracketPointer) {
                // Reached the end of the array
                break;
            }

            $currentPointer = $nextDelimiterPointer + 1;
        }

        return $count;
    }

    /**
     * Checks whether an array contains a key.
     *
     * @param int       $arrayPointer The pointer to the array opening bracket.
     * @param string    $key          The key to search for.
     */
    public static function hasKey(File $phpcsFile, int $arrayPointer, string $key): bool
    {
        return self::findKey($phpcsFile, $arrayPointer, $key) !== null;
    }

    /**
     * Returns the pointer to the value at given key.
     *
     * @param int       $arrayPointer The pointer to the array opening bracket.
     * @param string    $key          The key to of the array entry.
     */
    public static function getValuePointerAtKey(File $phpcsFile, int $arrayPointer, string $key): ?int
    {
        $keyPointer = self::findKey($phpcsFile, $arrayPointer, $key);
        if ($keyPointer === null) {
            return null;
        }

        $doubleArrowPointer = $phpcsFile->findNext([T_DOUBLE_ARROW], $keyPointer);
        if (!$doubleArrowPointer) {
            return null;
        }

        $valuePointer = TokenHelper::findNextEffective($phpcsFile, $doubleArrowPointer + 1);
        if (!$valuePointer) {
            return null;
        }

        return $valuePointer;
    }

    /**
     * Finds the pointer to the given key string.
     *
     * @param int       $arrayPointer The pointer to the array opening bracket.
     * @param string    $key          The key to search for.
     */
    private static function findKey(File $phpcsFile, int $arrayPointer, string $key): ?int
    {
        $tokens = $phpcsFile->getTokens();
        $array = $tokens[$arrayPointer];
        $closeBracketPointer = $array['bracket_closer'];

        $currentPointer = $arrayPointer + 1;
        while ($currentPointer < $closeBracketPointer) {
            $nextDelimiterPointer = $phpcsFile->findEndOfStatement($currentPointer, [T_DOUBLE_ARROW]);
            if (!$nextDelimiterPointer) {
                // Error
                return null;
            }

            $entryPointer = $phpcsFile->findNext([T_CONSTANT_ENCAPSED_STRING, T_VARIABLE], $currentPointer, $nextDelimiterPointer);
            if ($entryPointer === false) {
                // No key token candidate found.
                $currentPointer = $nextDelimiterPointer + 1;

                continue;
            }

            $doubleArrowPointer = $phpcsFile->findNext([T_DOUBLE_ARROW], $entryPointer, $nextDelimiterPointer);
            if ($doubleArrowPointer === false) {
                // Array entry doesn't not have the key => value form
                $currentPointer = $nextDelimiterPointer + 1;

                continue;
            }

            if ($tokens[$entryPointer]['type'] === 'T_CONSTANT_ENCAPSED_STRING' && $tokens[$entryPointer]['content'] === sprintf('\'%s\'', $key)) {
                return $entryPointer;
            }

            if ($nextDelimiterPointer === $closeBracketPointer) {
                // Reached the end of the array
                return null;
            }

            $currentPointer = $nextDelimiterPointer + 1;
        }

        return null;
    }
}
