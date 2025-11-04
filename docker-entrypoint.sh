#!/bin/bash

# Anviz Protocol Docker Entrypoint
# Validates environment and runs PHP application

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== Anviz Protocol PHP Implementation ===${NC}"
echo ""

# Validate environment variables
validate_env() {
    local var_name=$1
    local var_value=$(eval echo \$$var_name)

    if [ -z "$var_value" ]; then
        echo -e "${RED}✗ $var_name is not set${NC}"
        return 1
    else
        echo -e "${GREEN}✓ $var_name = $var_value${NC}"
        return 0
    fi
}

echo "Validating environment variables..."
validate_env DEVICE_IP || exit 1
validate_env DEVICE_PORT || exit 1
validate_env DEVICE_ID || exit 1

echo ""
echo "Environment:"
echo "  DEVICE_IP: $DEVICE_IP"
echo "  DEVICE_PORT: $DEVICE_PORT"
echo "  DEVICE_ID: $DEVICE_ID"
echo "  LOG_LEVEL: ${LOG_LEVEL:-INFO}"
echo ""

# Test connectivity (optional)
if [ "$TEST_CONNECTIVITY" = "1" ]; then
    echo "Testing device connectivity..."
    if timeout 5 bash -c "cat > /dev/null < /dev/tcp/$DEVICE_IP/$DEVICE_PORT" 2>/dev/null; then
        echo -e "${GREEN}✓ Device is reachable${NC}"
    else
        echo -e "${YELLOW}⚠ Device is not reachable - proceeding anyway${NC}"
    fi
    echo ""
fi

# Execute command
echo "Starting application..."
exec "$@"
