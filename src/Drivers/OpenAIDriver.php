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
 * AI driver for OpenAI via the /v1/chat/completions API.
 */
class OpenAIDriver implements AIDriver
{
    private const DEFAULT_API_URL = 'https://api.openai.com/v1/chat/completions';

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

        $messages = [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ];

        if ($assistant !== null) {
            $messages[] = ['role' => 'assistant', 'content' => $assistant];
        }

        $body = ['model' => $this->config['model'], 'messages' => $messages];

        if (isset($this->config['maxTokens'])) {
            $body['max_tokens'] = $this->config['maxTokens'];
        }

        if ($schema !== null) {
            $pos = strpos($system, AIDriver::SCHEMA_SYSTEM_MARKER);
            if ($pos !== false) {
                $system = substr($system, 0, $pos);
            }

            $body['response_format'] = [
                'type'        => 'json_schema',
                'json_schema' => [
                    'name'   => 'response',
                    'schema' => $schema,
                    'strict' => true,
                ],
            ];
        }

        $body = array_merge($body, $options);

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['apiKey'],
                    'content-type'  => 'application/json',
                ],
                'json'        => $body,
                'timeout'     => $this->config['timeout'] ?? 30,
                'http_errors' => false,
            ]);
        } catch (HTTPException $e) {
            throw new AIException('Network error communicating with OpenAI API: ' . $e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();

        if ($status === 401) {
            throw new AIAuthException('OpenAI API authentication failed (HTTP 401).');
        }

        if ($status === 429) {
            throw new AIRateLimitException('OpenAI API rate limit exceeded (HTTP 429).');
        }

        if ($status >= 400) {
            throw new AIException("OpenAI API returned HTTP {$status}.");
        }

        $data = json_decode((string) $response->getBody(), true);

        if (! is_array($data) || ! isset($data['choices'][0]['message']['content'])) {
            throw new AIException('Malformed response from OpenAI API: missing choices[0].message.content.');
        }

        return new AIResponse(
            content: $data['choices'][0]['message']['content'],
            model: $data['model'] ?? (string) $this->config['model'],
            inputTokens: $data['usage']['prompt_tokens'] ?? 0,
            outputTokens: $data['usage']['completion_tokens'] ?? 0,
            raw: $data,
        );
    }
}
