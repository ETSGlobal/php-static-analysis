<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Sniffs\Doctrine;

use ETSGlobalCodingStandard\Helpers\PropertyHelper;
use ETSGlobalCodingStandard\Helpers\WildcardHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\NamespaceHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;

/**
 * Ensures Doctrine repositories are not injected directly into other services.
 *
 * Doctrine repositories have known issues with long-running processes, resulting
 * in unstable behaviour when an error has occurred earlier in the process, the
 * connection used by the repository may be closed.
 * The solution is to inject a higher level service such as the EntityManager and
 * get the repositories using the <code>getRepository</code> factory method, to
 * always get a fresh repository instance with a living database connection.
 */
class ForbidRepositoryInjectionSniff implements Sniff
{
    public const string FORBIDDEN_REPOSITORY_INJECTION = 'ForbiddenRepositoryInjection';
    public const string FORBIDDEN_REPOSITORY_PROPERTY = 'ForbiddenRepositoryProperty';

    /**
     * All classes matching these FQCN glob patterns will be considered to be Doctrine repositories.
     *
     * @var string[]
     */
    public array $repositoryNamespacePatterns = [
        'App\Repository\*Repository',
    ];

    public function register(): array
    {
        return [T_FUNCTION, T_PUBLIC, T_PROTECTED, T_PRIVATE];
    }

    /** {@inheritDoc} */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['type'] !== 'T_FUNCTION') {
            // We are not on a function token, fallback to property check.
            $this->checkProperty($phpcsFile, $stackPtr);

            return;
        }

        $name = FunctionHelper::getName($phpcsFile, $stackPtr);
        if ($name !== '__construct') {
            return;
        }

        $argsTypehints = FunctionHelper::getParametersTypeHints($phpcsFile, $stackPtr);
        foreach ($argsTypehints as $typehint) {
            if ($typehint === null) {
                continue;
            }

            $type = $typehint->getTypeHint();
            $fqn = ltrim(NamespaceHelper::resolveClassName($phpcsFile, $type, $stackPtr), '\\');

            if ($this->validateType($fqn)) {
                continue;
            }

            $phpcsFile->addError(
                sprintf('Doctrine repository class "%s" must not be injected', $fqn),
                $stackPtr,
                self::FORBIDDEN_REPOSITORY_INJECTION,
            );
        }
    }

    private function checkProperty(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $propertyPointer = TokenHelper::findNext($phpcsFile, [T_FUNCTION, T_CONST, T_VARIABLE], $stackPtr + 1);
        if ($propertyPointer === null) {
            return;
        }

        if ($tokens[$propertyPointer]['code'] !== T_VARIABLE) {
            return;
        }

        $type = PropertyHelper::getPropertyType($phpcsFile, $propertyPointer);
        if ($type === null) {
            return;
        }

        $fqn = ltrim(NamespaceHelper::resolveClassName($phpcsFile, $type, $stackPtr), '\\');

        if ($this->validateType($fqn)) {
            return;
        }

        $phpcsFile->addError(
            sprintf('Doctrine repository class "%s" must not be assigned to class property', $fqn),
            $propertyPointer,
            self::FORBIDDEN_REPOSITORY_PROPERTY,
        );
    }

    private function validateType(string $fqn): bool
    {
        foreach ($this->repositoryNamespacePatterns as $repositoryPattern) {
            if (WildcardHelper::match($repositoryPattern, $fqn)) {
                return false;
            }
        }

        return true;
    }
}
