<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Factories;

use Cline\JsonSchema\Contracts\ValidatorInterface;
use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Validators\Draft04Validator;
use Cline\JsonSchema\Validators\Draft06Validator;
use Cline\JsonSchema\Validators\Draft07Validator;
use Cline\JsonSchema\Validators\Draft201909Validator;
use Cline\JsonSchema\Validators\Draft202012Validator;

/**
 * Factory for creating JSON Schema validators based on draft version.
 *
 * This factory instantiates the appropriate validator implementation for a given
 * JSON Schema draft specification. Each draft version (Draft 4, 6, 7, 2019-09,
 * 2020-12) has unique keywords, validation rules, and behavioral differences that
 * require separate validator implementations.
 *
 * The factory pattern ensures that validator creation is centralized and that the
 * correct validator is always used for each schema draft version, preventing
 * validation errors caused by applying the wrong draft rules.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/specification JSON Schema Specification Overview
 * @see https://json-schema.org/specification#published-drafts Published Draft Versions
 * @see https://json-schema.org/draft/2020-12/json-schema-core JSON Schema Draft 2020-12 Core
 * @see https://json-schema.org/draft/2020-12/json-schema-validation JSON Schema Draft 2020-12 Validation
 * @see https://json-schema.org/understanding-json-schema/reference/schema Schema Version and Declaration
 * @see https://json-schema.org/draft-04/json-schema-core JSON Schema Draft 04
 * @see https://json-schema.org/draft-06/json-schema-core JSON Schema Draft 06
 * @see https://json-schema.org/draft-07/json-schema-core JSON Schema Draft 07
 */
final class ValidatorFactory
{
    /**
     * Create a validator instance for the specified JSON Schema draft version.
     *
     * Instantiates and returns a validator implementation that correctly handles
     * the keywords, validation rules, and behaviors specific to the requested draft.
     * Each validator is stateless and can be safely reused for multiple validations.
     *
     * @param Draft $draft The JSON Schema draft version enum value specifying which
     *                     validator implementation to create (Draft04, Draft06, Draft07,
     *                     Draft201909, or Draft202012)
     *
     * @return ValidatorInterface The draft-specific validator instance ready to validate
     *                            data against schemas of the specified draft version
     */
    public function create(Draft $draft): ValidatorInterface
    {
        return match ($draft) {
            Draft::Draft04 => new Draft04Validator(),
            Draft::Draft06 => new Draft06Validator(),
            Draft::Draft07 => new Draft07Validator(),
            Draft::Draft201909 => new Draft201909Validator(),
            Draft::Draft202012 => new Draft202012Validator(),
        };
    }
}
