<?php

namespace OrderBundle\Serializer;

use OrderBundle\Product\ValueObject\ProductName;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ProductNameNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize(mixed $object, ?string $format = null, array $context = []): string
    {
        if (!$object instanceof ProductName) {
            throw new InvalidArgumentException('Expected ProductName object');
        }

        return $object->value;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ProductName;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): ProductName
    {
        if (!is_string($data) && $data !== null) {
            throw new InvalidArgumentException('Expected string or null');
        }

        return new ProductName((string) $data);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === ProductName::class && (is_string($data) || $data === null);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [ProductName::class => true];
    }
}