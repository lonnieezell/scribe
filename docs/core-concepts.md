# Core Concepts

Scribe has four moving parts. Once you understand how they fit together, everything else is just detail.

## The flow

```
Your code
  → BasePrompt   (what to ask)
  → AIService    (which driver to use)
  → AIDriver     (talks to the provider)
  → AIResponse   (what came back)
```

## BasePrompt

A **Prompt** is a plain PHP class that knows what to say to the AI. You extend `BasePrompt` and implement two methods:

```php
class ClassifyEmailPrompt extends BasePrompt
{
    public function systemPrompt(): string
    {
        return 'Classify the email as SPAM, HAM, or UNKNOWN. Reply with one word only.';
    }

    public function userPrompt(): string
    {
        return $this->emailBody;
    }

    public function __construct(private string $emailBody) {}
}
```

That's it for the common case. Prompts can also carry:

- `$format` — a format hint appended to the system prompt (e.g. `'json'`)
- `schema()` — a JSON schema for structured output
- `$driver` — override the default driver for this specific prompt
- `$options` — driver-specific options (passed through as-is)

See [Prompts](prompts.md) for details on all of these.

## AIService

`AIService` is the engine. You get it via CI4's service locator:

```php
$service = service('scribe');
$response = $service->run(new ClassifyEmailPrompt($body));
```

It does three things:

1. Picks the right driver (from the prompt's `$driver` property, or the config default)
2. Calls `buildSystemPrompt()` on your prompt to get the final system string
3. Hands everything to the driver and returns the `AIResponse`

You don't subclass `AIService`. You configure it via `Config/AI.php`.

## AIDriver

A **Driver** is the adapter that talks to one AI provider. The `AIDriver` interface defines a single method:

```php
interface AIDriver
{
    public function complete(
        string $system,
        string $user,
        ?string $assistant,
        ?array $schema,
        array $options,
    ): AIResponse;
}
```

Scribe ships with `FakeDriver` for testing. Real HTTP drivers (Claude, OpenAI, Gemini, Mistral) are registered by driver packages built on top of this foundation.

## AIResponse

Every `run()` call returns an `AIResponse` — a readonly value object:

```php
$response->content       // the model's reply (string)
$response->model         // which model was used
$response->inputTokens   // tokens consumed by your prompt
$response->outputTokens  // tokens in the reply
$response->raw           // the full provider response (array)
```

When you asked for structured JSON output, call `toArray()` to decode it:

```php
$data = $response->toArray(); // ['label' => 'SPAM', 'confidence' => 0.92]
```

`toArray()` throws `AIException` if the content isn't valid JSON — so you know immediately if the model wandered off-format.

## Next steps

- [Prompts](prompts.md) — format hints, schemas, per-prompt driver overrides
- [Configuration](configuration.md) — set up your drivers and API keys
- [Testing with FakeDriver](testing.md) — write tests that don't make HTTP calls
