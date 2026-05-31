<?php
/**
 * Property-based test for the Loader autoloader.
 *
 * Property 1: Autoloader ignores unregistered classes
 * Validates: Requirements 4.7
 *
 * For any fully-qualified class name not present in the Loader's internal class map,
 * invoking the autoload callback SHALL have no side effects — no exceptions, no errors,
 * and no file loads.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use WPBits\AiFaqGenerator\Includes\Loader;

class LoaderPropertyTest extends TestCase
{
    private Loader $loader;
    private ReflectionMethod $autoloadMethod;

    protected function setUp(): void
    {
        $this->loader = new Loader();

        // The autoload method is private, so we use Reflection to access it.
        $this->autoloadMethod = new ReflectionMethod(Loader::class, 'autoload');
        $this->autoloadMethod->setAccessible(true);
    }

    /**
     * **Validates: Requirements 4.7**
     *
     * Property 1: Autoloader ignores unregistered classes.
     * For any fully-qualified class name NOT in the Loader's class map,
     * calling autoload produces no side effects (no errors, no exceptions, no file loads).
     */
    #[Test]
    #[DataProvider('unregisteredClassNameProvider')]
    public function autoloader_ignores_unregistered_classes(string $className): void
    {
        $includedFilesBefore = get_included_files();

        // Call the private autoload method — should produce no side effects.
        $result = $this->autoloadMethod->invoke($this->loader, $className);

        $includedFilesAfter = get_included_files();

        // Assert no new files were loaded.
        $this->assertSame(
            $includedFilesBefore,
            $includedFilesAfter,
            "Autoloader should not load any files for unregistered class: {$className}"
        );

        // Assert no return value (void method).
        $this->assertNull($result);
    }

    /**
     * Data provider generating 100+ random fully-qualified class names
     * that are NOT in the Loader's internal class map.
     *
     * @return array<string, array{string}>
     */
    public static function unregisteredClassNameProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(42);

        for ($i = 0; $i < 100; $i++) {
            $className = self::generateRandomClassName($i);
            $cases["random_class_{$i}"] = [$className];
        }

        // Additional edge cases beyond the 100 minimum.
        $edgeCases = [
            'empty_string' => [''],
            'single_segment' => ['SomeClass'],
            'numeric_class' => ['Namespace123\\Class456'],
            'deep_namespace' => ['A\\B\\C\\D\\E\\F\\G\\H\\I\\J\\DeepClass'],
            'unicode_like' => ['Vendor\\Paket\\Klasse'],
            'similar_to_registered' => ['WPBits\\AiFaqGenerator\\Admin\\AdminExtra'],
            'partial_match' => ['WPBits\\AiFaqGenerator\\Admin'],
            'prefix_match' => ['WPBits\\AiFaqGenerator\\Admin\\Admin\\Sub'],
            'different_vendor' => ['OtherVendor\\AiFaqGenerator\\Admin\\Admin'],
            'lowercase_variant' => ['wpbits\\aifaqgenerator\\admin\\admin'],
        ];

        return array_merge($cases, $edgeCases);
    }

    /**
     * Generate a random fully-qualified class name.
     */
    private static function generateRandomClassName(int $seed): string
    {
        $namespaceDepth = mt_rand(1, 5);
        $segments = [];

        for ($j = 0; $j < $namespaceDepth; $j++) {
            $segments[] = self::generateRandomSegment();
        }

        $className = self::generateRandomSegment();

        return implode('\\', $segments) . '\\' . $className;
    }

    /**
     * Generate a random namespace/class segment (valid PHP identifier).
     */
    private static function generateRandomSegment(): string
    {
        $length = mt_rand(3, 15);
        // Start with a letter.
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $allChars = $chars . '0123456789_';

        $segment = $chars[mt_rand(0, strlen($chars) - 1)];
        for ($i = 1; $i < $length; $i++) {
            $segment .= $allChars[mt_rand(0, strlen($allChars) - 1)];
        }

        return $segment;
    }
}
