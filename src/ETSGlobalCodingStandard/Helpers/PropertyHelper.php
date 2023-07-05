<?php

declare(strict_types=1);

namespace ETSGlobalCodingStandard\Helpers;

use PHP_CodeSniffer\Files\File;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use SlevomatCodingStandard\Helpers\Annotation;
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
        $varAnnotations = AnnotationHelper::getAnnotations($phpcsFile, $propertyPointer, '@var');
        /** @var ?Annotation $annotation */
        $annotation = count($varAnnotations) > 0 ? $varAnnotations[0] : null;

        if ($annotation === null) {
            return null;
        }

        $type = self::getAnnotationType($annotation);

        if (!$type) {
            return null;
        }

        if (AnnotationTypeHelper::containsOneType($type)) {
            return AnnotationTypeHelper::getTypeHintFromOneType($type);
        }

        return null;
    }

    public static function getAnnotationType(Annotation $annotation): ?TypeNode
    {
        if (!property_exists($annotation->getValue(), 'type')) {
            return null;
        }

        /** @var TypeNode $type */
        $type = $annotation->getValue()->type;

        if (
            $type instanceof IntersectionTypeNode ||
            $type instanceof UnionTypeNode ||
            $type instanceof NullableTypeNode
        ) {
            return null;
        }

        return $type;
    }
}
