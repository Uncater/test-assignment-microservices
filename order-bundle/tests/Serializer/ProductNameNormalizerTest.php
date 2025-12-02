<?php

namespace OrderBundle\Tests\Serializer;

use OrderBundle\Serializer\ProductNameNormalizer;
use OrderBundle\Product\ValueObject\ProductName;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

class ProductNameNormalizerTest extends TestCase
{
    private ProductNameNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ProductNameNormalizer();
    }

    public function testNormalizeProductName(): void
    {
        $productName = new ProductName('Test Product');

        $result = $this->normalizer->normalize($productName);

        $this->assertEquals('Test Product', $result);
    }

    public function testNormalizeEmptyProductName(): void
    {
        $productName = new ProductName('');

        $result = $this->normalizer->normalize($productName);

        $this->assertEquals('', $result);
    }

    public function testNormalizeProductNameWithSpecialCharacters(): void
    {
        $productName = new ProductName('Product & Co. "Special" Edition');

        $result = $this->normalizer->normalize($productName);

        $this->assertEquals('Product & Co. "Special" Edition', $result);
    }

    public function testDenormalizeString(): void
    {
        $result = $this->normalizer->denormalize('Test Product', ProductName::class);

        $this->assertInstanceOf(ProductName::class, $result);
        $this->assertEquals('Test Product', $result->value);
    }

    public function testDenormalizeEmptyString(): void
    {
        $result = $this->normalizer->denormalize('', ProductName::class);

        $this->assertInstanceOf(ProductName::class, $result);
        $this->assertEquals('', $result->value);
    }

    public function testDenormalizeNull(): void
    {
        $result = $this->normalizer->denormalize(null, ProductName::class);

        $this->assertInstanceOf(ProductName::class, $result);
        $this->assertEquals('', $result->value);
    }

    public function testDenormalizeInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        @$this->normalizer->denormalize(['invalid'], ProductName::class);
    }

    public function testSupportsNormalization(): void
    {
        $productName = new ProductName('Test');

        $this->assertTrue($this->normalizer->supportsNormalization($productName));
        $this->assertFalse($this->normalizer->supportsNormalization('string'));
        $this->assertFalse($this->normalizer->supportsNormalization(123));
    }

    public function testSupportsDenormalization(): void
    {
        $this->assertTrue($this->normalizer->supportsDenormalization('string', ProductName::class));
        $this->assertTrue($this->normalizer->supportsDenormalization(null, ProductName::class));
        $this->assertFalse($this->normalizer->supportsDenormalization('string', 'SomeOtherClass'));
        $this->assertFalse($this->normalizer->supportsDenormalization([], ProductName::class));
    }

    public function testGetSupportedTypes(): void
    {
        $supportedTypes = $this->normalizer->getSupportedTypes(null);

        $this->assertArrayHasKey(ProductName::class, $supportedTypes);
        $this->assertTrue($supportedTypes[ProductName::class]);
    }

    public function testNormalizeWithLongString(): void
    {
        $longName = str_repeat('A', 1000);
        $productName = new ProductName($longName);

        $result = $this->normalizer->normalize($productName);

        $this->assertEquals($longName, $result);
    }

    public function testNormalizeWithUnicodeCharacters(): void
    {
        $productName = new ProductName('Café Münchën 日本語');

        $result = $this->normalizer->normalize($productName);

        $this->assertEquals('Café Münchën 日本語', $result);
    }

    public function testRoundTripNormalization(): void
    {
        $originalName = 'Test Product Name';
        $productName = new ProductName($originalName);

        $normalized = $this->normalizer->normalize($productName);
        $denormalized = $this->normalizer->denormalize($normalized, ProductName::class);

        $this->assertEquals($originalName, $denormalized->value);
    }
}