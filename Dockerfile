FROM php:8.1-cli

LABEL maintainer="Anviz Protocol Team"
LABEL description="Anviz PHP Protocol Implementation"

# Set working directory
WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    sockets \
    && rm -rf /var/lib/apt/lists/*

# Enable PHP sockets extension
RUN docker-php-ext-install sockets

# Copy application files
COPY AnvizProtocol.php /app/
COPY example_usage.php /app/
COPY docker-entrypoint.sh /app/
RUN chmod +x /app/docker-entrypoint.sh

# Create logs directory
RUN mkdir -p /app/logs

# Set environment variables with defaults
ENV DEVICE_IP=192.168.1.100
ENV DEVICE_PORT=5010
ENV DEVICE_ID=5
ENV LOG_LEVEL=INFO

# Health check
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
    CMD php -r "\$anviz = new Anviz\\AnvizProtocol(getenv('DEVICE_IP'), (int)getenv('DEVICE_PORT'), (int)getenv('DEVICE_ID')); if(@\$anviz->connect()) { \$anviz->disconnect(); exit(0); } exit(1);" || exit 1

# Default command
ENTRYPOINT ["/app/docker-entrypoint.sh"]
CMD ["php", "example_usage.php"]
