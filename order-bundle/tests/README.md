# OrderBundle Unit Tests

This directory contains comprehensive unit tests for all classes in the OrderBundle.

## Test Structure

The tests are organized to mirror the source code structure:

```
tests/
├── Dto/
│   └── ProductDtoTest.php
├── Entity/
│   ├── AbstractProductTest.php
│   └── PaginationInfoTest.php
├── Messaging/
│   └── Product/
│       └── Event/
│           ├── ProductCreatedMessageTest.php
│           ├── ProductQuantityDecreasedEventTest.php
│           └── ProductUpdatedMessageTest.php
├── Product/
│   └── ValueObject/
│       ├── ProductNameTest.php
│       ├── ProductPriceTest.php
│       └── ProductQuantityTest.php
├── Serializer/
│   ├── ProductNameNormalizerTest.php
│   ├── ProductPriceNormalizerTest.php
│   └── ProductQuantityNormalizerTest.php
├── Testing/
│   └── Handler/
│       └── TestProductMessageHandlerTest.php
└── OrderBundleTest.php
```

## Running Tests

### Run all tests:
```bash
vendor/bin/phpunit
```

### Run tests with coverage (requires Xdebug):
```bash
vendor/bin/phpunit --coverage-html coverage/
```

### Run specific test class:
```bash
vendor/bin/phpunit tests/Product/ValueObject/ProductPriceTest.php
```

### Run tests in a specific directory:
```bash
vendor/bin/phpunit tests/Product/
```

## Test Coverage

The test suite provides comprehensive coverage for:

### Value Objects
- **ProductName**: String value object with toString functionality
- **ProductPrice**: Price handling with cents/dollars conversion
- **ProductQuantity**: Integer quantity value object

### DTOs
- **ProductDto**: Immutable data transfer object with value objects

### Entities
- **AbstractProduct**: Doctrine-mapped superclass with value object methods
- **PaginationInfo**: Pagination metadata with array conversion

### Messaging
- **ProductCreatedMessage**: Event for product creation
- **ProductUpdatedMessage**: Event for product updates  
- **ProductQuantityDecreasedEvent**: Event for quantity decreases

### Serializers
- **ProductNameNormalizer**: Serialization for ProductName
- **ProductPriceNormalizer**: Complex serialization for ProductPrice (cents/dollars)
- **ProductQuantityNormalizer**: Serialization for ProductQuantity

### Testing Utilities
- **TestProductMessageHandler**: Test helper for message handling verification

### Bundle
- **OrderBundle**: Main bundle class functionality

## Test Quality

Each test class includes:

- ✅ **Property validation**: Readonly properties, correct types
- ✅ **Edge cases**: Empty values, zero values, negative values, large values
- ✅ **Error handling**: Invalid inputs, exception scenarios
- ✅ **Round-trip testing**: Serialization/deserialization cycles
- ✅ **Interface compliance**: Proper interface implementations
- ✅ **Class characteristics**: Final classes, abstract classes
- ✅ **Behavior verification**: Method functionality and side effects

## Dependencies

The tests require:
- PHPUnit 10.5+
- PHP 8.4+
- Symfony components (as defined in composer.json)

## Continuous Integration

These tests are designed to run in CI environments and provide:
- Fast execution (unit tests only, no external dependencies)
- Comprehensive coverage reporting
- Clear failure messages
- Deterministic results
