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

        // Driver factories are intentionally empty here; real HTTP drivers will be
        // registered in a subsequent slice. Use AIService directly with explicit
        // factories in tests, or extend this method in your application's Services.
        return new AIService($config, []);
    }
}
