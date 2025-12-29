# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.8.0] - 2025-12-26

### Added
- JSON Schema output format support with four standard formats (Flag, Basic, Detailed, Verbose) as defined in JSON Schema 2020-12 specification
- Schema generation from data samples with automatic type inference, format detection, and support for nested structures
- Custom format validator registration system allowing users to extend validation beyond built-in formats
- Schema validation against meta-schemas to ensure schema definitions are well-formed and comply with JSON Schema spec
- Performance optimizations including schema compilation/caching with LRU cache and lazy validation for fail-fast behavior
- Developer experience utilities:
  - SchemaMerger for combining schemas using allOf, anyOf, oneOf strategies
  - SchemaDiffer for comparing schemas and detecting breaking changes
  - SchemaMigrator for converting schemas between draft versions
- Convenience methods added to JsonSchemaManager for all new utilities

### Fixed
- Validation functions now properly report error messages when validation fails:
  - validateMaximum reports when values exceed maximum
  - validateMinLength reports when strings are too short
  - validateType reports when types don't match
  - validateFormat reports when format validation fails

### Changed
- OutputFormat enum cases now use PascalCase (Flag, Basic, Detailed, Verbose) following PHP enum naming conventions

[Unreleased]: https://git.cline.sh/faustbrian/json-schema/compare/v1.8.0...HEAD
[1.8.0]: https://git.cline.sh/faustbrian/json-schema/compare/v1.0.0...v1.8.0
