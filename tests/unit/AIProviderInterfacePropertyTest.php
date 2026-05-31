<?php
/**
 * Property-based test for the AIProviderInterface contract enforcement.
 *
 * Feature: ai-faq-generator-provider-interface, Property 1: Interface contract enforcement
 *
 * Property 1: Interface contract enforcement
 * Validates: Requirements 1.1, 1.2, 4.1
 *
 * For any PHP class that implements AIProviderInterface, that class SHALL declare both
 * `generateFaqs(string $prompt): array` and `testConnection(): bool` methods with the
 * correct type signatures, or PHP will produce a fatal error.
 */

declare(strict_types=1);

namespace WPBits\AiFaqGenerator\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use WPBits\AiFaqGenerator\Includes\Interfaces\AIProviderInterface;

class AIProviderInterfacePropertyTest extends TestCase
{
    /**
     * **Validates: Requirements 1.1, 1.2, 4.1**
     *
     * Property 1: Interface contract enforcement.
     * For any class stub that implements AIProviderInterface, reflection confirms
     * that both `generateFaqs` and `testConnection` are required with correct signatures.
     */
    #[Test]
    #[DataProvider('classStubProvider')]
    public function interface_enforces_generate_faqs_and_test_connection_methods(string $className): void
    {
        // Verify the interface itself requires both methods.
        $interfaceReflection = new ReflectionClass(AIProviderInterface::class);

        // Verify generateFaqs method exists on the interface.
        $this->assertTrue(
            $interfaceReflection->hasMethod('generateFaqs'),
            'AIProviderInterface must declare generateFaqs method'
        );

        // Verify testConnection method exists on the interface.
        $this->assertTrue(
            $interfaceReflection->hasMethod('testConnection'),
            'AIProviderInterface must declare testConnection method'
        );

        // Verify generateFaqs signature.
        $generateFaqs = $interfaceReflection->getMethod('generateFaqs');
        $this->assertGenerateFaqsSignature($generateFaqs);

        // Verify testConnection signature.
        $testConnection = $interfaceReflection->getMethod('testConnection');
        $this->assertTestConnectionSignature($testConnection);

        // Create the class stub dynamically and verify it implements the interface.
        $stubCode = $this->buildClassStub($className);
        eval($stubCode);

        $fqcn = 'WPBits\\AiFaqGenerator\\Tests\\Stubs\\' . $className;
        $stubReflection = new ReflectionClass($fqcn);

        // Verify the stub implements AIProviderInterface.
        $this->assertTrue(
            $stubReflection->implementsInterface(AIProviderInterface::class),
            "{$className} must implement AIProviderInterface"
        );

        // Verify the stub has both required methods with correct signatures.
        $this->assertTrue(
            $stubReflection->hasMethod('generateFaqs'),
            "{$className} must have generateFaqs method"
        );
        $this->assertTrue(
            $stubReflection->hasMethod('testConnection'),
            "{$className} must have testConnection method"
        );

        // Verify the stub's generateFaqs has correct parameter and return types.
        $stubGenerateFaqs = $stubReflection->getMethod('generateFaqs');
        $this->assertGenerateFaqsSignature($stubGenerateFaqs);

        // Verify the stub's testConnection has correct return type.
        $stubTestConnection = $stubReflection->getMethod('testConnection');
        $this->assertTestConnectionSignature($stubTestConnection);
    }

    /**
     * Assert that generateFaqs has the correct signature:
     * - One parameter of type string
     * - Return type of array
     */
    private function assertGenerateFaqsSignature(ReflectionMethod $method): void
    {
        // Must have exactly one parameter.
        $params = $method->getParameters();
        $this->assertCount(1, $params, 'generateFaqs must accept exactly one parameter');

        // Parameter must be typed as string.
        $paramType = $params[0]->getType();
        $this->assertInstanceOf(ReflectionNamedType::class, $paramType);
        $this->assertSame('string', $paramType->getName(), 'generateFaqs parameter must be typed as string');

        // Return type must be array.
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        $this->assertSame('array', $returnType->getName(), 'generateFaqs must return array');
    }

    /**
     * Assert that testConnection has the correct signature:
     * - No parameters
     * - Return type of bool
     */
    private function assertTestConnectionSignature(ReflectionMethod $method): void
    {
        // Must have no parameters.
        $params = $method->getParameters();
        $this->assertCount(0, $params, 'testConnection must accept no parameters');

        // Return type must be bool.
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(ReflectionNamedType::class, $returnType);
        $this->assertSame('bool', $returnType->getName(), 'testConnection must return bool');
    }

    /**
     * Build a PHP class stub string that implements AIProviderInterface.
     *
     * Each stub is a unique class in the Stubs namespace that provides
     * minimal implementations of both required methods.
     */
    private function buildClassStub(string $className): string
    {
        return <<<PHP
namespace WPBits\\AiFaqGenerator\\Tests\\Stubs;

use WPBits\\AiFaqGenerator\\Includes\\Interfaces\\AIProviderInterface;

class {$className} implements AIProviderInterface
{
    public function generateFaqs(string \$prompt): array
    {
        return [];
    }

    public function testConnection(): bool
    {
        return false;
    }
}
PHP;
    }

    /**
     * Data provider generating 100+ random class stub names.
     *
     * Each entry represents a unique class that will be dynamically created
     * to implement AIProviderInterface, verifying the contract is enforced.
     *
     * @return array<string, array{string}>
     */
    public static function classStubProvider(): array
    {
        $cases = [];

        // Seed for reproducibility.
        mt_srand(42);

        for ($i = 0; $i < 100; $i++) {
            $className = self::generateRandomClassName($i);
            $cases["stub_class_{$i}"] = [$className];
        }

        // Additional edge-case class names beyond the 100 minimum.
        $edgeCases = [
            'short_name' => ['Ai'],
            'long_name' => ['VeryLongProviderClassNameThatExceedsTypicalNaming'],
            'numeric_suffix' => ['Provider123'],
            'underscore_name' => ['My_Custom_Provider'],
            'camel_case' => ['myCustomAiProvider'],
            'pascal_case' => ['OpenAIFaqProvider'],
            'single_char' => ['X'],
            'provider_prefix' => ['ProviderOpenRouter'],
            'provider_suffix' => ['OllamaProvider'],
            'mixed_case' => ['dEePsEeKpRoViDeR'],
        ];

        return array_merge($cases, $edgeCases);
    }

    /**
     * Generate a random valid PHP class name.
     */
    private static function generateRandomClassName(int $index): string
    {
        $prefixes = ['Provider', 'AI', 'Faq', 'Service', 'Connector', 'Client', 'Adapter', 'Handler'];
        $suffixes = ['Impl', 'Class', 'Service', 'Provider', 'Adapter', 'Stub', 'Mock', 'Test'];

        $prefix = $prefixes[mt_rand(0, count($prefixes) - 1)];
        $suffix = $suffixes[mt_rand(0, count($suffixes) - 1)];
        $middle = self::generateRandomSegment();

        return $prefix . $middle . $suffix . 'N' . $index;
    }

    /**
     * Generate a random segment for class names (valid PHP identifier characters).
     */
    private static function generateRandomSegment(): string
    {
        $length = mt_rand(3, 10);
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        $segment = '';
        for ($i = 0; $i < $length; $i++) {
            $segment .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        return $segment;
    }
}
