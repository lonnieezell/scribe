# Configuration

All of Scribe's settings live in `app/Config/AI.php` after you [publish the config](installation.md#publish-the-config-file).

## defaultDriver

```php
<?php

public string $defaultDriver = 'claude';
```

The driver key to use when a prompt doesn't specify one. Must match a key in the `$drivers` array below.

Change it to switch providers globally:

```php
<?php

public string $defaultDriver = 'openai';
```

## drivers

```php
<?php

public array $drivers = [
    'claude'  => [...],
    'openai'  => [...],
    'gemini'  => [...],
    'mistral' => [...],
];
```

Each driver entry is a keyed array. The key is what you use in `$defaultDriver` and in `BasePrompt::$driver`.

### Per-driver options

Every driver supports these keys:

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `apiKey` | string | `''` | Your API key for this provider |
| `model` | string | provider default | The model to use for completion requests |
| `timeout` | int | `30` | Request timeout in seconds |
| `maxTokens` | int | `4096` | Maximum tokens the model may generate (Claude only) |
| `baseUrl` | string | *(optional)* | Override the provider's API endpoint |

### Example: full config

```php
<?php

public array $drivers = [
    'claude' => [
        'apiKey'    => env('ANTHROPIC_API_KEY', ''),
        'model'     => 'claude-haiku-4-5',
        'timeout'   => 30,
        'maxTokens' => 4096,
    ],
    'openai' => [
        'apiKey'  => env('OPENAI_API_KEY', ''),
        'model'   => 'gpt-5.4-mini',
        'timeout' => 30,
    ],
    'gemini' => [
        'apiKey'  => env('GOOGLE_API_KEY', ''),
        'model'   => 'gemini-flash-latest',
        'timeout' => 30,
    ],
    'mistral' => [
        'apiKey'  => env('MISTRAL_API_KEY', ''),
        'model'   => 'mistral-large-latest',
        'timeout' => 30,
    ],
];
```

### Using a custom endpoint

The optional `baseUrl` key lets you point a driver at a compatible API endpoint — useful for local models or proxies:

```php
<?php

'openai' => [
    'apiKey'  => 'not-needed',
    'model'   => 'local-llama',
    'timeout' => 60,
    'baseUrl' => 'http://localhost:11434/v1',
],
```

### Adding a custom driver

You can add your own driver key for any provider that has a matching Scribe driver package:

```php
<?php

public string $defaultDriver = 'my-provider';

public array $drivers = [
    'my-provider' => [
        'apiKey'  => env('MY_PROVIDER_KEY', ''),
        'model'   => 'my-model',
        'timeout' => 30,
    ],
];
```

!!! note
    Scribe doesn't validate driver keys at config-load time. If you reference a key with no registered driver factory, `AIService::run()` throws `AIException` with a clear message.

## Next steps

- [Prompts](prompts.md) — override the driver on a per-prompt basis
- [Testing](testing.md) — use FakeDriver so tests don't hit real endpoints
