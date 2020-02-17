# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com) and this project adheres to [Semantic Versioning](https://semver.org).

## [Unreleased](https://github.com/SergeyBrook/jsonrpc-ws)
### Added
- README file to examples.
- Postman test requests to examples.
- Check for allowed parameter datatype.
- `setName` and `getName` methods to manipulate service name.
- `userData` array property to inject handlers dependencies.
- JSON-RPC spec reference to README file.
### Changed
- Examples to demonstrate new features.
- CHANGELOG file format.
- VERSION file renamed to CHANGELOG.
### Fixed
- Minimum PHP version in Composer file.
### Deprecated
- `setServiceName` method. Use `setName` method instead.

## [1.0.1](https://github.com/SergeyBrook/jsonrpc-ws/releases/tag/v1.0.1) - 2018-11-08
### Fixed
- Correctly passing empty array to service method handler when request params property is not set.
### Changed
- VERSION file format to markdown.

## [1.0.0](https://github.com/SergeyBrook/jsonrpc-ws/releases/tag/v1.0.0) - 2018-07-11
### Added
- Support information to Composer file.
- Installation and usage instructions to README.
### Removed
- Composer file from examples dir.

## [0.1.0](https://github.com/SergeyBrook/jsonrpc-ws/releases/tag/v0.1.0) - 2018-07-10 - Initial release.
