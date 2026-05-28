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
 * AI driver for Google Gemini via the /v1beta/models/{model}:generateContent API.
 */
class GeminiDriver extends AbstractHttpDriver
{
    private const DEFAULT_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

    protected function providerName(): string
    {
        return 'Gemini';
    }

    protected function authStatusCodes(): array
    {
        return [401, 403];
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

        $generationConfig = [];

        if (isset($this->config['maxTokens'])) {
            $generationConfig['maxOutputTokens'] = $this->config['maxTokens'];
        }

        if ($schema !== null) {
            $pos = strpos($system, AIDriver::SCHEMA_SYSTEM_MARKER);
            if ($pos !== false) {
                $system = substr($system, 0, $pos);
            }

            $generationConfig['responseSchema']   = $schema;
            $generationConfig['responseMimeType'] = 'application/json';
        }

        $body = [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents'          => $contents,
        ];

        if ($generationConfig !== []) {
            $body['generationConfig'] = $generationConfig;
        }

        $body = array_merge($body, $options);

        $data = $this->sendRequest($url, [
            'headers'     => ['content-type' => 'application/json', 'x-goog-api-key' => $this->config['apiKey']],
            'json'        => $body,
            'timeout'     => $this->config['timeout'] ?? 30,
            'http_errors' => false,
        ]);

        if (! isset($data['candidates'][0]['content']['parts'][0]['text'])) {
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
