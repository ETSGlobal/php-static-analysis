<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Sniffs\Doctrine;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\NamespaceHelper;
use SlevomatCodingStandard\Helpers\ReferencedNameHelper;

/**
 * Ensures Doctrine classes are not used outside the persistence layer.
 */
class ForbidDoctrineClassesSniff implements Sniff
{
    public const string FORBIDDEN_CLASS_FOUND = 'ForbiddenDoctrineClassFound';

    /**
     * List of classes that should not be used.
     *
     * @var string[]
     */
    public array $forbiddenClasses = [
        'Doctrine\Common\Persistence\ManagerRegistry',
        'Doctrine\Persistence\ManagerRegistry',
        'Doctrine\Persistence\ObjectRepository',
        'Doctrine\ORM\EntityManager',
        'Doctrine\ORM\EntityManagerInterface',
        'Doctrine\ORM\EntityRepository',
        'Doctrine\ODM\MongoDB\DocumentManager',
    ];

    /**
     * List of namespaces allowed to use the forbidden classes.
     * Add a trailing '\' to also ignore all sub-namespaces within it.
     *
     * @var string[]
     */
    public array $ignoredNamespaces = [
        'App\Migration',
        'App\Persistence',
        'App\Repository',
        'Tests\\',
    ];

    public function register(): array
    {
        return [\T_OPEN_TAG];
    }

    /** {@inheritDoc} */
    public function process(File $phpcsFile, $stackPtr): void
    {
        // Get all referenced names in file from native Typehints (no annotations)
        $referencedNames = ReferencedNameHelper::getAllReferencedNames($phpcsFile, $stackPtr);

        foreach ($referencedNames as $referencedName) {
            $pointer = $referencedName->getStartPointer();
            $name = $referencedName->getNameAsReferencedInFile();

            // Resolve the FQCN by looking at use statements, remove leading '\', compare with forbidden classes.
            $fullyQualifiedName = ltrim(NamespaceHelper::resolveClassName($phpcsFile, $name, $pointer), '\\');
            if (!\in_array($fullyQualifiedName, $this->forbiddenClasses, true)) {
                continue;
            }

            // Resolve current namespace, and check if it should be ignored.
            $currentNamespace = NamespaceHelper::findCurrentNamespaceName($phpcsFile, $pointer);
            if ($currentNamespace === null || $this->isIgnored($currentNamespace)) {
                continue;
            }

            $error = sprintf(
                'The use of Doctrine class "%s" is forbidden in namespace "%s"',
                $fullyQualifiedName,
                $currentNamespace,
            );

            $phpcsFile->addError($error, $pointer, self::FORBIDDEN_CLASS_FOUND);
        }
    }

    private function isIgnored(string $currentNamespace): bool
    {
        if (in_array($currentNamespace, $this->ignoredNamespaces, true)) {
            return true;
        }

        // Handle wildcard exclusions
        foreach ($this->ignoredNamespaces as $ignoredNamespace) {
            if (!str_ends_with($ignoredNamespace, '\\')) {
                continue;
            }

            if (str_starts_with($currentNamespace, $ignoredNamespace)) {
                return true;
            }
        }

        return false;
    }
}
