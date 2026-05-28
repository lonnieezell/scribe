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
 * AI driver for OpenAI via the /v1/chat/completions API.
 */
class OpenAIDriver extends AbstractHttpDriver
{
    private const DEFAULT_API_URL = 'https://api.openai.com/v1/chat/completions';

    protected function providerName(): string
    {
        return 'OpenAI';
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

        $data = $this->sendRequest($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->config['apiKey'],
                'content-type'  => 'application/json',
            ],
            'json'        => $body,
            'timeout'     => $this->config['timeout'] ?? 30,
            'http_errors' => false,
        ]);

        if (! isset($data['choices'][0]['message']['content'])) {
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
