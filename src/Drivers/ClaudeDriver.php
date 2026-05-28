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
use Myth\Scribe\Exceptions\AIException;

/**
 * AI driver for Anthropic Claude via the /v1/messages API.
 */
class ClaudeDriver extends AbstractHttpDriver
{
    private const DEFAULT_API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION     = '2023-06-01';

    protected function providerName(): string
    {
        return 'Claude';
    }

    public function complete(
        string $system,
        string $user,
        ?string $assistant,
        ?array $schema,
        array $options,
    ): AIResponse {
        $url = $this->config['baseUrl'] ?? self::DEFAULT_API_URL;

        $messages = [['role' => 'user', 'content' => $user]];

        if ($assistant !== null) {
            $messages[] = ['role' => 'assistant', 'content' => $assistant];
        }

        $body = array_merge([
            'model'      => $this->config['model'],
            'system'     => $system,
            'messages'   => $messages,
            'max_tokens' => $this->config['maxTokens'] ?? 4096,
        ], $options);

        $data = $this->sendRequest($url, [
            'headers' => [
                'x-api-key'         => $this->config['apiKey'],
                'anthropic-version' => self::API_VERSION,
                'content-type'      => 'application/json',
            ],
            'json'        => $body,
            'timeout'     => $this->config['timeout'] ?? 30,
            'http_errors' => false,
        ]);

        if (! isset($data['content'][0]['text'])) {
            throw new AIException('Malformed response from Claude API: missing content[0].text.');
        }

        return new AIResponse(
            content: $data['content'][0]['text'],
            model: $data['model'] ?? (string) $this->config['model'],
            inputTokens: $data['usage']['input_tokens'] ?? 0,
            outputTokens: $data['usage']['output_tokens'] ?? 0,
            raw: $data,
        );
    }
}
