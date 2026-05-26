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

/**
 * In-memory driver for use in tests. No HTTP calls are made.
 */
class FakeDriver implements AIDriver
{
    private AIResponse $response;

    public function __construct(?AIResponse $response = null)
    {
        $this->response = $response ?? new AIResponse(
            content: 'fake-response',
            model: 'fake-model',
            inputTokens: 0,
            outputTokens: 0,
            raw: [],
        );
    }

    public function complete(
        string $system,
        string $user,
        ?string $assistant,
        ?array $schema,
        array $options,
    ): AIResponse {
        return $this->response;
    }
}
