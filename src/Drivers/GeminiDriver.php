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
 * AI driver for Google Gemini via the /v1beta/models/{model}:generateContent API.
 */
class GeminiDriver implements AIDriver
{
    private const DEFAULT_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

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
        $model   = (string) $this->config['model'];
        $baseUrl = rtrim((string) ($this->config['baseUrl'] ?? self::DEFAULT_BASE_URL), '/');
        $url     = $baseUrl . '/' . $model . ':generateContent';

        $contents = [
            ['role' => 'user', 'parts' => [['text' => $user]]],
        ];

        if ($assistant !== null) {
            $contents[] = ['role' => 'model', 'parts' => [['text' => $assistant]]];
            $contents[] = ['role' => 'user', 'parts' => [['text' => $user]]];
        }

        $body = [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents'          => $contents,
        ];

        $generationConfig = [];

        if (isset($this->config['maxTokens'])) {
            $generationConfig['maxOutputTokens'] = $this->config['maxTokens'];
        }

        if ($schema !== null) {
            $generationConfig['responseSchema']   = $schema;
            $generationConfig['responseMimeType'] = 'application/json';
        }

        if ($generationConfig !== []) {
            $body['generationConfig'] = $generationConfig;
        }

        $body = array_merge($body, $options);

        try {
            $response = $this->client->request('POST', $url, [
                'headers'     => ['content-type' => 'application/json', 'x-goog-api-key' => $this->config['apiKey']],
                'json'        => $body,
                'timeout'     => $this->config['timeout'] ?? 30,
                'http_errors' => false,
            ]);
        } catch (HTTPException $e) {
            throw new AIException('Network error communicating with Gemini API: ' . $e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();

        if ($status === 401 || $status === 403) {
            throw new AIAuthException("Gemini API authentication failed (HTTP {$status}).");
        }

        if ($status === 429) {
            throw new AIRateLimitException('Gemini API rate limit exceeded (HTTP 429).');
        }

        if ($status >= 400) {
            throw new AIException("Gemini API returned HTTP {$status}.");
        }

        $data = json_decode((string) $response->getBody(), true);

        if (! is_array($data) || ! isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new AIException('Malformed response from Gemini API: missing candidates[0].content.parts[0].text.');
        }

        return new AIResponse(
            content: $data['candidates'][0]['content']['parts'][0]['text'],
            model: $data['modelVersion'] ?? $model,
            inputTokens: $data['usageMetadata']['promptTokenCount'] ?? 0,
            outputTokens: $data['usageMetadata']['candidatesTokenCount'] ?? 0,
            raw: $data,
        );
    }
}
