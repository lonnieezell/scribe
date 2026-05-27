# Testing with FakeDriver

Scribe ships with `FakeDriver` — an in-memory driver that returns whatever you tell it to, with no HTTP calls. Use it whenever you want to test prompt logic, service wiring, or response handling without hitting a real API.

## Basic usage

Construct an `AIService` directly with `FakeDriver` factories instead of using `service('scribe')`:

```php
<?php

use Myth\Scribe\AIService;
use Myth\Scribe\Config\AI;
use Myth\Scribe\Drivers\FakeDriver;

// In your test setUp or test method:
$config  = new AI();
$service = new AIService($config, [
    'claude' => static fn () => new FakeDriver(),
]);

$response = $service->run(new MyPrompt());

$this->assertSame('fake-response', $response->content);
```

`FakeDriver` returns a response with `content = 'fake-response'` and `model = 'fake-model'` by default.

## Returning custom responses

Pass an `AIResponse` into `FakeDriver`'s constructor to control exactly what comes back:

```php
<?php

use Myth\Scribe\AIResponse;
use Myth\Scribe\Drivers\FakeDriver;

$canned = new AIResponse(
    content: '{"label":"SPAM","confidence":0.97}',
    model: 'claude-haiku-4-5',
    inputTokens: 42,
    outputTokens: 18,
    raw: [],
);

$service = new AIService($config, [
    'claude' => static fn () => new FakeDriver($canned),
]);

$response = $service->run(new ClassifyEmailPrompt($emailBody));
$data = $response->toArray();

$this->assertSame('SPAM', $data['label']);
$this->assertSame(0.97, $data['confidence']);
```

This lets you test the full round-trip — including `toArray()` decoding and any business logic that acts on the response — without mocking anything.

## Testing the toArray() error path

To test that your code handles a non-JSON response gracefully:

```php
<?php

$badResponse = new AIResponse(
    content: 'Sorry, I cannot help with that.',
    model: 'claude-haiku-4-5',
    inputTokens: 10,
    outputTokens: 8,
    raw: [],
);

$service = new AIService($config, [
    'claude' => static fn () => new FakeDriver($badResponse),
]);

$this->expectException(AIException::class);
$service->run(new MyStructuredPrompt())->toArray();
```

## Testing prompt construction

`FakeDriver` ignores all arguments passed to `complete()` — which is fine for response-side tests but means you can't assert what the prompt actually sent.

To verify that `buildSystemPrompt()` produces the right output, test the prompt class directly:

```php
<?php

$prompt = new ClassifyEmailPrompt('Buy cheap meds now!!!');

$this->assertStringContainsString('Classify', $prompt->buildSystemPrompt());
$this->assertSame('Buy cheap meds now!!!', $prompt->userPrompt());
$this->assertNull($prompt->assistant());
```

Prompt classes are plain PHP — no service container needed. Test them directly.

## Testing unknown driver errors

Verify that your code handles an unregistered driver key gracefully:

```php
<?php

$config = new AI();
$config->defaultDriver = 'nonexistent';

$service = new AIService($config, []); // no factories registered

$this->expectException(AIException::class);
$service->run(new MyPrompt());
```

## CI4 test setup

If you're writing `CIUnitTestCase` tests, extend the base class as usual — the CI4 test bootstrap (set in `phpunit.xml.dist`) handles service container setup automatically:

```php
<?php

use CodeIgniter\Test\CIUnitTestCase;
use Myth\Scribe\AIService;
use Myth\Scribe\Config\AI;
use Myth\Scribe\Drivers\FakeDriver;

final class ClassifyEmailTest extends CIUnitTestCase
{
    private AIService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new AIService(new AI(), [
            'claude' => static fn () => new FakeDriver(
                new AIResponse('{"label":"HAM"}', 'claude-haiku-4-5', 5, 3, [])
            ),
        ]);
    }

    public function testClassifiesHam(): void
    {
        $response = $this->service->run(new ClassifyEmailPrompt('Hi Alice, see you at 3pm.'));
        $this->assertSame('HAM', $response->toArray()['label']);
    }
}
```

## Next steps

- [Prompts](prompts.md) — what to test in your prompt classes
- [AIResponse](ai-response.md) — the full response API
