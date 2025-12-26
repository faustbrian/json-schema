<?php

require_once __DIR__ . '/vendor/autoload.php';

use Cline\JsonSchema\Validator;

// Test 1: Draft 2020-12 with default metaschema (format-annotation only)
$validator = new Validator();
$schema = [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    'format' => 'email'
];

// Invalid email should pass because format is annotation-only by default
$result = $validator->validate('not-an-email', $schema);
echo "Test 1 (Draft 2020-12, invalid email, annotation-only): ";
echo $result->isValid() ? "PASS (annotation-only)\n" : "FAIL (should be annotation-only)\n";

// Test 2: Draft 2020-12 with format-assertion vocabulary enabled
$schema2 = [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    '$vocabulary' => [
        'https://json-schema.org/draft/2020-12/vocab/core' => true,
        'https://json-schema.org/draft/2020-12/vocab/format-assertion' => true,
    ],
    'format' => 'email'
];

// Invalid email should fail because format-assertion is active
$result2 = $validator->validate('not-an-email', $schema2);
echo "Test 2 (Draft 2020-12, invalid email, format-assertion): ";
echo $result2->isValid() ? "FAIL (should validate)\n" : "PASS (validated)\n";

// Test 3: Draft 07 should always validate format
$schema3 = [
    '$schema' => 'http://json-schema.org/draft-07/schema#',
    'format' => 'email'
];

$result3 = $validator->validate('not-an-email', $schema3);
echo "Test 3 (Draft 07, invalid email, always validates): ";
echo $result3->isValid() ? "FAIL (should validate)\n" : "PASS (validated)\n";

// Test 4: Valid email should always pass
$result4 = $validator->validate('test@example.com', $schema);
echo "Test 4 (Draft 2020-12, valid email): ";
echo $result4->isValid() ? "PASS\n" : "FAIL\n";

