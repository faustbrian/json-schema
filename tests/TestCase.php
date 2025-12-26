<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests;

use Cline\JsonSchema\Facades\JsonSchema;
use Cline\JsonSchema\JsonSchemaServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Override;

/**
 * @author Brian Faust <brian@cline.sh>
 * @internal
 */
abstract class TestCase extends BaseTestCase
{
    /**
     * Get package providers.
     *
     * @param Application $app The application instance
     *
     * @return array<int, class-string> The package service providers
     */
    #[Override()]
    protected function getPackageProviders($app): array
    {
        return [
            JsonSchemaServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param Application $app The application instance
     *
     * @return array<string, class-string> The package aliases
     */
    #[Override()]
    protected function getPackageAliases($app): array
    {
        return [
            'JsonSchema' => JsonSchema::class,
        ];
    }
}
