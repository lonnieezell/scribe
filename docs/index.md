# Myth/Scribe

Scribe is a CodeIgniter 4 package that gives you a clean, driver-based abstraction for talking to AI language models. Point it at Claude, OpenAI, Gemini, or Mistral — your application code stays the same regardless of which provider you use.

## What it does

You define a **Prompt** class that describes what you want the AI to do. Scribe takes care of building the system prompt, calling the right provider, and returning a normalized **AIResponse** you can work with in plain PHP.

```php
// Define once
class SummarizePrompt extends BasePrompt
{
    public function systemPrompt(): string
    {
        return 'You are a concise summarizer. Return a 2-sentence summary.';
    }

    public function userPrompt(): string
    {
        return 'Summarize: ' . $this->text;
    }

    public function __construct(private string $text) {}
}

// Use anywhere
$response = service('scribe')->run(new SummarizePrompt($articleText));
echo $response->content;
```

## Key features

- **Driver-based** — swap providers without touching your prompt classes
- **Structured output** — pass a JSON schema and get typed data back via `toArray()`
- **Test-friendly** — `FakeDriver` lets you unit-test prompts without any HTTP calls
- **CI4-native** — auto-discovered via Composer, wired into CI4's service layer

## Next steps

- [Installation](installation.md) — get up and running in a few minutes
- [Core Concepts](core-concepts.md) — understand the mental model before diving in
- [Configuration](configuration.md) — set your API keys and pick your models
