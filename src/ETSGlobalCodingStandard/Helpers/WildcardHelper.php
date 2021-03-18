<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Helpers;

class WildcardHelper
{
    /**
     * Searches for a "glob" pattern in a string.
     *
     * @param string $pattern The "glob" pattern
     * @param string $subject The string to search from
     *
     * @return bool True if the pattern was found in the subject string, false otherwise.
     */
    public static function match(string $pattern, string $subject): bool
    {
        // Escape backslashes, replace '*' by regexp wildcard (.*)
        $regexp = '#^' . str_replace(['\\', '*'], ['\\\\', '(.*)'], $pattern) . '$#';

        return preg_match($regexp, $subject) === 1;
    }
}
