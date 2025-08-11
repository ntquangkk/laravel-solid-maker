# Changelog

## [2.1.0] - 2025-08-11

### Added
- `module-service-provider.stub`.
- New features in `MakeSolidCommand`:
  - Register module test suites in `phpunit.xml`.
  - Register Policy classes.
  - Add `HasFactory` trait and `newFactory` method to models.

### Removed
- `form_request.stub`.

### Changed
- Updated, renamed, and moved stubs into subfolders.
- Updated `README.md` and `composer.json`.

### Fixed
- `MakeSolidCommand`: Fixed route registration.


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
