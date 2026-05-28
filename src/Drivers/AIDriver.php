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
 * Contract that all AI driver implementations must fulfil.
 */
interface AIDriver
{
    /**
     * Prefix appended by BasePrompt::buildSystemPrompt() when a schema is present.
     * Drivers that handle schema natively should strip this from $system to avoid duplication.
     */
    public const SCHEMA_SYSTEM_MARKER = "\n\nRespond using the following JSON schema:\n";

    /**
     * Send a completion request to the AI provider.
     *
     * Note: when $schema is non-null, BasePrompt::buildSystemPrompt() has already
     * appended the schema as text instructions inside $system. Drivers that support
     * native structured-output (e.g. Anthropic tool-use, OpenAI response_format) should
     * strip SCHEMA_SYSTEM_MARKER from $system and use $schema natively. Drivers without
     * native support can ignore $schema entirely and rely on the text in $system.
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
