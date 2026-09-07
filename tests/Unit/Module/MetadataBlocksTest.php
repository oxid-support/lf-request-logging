<?php

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Module;

use OxidSupport\Heartbeat\Module\Module;
use PHPUnit\Framework\TestCase;

/**
 * Guards the template block registrations in metadata.php.
 *
 * A registered block replaces the core block it hooks into. An empty or missing
 * block file therefore does not "do nothing", it wipes the core markup out of
 * the rendered page.
 */
class MetadataBlocksTest extends TestCase
{
    private const MODULE_ROOT = __DIR__ . '/../../..';

    /**
     * @dataProvider blockProvider
     */
    public function testBlockFileIsNotEmpty(string $template, string $block, string $file): void
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
     * The core "admin_module_config_form" block holds the whole settings form of
     * every module. Dropping the parent here leaves the settings page of all
     * installed modules without any input fields.
     */
    public function testModuleConfigBlockKeepsTheCoreForm(): void
    {
        $file = $this->findBlockFile('module_config.tpl', 'admin_module_config_form');

        if ($file === null) {
            $this->assertTrue(true, 'No module_config block registered, the core form stays untouched');
            return;
        }

        $this->assertStringContainsString(
            '$smarty.block.parent',
            self::readWithoutComments(self::MODULE_ROOT . '/' . $file),
            sprintf('%s must render $smarty.block.parent, otherwise no module can be configured', $file)
        );
        $this->assertSame(
            1,
            substr_count((string) file_get_contents(self::MODULE_ROOT . '/' . $file), '$smarty.block.parent'),
            sprintf(
                '%s must carry the parent tag exactly once: the prefilter replaces every occurrence, '
                . 'so a second one (a comment included) injects the core block twice',
                $file
            )
        );
    }

    /**
     * Smarty 2 cannot read a class constant, so the block compares the module id
     * as a literal. This pins that literal to Module::ID: renaming the constant
     * would otherwise leave a hint that never shows again, with nothing failing.
     */
    public function testModuleConfigBlockComparesAgainstTheModuleId(): void
    {
        $file = $this->findBlockFile('module_config.tpl', 'admin_module_config_form');

        if ($file === null) {
            $this->assertTrue(true, 'No module_config block registered');
            return;
        }

        $this->assertStringContainsString(
            "== '" . Module::ID . "'",
            self::readWithoutComments(self::MODULE_ROOT . '/' . $file),
            sprintf('%s must compare getEditObjectId() against Module::ID (%s)', $file, Module::ID)
        );
    }

    /**
     * The navigation block appends the module menu entries, so it has to keep the
     * core menu structure as well.
     */
    public function testNavigationBlockKeepsTheCoreMenu(): void
    {
        $file = $this->findBlockFile('navigation.tpl', 'admin_navigation_menustructure');

        if ($file === null) {
            $this->assertTrue(true, 'No navigation block registered');
            return;
        }

        $this->assertStringContainsString(
            '$smarty.block.parent',
            self::readWithoutComments(self::MODULE_ROOT . '/' . $file),
            sprintf('%s must render $smarty.block.parent, otherwise the admin menu is empty', $file)
        );
        $this->assertSame(
            1,
            substr_count((string) file_get_contents(self::MODULE_ROOT . '/' . $file), '$smarty.block.parent'),
            sprintf(
                '%s must carry the parent tag exactly once: the prefilter replaces every occurrence, '
                . 'so a second one (a comment included) injects the core block twice',
                $file
            )
        );
    }

    public function blockProvider(): array
    {
        $cases = [];

        foreach ($this->loadMetadataBlocks() as $block) {
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
        foreach ($this->loadMetadataBlocks() as $registered) {
            if ($registered['template'] === $template && $registered['block'] === $block) {
                return $registered['file'];
            }
        }

        return null;
    }

    /**
     * @return array<int, array{template: string, block: string, file: string}>
     */
    private function loadMetadataBlocks(): array
    {
        $metadata = self::readMetadata();

        return $metadata['blocks'] ?? [];
    }

    /**
     * Smarty comments must not count as template code here. The block prefilter
     * replaces every parent tag in the file before smarty strips comments, so a
     * tag inside a comment is both a duplicate injection and a test that passes
     * while the real call is gone.
     */
    private static function readWithoutComments(string $path): string
    {
        $content = (string) file_get_contents($path);

        return trim((string) preg_replace('/\\[\\{\\*.*?\\*\\}\\]/s', '', $content));
    }

    private static function readMetadata(): array
    {
        // metadata.php declares $aModule; include it in a function scope so the
        // variable does not leak into the test case.
        $sMetadataVersion = null;
        $aModule = [];

        require self::MODULE_ROOT . '/metadata.php';

        unset($sMetadataVersion);

        return $aModule;
    }
}
