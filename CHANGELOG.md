# Changelog

All notable changes to this project will be documented in this file.

## [1.4.0] - 2025-12-10

### Added

- **🎯 Runtime Path Configuration**: New methods to dynamically change base path at runtime
  - `setPath(string $path)`: Set a custom base path for subsequent requests
  - `getPath()`: Get the currently active base path
  - `resetPath()`: Reset back to config default path
- **📝 Method Renaming**: `getSecret()` renamed to `read()` for more generic API
  - `read(string $path)`: Read data from Vault (new primary method)
  - `getSecret(string $path)`: Kept as alias for backward compatibility
- **🔒 Internal Method for Provider**: Added `readFromConfig()` method that ignores runtime customizations
  - Ensures `VaultServiceProvider` auto-sync always uses config values
  - Runtime engine/path settings don't affect automatic .env synchronization
- Method chaining support for both engine and path configuration
- Enhanced cache key generation to include custom path
- Updated `clearCache()` to handle custom path scenarios

### Example

```php
// Set base path dynamically
$vault->setPath('app/staging')->read('database');

// Chain engine and path
$vault->setEngine('secret')->setPath('app/production')->read('api/keys');

// Use new read() method (recommended)
$secret = $vault->read('path/to/data');

// Old getSecret() still works (deprecated)
$secret = $vault->getSecret('path/to/data');
```

### Use Cases

- Multi-environment configurations (dev/staging/production)
- Per-tenant secret paths in SaaS applications
- Namespace isolation for microservices
- Dynamic path switching based on context

## [1.3.4] - 2025-12-10

### Added

- **🔧 Runtime Engine Switching**: New methods to dynamically change Vault engine at runtime
  - `setEngine(string $engine)`: Set a custom engine for subsequent requests
  - `getEngine()`: Get the currently active engine
  - `resetEngine()`: Reset back to config default engine
- Method chaining support for fluent API usage
- Enhanced cache key generation to include custom engine

### Example

```php
// Switch engines dynamically
$vault->setEngine('kv-v1')->getSecret('path');
$vault->setEngine('custom-engine')->getSecret('another/path');
$vault->resetEngine(); // Back to config default
```

### Use Cases

- Multiple KV engines in same Vault instance
- Switching between KV v1 and KV v2
- Working with custom secret engines
- Per-request engine configuration

## [1.3.3] - 2025-12-10

### Added

- **🐳 Dual Sync Modes**: New `sync_mode` config option to support both traditional and container environments
- **DOTENV Mode**: Reads `.env` file first, finds empty keys, then syncs from Vault (default behavior)
- **VAULT Mode**: Reads Vault first, applies only to empty `env()` values - perfect for Docker/Kubernetes
- Better support for containerized deployments where `.env` file doesn't exist

### Changed

- Refactored sync logic to support both modes seamlessly
- Improved logging to show which sync mode is active
- Enhanced documentation with Docker/Kubernetes examples

### Configuration

```env
# Traditional .env based (default)
VAULT_SYNC_MODE=dotenv

# Docker/Container based (new!)
VAULT_SYNC_MODE=vault
```

## [1.3.2] - 2025-12-10

### Changed

- **Improved console command handling**: Now only skips specific cache/config commands instead of all console commands
- **Apply all Vault secrets**: Changed behavior to apply ALL secrets from Vault (not just empty .env keys)
- Better support for artisan commands and queues while maintaining security for cache operations

### Fixed

- Artisan commands like `tinker`, `queue:work`, and custom commands now work properly with Vault
- More selective command filtering for better developer experience

## [1.3.1] - 2025-12-10

### Changed

- 🎨 **Rebranded to laravel-vault-pro**: Repository renamed for better visibility and professional appearance
- 📝 **Enhanced package description**: More compelling and feature-rich description highlighting all capabilities
- 🏷️ **Expanded keywords**: Added comprehensive keywords for better discoverability (security, credentials, devops, kubernetes, etc.)
- 🔗 **Updated all URLs**: Repository links updated throughout documentation
- ✨ **Improved README**: More attractive presentation with emojis and better feature highlights

### Notes

- This is a branding and documentation improvement release
- No breaking changes - fully backward compatible
- GitHub automatically redirects from old URL to new one

## [1.2.2] - 2025-12-10

### Changed

- Maintenance release with minor improvements
- Updated documentation and package metadata

## [1.2.1] - 2025-12-09

### Fixed

- **Corrected sync logic flow**: Now correctly reads empty keys from `.env` first, then checks Vault (instead of iterating Vault keys first)
- Improved logging to show which empty keys were found and which were applied from Vault
- Better handling of empty value detection in `.env` file (strips quotes properly)

### Changed

- Renamed `getEnvKeys()` to `getEmptyEnvKeys()` to better reflect its purpose
- Enhanced debug logging throughout the sync process

## [1.2.0] - 2025-12-09

### Added

- **Auto-sync with .env file**: Package now automatically reads `.env` file and syncs empty variables from Vault
- **Flexible sync control**: New config options `update_env` and `update_config` to control what gets updated
- **Custom config mappings**: Define custom mappings between env variables and Laravel config paths via `config_mappings`
- Built-in config mappings for common Laravel services (Database, Redis, Mail, AWS, Cache, Queue, Session)
- New `getEnvKeys()` method to read and parse `.env` file keys

### Changed

- Secret application logic now only processes keys that exist in `.env` file
- Config mappings now support custom user-defined mappings with priority over defaults
- Improved documentation with comprehensive examples of new features

### Notes

- This is a major feature release that changes how secrets are applied
- Backward compatible with existing configurations
- Recommended to review and test sync behavior before deploying to production

## [1.1.3] - 2025-12-09

### Fixed

- Improved address validation in `VaultServiceProvider::register()` to prevent port being added to empty addresses
- Added `runningInConsole()` check in `VaultServiceProvider::boot()` to skip Vault fetching during artisan commands (config:cache, cache:clear, etc.)
- Better handling of base_uri construction when address or port is missing

### Changed

- Refactored address construction logic for better reliability and validation

## [1.1.2] - 2025-12-09

### Added

- Honor `port` config / `VAULT_PORT` when building the Vault base URI.
- Improved README with a complete English usage guide and examples.

### Changed

- Documentation improvements and packaging metadata updates.

### Notes

- This release is a documentation and small feature bump (port support).

## [1.1.1] - 2025-12-09

### Added

- Support for reading Vault token from a `token_file` (useful for Vault Agent or mounted secrets).
- Improved HTTP status logging and graceful handling of 404 responses.

### Changed

- Minor docs updates and packaging metadata improvements.

### Fixed

- Better base URI normalization in the service provider.
