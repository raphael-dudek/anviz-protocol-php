# Contributing to Anviz Protocol PHP Implementation

Thank you for your interest in contributing! This document provides guidelines and instructions for contributing to this project.

## Code of Conduct

Be respectful, inclusive, and professional in all interactions.

## How to Contribute

### Reporting Bugs

If you find a bug, please create an issue with:
- Clear description of the problem
- Steps to reproduce
- Expected vs actual behavior
- Device model and OS information
- PHP version and environment

### Suggesting Enhancements

Enhancement suggestions are welcome:
- Clear description of the feature
- Use case and benefits
- Possible implementation approach
- References to protocol documentation if applicable

### Pull Requests

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Open a Pull Request

### Coding Standards

- PSR-12 compliant code
- Comprehensive comments for complex logic
- Error handling for all network operations
- No hardcoded credentials or sensitive data

### Testing

- Test with real Anviz devices if possible
- Document tested device models
- Report any device-specific behaviors

## Development Setup

```bash
# Clone repository
git clone https://github.com/yourusername/anviz-protocol-php.git
cd anviz-protocol-php

# Test locally
php example_usage.php

# Test with Docker
docker build -t anviz-test .
docker run -e DEVICE_IP=192.168.1.100 anviz-test
```

## Protocol Implementation

When implementing new commands:

1. Reference the protocol specification (CommsProtocol.pdf)
2. Follow the existing command pattern
3. Include command code constant (0xXX)
4. Add method documentation with command code
5. Test with device if possible

Example:

```php
private const CMD_NEW_FEATURE = 0xNN;

/**
 * New feature description
 * Command: 0xNN
 * 
 * @param mixed $param Parameter description
 * @return array|null Result
 */
public function newFeature($param)
{
    $data = [...]; // Build command data
    $response = $this->sendCommand(self::CMD_NEW_FEATURE, $data);
    return $this->parseResponse($response);
}
```

## Documentation

- Update README.md for new features
- Add examples to example_usage.php
- Update IMPLEMENTATION_COVERAGE.md
- Document any limitations or known issues

## Licensing

By contributing, you agree that your contributions will be licensed under the MIT License.

## Questions?

Open an issue or contact the maintainers.

Thank you for contributing!
