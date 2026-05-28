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

namespace Tests\Commands;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\StreamFilterTrait;
use Myth\Scribe\Commands\MakePromptCommand;

/**
 * @internal
 */
final class MakePromptCommandTest extends CIUnitTestCase
{
    use StreamFilterTrait;

    private string $generatedFile = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpStreamFilterTrait();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->tearDownStreamFilterTrait();

        if ($this->generatedFile !== '' && is_file($this->generatedFile)) {
            unlink($this->generatedFile);
        }
    }

    public function testGeneratesPromptFileWithCorrectContent(): void
    {
        $this->generatedFile = APPPATH . 'Prompts/SummarizeText.php';

        $command = new MakePromptCommand(service('logger'), service('commands'));
        $command->run(['SummarizeText']);

        $this->assertFileExists($this->generatedFile);

        $content = file_get_contents($this->generatedFile);

        $this->assertStringContainsString('namespace App\Prompts;', $content);
        $this->assertStringContainsString('class SummarizeText extends BasePrompt', $content);
        $this->assertStringContainsString('use Myth\Scribe\Prompts\BasePrompt;', $content);
        $this->assertStringContainsString('public function systemPrompt(): string', $content);
        $this->assertStringContainsString('public function userPrompt(): string', $content);
        $this->assertStringContainsString('declare(strict_types=1);', $content);
    }

    public function testDoesNotOverwriteExistingFile(): void
    {
        $this->generatedFile = APPPATH . 'Prompts/ExistingPrompt.php';

        // Write a sentinel file
        @mkdir(APPPATH . 'Prompts', 0755, true);
        file_put_contents($this->generatedFile, '<?php // original');

        $command = new MakePromptCommand(service('logger'), service('commands'));
        $command->run(['ExistingPrompt']);

        $this->assertSame('<?php // original', file_get_contents($this->generatedFile));
    }
}
