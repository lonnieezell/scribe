# Installation

## Requirements

- PHP 8.2 or higher
- CodeIgniter 4.7 or higher

## Install via Composer

```bash
composer require lonnieezell/scribe
```

CI4 auto-discovers the package via Composer's PSR-4 autoload — no manual wiring in `Config/Registrar.php` or `Config/Services.php` is needed.

## Publish the config file

Scribe ships with a `Config/AI.php` that holds your driver settings. Copy it into your application so you can customize it:

```bash
php spark config:publish Myth\Scribe\Config\AI
```

This creates `app/Config/AI.php`. Open it and add your API key for whichever provider you want to use:

```php
// app/Config/AI.php
public array $drivers = [
    'claude' => [
        'apiKey'  => env('CLAUDE_API_KEY', ''),
        'model'   => 'claude-haiku-4-5',
        'timeout' => 30,
    ],
    // ...
];
```

!!! tip "Use environment variables"
    Never commit API keys to version control. Store them in `.env` and reference them with `env('YOUR_KEY')`.

## Verify the install

Run this in a controller or a quick Spark command to confirm everything is wired up:

```php
$service = service('scribe');
// Should return an AIService instance — no exception means you're good.
var_dump(get_class($service)); // "Myth\Scribe\AIService"
```

## Next steps

- [Configuration](configuration.md) — all the driver options explained
- [Core Concepts](core-concepts.md) — understand prompts, drivers, and responses
