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

namespace Myth\Scribe\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\GeneratorTrait;

/**
 * Spark generator command: php spark make:prompt ClassName
 *
 * Writes a ready-to-edit BasePrompt stub to app/Prompts/{ClassName}.php.
 */
class MakePromptCommand extends BaseCommand
{
    use GeneratorTrait;

    protected $group = 'make';

    protected $name = 'make:prompt';

    protected $description = 'Generates a new prompt class.';

    protected $usage = 'make:prompt <name> [options]';

    protected $arguments = [
        'name' => 'The prompt class name.',
    ];

    protected $options = [
        '--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
        '--suffix'    => 'Append the component title to the class name (e.g. Summarize => SummarizePrompt).',
        '--force'     => 'Force overwrite existing file.',
    ];

    /**
     * @param array<int|string, string|null> $params
     */
    public function run(array $params): void
    {
        $this->component    = 'Prompt';
        $this->directory    = 'Prompts';
        $this->template     = 'prompt.tpl.php';
        $this->templatePath = 'Myth\Scribe\Commands\Generators\Views\prompt.tpl.php';

        $this->generateClass($params);
    }
}
