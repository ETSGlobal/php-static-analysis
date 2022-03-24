<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Helpers;

use PHP_CodeSniffer\Files\File;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use SlevomatCodingStandard\Helpers\Annotation\PropertyAnnotation;
use SlevomatCodingStandard\Helpers\AnnotationHelper;
use SlevomatCodingStandard\Helpers\AnnotationTypeHelper;

class PropertyHelper
{
    public static function getPropertyType(File $phpcsFile, int $propertyPointer): ?string
    {
        // Check native typehint
        $propertyTypehint = \SlevomatCodingStandard\Helpers\PropertyHelper::findTypeHint($phpcsFile, $propertyPointer);
        if ($propertyTypehint !== null) {
            return $propertyTypehint->getTypeHint();
        }

        // Check PHPDoc type (legacy support)
        $varAnnotations = AnnotationHelper::getAnnotationsByName($phpcsFile, $propertyPointer, '@var');
        /** @var PropertyAnnotation|null $propertyAnnotation */
        $propertyAnnotation = count($varAnnotations) > 0 ? $varAnnotations[0] : null;

        if ($propertyAnnotation === null) {
            return null;
        }

        $type = $propertyAnnotation->getType();

        if (
            $type instanceof IntersectionTypeNode ||
            $type instanceof UnionTypeNode ||
            $type instanceof NullableTypeNode
        ) {
            return null;
        }

        if (AnnotationTypeHelper::containsOneType($type)) {
            return AnnotationTypeHelper::getTypeHintFromOneType($type);
        }

        return null;
    }
}
