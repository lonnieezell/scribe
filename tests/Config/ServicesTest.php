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
use Myth\Scribe\AIService;
use Myth\Scribe\Config\Services;

/**
 * @internal
 */
final class ServicesTest extends CIUnitTestCase
{
    public function testScribeServiceCanRunWithoutException(): void
    {
        // Verify service construction succeeds and returns the expected class
        $service = Services::scribe();
        $this->assertSame(AIService::class, get_class($service));
    }

    public function testScribeServiceIsShared(): void
    {
        $a = Services::scribe();
        $b = Services::scribe();
        $this->assertSame($a, $b);
    }

    public function testScribeServiceCanBeNonShared(): void
    {
        $a = Services::scribe(false);
        $b = Services::scribe(false);
        $this->assertNotSame($a, $b);
    }
}
