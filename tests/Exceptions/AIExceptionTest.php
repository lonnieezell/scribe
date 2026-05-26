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

namespace Tests\Exceptions;

use CodeIgniter\Test\CIUnitTestCase;
use Myth\Scribe\Exceptions\AIAuthException;
use Myth\Scribe\Exceptions\AIException;
use Myth\Scribe\Exceptions\AIRateLimitException;
use Myth\Scribe\Exceptions\PackageException;

/**
 * @internal
 */
final class AIExceptionTest extends CIUnitTestCase
{
    public function testAIExceptionExtendsPackageException(): void
    {
        $e = new AIException('Something failed');
        $this->assertInstanceOf(PackageException::class, $e);
        $this->assertSame('Something failed', $e->getMessage());
    }

    public function testAIAuthExceptionExtendsAIException(): void
    {
        $e = new AIAuthException('Invalid API key');
        $this->assertInstanceOf(AIException::class, $e);
        $this->assertSame('Invalid API key', $e->getMessage());
    }

    public function testAIRateLimitExceptionExtendsAIException(): void
    {
        $e = new AIRateLimitException('Rate limit exceeded');
        $this->assertInstanceOf(AIException::class, $e);
        $this->assertSame('Rate limit exceeded', $e->getMessage());
    }
}
