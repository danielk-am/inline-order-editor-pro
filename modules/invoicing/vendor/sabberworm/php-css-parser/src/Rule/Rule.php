<?php
/* Modified by Alkamy on 2026-10-23: namespaces/references prefixed to avoid plugin conflicts; upstream copyright/licence retained. */

declare (strict_types=1);
namespace AlkamyPdfVendor\Sabberworm\CSS\Rule;

use AlkamyPdfVendor\Sabberworm\CSS\Property\Declaration;
// @phpstan-ignore function.impossibleType
if (!\class_exists(Rule::class, \false) && !\interface_exists(Rule::class, \false)) {
    $aliasWasCreated = \class_alias(Declaration::class, Rule::class);
    \assert($aliasWasCreated);
    // The test is expected to evaluate to false,
    // but allows for the deprecation notice to be picked up by IDEs like PHPStorm.
    // @phpstan-ignore function.impossibleType, booleanNot.alwaysTrue, booleanAnd.alwaysTrue
    if (!\class_exists(Rule::class, \false) && !\interface_exists(Rule::class, \false)) {
        /**
         * @deprecated in v9.2, will be removed in v10.0.  Use `Property\Declaration` instead, which is a direct
         *             replacement.
         */
        class Rule
        {
        }
    }
}
