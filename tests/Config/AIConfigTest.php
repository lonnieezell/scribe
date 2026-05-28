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

namespace Tests\Config;

use CodeIgniter\Test\CIUnitTestCase;
use Myth\Scribe\Config\AI;

/**
 * @internal
 */
final class AIConfigTest extends CIUnitTestCase
{
    public function testHasDefaultDriver(): void
    {
        $config = new AI();
        $this->assertSame('claude', $config->defaultDriver);
    }

    public function testHasDriverEntries(): void
    {
        $config  = new AI();
        $drivers = ['claude', 'openai', 'gemini'];

        foreach ($drivers as $key) {
            $this->assertArrayHasKey($key, $config->drivers, "Driver '{$key}' missing from config");
        }
    }

    public function testDriverEntryHasRequiredKeys(): void
    {
        $config = new AI();

        foreach ($config->drivers as $name => $driver) {
            $this->assertArrayHasKey('apiKey', $driver, "{$name} missing apiKey");
            $this->assertArrayHasKey('model', $driver, "{$name} missing model");
            $this->assertArrayHasKey('timeout', $driver, "{$name} missing timeout");
            $this->assertSame(30, $driver['timeout'], "{$name} timeout should default to 30");
        }
    }
}
