# shahkochaki / Laravel Vault

Lightweight Laravel integration for HashiCorp Vault (KV v2-aware).

Installation (local development via path repository):

1. Add repository to your project's `composer.json`:

```json
"repositories": [
  {
    "type": "path",
    "url": "packages/shahkochaki/laravel-vault"
  }
]
```

2. Require the package:

```bash
composer require shahkochaki/laravel-vault:dev-main
```

3. (Optional) Publish config:

```bash
php artisan vendor:publish --provider="shahkochaki\\Vault\\VaultServiceProvider" --tag=config
```

Usage:

- Set `VAULT_ADDR`, `VAULT_TOKEN`, `VAULT_PATH` in `.env` or `config/vault.php`.
- The package registers a `VaultService` singleton and will attempt to read the configured secret path at boot.

Publishing to Packagist

- Ensure this repository is pushed to GitHub (or another VCS) and has a valid `composer.json`.
- Tag a release: `git tag v1.0.0 && git push --tags`.
- Add the repository to Packagist and follow their instructions to enable automated updates.

Usage example

1. Install via Composer (when published):

```bash
composer require shahkochaki/laravel-vault
```

2. Publish config (optional):

```bash
php artisan vendor:publish --provider="shahkochaki\\Vault\\VaultServiceProvider" --tag=config
```

3. Set `.env` values:

```
VAULT_ADDR=https://vault.example.com:8200
VAULT_TOKEN=your_token_here
VAULT_ENGINE=secret
VAULT_PATH=app/production
VAULT_SECRET=database
```

4. The package will attempt to read the configured secret and apply common DB keys (DB_PASSWORD, DB_USER, DB_HOST, DB_DATABASE) into runtime config.

Notes:

- This package is lightweight and intended as a starting point; for production consider AppRole auth and Vault Agent.
