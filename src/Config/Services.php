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

use CodeIgniter\Config\BaseService;
use Myth\Scribe\AIService;
use Myth\Scribe\Drivers\ClaudeDriver;
use Myth\Scribe\Drivers\GeminiDriver;
use Myth\Scribe\Drivers\OpenAIDriver;

/**
 * Registers package services with the CodeIgniter 4 service container.
 */
class Services extends BaseService
{
    /**
     * Returns the shared AIService instance.
     *
     * @param bool $getShared Return the shared instance (true) or a fresh one (false).
     */
    public static function scribe(bool $getShared = true): AIService
    {
        if ($getShared) {
            return static::getSharedInstance('scribe');
        }

        /** @var AI $config */
        $config = config('AI');

        $driverFactories = [
            'claude' => static fn () => new ClaudeDriver($config->drivers['claude'] ?? [], static::curlrequest()),
            'openai' => static fn () => new OpenAIDriver($config->drivers['openai'] ?? [], static::curlrequest()),
            'gemini' => static fn () => new GeminiDriver($config->drivers['gemini'] ?? [], static::curlrequest()),
        ];

        return new AIService($config, $driverFactories);
    }
}
