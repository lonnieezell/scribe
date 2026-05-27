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

use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\Exceptions\HTTPException;
use Myth\Scribe\AIResponse;
use Myth\Scribe\Exceptions\AIAuthException;
use Myth\Scribe\Exceptions\AIException;
use Myth\Scribe\Exceptions\AIRateLimitException;

/**
 * AI driver for Anthropic Claude via the /v1/messages API.
 */
class ClaudeDriver implements AIDriver
{
    private const DEFAULT_API_URL = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION     = '2023-06-01';

    /**
     * @param array<string, mixed> $config Pre-extracted driver config slice
     */
    public function __construct(
        private readonly array $config,
        private readonly CURLRequest $client,
    ) {
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

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'x-api-key'         => $this->config['apiKey'],
                    'anthropic-version' => self::API_VERSION,
                    'content-type'      => 'application/json',
                ],
                'json'    => $body,
                'timeout' => $this->config['timeout'] ?? 30,
            ]);
        } catch (HTTPException $e) {
            throw new AIException('Network error communicating with Claude API: ' . $e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();

        if ($status === 401) {
            throw new AIAuthException('Claude API authentication failed (HTTP 401).');
        }

        if ($status === 429) {
            throw new AIRateLimitException('Claude API rate limit exceeded (HTTP 429).');
        }

        if ($status >= 400) {
            throw new AIException("Claude API returned HTTP {$status}.");
        }

        $data = json_decode((string) $response->getBody(), true);

        if (! is_array($data) || ! isset($data['content'][0]['text'])) {
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
