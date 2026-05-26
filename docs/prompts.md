# Prompts

A prompt is a class that encapsulates everything the AI needs to know about a single request. You create one by extending `BasePrompt`.

## The basics

Two abstract methods are required:

```php
class TranslatePrompt extends BasePrompt
{
    public function __construct(
        private string $text,
        private string $targetLanguage,
    ) {}

    public function systemPrompt(): string
    {
        return "You are a professional translator. Translate text into {$this->targetLanguage}. Return only the translation.";
    }

    public function userPrompt(): string
    {
        return $this->text;
    }
}
```

Pass it to the service:

```php
$response = service('scribe')->run(new TranslatePrompt('Hello!', 'French'));
echo $response->content; // "Bonjour !"
```

## Format hints

Set `$format` to append a format instruction to the system prompt automatically:

```php
class SentimentPrompt extends BasePrompt
{
    public string $format = 'json';

    public function systemPrompt(): string
    {
        return 'Analyze the sentiment of the text.';
    }

    public function userPrompt(): string { /* ... */ }
}
```

Scribe appends `"\n\nRespond in json format."` to your system prompt. Simple and non-prescriptive — useful when you want JSON but don't need to enforce a specific shape.

## Structured output with schema()

For precise structured output, override `schema()` instead of setting `$format`. Scribe encodes your schema and appends it to the system prompt:

```php
class ExtractContactPrompt extends BasePrompt
{
    public function systemPrompt(): string
    {
        return 'Extract contact information from the text.';
    }

    public function userPrompt(): string { /* ... */ }

    public function schema(): ?array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name'  => ['type' => 'string'],
                'email' => ['type' => 'string', 'format' => 'email'],
                'phone' => ['type' => 'string'],
            ],
            'required' => ['name'],
        ];
    }
}

// Then:
$data = service('scribe')->run(new ExtractContactPrompt($text))->toArray();
// ['name' => 'Alice Smith', 'email' => 'alice@example.com', 'phone' => null]
```

!!! warning "Don't set both $format and schema()"
    If you set `$format` and also return a non-null `schema()`, Scribe uses the schema and logs a warning. The format hint is ignored. Pick one.

## Assistant prefill

Some providers (notably Claude) support an assistant prefill — text that primes the model's response. Override `assistant()` to use it:

```php
public function assistant(): ?string
{
    return '{"'; // nudge the model to start a JSON object
}
```

## Overriding the driver

By default every prompt uses the driver configured in `Config/AI::$defaultDriver`. Override it per-prompt when you need a specific provider for a specific task:

```php
class ModerationPrompt extends BasePrompt
{
    public ?string $driver = 'openai'; // always use OpenAI for this one

    // ...
}
```

## Passing driver options

`$options` is an escape hatch for provider-specific settings that don't fit the standard interface:

```php
class CreativePrompt extends BasePrompt
{
    public array $options = [
        'temperature' => 1.2,
        'max_tokens'  => 500,
    ];

    // ...
}
```

The driver receives `$options` as-is via `complete()`. What it does with them is driver-specific.

## Next steps

- [AIResponse](ai-response.md) — working with what comes back
- [Configuration](configuration.md) — choosing which driver runs by default
- [Testing](testing.md) — test your prompts without HTTP calls
