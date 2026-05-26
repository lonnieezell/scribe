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

use Myth\Scribe\Exceptions\AIException;

readonly class AIResponse
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $content,
        public string $model,
        public int $inputTokens,
        public int $outputTokens,
        public array $raw,
    ) {
    }

    /**
     * Decodes the response content as JSON and returns it as an array.
     *
     * @return array<string, mixed>
     *
     * @throws AIException when content is not valid JSON
     */
    public function toArray(): array
    {
        $decoded = json_decode($this->content, true);

        if (! is_array($decoded)) {
            throw new AIException('Response content is not valid JSON: ' . $this->content);
        }

        return $decoded;
    }
}
