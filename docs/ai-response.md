# AIResponse

Every call to `service('scribe')->run($prompt)` returns an `AIResponse`. It's a readonly value object — nothing on it can be mutated after creation.

## Properties

```php
<?php

$response->content       // string — the model's reply
$response->model         // string — model identifier used (e.g. "claude-haiku-4-5")
$response->inputTokens   // int    — tokens consumed by your prompt + system
$response->outputTokens  // int    — tokens in the model's reply
$response->raw           // array  — the full, unprocessed provider response
```

### content

This is what you're usually after — the model's reply as a plain string:

```php
<?php

$response = service('scribe')->run(new SummarizePrompt($text));
echo $response->content;
```

### model

Useful for logging, debugging, or cost attribution when your config uses different models per environment:

```php
<?php

log_message('info', 'AI request used model: ' . $response->model);
```

### inputTokens / outputTokens

Track token usage for cost monitoring or staying within provider limits:

```php
<?php

$totalTokens = $response->inputTokens + $response->outputTokens;
```

### raw

The full response payload exactly as the driver received it from the provider. Useful when you need something the normalized properties don't expose — like finish reason, logprobs, or tool call metadata:

```php
<?php

$finishReason = $response->raw['choices'][0]['finish_reason'] ?? null;
```

What's in `raw` is driver-specific and may change across provider API versions.

## toArray()

When your prompt asks for structured JSON output (via `schema()` or `$format = 'json'`), decode the response with `toArray()`:

```php
<?php

$data = $response->toArray();
// Returns the decoded array, e.g. ['name' => 'Alice', 'email' => 'alice@example.com']
```

`toArray()` decodes `content` with `json_decode` and returns the result. It throws `AIException` if `content` isn't valid JSON:

```php
<?php

try {
    $data = $response->toArray();
} catch (AIException $e) {
    // Model didn't return valid JSON — log it and fall back
    log_message('warning', 'AI response was not JSON: ' . $e->getMessage());
}
```

!!! tip "When to catch AIException"
    Models occasionally produce non-JSON output even when instructed otherwise — especially under high temperature settings or for edge-case inputs. Wrapping `toArray()` in a try/catch is good practice in production code.

## Next steps

- [Prompts](prompts.md) — how to request structured JSON output via schema()
- [Testing](testing.md) — return a custom AIResponse from FakeDriver in your tests
