<?php

declare(strict_types=1);

namespace OxidSupport\Heartbeat\Tests\Unit\Module;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Guards the admin template extensions under views/twig/extensions.
 *
 * A template extension replaces the blocks it declares. An empty extension file
 * therefore does not "do nothing", it removes the core markup from the rendered
 * page. The 6.5 line shipped exactly that: an emptied module_config extension
 * left the settings page of every installed module without input fields.
 */
class TemplateExtensionsTest extends TestCase
{
    private const MODULE_ROOT = __DIR__ . '/../../..';
    private const EXTENSIONS_DIR = self::MODULE_ROOT . '/views/twig/extensions';

    #[DataProvider('extensionProvider')]
    public function testExtensionIsNotEmpty(string $file): void
    {
        $this->assertNotSame(
            '',
            trim((string) file_get_contents(self::EXTENSIONS_DIR . '/' . $file)),
            sprintf('Template extension %s is empty and would blank out the template it extends', $file)
        );
    }

    #[DataProvider('extensionProvider')]
    public function testExtensionExtendsATemplate(string $file): void
    {
        $this->assertStringContainsString(
            '{% extends',
            (string) file_get_contents(self::EXTENSIONS_DIR . '/' . $file),
            sprintf('Template extension %s must extend the template it overrides', $file)
        );
    }

    /**
     * The core "admin_module_config_form" block holds the settings form of every
     * module, so this block has to fall back to parent() for the modules it does
     * not render its own content for.
     */
    public function testModuleConfigExtensionRendersTheCoreForm(): void
    {
        $files = array_filter(
            array_keys(self::extensionProvider()),
            static fn (string $file): bool => basename($file) === 'module_config.html.twig'
        );

        $this->assertNotEmpty($files, 'No module_config extension found');

        foreach ($files as $file) {
            $body = self::readBlockBody(self::EXTENSIONS_DIR . '/' . $file, 'admin_module_config_form');

            $this->assertNotNull(
                $body,
                sprintf('%s no longer overrides admin_module_config_form, check whether that is intended', $file)
            );
            $this->assertStringContainsString(
                'parent()',
                (string) $body,
                sprintf(
                    'Block admin_module_config_form in %s must render parent(), otherwise no module can be configured',
                    $file
                )
            );
        }
    }

    public static function extensionProvider(): array
    {
        $root = realpath(self::EXTENSIONS_DIR);

        if ($root === false) {
            return [];
        }

        $cases = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'twig') {
                $relative = substr($file->getPathname(), strlen($root) + 1);
                $cases[$relative] = [$relative];
            }
        }

        ksort($cases);

        return $cases;
    }

    /**
     * Returns the body of one block, or null when the file does not override it.
     * Only that block's own body counts: a parent() call in a sibling block of
     * the same file says nothing about this one.
     */
    private static function readBlockBody(string $path, string $name): ?string
    {
        $pattern = sprintf(
            '/\{%%\s*block\s+%s\s*%%\}(.*?)\{%%\s*endblock(?:\s+[A-Za-z0-9_]+)?\s*%%\}/s',
            preg_quote($name, '/')
        );

        return preg_match($pattern, (string) file_get_contents($path), $matches) === 1 ? $matches[1] : null;
    }
}
