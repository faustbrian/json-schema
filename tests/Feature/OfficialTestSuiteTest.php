<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\JsonSchema\Enums\Draft;
use Cline\JsonSchema\Factories\ValidatorFactory;
use Cline\JsonSchema\Support\JsonDecoder;

/**
 * Official JSON Schema Test Suite.
 *
 * Runs the complete official test suite from json-schema-org/JSON-Schema-Test-Suite
 * to ensure 100% spec compliance across all supported draft versions.
 */
describe('JSON Schema Official Test Suite', function (): void {
    $factory = new ValidatorFactory();
    $testSuiteDir = __DIR__.'/../JSON-Schema-Test-Suite/tests';

    // Map draft versions to test directories and validators
    $draftMappings = [
        'draft4' => Draft::Draft04,
        'draft6' => Draft::Draft06,
        'draft7' => Draft::Draft07,
        'draft2019-09' => Draft::Draft201909,
        'draft2020-12' => Draft::Draft202012,
    ];

    foreach ($draftMappings as $draftDir => $draftEnum) {
        describe($draftDir, function () use ($factory, $testSuiteDir, $draftDir, $draftEnum): void {
            $testDir = sprintf('%s/%s', $testSuiteDir, $draftDir);

            if (!is_dir($testDir)) {
                test('test directory exists', function () use ($testDir): void {
                    expect($testDir)->toBeDirectory();
                })->skip(sprintf('Test directory %s not found', $testDir));

                return;
            }

            $testFiles = glob($testDir.'/*.json');

            foreach ($testFiles as $testFile) {
                $testFileName = basename($testFile, '.json');

                describe($testFileName, function () use ($factory, $testFile, $draftEnum): void {
                    $testContents = file_get_contents($testFile);
                    $testGroups = JsonDecoder::decode($testContents);

                    if (!is_array($testGroups)) {
                        test('valid JSON file', function () use ($testFile): void {
                            expect($testFile)->toBeReadable();
                        })->skip('Could not parse '.$testFile);

                        return;
                    }

                    foreach ($testGroups as $groupIndex => $testGroup) {
                        $groupDescription = $testGroup['description'] ?? 'Group '.$groupIndex;
                        $schema = $testGroup['schema'] ?? [];
                        $tests = $testGroup['tests'] ?? [];

                        // Make group description unique by including index
                        $uniqueGroupDescription = sprintf('[%s] %s', $groupIndex, $groupDescription);

                        describe($uniqueGroupDescription, function () use ($factory, $schema, $tests, $draftEnum): void {
                            $validator = $factory->create($draftEnum);

                            foreach ($tests as $testIndex => $testCase) {
                                $description = $testCase['description'] ?? 'unnamed test';
                                $data = $testCase['data'] ?? null;
                                $expectedValid = $testCase['valid'] ?? false;

                                // Make test name unique by appending index
                                $uniqueDescription = sprintf('[%s] %s', $testIndex, $description);

                                test($uniqueDescription, function () use ($validator, $data, $schema, $expectedValid): void {
                                    $result = $validator->validate($data, $schema);

                                    expect($result->isValid())->toBe($expectedValid)
                                        ->and($result->isInvalid())->toBe(!$expectedValid);
                                });
                            }
                        });
                    }
                });
            }
        });
    }
});
