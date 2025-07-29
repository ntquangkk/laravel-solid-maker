# Changelog

## [2.0.0] - 2025-07-29
### Added
- Initial .gitignore file to exclude common temporary and build files.

### Changed
- Renamed class MakeSolidCommand → MakeSolidScaffoldCommand for clarity and consistency.
- Renamed Artisan command from make:solid-files → make:solid-scaffold.
- Updated README.md and composer.json to reflect new command name and structure.

### Fixed
- Corrected migration file generation path to be under Modules/{ModuleName}/database/migrations.

## [1.0.0] - 2025-07-27
### Added
- First stable release of Laravel Solid Maker.
- Support for:
  - Model, Migration, Seeder, Factory.
  - Controller, FormRequest, Policy, Resource.
  - Service, Repository, Interface.
  - Unit and Feature Tests.
