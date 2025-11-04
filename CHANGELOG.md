# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-11-04

### Added
- Initial release of Anviz Protocol PHP implementation
- 25+ implemented commands covering core functionality
- Full TCP/IP communication with CRC-16 CCITT checksum
- Date/Time synchronization support
- Device information retrieval
- User management (download, delete)
- Attendance records management (download, clear, info)
- Network configuration (get/set TCP/IP parameters)
- Timezone information (get/set)
- Device control (ping, reboot, factory reset, unlock door)
- Docker support with docker-compose
- Comprehensive documentation (11,000+ words)
- Implementation coverage analysis
- Quick start guide
- Security documentation
- Contributing guidelines
- GitHub Actions CI/CD pipeline

### Features Implemented
- Command 0x38: Get Device Clock
- Command 0x39: Set Device Clock
- Command 0x30: Get Device Configuration 1
- Command 0x50: Get Device S/N
- Command 0x52: Get Device ID
- Command 0x3C: Download Staff Data
- Command 0x92: Delete User
- Command 0x4C: Download Records
- Command 0x74: Download New Records
- Command 0x82: Get Record Information
- Command 0x4D: Clear Records
- Command 0x5C: Get TCP/IP Parameters
- Command 0x5D: Set TCP/IP Parameters
- Command 0xB0: Get Timezone
- Command 0xB1: Set Timezone
- Command 0x7E: Open Door
- Command 0x8B: Reboot Device
- Command 0x8D: Factory Reset
- Command 0x81: Ping Device

### Documentation
- README.md with features, installation, usage
- IMPLEMENTATION_COVERAGE.md with detailed analysis
- QUICKSTART.md for 5-minute setup
- SECURITY.md with security considerations
- CONTRIBUTING.md with contribution guidelines
- Inline code documentation and examples

### Testing
- Tested with Anviz A300 series
- Tested on PHP 7.4, 8.0, 8.1, 8.2
- Docker testing environment
- Example usage script

### Infrastructure
- GitHub Actions CI/CD workflow
- Docker and docker-compose support
- Environment variable configuration
- Issue templates

## [Unreleased]

### Planned for Next Release
- [ ] Fingerprint template download/upload
- [ ] Advanced device settings (0x34, 0x35)
- [ ] Bell schedule configuration (0xB2, 0xB3)
- [ ] Facepass template support
- [ ] Real-time event streaming
- [ ] Connection pooling
- [ ] Async/await support
- [ ] PHP unit tests
- [ ] Performance benchmarks

### Known Issues
- Fingerprint templates not implemented (requires biometric format docs)
- No native async support (blocking operations)
- Advanced settings parsing incomplete
- Real-time events not implemented

---

## How to Use This Changelog

- Use "Added" for new features
- Use "Changed" for changes in existing functionality
- Use "Deprecated" for soon-to-be removed features
- Use "Removed" for now removed features
- Use "Fixed" for any bug fixes
- Use "Security" for security fixes or advisories

### Release Process

1. Update version numbers according to SemVer
2. Update CHANGELOG.md with new section
3. Create git tag: `git tag -a v1.0.0 -m "Release v1.0.0"`
4. Push to GitHub: `git push --tags`
5. Create GitHub Release from tag

---

**Latest Release**: v1.0.0
**Release Date**: November 4, 2024
