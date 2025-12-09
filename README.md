# Laravel Vault

**یک پکیج سبک و حرفه‌ای برای اتصال Laravel به HashiCorp Vault**

[![Latest Version](https://img.shields.io/packagist/v/shahkochaki/laravel-vault.svg)](https://packagist.org/packages/shahkochaki/laravel-vault)
[![License](https://img.shields.io/packagist/l/shahkochaki/laravel-vault.svg)](https://packagist.org/packages/shahkochaki/laravel-vault)

## معرفی

Laravel Vault یک پکیج سبک و قدرتمند برای یکپارچه‌سازی Laravel با HashiCorp Vault است. این پکیج به شما امکان می‌دهد به‌صورت امن اسرار (secrets) را از Vault خوانده و در برنامه Laravel خود استفاده کنید، بدون اینکه نیاز باشد اسرار را در فایل‌های `.env` یا کد منبع ذخیره کنید.

### ویژگی‌های کلیدی

- ✅ پشتیبانی کامل از **KV v2 Engine** هشی‌کرپ Vault
- ✅ **Token File Support** برای Vault Agent و محیط‌های کانتینری
- ✅ **کش داخلی** با TTL قابل تنظیم برای کاهش ترافیک به Vault
- ✅ **Runtime Config Injection** - تزریق امن تنظیمات دیتابیس و سایر کانفیگ‌ها
- ✅ **مدیریت خطای ایمن** - برنامه در صورت عدم دسترسی به Vault crash نمی‌کند
- ✅ سبک و بدون وابستگی اضافی
- ✅ سازگار با Laravel 9, 10, 11, 12

---

## نصب و راه‌اندازی

### گام ۱: نصب از طریق Composer

```bash
composer require shahkochaki/laravel-vault
```

پکیج به‌صورت خودکار از طریق Package Auto-Discovery لاراول ثبت می‌شود.

### گام ۲: انتشار فایل پیکربندی (اختیاری)

```bash
php artisan vendor:publish --provider="ShahKochaki\Vault\VaultServiceProvider" --tag=config
```

این دستور فایل `config/vault.php` را ایجاد می‌کند.

### گام ۳: تنظیم متغیرهای محیطی

فایل `.env` خود را ویرایش کنید:

```env
VAULT_ADDR=https://vault.example.com:8200
VAULT_TOKEN=your_vault_token_here
VAULT_ENGINE=secret
VAULT_PATH=app/production
VAULT_SECRET=database
```

**یا برای محیط‌های production با Vault Agent:**

```env
VAULT_ADDR=https://vault.example.com:8200
VAULT_TOKEN=
VAULT_TOKEN_FILE=/var/run/secrets/vault-token
VAULT_ENGINE=secret
VAULT_PATH=app/production
VAULT_SECRET=database
```

---

## آموزش استفاده

### ۱. استفاده ساده - خواندن یک Secret

```php
<?php

namespace App\Http\Controllers;

use ShahKochaki\Vault\VaultService;

class ExampleController extends Controller
{
    public function index(VaultService $vault)
    {
        // خواندن secret از مسیر app/production/database
        $secret = $vault->getSecret('app/production/database');
        
        if ($secret) {
            // $secret یک آرایه associative است
            echo "Database Password: " . $secret['DB_PASSWORD'] ?? 'N/A';
            echo "Database User: " . $secret['DB_USER'] ?? 'N/A';
        } else {
            echo "Secret not found or Vault unavailable";
        }
        
        return response()->json($secret);
    }
}
```

### ۲. Dependency Injection در Constructor

```php
<?php

namespace App\Services;

use ShahKochaki\Vault\VaultService;

class PaymentService
{
    protected $vault;
    
    public function __construct(VaultService $vault)
    {
        $this->vault = $vault;
    }
    
    public function getApiCredentials()
    {
        $credentials = $this->vault->getSecret('app/payment/stripe');
        
        return [
            'api_key' => $credentials['STRIPE_KEY'] ?? null,
            'secret' => $credentials['STRIPE_SECRET'] ?? null,
        ];
    }
}
```

### ۳. استفاده از Service Container

```php
// در هر جای برنامه
$vault = app(ShahKochaki\Vault\VaultService::class);
$secret = $vault->getSecret('my/secret/path');
```

### ۴. پاک کردن کش یک Secret

اگر می‌خواهید کش یک secret خاص را پاک کنید:

```php
$vault->clearCache('app/production/database');

// حالا با خواندن مجدد، از Vault جدید می‌خواند
$freshSecret = $vault->getSecret('app/production/database');
```

---

## پیکربندی پیشرفته

### فایل `config/vault.php`

```php
<?php

return [
    // آدرس سرور Vault
    'addr' => env('VAULT_ADDR', 'http://127.0.0.1:8200'),
    
    // توکن احراز هویت (برای dev/test)
    'token' => env('VAULT_TOKEN', ''),
    
    // مسیر فایل توکن (برای production با Vault Agent)
    'token_file' => env('VAULT_TOKEN_FILE', ''),
    
    // نام engine در Vault (معمولاً 'secret')
    'engine' => env('VAULT_ENGINE', 'secret'),
    
    // مسیر پیش‌فرض برای خواندن secrets
    'path' => env('VAULT_PATH', ''),
    
    // Timeout برای درخواست‌های HTTP (ثانیه)
    'timeout' => 5,
    
    // مدت زمان کش کردن secrets (ثانیه)
    'cache_ttl' => 30,
    
    // مقدار تست (برای بررسی عملکرد)
    'test' => env('VAULT_TEST', 'vault_config_test_value'),
];
```

### تنظیمات مختلف محیط‌ها

**محیط توسعه (Development):**

```env
VAULT_ADDR=http://localhost:8200
VAULT_TOKEN=dev-only-token
VAULT_ENGINE=secret
```

**محیط تولید با Vault Agent (Production):**

```env
VAULT_ADDR=https://vault.example.com:8200
VAULT_TOKEN=
VAULT_TOKEN_FILE=/var/run/secrets/vault-token
VAULT_ENGINE=secret
VAULT_PATH=app/production
```

---

## سناریوهای کاربردی

### سناریو ۱: تزریق خودکار تنظیمات دیتابیس

پکیج به‌صورت خودکار در زمان boot این کلیدها را می‌خواند و در `config('database')` تزریق می‌کند:

در Vault، secret خود را با این کلیدها بسازید:

```json
{
  "DB_PASSWORD": "super_secret_password",
  "DB_USER": "app_user",
  "DB_HOST": "db.example.com",
  "DB_DATABASE": "production_db"
}
```

سپس در `.env`:

```env
VAULT_PATH=app/production
VAULT_SECRET=database
```

لاراول به‌صورت خودکار این مقادیر را در `config('database.connections.mysql')` اعمال می‌کند.

### سناریو ۲: API Keys و اعتبارنامه‌های شخص ثالث

```php
// ذخیره در Vault با مسیر: app/services/stripe
{
  "STRIPE_KEY": "pk_live_xxxxx",
  "STRIPE_SECRET": "sk_live_xxxxx",
  "STRIPE_WEBHOOK_SECRET": "whsec_xxxxx"
}
```

```php
// خواندن در Laravel
$vault = app(\ShahKochaki\Vault\VaultService::class);
$stripe = $vault->getSecret('app/services/stripe');

\Stripe\Stripe::setApiKey($stripe['STRIPE_SECRET']);
```

### سناریو ۳: استفاده در Job ها و Queue

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use ShahKochaki\Vault\VaultService;

class ProcessPayment implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;
    
    public function handle(VaultService $vault)
    {
        $credentials = $vault->getSecret('app/payment/gateway');
        
        // پردازش با credentials امن
        // ...
    }
}
```

### سناریو ۴: استفاده در Command ها

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ShahKochaki\Vault\VaultService;

class SyncDataCommand extends Command
{
    protected $signature = 'data:sync';
    
    public function handle(VaultService $vault)
    {
        $apiCredentials = $vault->getSecret('app/external/api');
        
        $this->info('Syncing data with API...');
        // استفاده از credentials
    }
}
```

---

## ساختار مسیر در Vault

این پکیج به‌صورت خودکار مسیرهای KV v2 را می‌سازد:

```
Input Path: app/production/database
Vault API Path: /v1/secret/data/app/production/database
                     ↑      ↑
                  engine   /data/ (KV v2)
```

اگر مسیر کامل API را بدهید، همان را استفاده می‌کند:

```php
$vault->getSecret('v1/secret/data/custom/path');
```

---

## Token File و Vault Agent

در محیط‌های production، توصیه می‌شود از Vault Agent یا Kubernetes Auth استفاده کنید:

### با Vault Agent:

```bash
# فایل vault-agent.hcl
auto_auth {
  method "approle" {
    config = {
      role_id_file_path = "/etc/vault/role-id"
      secret_id_file_path = "/etc/vault/secret-id"
    }
  }
  
  sink "file" {
    config = {
      path = "/var/run/secrets/vault-token"
    }
  }
}
```

سپس در `.env`:

```env
VAULT_TOKEN_FILE=/var/run/secrets/vault-token
```

---

## مدیریت خطا و Logging

پکیج به‌صورت ایمن با خطاها برخورد می‌کند:

```php
$secret = $vault->getSecret('non/existent/path');
// اگر secret وجود نداشته باشد، null برمی‌گرداند
// و یک warning در log می‌نویسد

if ($secret === null) {
    // مدیریت حالت عدم دسترسی
    Log::warning('Failed to fetch secret from Vault');
    // fallback به مقادیر پیش‌فرض
}
```

Logs را در `storage/logs/laravel.log` بررسی کنید:

```
[2025-12-09 12:34:56] local.WARNING: VaultService getSecret failed: cURL error 28: Timeout
```

---

## کش و Performance

پکیج به‌صورت خودکار secrets را کش می‌کند:

- **TTL پیش‌فرض**: 30 ثانیه
- **تنظیم TTL**: در `config/vault.php` → `cache_ttl`
- **پاک کردن کش**: `$vault->clearCache($path)`

```php
// خواندن اول - از Vault
$secret = $vault->getSecret('app/db'); // HTTP request به Vault

// خواندن دوم (در 30 ثانیه بعد) - از کش
$secret = $vault->getSecret('app/db'); // از cache

// پاک کردن و خواندن مجدد
$vault->clearCache('app/db');
$secret = $vault->getSecret('app/db'); // دوباره HTTP request
```

---

## تست و Development

### تست محلی با Vault Dev Server

```bash
# نصب و اجرای Vault در حالت dev
vault server -dev

# در ترمینال دیگر
export VAULT_ADDR='http://127.0.0.1:8200'
export VAULT_TOKEN='root'  # توکن dev mode

# ساخت یک secret تست
vault kv put secret/app/test DB_PASSWORD=test123 DB_USER=testuser
```

در `.env`:

```env
VAULT_ADDR=http://127.0.0.1:8200
VAULT_TOKEN=root
```

### تست در Laravel:

```php
php artisan tinker

>>> $vault = app(\ShahKochaki\Vault\VaultService::class);
>>> $secret = $vault->getSecret('app/test');
>>> dd($secret);
// => ["DB_PASSWORD" => "test123", "DB_USER" => "testuser"]
```

---

## امنیت و Best Practices

### ✅ انجام دهید:

- از `token_file` در production استفاده کنید
- TTL کوتاه برای توکن‌ها تنظیم کنید
- از Vault Agent یا AppRole auth استفاده کنید
- Secrets را در runtime بخوانید، نه در زمان build
- Logs را برای خطاهای Vault مانیتور کنید

### ❌ انجام ندهید:

- توکن‌های Vault را در git commit نکنید
- از root token در production استفاده نکنید
- TTL کش را خیلی بالا نگذارید
- Secrets را در متغیرهای global ذخیره نکنید
- از توکن‌های static طولانی‌مدت استفاده نکنید

---

## عیب‌یابی (Troubleshooting)

### خطا: "VaultService getSecret failed: Connection refused"

```bash
# بررسی کنید Vault در دسترس است
curl -k $VAULT_ADDR/v1/sys/health

# بررسی کنید VAULT_ADDR صحیح است
echo $VAULT_ADDR
```

### خطا: "403 permission denied"

- بررسی کنید توکن شما دسترسی به مسیر درخواستی دارد
- Policy مربوطه را بررسی کنید:

```bash
vault token lookup
vault policy read <policy-name>
```

### Secret خوانده نمی‌شود (null برمی‌گردد)

```php
// فعال‌سازی debug logging
Log::debug('Attempting to read: app/production/db');
$secret = $vault->getSecret('app/production/db');
Log::debug('Result: ', ['secret' => $secret]);

// بررسی کش
$vault->clearCache('app/production/db');
$secret = $vault->getSecret('app/production/db');
```

---

## ارتقا و تغییرات

### نسخه ۱.۱.۱ (فعلی)

- ✨ افزودن پشتیبانی `token_file`
- 🐛 بهبود مدیریت خطا و logging
- 📝 بهبود مستندات

برای مشاهده تغییرات کامل، به فایل [CHANGELOG.md](CHANGELOG.md) مراجعه کنید.

---

## مشارکت و پشتیبانی

- 🐛 **گزارش باگ**: [GitHub Issues](https://github.com/shahkochaki/laravel-vault/issues)
- 💡 **درخواست ویژگی جدید**: [GitHub Issues](https://github.com/shahkochaki/laravel-vault/issues)
- 📖 **مستندات**: همین فایل README
- 📦 **Packagist**: [shahkochaki/laravel-vault](https://packagist.org/packages/shahkochaki/laravel-vault)

---

## مجوز

این پکیج تحت مجوز MIT منتشر شده است. برای اطلاعات بیشتر به فایل [LICENSE](LICENSE) مراجعه کنید.

---

## تشکر

ساخته شده با ❤️ برای جامعه Laravel و HashiCorp Vault

**نسخه**: 1.1.1  
**نویسنده**: [shahkochaki](https://github.com/shahkochaki)
