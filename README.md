# Event-Driven Microservices with Symfony & Docker

## 🚀 Quick Start

### Prerequisites
- [Docker](https://www.docker.com/) and [Docker Compose](https://docs.docker.com/compose/install/) (v2.10+)
- Make (usually pre-installed on macOS/Linux)

### 🎯 One-Command Setup
```bash
make setup
```
This will:
- Start all containers
- Install dependencies
- Create databases
- Wait for services to be ready

### 🧪 Run All Tests
```bash
make test
```
This runs:
- Unit tests for all services
- Integration tests for the complete event flow

### 🎬 Full Demo
```bash
make demo
```
Complete setup + all tests to verify everything works.

## 📋 Available Commands

### **Container Management**
```bash
make start          # Start all containers
make stop           # Stop all containers
make restart        # Restart all containers
make build          # Build containers from scratch
make status         # Show container status
```

### **Testing**
```bash
make test                    # Run all tests (unit + integration)
make test-unit              # Run unit tests for all services
make test-integration       # Run end-to-end integration tests
make test-order-bundle      # Test order-bundle only
make test-product-service   # Test product-service only  
make test-order-service     # Test order-service only
make test-coverage          # Generate coverage reports
```

### **Development**
```bash
make install        # Install dependencies for all services
make dev           # Quick development setup
make clean         # Clean up containers and volumes
```

### **Database Management**
```bash
make db-create     # Create databases
make db-drop       # Drop databases
make db-reset      # Reset databases (drop + create)
```

### **Messaging & Monitoring**
```bash
make consumer-start    # Start RabbitMQ consumer
make consumer-stop     # Stop RabbitMQ consumer
make rabbitmq-ui      # Open RabbitMQ management UI
make health           # Check service health
make urls             # Show all service URLs
```

### **CI/CD**
```bash
make ci            # Full CI pipeline (build + test)
```

## 🌐 Service URLs

| Service | URL | Description |
|---------|-----|-------------|
| **Order Service** | http://localhost:8081 | Order management API |
| **Product Service** | http://localhost:8080 | Product catalog API |
| **RabbitMQ Management** | http://localhost:15672 | Message broker UI (admin/pass) |

## 🧪 Testing Strategy

### **Unit Tests**
- **Order Bundle**: 141 tests, 301 assertions - Complete value objects, DTOs, events
- **Product Service**: 15 tests, 81 assertions - Domain logic and message handlers  
- **Order Service**: 24 tests, 91 assertions - Business logic and external integrations

### **Integration Tests**
- End-to-end event flow testing
- Real service communication
- Database transactions
- Message queue processing

### **Test Coverage**
```bash
make test-coverage
```
Generates HTML coverage reports in each service's `coverage/` directory.

## 🏛️ Project Structure

```
├── order-service/          # Order management microservice
│   ├── src/
│   │   ├── Order/Domain/    # Order domain logic
│   │   ├── Order/Infrastructure/  # Controllers, repositories
│   │   └── Product/         # Product bounded context
│   └── tests/               # Unit and functional tests
├── product-service/         # Product catalog microservice  
│   ├── src/
│   │   ├── Product/Domain/  # Product domain logic
│   │   └── Product/Infrastructure/  # Controllers, repositories, message handlers
│   └── tests/               # Unit and functional tests
├── order-bundle/           # Shared domain library
│   ├── src/
│   │   ├── Dto/            # Data transfer objects
│   │   ├── Entity/         # Shared entities
│   │   ├── Messaging/      # Domain events
│   │   ├── Product/        # Value objects
│   │   └── Serializer/     # Normalizers
│   └── tests/              # Comprehensive unit tests
├── Makefile               # Development automation
├── run-integration-test.sh # Integration test runner
└── docker-compose.yml     # Container orchestration
```

## 🔄 Event Flow Example

1. **Order Creation** → Order Service receives HTTP request
2. **Event Dispatch** → `ProductQuantityDecreasedEvent` sent to RabbitMQ
3. **Event Consumption** → Product Service processes the event
4. **Inventory Update** → Product quantity decreased in database
5. **Confirmation** → Updated product state available via API

## 🛠️ Development Workflow

```bash
# Start development environment
make dev

# Run tests during development
make test-unit

# Test the complete event flow
make test-integration

# Check service health
make health

# View logs
make logs
```

## 📚 Additional Resources

- **Integration Test Script**: `./run-integration-test.sh` - Standalone integration testing
- **Order Bundle Documentation**: `order-bundle/tests/README.md` - Detailed test documentation
- **API Documentation**: Each service exposes REST APIs for orders and products
- **Message Contracts**: Shared events defined in order-bundle for service communication

**Ready to explore event-driven microservices?** Start with `make demo` and dive into the code! 🚀
