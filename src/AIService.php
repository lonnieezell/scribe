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

namespace Myth\Scribe;

use Myth\Scribe\Config\AI;
use Myth\Scribe\Drivers\AIDriver;
use Myth\Scribe\Exceptions\AIException;
use Myth\Scribe\Prompts\BasePrompt;

class AIService
{
    /**
     * @param array<string, callable(): AIDriver> $driverFactories
     */
    public function __construct(
        private readonly AI $config,
        private readonly array $driverFactories,
    ) {
    }

    /**
     * Resolve the correct driver, call complete(), and return a normalised AIResponse.
     *
     * @throws AIException when the resolved driver key is not registered
     */
    public function run(BasePrompt $prompt): AIResponse
    {
        $key = $prompt->driver ?? $this->config->defaultDriver;

        if (! isset($this->driverFactories[$key])) {
            throw new AIException("Unknown AI driver key: '{$key}'");
        }

        /** @var AIDriver $driver */
        $driver = ($this->driverFactories[$key])();

        return $driver->complete(
            system: $prompt->buildSystemPrompt(),
            user: $prompt->userPrompt(),
            assistant: $prompt->assistant(),
            schema: $prompt->schema(),
            options: $prompt->options,
        );
    }
}
