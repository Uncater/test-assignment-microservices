<?php

namespace OrderBundle\Serializer;

use OrderBundle\Product\ValueObject\ProductQuantity;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ProductQuantityNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize(mixed $object, ?string $format = null, array $context = []): int
    {
        if (!$object instanceof ProductQuantity) {
            throw new InvalidArgumentException('Expected ProductQuantity object');
        }

        return $object->value;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ProductQuantity;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): ProductQuantity
    {
        if (!is_numeric($data) && $data !== null) {
            throw new InvalidArgumentException('Expected numeric value or null');
        }

        return new ProductQuantity((int) $data);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === ProductQuantity::class && (is_numeric($data) || $data === null);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [ProductQuantity::class => true];
    }
}