<?php declare(strict_types=1);

/**
 * Run official JSON Schema test suite for a specific draft.
 *
 * Usage: php run_draft_tests.php <draft>
 * Example: php run_draft_tests.php draft4
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Cline\JsonSchema\Support\JsonDecoder;
use Cline\JsonSchema\Support\SchemaLoader;
use Cline\JsonSchema\Validators\Draft04Validator;
use Cline\JsonSchema\Validators\Draft06Validator;
use Cline\JsonSchema\Validators\Draft07Validator;
use Cline\JsonSchema\Validators\Draft201909Validator;
use Cline\JsonSchema\Validators\Draft202012Validator;

if ($argc < 2) {
    echo "Usage: php run_draft_tests.php <draft>\n";
    echo "Example: php run_draft_tests.php draft4\n";
    echo "Available drafts: draft4, draft6, draft7, draft2019-09, draft2020-12\n";
    exit(1);
}

$draft = $argv[1];

// Map draft to validator class
$validators = [
    'draft4' => Draft04Validator::class,
    'draft6' => Draft06Validator::class,
    'draft7' => Draft07Validator::class,
    'draft2019-09' => Draft201909Validator::class,
    'draft2020-12' => Draft202012Validator::class,
];

if (!isset($validators[$draft])) {
    echo "Error: Unknown draft '$draft'\n";
    echo "Available drafts: " . implode(', ', array_keys($validators)) . "\n";
    exit(1);
}

$validatorClass = $validators[$draft];
$testSuiteDir = __DIR__ . "/JSON-Schema-Test-Suite/tests/$draft";

if (!is_dir($testSuiteDir)) {
    echo "Error: Test suite directory not found: $testSuiteDir\n";
    exit(1);
}

echo "Running $draft test suite...\n";
echo str_repeat('=', 80) . "\n";

$schemaLoader = new SchemaLoader(__DIR__ . '/JSON-Schema-Test-Suite/remotes');
$validator = new $validatorClass($schemaLoader);

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$failures = [];

// Recursively find all JSON test files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($testSuiteDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$testFiles = [];
foreach ($iterator as $file) {
    if ($file->getExtension() === 'json') {
        $testFiles[] = $file->getPathname();
    }
}

sort($testFiles);

foreach ($testFiles as $testFile) {
    $fileContents = file_get_contents($testFile);
    // Use JsonDecoder to preserve empty object/array distinction throughout
    $testGroups = JsonDecoder::decode($fileContents);
    $relativePath = str_replace($testSuiteDir . '/', '', $testFile);

    foreach ($testGroups as $groupIndex => $group) {
        $schema = $group['schema'];
        $description = $group['description'];

        foreach ($group['tests'] as $testIndex => $test) {
            $totalTests++;
            $data = $test['data'];
            $expectedValid = $test['valid'];
            $testDescription = $test['description'];

            $result = $validator->validate($data, $schema);
            $actualValid = $result->isValid();
            
            if ($actualValid === $expectedValid) {
                $passedTests++;
            } else {
                $failedTests++;
                $failures[] = [
                    'file' => $relativePath,
                    'group' => $description,
                    'test' => $testDescription,
                    'expected' => $expectedValid ? 'VALID' : 'INVALID',
                    'actual' => $actualValid ? 'VALID' : 'INVALID',
                    'data' => $data,
                ];
            }
        }
    }
}

// Print summary
echo "\n";
echo str_repeat('=', 80) . "\n";
echo "Test Results for $draft\n";
echo str_repeat('=', 80) . "\n";
echo "Total tests: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: $failedTests\n";
echo "Pass rate: " . number_format(($passedTests / $totalTests) * 100, 1) . "%\n";
echo str_repeat('=', 80) . "\n";

if ($failedTests > 0) {
    echo "\nFailures:\n";
    echo str_repeat('-', 80) . "\n";
    
    foreach ($failures as $i => $failure) {
        echo ($i + 1) . ". {$failure['file']}\n";
        echo "   Group: {$failure['group']}\n";
        echo "   Test: {$failure['test']}\n";
        echo "   Expected: {$failure['expected']}, Got: {$failure['actual']}\n";
        echo "   Data: " . json_encode($failure['data']) . "\n";
        echo str_repeat('-', 80) . "\n";
    }
    
    exit(1);
}

exit(0);
