# Myth/Scribe

A CodeIgniter 4 package that gives you a clean, driver-based abstraction for talking to AI language models. Point it at Claude, OpenAI, or Gemini — your application code stays the same regardless of which provider you use.

[![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen.svg)](https://phpstan.org/)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

📖 **[Full Documentation →](https://lonnieezell.github.io/scribe)**

---

## What it does

You define a **Prompt** class that describes what you want the AI to do. Scribe takes care of building the system prompt, calling the right provider, and returning a normalized **AIResponse** you can work with in plain PHP.

```php
<?php

// Define once
class SummarizePrompt extends BasePrompt
{
    public function __construct(private string $text) {}

    public function systemPrompt(): string
    {
        return 'You are a concise summarizer. Return a 2-sentence summary.';
    }

    public function userPrompt(): string
    {
        return 'Summarize: ' . $this->text;
    }
}

// Use anywhere in your CI4 app
$response = service('scribe')->run(new SummarizePrompt($articleText));
echo $response->content;
```

## Key Features

- **Driver-based** — swap providers (Claude, OpenAI, Gemini) without touching your prompt classes
- **Structured output** — pass a JSON schema and get typed data back via `toArray()`
- **Test-friendly** — `FakeDriver` lets you unit-test prompts without any HTTP calls
- **CI4-native** — auto-discovered via Composer, wired into CI4's service layer; no manual wiring needed

## What it isn't

- **No streaming** — responses are returned as a complete string, not streamed token-by-token
- **No conversation management** — each `run()` call is stateless; multi-turn chat history is your responsibility
- **No agent loop** — there is no built-in tool-calling or autonomous reasoning loop
- **Not a full AI framework** — no embeddings, RAG, vector search, or fine-tuning helpers
- **Not a managed client** — it does not retry, throttle, or rotate API keys on your behalf

If you need those features, this library is intentionally out of scope for them.

## Requirements

- PHP 8.2+
- CodeIgniter 4.7+

## Installation

Install via Composer:

```bash
composer require lonnieezell/scribe
```

Publish the config file so you can add your API keys:

```bash
php spark publish:config AI
```

Then open `app/Config/AI.php` and set at least one driver's `apiKey`:

```php
public array $drivers = [
    'claude' => [
        'apiKey' => env('CLAUDE_API_KEY', ''),
        'model'  => 'claude-haiku-4-5',
    ],
    // ...
];
```

## Quick Start

### 1. Create a Prompt class

```php
<?php

namespace App\Prompts;

use Myth\Scribe\Prompts\BasePrompt;

class ClassifyEmailPrompt extends BasePrompt
{
    public function __construct(private string $emailBody) {}

    public function systemPrompt(): string
    {
        return 'Classify the email as spam or not_spam.';
    }

    public function userPrompt(): string
    {
        return $this->emailBody;
    }

    public function schema(): ?array
    {
        return [
            'type'       => 'object',
            'properties' => ['label' => ['type' => 'string', 'enum' => ['spam', 'not_spam']]],
            'required'   => ['label'],
        ];
    }
}
```

### 2. Run it

```php
$response = service('scribe')->run(new ClassifyEmailPrompt($body));
$result   = $response->toArray(); // ['label' => 'spam']
```

### 3. Test it

```php
use Myth\Scribe\Drivers\FakeDriver;

$fake = new FakeDriver(['content' => '{"label":"spam"}']);
// inject $fake into your service or swap the driver in config
```

## Configuration

The `AI` config class lives at `app/Config/AI.php` after publishing. The key settings:

| Key | Description |
|-----|-------------|
| `$defaultDriver` | Driver used when none is specified on the prompt (default: `'claude'`) |
| `$drivers['claude']` | Anthropic Claude settings: `apiKey`, `model`, `timeout` |
| `$drivers['openai']` | OpenAI settings: `apiKey`, `model`, `timeout` |
| `$drivers['gemini']` | Google Gemini settings: `apiKey`, `model`, `timeout` |

A specific prompt can override the driver at runtime:

```php
$prompt = new SummarizePrompt($text);
$prompt->driver = 'openai';
```

## Documentation

Full documentation — installation, core concepts, configuration, structured output, testing, and the changelog — is available at:

**[https://lonnieezell.github.io/scribe](https://lonnieezell.github.io/scribe)**

## Contributing

1. Fork the repo and create a feature branch.
2. Run the full CI suite before opening a PR: `composer ci`
3. All new code must include tests and pass PHPStan level 5.

## License

MIT — see [LICENSE](LICENSE).
