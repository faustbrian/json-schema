#!/usr/bin/env php
<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Support\JsonDecoder;
use Cline\JsonSchema\Support\SchemaLoader;
use Cline\JsonSchema\Validators\Draft04Validator;
use Cline\JsonSchema\Validators\Draft06Validator;
use Cline\JsonSchema\Validators\Draft07Validator;
use Cline\JsonSchema\Validators\Draft201909Validator;
use Cline\JsonSchema\Validators\Draft202012Validator;

$drafts = [
    'Draft 04' => ['class' => Draft04Validator::class, 'dir' => 'draft4'],
    'Draft 06' => ['class' => Draft06Validator::class, 'dir' => 'draft6'],
    'Draft 07' => ['class' => Draft07Validator::class, 'dir' => 'draft7'],
    'Draft 2019-09' => ['class' => Draft201909Validator::class, 'dir' => 'draft2019-09'],
    'Draft 2020-12' => ['class' => Draft202012Validator::class, 'dir' => 'draft2020-12'],
];

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║          JSON Schema Compliance Test Suite                      ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$schemaLoader = new SchemaLoader(__DIR__ . '/JSON-Schema-Test-Suite/remotes');
$overallResults = [];

foreach ($drafts as $draftName => $draftConfig) {
    $validatorClass = $draftConfig['class'];
    $testDir = __DIR__ . '/JSON-Schema-Test-Suite/tests/' . $draftConfig['dir'];

    if (!is_dir($testDir)) {
        echo "⚠ $draftName: Test directory not found - run 'make sync-compliance'\n";
        continue;
    }

    // Get required test files (no optional subdirectory)
    $requiredFiles = glob($testDir . '/*.json') ?: [];

    // Get optional test files
    $optionalFiles = array_merge(
        glob($testDir . '/optional/*.json') ?: [],
        glob($testDir . '/optional/*/*.json') ?: []
    );

    $totalTests = 0;
    $totalFailures = 0;

    // Run required tests with format validation disabled (annotation-only)
    $validator = new $validatorClass($schemaLoader, false);
    foreach ($requiredFiles as $testFile) {
        $testContents = file_get_contents($testFile);
        $testGroups = JsonDecoder::decode($testContents);

        foreach ($testGroups as $testGroup) {
            $schema = $testGroup['schema'] ?? [];
            $tests = $testGroup['tests'] ?? [];

            foreach ($tests as $testCase) {
                $data = $testCase['data'] ?? null;
                $expectedValid = $testCase['valid'] ?? false;
                $totalTests++;

                try {
                    $result = $validator->validate($data, $schema);
                    if ($result->valid !== $expectedValid) {
                        $totalFailures++;
                    }
                } catch (\Throwable $e) {
                    $totalFailures++;
                }
            }
        }
    }

    // Run optional tests
    // Enable format validation for optional/format tests as per test suite requirements
    foreach ($optionalFiles as $testFile) {
        $enableFormat = str_contains($testFile, '/optional/format/');
        $validator = new $validatorClass($schemaLoader, $enableFormat);

        $testContents = file_get_contents($testFile);
        $testGroups = JsonDecoder::decode($testContents);

        foreach ($testGroups as $testGroup) {
            $schema = $testGroup['schema'] ?? [];
            $tests = $testGroup['tests'] ?? [];

            foreach ($tests as $testCase) {
                $data = $testCase['data'] ?? null;
                $expectedValid = $testCase['valid'] ?? false;
                $totalTests++;

                try {
                    $result = $validator->validate($data, $schema);
                    if ($result->valid !== $expectedValid) {
                        $totalFailures++;
                    }
                } catch (\Throwable $e) {
                    $totalFailures++;
                }
            }
        }
    }

    $passRate = $totalTests > 0 ? (($totalTests - $totalFailures) / $totalTests) * 100 : 0;
    $status = $totalFailures === 0 ? '✓' : '✗';

    $overallResults[$draftName] = [
        'total' => $totalTests,
        'failures' => $totalFailures,
        'pass_rate' => $passRate,
    ];

    printf(
        "%s %-15s  %4d/%4d tests  (%5.1f%%)\n",
        $status,
        $draftName . ':',
        $totalTests - $totalFailures,
        $totalTests,
        $passRate
    );
}

echo "\n";

// Summary
$allPassed = true;
foreach ($overallResults as $result) {
    if ($result['failures'] > 0) {
        $allPassed = false;
        break;
    }
}

if ($allPassed) {
    echo "╔══════════════════════════════════════════════════════════════════╗\n";
    echo "║  🎉 ALL DRAFTS: 100% COMPLIANCE                                  ║\n";
    echo "╚══════════════════════════════════════════════════════════════════╝\n";
} else {
    echo "Some drafts have failing tests. Run individual test files for details.\n";
}

echo "\n";
