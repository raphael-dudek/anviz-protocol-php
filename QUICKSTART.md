# Quick Start Guide - Anviz PHP Protocol Implementation

## 5-Minute Setup

### Prerequisites
- PHP 7.0+ (CLI or Web Server)
- Network connection to Anviz device
- Device IP address and port (default: 5010)

### Local Installation

```bash
# 1. Clone/Download the files
git clone <repository-url>
cd anviz-php

# 2. Configure device connection
cp .env.example .env
# Edit .env with your device details:
# DEVICE_IP=192.168.1.100
# DEVICE_PORT=5010
# DEVICE_ID=5

# 3. Run example
php example_usage.php
```

### Docker Installation

```bash
# 1. Build image
docker build -t anviz-php .

# 2. Run with environment variables
docker run --rm \
  -e DEVICE_IP=192.168.1.100 \
  -e DEVICE_PORT=5010 \
  -e DEVICE_ID=5 \
  anviz-php

# 3. Or use docker-compose
docker-compose up
```

## Basic Usage Example

```php
<?php
require_once 'AnvizProtocol.php';
use Anviz\AnvizProtocol;

// Connect
$anviz = new AnvizProtocol('192.168.1.100');
$anviz->connect();

// Get device time
$time = $anviz->getDeviceClock();
echo "Device time: {$time['year']}-{$time['month']}-{$time['day']}\n";

// Download records
$records = $anviz->downloadRecords();
foreach ($records as $r) {
    echo "User {$r['user_id']} @ {$r['datetime']}\n";
}

// Disconnect
$anviz->disconnect();
?>
```

## Common Tasks

### Get Device Information
```php
$sn = $anviz->getDeviceSerialNumber();
$id = $anviz->getDeviceID();
$config = $anviz->getDeviceConfig();
```

### Sync Device Time
```php
$anviz->setDeviceClock(date('Y-m-d H:i:s'));
```

### Download Attendance Records
```php
$info = $anviz->getRecordInfo();
echo "Total: {$info['total_records']} records\n";

$records = $anviz->downloadRecords();
```

### Configure Network
```php
$params = $anviz->getTcpIpParams();
echo "IP: {$params['ip_address']}\n";

$anviz->setTcpIpParams('192.168.1.101', '255.255.255.0', '192.168.1.1');
```

### Set Timezone
```php
$anviz->setTimezone(1, false); // UTC+1, no DST
```

## Troubleshooting

### Connection Failed
```
Error: Connection refused
```
- Check device IP and port
- Verify network connectivity: `ping 192.168.1.100`
- Ensure device is powered on

### Checksum Error
- Verify device ID is correct
- Check network isn't corrupting packets

### Timeout
- Increase socket timeout in code
- Check network latency

## Next Steps

1. Read `README.md` for complete feature list
2. Check `IMPLEMENTATION_COVERAGE.md` for what's implemented
3. Review `example_usage.php` for more examples
4. Consult `CommsProtocol.pdf` for protocol details

## Support

- GitHub Issues: Report bugs
- Documentation: See README.md
- Examples: See example_usage.php

## License

MIT - See LICENSE file
