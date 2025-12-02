<?php

namespace OrderBundle\Tests\Serializer;

use OrderBundle\Serializer\ProductPriceNormalizer;
use OrderBundle\Product\ValueObject\ProductPrice;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

class ProductPriceNormalizerTest extends TestCase
{
    private ProductPriceNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ProductPriceNormalizer();
    }

    public function testNormalizeProductPrice(): void
    {
        $productPrice = new ProductPrice(1999); // $19.99

        $result = $this->normalizer->normalize($productPrice);

        $this->assertEquals(19.99, $result);
    }

    public function testNormalizeZeroPrice(): void
    {
        $productPrice = new ProductPrice(0);

        $result = $this->normalizer->normalize($productPrice);

        $this->assertEquals(0.0, $result);
    }

    public function testNormalizeLargePrice(): void
    {
        $productPrice = new ProductPrice(999999); // $9999.99

        $result = $this->normalizer->normalize($productPrice);

        $this->assertEquals(9999.99, $result);
    }

    public function testDenormalizeFloat(): void
    {
        $result = $this->normalizer->denormalize(19.99, ProductPrice::class);

        $this->assertInstanceOf(ProductPrice::class, $result);
        $this->assertEquals(1999, $result->cents);
        $this->assertEquals(19.99, $result->toDollars());
    }

    public function testDenormalizeInteger(): void
    {
        $result = $this->normalizer->denormalize(20, ProductPrice::class);

        $this->assertInstanceOf(ProductPrice::class, $result);
        $this->assertEquals(2000, $result->cents);
        $this->assertEquals(20.0, $result->toDollars());
    }

    public function testDenormalizeZero(): void
    {
        $result = $this->normalizer->denormalize(0, ProductPrice::class);

        $this->assertInstanceOf(ProductPrice::class, $result);
        $this->assertEquals(0, $result->cents);
        $this->assertEquals(0.0, $result->toDollars());
    }

    public function testDenormalizeNull(): void
    {
        $result = $this->normalizer->denormalize(null, ProductPrice::class);

        $this->assertInstanceOf(ProductPrice::class, $result);
        $this->assertEquals(0, $result->cents);
        $this->assertEquals(0.0, $result->toDollars());
    }

    public function testDenormalizeInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->normalizer->denormalize('invalid', ProductPrice::class);
    }

    public function testSupportsNormalization(): void
    {
        $productPrice = new ProductPrice(1999);

        $this->assertTrue($this->normalizer->supportsNormalization($productPrice));
        $this->assertFalse($this->normalizer->supportsNormalization('string'));
        $this->assertFalse($this->normalizer->supportsNormalization(123));
    }

    public function testSupportsDenormalization(): void
    {
        $this->assertTrue($this->normalizer->supportsDenormalization(19.99, ProductPrice::class));
        $this->assertTrue($this->normalizer->supportsDenormalization(20, ProductPrice::class));
        $this->assertTrue($this->normalizer->supportsDenormalization(null, ProductPrice::class));
        $this->assertFalse($this->normalizer->supportsDenormalization('string', ProductPrice::class));
        $this->assertFalse($this->normalizer->supportsDenormalization(19.99, 'SomeOtherClass'));
    }

    public function testGetSupportedTypes(): void
    {
        $supportedTypes = $this->normalizer->getSupportedTypes(null);

        $this->assertArrayHasKey(ProductPrice::class, $supportedTypes);
        $this->assertTrue($supportedTypes[ProductPrice::class]);
    }

    public function testRoundTripNormalization(): void
    {
        $originalPrice = 29.99;
        $productPrice = ProductPrice::fromDollars($originalPrice);

        $normalized = $this->normalizer->normalize($productPrice);
        $denormalized = $this->normalizer->denormalize($normalized, ProductPrice::class);

        $this->assertEquals($originalPrice, $denormalized->toDollars());
    }

    public function testHandlesRounding(): void
    {
        $productPrice = ProductPrice::fromDollars(19.999); // Should round to 19.99

        $result = $this->normalizer->normalize($productPrice);

        $this->assertEquals(20.0, $result); // fromDollars rounds 19.999 to 20.00
    }
}