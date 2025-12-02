<?php

namespace OrderBundle\Tests\Serializer;

use OrderBundle\Serializer\ProductQuantityNormalizer;
use OrderBundle\Product\ValueObject\ProductQuantity;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

class ProductQuantityNormalizerTest extends TestCase
{
    private ProductQuantityNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ProductQuantityNormalizer();
    }

    public function testNormalizeProductQuantity(): void
    {
        $productQuantity = new ProductQuantity(10);

        $result = $this->normalizer->normalize($productQuantity);

        $this->assertEquals(10, $result);
    }

    public function testNormalizeZeroQuantity(): void
    {
        $productQuantity = new ProductQuantity(0);

        $result = $this->normalizer->normalize($productQuantity);

        $this->assertEquals(0, $result);
    }

    public function testNormalizeLargeQuantity(): void
    {
        $productQuantity = new ProductQuantity(99999);

        $result = $this->normalizer->normalize($productQuantity);

        $this->assertEquals(99999, $result);
    }

    public function testNormalizeNegativeQuantity(): void
    {
        $productQuantity = new ProductQuantity(-5);

        $result = $this->normalizer->normalize($productQuantity);

        $this->assertEquals(-5, $result);
    }

    public function testDenormalizeInteger(): void
    {
        $result = $this->normalizer->denormalize(15, ProductQuantity::class);

        $this->assertInstanceOf(ProductQuantity::class, $result);
        $this->assertEquals(15, $result->value);
    }

    public function testDenormalizeFloat(): void
    {
        $result = $this->normalizer->denormalize(15.7, ProductQuantity::class);

        $this->assertInstanceOf(ProductQuantity::class, $result);
        $this->assertEquals(15, $result->value); // Cast to int
    }

    public function testDenormalizeZero(): void
    {
        $result = $this->normalizer->denormalize(0, ProductQuantity::class);

        $this->assertInstanceOf(ProductQuantity::class, $result);
        $this->assertEquals(0, $result->value);
    }

    public function testDenormalizeNull(): void
    {
        $result = $this->normalizer->denormalize(null, ProductQuantity::class);

        $this->assertInstanceOf(ProductQuantity::class, $result);
        $this->assertEquals(0, $result->value);
    }

    public function testDenormalizeNegative(): void
    {
        $result = $this->normalizer->denormalize(-3, ProductQuantity::class);

        $this->assertInstanceOf(ProductQuantity::class, $result);
        $this->assertEquals(-3, $result->value);
    }

    public function testDenormalizeInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->normalizer->denormalize('invalid', ProductQuantity::class);
    }

    public function testSupportsNormalization(): void
    {
        $productQuantity = new ProductQuantity(10);

        $this->assertTrue($this->normalizer->supportsNormalization($productQuantity));
        $this->assertFalse($this->normalizer->supportsNormalization('string'));
        $this->assertFalse($this->normalizer->supportsNormalization(123));
    }

    public function testSupportsDenormalization(): void
    {
        $this->assertTrue($this->normalizer->supportsDenormalization(10, ProductQuantity::class));
        $this->assertTrue($this->normalizer->supportsDenormalization(10.5, ProductQuantity::class));
        $this->assertTrue($this->normalizer->supportsDenormalization(null, ProductQuantity::class));
        $this->assertFalse($this->normalizer->supportsDenormalization('string', ProductQuantity::class));
        $this->assertFalse($this->normalizer->supportsDenormalization(10, 'SomeOtherClass'));
    }

    public function testGetSupportedTypes(): void
    {
        $supportedTypes = $this->normalizer->getSupportedTypes(null);

        $this->assertArrayHasKey(ProductQuantity::class, $supportedTypes);
        $this->assertTrue($supportedTypes[ProductQuantity::class]);
    }

    public function testRoundTripNormalization(): void
    {
        $originalQuantity = 25;
        $productQuantity = new ProductQuantity($originalQuantity);

        $normalized = $this->normalizer->normalize($productQuantity);
        $denormalized = $this->normalizer->denormalize($normalized, ProductQuantity::class);

        $this->assertEquals($originalQuantity, $denormalized->value);
    }

    public function testHandlesStringNumbers(): void
    {
        $result = $this->normalizer->denormalize('42', ProductQuantity::class);

        $this->assertInstanceOf(ProductQuantity::class, $result);
        $this->assertEquals(42, $result->value);
    }

    public function testHandlesFloatToIntConversion(): void
    {
        $result = $this->normalizer->denormalize(42.9, ProductQuantity::class);

        $this->assertInstanceOf(ProductQuantity::class, $result);
        $this->assertEquals(42, $result->value); // Truncated, not rounded
    }
}