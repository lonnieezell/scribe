<?php

declare(strict_types=1);

/**
 * This file is part of Myth/Scribe.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use Myth\Scribe\AIResponse;
use Myth\Scribe\Exceptions\AIException;

/**
 * @internal
 */
final class AIResponseTest extends CIUnitTestCase
{
    public function testHoldsProperties(): void
    {
        $raw  = ['id' => 'abc', 'choices' => []];
        $resp = new AIResponse(
            content: 'Hello world',
            model: 'claude-sonnet-4-6',
            inputTokens: 10,
            outputTokens: 25,
            raw: $raw,
        );

        $this->assertSame('Hello world', $resp->content);
        $this->assertSame('claude-sonnet-4-6', $resp->model);
        $this->assertSame(10, $resp->inputTokens);
        $this->assertSame(25, $resp->outputTokens);
        $this->assertSame($raw, $resp->raw);
    }

    public function testToArrayDecodesJsonContent(): void
    {
        $resp = new AIResponse(
            content: '{"name":"Alice","age":30}',
            model: 'gpt-4o',
            inputTokens: 5,
            outputTokens: 12,
            raw: [],
        );

        $this->assertSame(['name' => 'Alice', 'age' => 30], $resp->toArray());
    }

    public function testToArrayThrowsOnNonJsonContent(): void
    {
        $resp = new AIResponse(
            content: 'This is plain text, not JSON.',
            model: 'gpt-4o',
            inputTokens: 5,
            outputTokens: 8,
            raw: [],
        );

        $this->expectException(AIException::class);
        $resp->toArray();
    }
}
