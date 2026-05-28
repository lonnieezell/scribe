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

namespace Myth\Scribe\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Package configuration for AI drivers and their credentials.
 */
class AI extends BaseConfig
{
    /**
     * The default driver key to use when no driver is specified on a prompt.
     */
    public string $defaultDriver = 'claude';

    /**
     * Driver configurations.
     *
     * Each entry supports: apiKey, model, timeout (default 30s), and optional baseUrl.
     *
     * @var array<string, array<string, mixed>>
     */
    public array $drivers = [
        'claude' => [
            'apiKey'    => '',
            'model'     => 'claude-haiku-4-5',
            'timeout'   => 30,
            'maxTokens' => 4096,
        ],
        'openai' => [
            'apiKey'  => '',
            'model'   => 'gpt-5.4-mini',
            'timeout' => 30,
        ],
        'gemini' => [
            'apiKey'    => '',
            'model'     => 'gemini-flash-latest',
            'timeout'   => 30,
            'maxTokens' => null,
        ],
    ];
}
