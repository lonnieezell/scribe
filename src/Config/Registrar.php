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

/**
 * Registers package-level overrides into CI4 Config classes at bootstrap.
 * CI4 discovers this class automatically via the Myth\Scribe namespace.
 */
class Registrar
{
    /**
     * Registers the make:prompt generator template so app developers can
     * override it by publishing Config\Generators and setting:
     *   $views['make:prompt'] = 'App\Commands\Generators\Views\my_prompt';
     *
     * @return array<string, array<string, string>>
     */
    public static function Generators(): array
    {
        return [
            'views' => [
                'make:prompt' => 'Myth\Scribe\Commands\Generators\Views\prompt.tpl.php',
            ],
        ];
    }
}
