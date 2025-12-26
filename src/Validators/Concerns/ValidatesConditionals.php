<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema\Validators\Concerns;

/**
 * Conditional validation support for JSON Schema.
 *
 * Implements if/then/else conditional schema application (Draft-07+), allowing
 * schema validation to branch based on whether the instance validates against
 * a conditional schema. Manages annotation collection according to which branch
 * is taken during validation.
 *
 * @author Brian Faust <brian@cline.sh>
 * @see https://json-schema.org/understanding-json-schema/reference/conditionals Understanding JSON Schema - Conditionals
 * @see https://json-schema.org/draft-07/json-schema-validation#rfc.section.6.6 Draft-07 - Conditional Subschemas
 * @see https://json-schema.org/draft/2019-09/json-schema-core#rfc.section.9.2.2 Draft 2019-09 - Conditional Applicator Keywords
 * @see https://json-schema.org/draft/2020-12/json-schema-core#name-keywords-for-applying-subsc Draft 2020-12 - Conditional Applicator Keywords
 */
trait ValidatesConditionals
{
    /**
     * Validate conditional schema application using if/then/else keywords.
     *
     * Implements conditional validation logic where the instance is validated
     * against different subschemas based on whether it satisfies the "if" schema.
     * When the "if" schema validates successfully and a "then" schema exists, the
     * instance must also validate against "then". When the "if" schema fails and
     * an "else" schema exists, the instance must validate against "else".
     *
     * Annotation collection follows these rules:
     * - If passes + then exists: collect annotations from both "if" and "then"
     * - If fails + else exists: discard "if" annotations, collect only "else"
     * - If passes + no then: collect annotations from "if" only
     * - If fails + no else: discard all "if" annotations (no collection)
     *
     * @see https://json-schema.org/understanding-json-schema/reference/conditionals#if-then-else
     * @see https://json-schema.org/draft-07/json-schema-validation#rfc.section.6.6
     * @param  mixed                $data   The instance to validate against the conditional schemas
     * @param  array<string, mixed> $schema The schema definition containing if/then/else keywords
     * @return bool                 True if the conditional validation succeeds, false otherwise
     */
    protected function validateIf(mixed $data, array $schema): bool
    {
        if (!isset($schema['if'])) {
            return true;
        }

        // Save state before "if"
        $baseState = $this->saveEvaluationState();

        // Check "if" condition
        /** @var array<string, mixed>|bool $subSchema */
        $subSchema = $schema['if'];
        $ifValid = $this->validateSchema($data, $subSchema);

        if ($ifValid && isset($schema['then'])) {
            // if passed and then exists: keep "if" annotations, add "then" annotations
            /** @var array<string, mixed>|bool $thenSchema */
            $thenSchema = $schema['then'];

            return $this->validateSchema($data, $thenSchema);
        }

        if (!$ifValid && isset($schema['else'])) {
            // if failed and else exists: discard "if" annotations, collect "else" only
            $this->restoreEvaluationState($baseState);

            /** @var array<string, mixed>|bool $elseSchema */
            $elseSchema = $schema['else'];

            return $this->validateSchema($data, $elseSchema);
        }

        // if passed with no then: keep "if" annotations (do nothing)
        // if failed with no else: discard "if" annotations
        if (!$ifValid) {
            $this->restoreEvaluationState($baseState);
        }

        return true;
    }
}
