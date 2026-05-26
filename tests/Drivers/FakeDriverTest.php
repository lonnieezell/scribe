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

namespace Tests\Drivers;

use CodeIgniter\Test\CIUnitTestCase;
use Myth\Scribe\AIResponse;
use Myth\Scribe\Drivers\AIDriver;
use Myth\Scribe\Drivers\FakeDriver;

/**
 * @internal
 */
final class FakeDriverTest extends CIUnitTestCase
{
    public function testImplementsAIDriver(): void
    {
        $driver = new FakeDriver();
        $this->assertInstanceOf(AIDriver::class, $driver);
    }

    public function testReturnsDefaultResponse(): void
    {
        $driver   = new FakeDriver();
        $response = $driver->complete('sys', 'user', null, null, []);

        $this->assertInstanceOf(AIResponse::class, $response);
        $this->assertSame('fake-response', $response->content);
        $this->assertSame('fake-model', $response->model);
        $this->assertSame(0, $response->inputTokens);
        $this->assertSame(0, $response->outputTokens);
    }

    public function testAllowsCustomResponse(): void
    {
        $custom = new AIResponse('{"ok":true}', 'gpt-4o', 5, 10, []);
        $driver = new FakeDriver($custom);

        $response = $driver->complete('sys', 'user', null, null, []);
        $this->assertSame($custom, $response);
    }
}
