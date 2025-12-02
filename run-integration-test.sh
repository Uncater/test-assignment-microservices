#!/bin/bash

# Integration Test Runner for Symfony Docker Project
# Tests the complete event-driven architecture between order-service and product-service

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PRODUCT_ID=""
ORDER_ID=""
CONSUMER_PID=""
EXPECTED_QUANTITY=""

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Function to cleanup on exit
cleanup() {
    if [ ! -z "$CONSUMER_PID" ]; then
        print_status "Stopping RabbitMQ consumer..."
        docker-compose exec -T product-service pkill -f "messenger:consume" || true
    fi
}

# Set trap for cleanup
trap cleanup EXIT

# Function to wait for services to be ready
wait_for_services() {
    print_status "Waiting for services to be ready..."
    
    # Wait for product-service
    for i in {1..30}; do
        if docker-compose exec -T product-service curl -s http://product-service/health > /dev/null 2>&1; then
            break
        fi
        if [ $i -eq 30 ]; then
            print_error "Product service not ready after 30 seconds"
            exit 1
        fi
        sleep 1
    done
    
    # Wait for order-service
    for i in {1..30}; do
        if docker-compose exec -T order-service curl -s http://order-service/health > /dev/null 2>&1; then
            break
        fi
        if [ $i -eq 30 ]; then
            print_error "Order service not ready after 30 seconds"
            exit 1
        fi
        sleep 1
    done
    
    print_success "All services are ready"
}

# Function to start RabbitMQ consumer
start_consumer() {
    print_status "Starting RabbitMQ consumer for product events..."
    
    # Clear any existing log file
    docker-compose exec -T product-service rm -f /tmp/consumer.log
    
    # Start consumer in background with output to log file
    docker-compose exec -d product-service bash -c "php bin/console messenger:consume order_events -vv > /tmp/consumer.log 2>&1"
    sleep 2
    
    print_success "Consumer started (logging to /tmp/consumer.log)"
}

# Function to create a test product
create_product() {
    print_status "Creating test product..."
    
    local response=$(docker-compose exec -T product-service curl -s -X POST http://product-service/product \
        -H "Content-Type: application/json" \
        -d '{"name": "Integration Test Product", "price": 99.99, "quantity": 10}')
    
    PRODUCT_ID=$(echo "$response" | grep -o '"id":"[^"]*"' | cut -d'"' -f4)
    
    if [ -z "$PRODUCT_ID" ]; then
        print_error "Failed to create product. Response: $response"
        exit 1
    fi
    
    print_success "Product created with ID: $PRODUCT_ID"
}

# Function to verify product creation
verify_product() {
    print_status "Verifying product exists..."
    
    local response=$(docker-compose exec -T product-service curl -s http://product-service/product/$PRODUCT_ID)
    local quantity=$(echo "$response" | awk -F'"quantity":' '{print $2}' | awk -F'[,}]' '{print $1}')
    
    if [ "$quantity" != "10" ]; then
        print_error "Product verification failed. Expected quantity 10, got: $quantity"
        exit 1
    fi
    
    print_success "Product verified with initial quantity: $quantity"
}

# Function to create an order
create_order() {
    print_status "Creating order to trigger quantity decrease event..."
    
    local response=$(docker-compose exec -T order-service curl -s -X POST http://order-service/order \
        -H "Content-Type: application/json" \
        -d "{\"data\": {\"productId\": \"$PRODUCT_ID\", \"customerName\": \"Integration Test Customer\", \"quantityOrdered\": 3}}")
    
    ORDER_ID=$(echo "$response" | grep -o '"orderId":"[^"]*"' | cut -d'"' -f4)
    
    if [ -z "$ORDER_ID" ]; then
        print_error "Failed to create order. Response: $response"
        exit 1
    fi
    
    print_success "Order created with ID: $ORDER_ID"
}

# Function to wait for event processing
wait_for_event_processing() {
    print_status "Waiting for event processing (5 seconds)..."
    sleep 5
}

# Function to verify quantity decrease
verify_quantity_decrease() {
    print_status "Verifying product quantity was decreased..."
    
    local response=$(docker-compose exec -T product-service curl -s http://product-service/product/$PRODUCT_ID)
    echo $response
    local new_quantity=$(echo "$response" | awk -F'"quantity":' '{print $2}' | awk -F'[,}]' '{print $1}')
    
    # Check if quantity decreased (should be less than 10)
    if [ "$new_quantity" -ge "10" ]; then
        print_error "Quantity was not decreased. Expected less than 10, got: $new_quantity"
        print_error "Full response: $response"
        exit 1
    fi
    
    # Check if quantity is reasonable (should be positive and less than original)
    if [ "$new_quantity" -lt "0" ]; then
        print_error "Quantity became negative: $new_quantity"
        print_error "Full response: $response"
        exit 1
    fi
    
    print_success "Product quantity correctly decreased from 10 to: $new_quantity"
    
    # Store the new quantity for later verification
    EXPECTED_QUANTITY=$new_quantity
}

# Function to test insufficient stock scenario
test_insufficient_stock() {
    print_status "Testing insufficient stock scenario..."
    
    local response=$(docker-compose exec -T order-service curl -s -X POST http://order-service/order \
        -H "Content-Type: application/json" \
        -d "{\"data\": {\"productId\": \"$PRODUCT_ID\", \"customerName\": \"Test Customer 2\", \"quantityOrdered\": 10}}")
    
    if echo "$response" | grep -q "Insufficient stock"; then
        print_success "Insufficient stock scenario handled correctly"
    else
        print_error "Insufficient stock scenario failed. Response: $response"
        exit 1
    fi
}

# Function to test non-existent product
test_nonexistent_product() {
    print_status "Testing non-existent product scenario..."
    
    local fake_id="01234567-89ab-cdef-0123-456789abcdef"
    local response=$(docker-compose exec -T order-service curl -s -X POST http://order-service/order \
        -H "Content-Type: application/json" \
        -d "{\"data\": {\"productId\": \"$fake_id\", \"customerName\": \"Test Customer 3\", \"quantityOrdered\": 1}}")
    
    if echo "$response" | grep -q "Product not found"; then
        print_success "Non-existent product scenario handled correctly"
    else
        print_error "Non-existent product scenario failed. Response: $response"
        exit 1
    fi
}


# Main integration test function
run_integration_test() {
    echo "=========================================="
    echo "🧪 INTEGRATION TEST - Event-Driven Architecture"
    echo "=========================================="
    echo ""
    
    print_status "Starting integration test..."
    
    # Check if containers are running
    if ! docker-compose ps | grep -q "Up"; then
        print_error "Containers are not running. Please start them with 'make start' or 'docker-compose up -d'"
        exit 1
    fi
    
    # Run test steps
    wait_for_services
    start_consumer
    create_product
    verify_product
    start_consumer  # Restart consumer to ensure it's active
    create_order
    wait_for_event_processing
    verify_quantity_decrease
    test_insufficient_stock
    test_nonexistent_product
    
    echo ""
    echo "=========================================="
    print_success "🎉 INTEGRATION TEST PASSED!"
    echo "=========================================="
    echo ""
    print_success "✅ Product creation: WORKING"
    print_success "✅ Order creation: WORKING"
    print_success "✅ Event dispatching: WORKING"
    print_success "✅ Event consumption: WORKING"
    print_success "✅ Quantity updates: WORKING"
    print_success "✅ Error handling: WORKING"
    echo ""
    print_status "Test Results:"
    print_status "- Product ID: $PRODUCT_ID"
    print_status "- Order ID: $ORDER_ID"
    print_status "- Initial quantity: 10"
    print_status "- Final quantity: $EXPECTED_QUANTITY (events processed successfully)"
    echo ""
}

# Run the integration test
run_integration_test
