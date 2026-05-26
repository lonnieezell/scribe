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

namespace Myth\Scribe\Drivers;

use Myth\Scribe\AIResponse;

interface AIDriver
{
    /**
     * Send a completion request to the AI provider.
     *
     * @param array<string, mixed>|null $schema  JSON schema for structured output
     * @param array<string, mixed>      $options Driver-specific options
     */
    public function complete(
        string $system,
        string $user,
        ?string $assistant,
        ?array $schema,
        array $options,
    ): AIResponse;
}
