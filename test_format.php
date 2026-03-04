<?php

require_once __DIR__ . '/vendor/autoload.php';

use Cline\JsonSchema\Support\SchemaLoader;
use Cline\JsonSchema\Validators\Draft202012Validator;

$schemaLoader = new SchemaLoader();
$validator = new Draft202012Validator($schemaLoader);

// Test case from format.json
$schema = [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    'format' => 'email'
];

// This should pass because format is annotation-only by default
$result = $validator->validate('2962', $schema);

echo "Test: invalid email string is only an annotation by default\n";
echo "Data: '2962'\n";
echo "Expected: VALID (true)\n";
echo "Got: " . ($result->isValid() ? "VALID (true)" : "INVALID (false)") . "\n";
echo "\n";

if ($result->isValid()) {
    echo "✓ PASS - Format is annotation-only by default in Draft 2020-12\n";
} else {
    echo "✗ FAIL - Format should be annotation-only by default\n";
    echo "Errors: " . json_encode($result->getErrors(), JSON_PRETTY_PRINT) . "\n";
}

