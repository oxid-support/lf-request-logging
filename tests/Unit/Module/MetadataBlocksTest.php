<?php

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Module;

use OxidSupport\Heartbeat\Module\Module;
use PHPUnit\Framework\TestCase;

/**
 * Guards the template block registrations in metadata.php.
 *
 * A registered block replaces the core block it hooks into. An empty block file
 * therefore does not "do nothing", it wipes the core markup out of the rendered
 * page, and the core block only survives where the block file renders the parent
 * tag.
 */
class MetadataBlocksTest extends TestCase
{
    private const MODULE_ROOT = __DIR__ . '/../../..';

    /**
     * Same expression the core block prefilter substitutes with, see
     * Core/Smarty/Plugin/prefilter.oxblock.php. Only a full tag counts, naming
     * the tag in prose does not.
     */
    private const PARENT_TAG_PATTERN = '/\[\{\s*\$smarty\.block\.parent\s*\}\]/i';

    /** @var array<string, mixed>|null */
    private static $metadata = null;

    /**
     * @dataProvider blockProvider
     */
    public function testBlockFileHasTemplateCode(string $template, string $block, string $file): void
    {
        $path = self::MODULE_ROOT . '/' . $file;

        $this->assertFileExists($path, sprintf('Block file for %s/%s is missing', $template, $block));
        $this->assertNotSame(
            '',
            self::readWithoutComments($path),
            sprintf(
                'Block file %s has no template code, which replaces the core block "%s" of %s with nothing',
                $file,
                $block,
                $template
            )
        );
    }

    /**
     * Holds for every registered block: the core content it replaces has to come
     * back exactly once. None wipes the core block out (that is how this line
     * lost the settings form of every module), more than once injects it twice,
     * because the prefilter substitutes every occurrence in the file, comments
     * included, and it runs before smarty strips those comments.
     *
     * @dataProvider blockProvider
     */
    public function testBlockFileRendersTheParentBlockExactlyOnce(string $template, string $block, string $file): void
    {
        $path = self::MODULE_ROOT . '/' . $file;

        $this->assertSame(
            1,
            preg_match_all(self::PARENT_TAG_PATTERN, (string) file_get_contents($path)),
            sprintf(
                'Block file %s must carry the parent tag exactly once, it extends the core block "%s" of %s',
                $file,
                $block,
                $template
            )
        );
    }

    /**
     * Smarty 2 cannot read a class constant, so the block compares the module id
     * as a literal. This pins the literal to Module::ID without pinning how the
     * comparison is written: renaming the constant would otherwise leave a hint
     * that never shows again, with nothing failing.
     */
    public function testModuleConfigBlockComparesAgainstTheModuleId(): void
    {
        $file = $this->findBlockFile('module_config.tpl', 'admin_module_config_form');

        if ($file === null) {
            $this->markTestSkipped('No module_config block registered, the core form stays untouched');
        }

        $content = self::readWithoutComments(self::MODULE_ROOT . '/' . $file);

        $this->assertStringContainsString(
            'getEditObjectId()',
            $content,
            sprintf('%s must read the shown module through getEditObjectId()', $file)
        );
        $this->assertStringContainsString(
            "'" . Module::ID . "'",
            $content,
            sprintf('%s must compare against Module::ID (%s)', $file, Module::ID)
        );
    }

    public function blockProvider(): array
    {
        $cases = [];

        foreach (self::readMetadata()['blocks'] ?? [] as $block) {
            $cases[$block['template'] . '::' . $block['block']] = [
                $block['template'],
                $block['block'],
                $block['file'],
            ];
        }

        return $cases;
    }

    private function findBlockFile(string $template, string $block): ?string
    {
        foreach (self::readMetadata()['blocks'] ?? [] as $registered) {
            if ($registered['template'] === $template && $registered['block'] === $block) {
                return $registered['file'];
            }
        }

        return null;
    }

    /**
     * Smarty comments do not count as template code: a parent tag inside one is
     * both a duplicate injection and a guard that passes while the real call is
     * gone. Mirrors smarty's own comment expression, see Smarty_Compiler.
     */
    private static function readWithoutComments(string $path): string
    {
        $content = (string) file_get_contents($path);

        return trim((string) preg_replace('/\[\{\*.*?\*\}\]/s', '', $content));
    }

    /**
     * @return array<string, mixed>
     */
    private static function readMetadata(): array
    {
        if (self::$metadata === null) {
            // metadata.php declares $aModule; including it in this scope keeps the
            // variable out of the test case.
            require self::MODULE_ROOT . '/metadata.php';

            /** @var array<string, mixed> $aModule */
            self::$metadata = $aModule;
        }

        return self::$metadata;
    }
}
