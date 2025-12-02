<?php

namespace OrderBundle\Serializer;

use OrderBundle\Product\ValueObject\ProductPrice;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ProductPriceNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize(mixed $object, ?string $format = null, array $context = []): float
    {
        if (!$object instanceof ProductPrice) {
            throw new InvalidArgumentException('Expected ProductPrice object');
        }

        return $object->toDollars();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ProductPrice;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): ProductPrice
    {
        if (!is_numeric($data) && $data !== null) {
            throw new InvalidArgumentException('Expected numeric value or null');
        }

        return ProductPrice::fromDollars((float) $data);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === ProductPrice::class && (is_numeric($data) || $data === null);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [ProductPrice::class => true];
    }
}