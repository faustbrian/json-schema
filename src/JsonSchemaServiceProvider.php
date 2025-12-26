<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\JsonSchema;

use Cline\JsonSchema\Factories\ValidatorFactory;
use Override;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Laravel service provider for the JSON Schema validation package.
 *
 * Registers core services for JSON Schema validation including the validator
 * factory and schema manager as singletons in the Laravel service container.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @see https://json-schema.org/specification JSON Schema Specification
 * @see https://json-schema.org/draft/2020-12/json-schema-core Core specification (Draft 2020-12)
 * @see https://laravel.com/docs/packages#service-providers Laravel package service providers
 */
final class JsonSchemaServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package settings.
     *
     * @param Package $package The package configuration instance
     */
    public function configurePackage(Package $package): void
    {
        $package->name('json-schema');
    }

    /**
     * Register package services in the Laravel container.
     *
     * Binds ValidatorFactory and JsonSchemaManager as singletons to ensure
     * consistent schema validation and management throughout the application
     * lifecycle.
     */
    #[Override()]
    public function registeringPackage(): void
    {
        $this->app->singleton(ValidatorFactory::class);
        $this->app->singleton(JsonSchemaManager::class);
    }
}
