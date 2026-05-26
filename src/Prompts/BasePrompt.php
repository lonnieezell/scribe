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

namespace Myth\Scribe\Prompts;

use Myth\Scribe\Exceptions\AIException;

abstract class BasePrompt
{
    /**
     * Optional format hint (e.g. 'json'). When set, format instructions are
     * appended to the built system prompt.
     */
    public string $format = '';

    /**
     * Override the default driver key from config for this prompt.
     */
    public ?string $driver = null;

    /**
     * Driver-specific options passed through to the driver's complete() call.
     *
     * @var array<string, mixed>
     */
    public array $options = [];

    /**
     * The static system prompt text for this prompt type.
     */
    abstract public function systemPrompt(): string;

    /**
     * The user message for this request.
     */
    abstract public function userPrompt(): string;

    /**
     * Optional assistant prefill. Return null to omit.
     */
    public function assistant(): ?string
    {
        return null;
    }

    /**
     * Optional JSON schema for structured output. Return null to omit.
     *
     * @return array<string, mixed>|null
     */
    public function schema(): ?array
    {
        return null;
    }

    /**
     * Build the final system prompt, appending format/schema instructions as needed.
     *
     * Logs a warning when both $format and schema() are set simultaneously.
     */
    public function buildSystemPrompt(): string
    {
        $schema = $this->schema();

        if ($this->format !== '' && $schema !== null) {
            log_message('warning', 'BasePrompt: both $format and schema() are set; schema() takes precedence.');
        }

        $system = $this->systemPrompt();

        if ($schema !== null) {
            $encoded = json_encode($schema, JSON_PRETTY_PRINT);
            if ($encoded === false) {
                throw new AIException('Failed to JSON-encode schema: ' . json_last_error_msg());
            }

            $system .= "\n\nRespond using the following JSON schema:\n" . $encoded;
        } elseif ($this->format !== '') {
            $system .= "\n\nRespond in {$this->format} format.";
        }

        return $system;
    }
}
