<?php

/**
 * Copyright Youwe. All rights reserved.
 * https://www.youweagency.com
 */

declare(strict_types=1);

namespace Youwe\Composer\DependencyInstaller\Tests;

use Composer\Json\JsonFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use Seld\JsonLint\ParsingException;
use Youwe\Composer\DependencyInstaller\DependencyInstaller;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;

#[CoversClass(DependencyInstaller::class)]
#[CoversMethod(DependencyInstaller::class, '__construct')]
#[CoversMethod(DependencyInstaller::class, 'installPackage')]
#[CoversMethod(DependencyInstaller::class, 'installRepository')]
class DependencyInstallerTest extends TestCase
{
    private static string $directory = __DIR__ . DIRECTORY_SEPARATOR . 'tmp';
    private DependencyInstaller $dependencyInstaller;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        mkdir(static::$directory);
        chdir(static::$directory);
        file_put_contents('composer.json', '{}');

        $this->updateComposerFileReference();
    }

    protected function tearDown(): void
    {
        if (is_dir(static::$directory)) {
            static::rrmdir(static::$directory);
        }
    }

    private static function rrmdir(string $src): void
    {
        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    static::rrmdir($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }

    public function testConstructor(): void
    {
        $this->assertInstanceOf(
            DependencyInstaller::class,
            $this->dependencyInstaller
        );
    }

    /**
     * @throws ParsingException
     */
    #[DataProvider('repositoryProvider')]
    public function testInstallRepository(
        string $name,
        string $type,
        string $url,
    ): void {
        $this->dependencyInstaller->installRepository($name, $type, $url);

        $jsonFile   = new JsonFile('composer.json');
        $definition = $jsonFile->read();

        $this->assertArrayHasKey('repositories', $definition);
        $this->assertArrayHasKey($name, $definition['repositories']);
        $this->assertArrayHasKey('type', $definition['repositories'][$name]);
        $this->assertArrayHasKey('url', $definition['repositories'][$name]);
        $this->assertEquals($type, $definition['repositories'][$name]['type']);
        $this->assertEquals($url, $definition['repositories'][$name]['url']);
    }

    public static function repositoryProvider(): array
    {
        return [
            ['mediact', 'composer', 'https://composer.mediact.nl']
        ];
    }

    /**
     * @throws ParsingException
     */
    #[DataProvider('packageProvider')]
    public function testInstallPackage(
        string $name,
        string $version,
        bool $dev,
        bool $updateDependencies,
        bool $allowOverrideVersion
    ): void {
        $this->dependencyInstaller->installPackage($name, $version, $dev, $updateDependencies, $allowOverrideVersion);

        $jsonFile   = new JsonFile('composer.json');
        $definition = $jsonFile->read();

        $node = $dev ? 'require-dev' : 'require';

        $this->assertArrayHasKey($node, $definition);
        $this->assertArrayHasKey($name, $definition[$node]);
        $this->assertEquals($version, $definition[$node][$name]);
    }

    public static function packageProvider(): array
    {
        return [
            ['psr/log', '@stable', true, false, true],
            ['psr/log', '@stable', false, false, true]
        ];
    }

    /**
     * @throws ParsingException
     * @throws Exception
     */
    public function testOriginalPackageVersionCanBePreserved(): void
    {
        $this->dependencyInstaller->installPackage(
            'psr/link',
            '1.1.1',
            false,
            false,
            false
        );

        $this->updateComposerFileReference();

        $this->dependencyInstaller->installPackage(
            'psr/link',
            '^2',
            false,
            false,
            false
        );

        $this->updateComposerFileReference();

        $jsonFile   = new JsonFile('composer.json');
        $definition = $jsonFile->read();

        // Assert the pre-existing composer versions was NOT wiped by a dependency installer package installation
        $this->assertArrayHasKey('require', $definition);
        $this->assertArrayHasKey('psr/link', $definition['require']);
        $this->assertEquals('1.1.1', $definition['require']['psr/link']);
    }

    /**
     * @throws ParsingException|Exception
     */
    public function testOriginalPackageVersionCanBeOverridden(): void
    {
        // Install and validate base package availability
        $this->dependencyInstaller->installPackage(
            'psr/link',
            '1.1.1',
            false
        );

        $this->updateComposerFileReference();

        $this->dependencyInstaller->installPackage(
            'psr/link',
            '@stable',
            false
        );

        $jsonFile   = new JsonFile('composer.json');
        $definition = $jsonFile->read();

        $this->assertArrayHasKey('require', $definition);
        $this->assertArrayHasKey('psr/link', $definition['require']);
        $this->assertEquals('@stable', $definition['require']['psr/link']);
    }

    /**
     * Update the composer file reference since it is loaded only once as part of the constructor.
     *
     * We refresh the composer file reference whenever the composer file is changed, otherwise the dependency
     * installer will always hold a reference to the previous state of the composer file and not contain any
     * changes if multiple installations happen in the same testing function.
     *
     * @return void
     * @throws Exception
     */
    private function updateComposerFileReference(): void
    {
        $this->dependencyInstaller = new DependencyInstaller(
            'composer.json',
            $this->createMock(OutputInterface::class)
        );
    }
}
