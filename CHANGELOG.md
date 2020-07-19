# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com) and this project adheres to [Semantic Versioning](https://semver.org).

## [Unreleased](https://github.com/SergeyBrook/jsonrpc-ws)
### Added
- `getResponse` method to manually get response.
### Changed
- `respond` method now returns `true` if response sent successfully or `false` otherwise.
- `respond` method now accepts optional `request` parameter.

## [1.1.0](https://github.com/SergeyBrook/jsonrpc-ws/releases/tag/v1.1.0) - 2020-02-18

## [1.1.0-rc.2](https://github.com/SergeyBrook/jsonrpc-ws/releases/tag/v1.1.0-rc.2) - 2020-02-18
### Added
- Validation of response type being `result` or `error` as per spec.
- Validation of type 'string' in `method` request field as per spec.
- Validation of version in `jsonrpc` request field as per spec.

## [1.1.0-rc.1](https://github.com/SergeyBrook/jsonrpc-ws/releases/tag/v1.1.0-rc.1) - 2020-02-17
### Added
- README file to examples.
- Postman test requests to examples.
- Check for allowed datatype in request parameters.
- `setName` and `getName` methods to manipulate service name.
- `userData` property (array) to inject dependencies for method handlers.
- JSON-RPC 2.0 spec reference to README file.
### Changed
- Examples to demonstrate new features.
- CHANGELOG file format.
- VERSION file renamed to CHANGELOG.
### Fixed
- Minimum PHP version in Composer file.
### Deprecated
- `setServiceName` method will be removed in v2.0.0. Use `setName` method instead.

## [1.0.1](https://github.com/SergeyBrook/jsonrpc-ws/releases/tag/v1.0.1) - 2018-11-08
### Fixed
- Correctly passing empty array to service method handler when request params property is not set.
### Changed
- VERSION file format to markdown.

## [1.0.0](https://github.com/SergeyBrook/jsonrpc-ws/releases/tag/v1.0.0) - 2018-07-11
### Added
- Support information to Composer file.
- Installation and usage instructions to README file.
### Removed
- Composer file from examples dir.

## [0.1.0](https://github.com/SergeyBrook/jsonrpc-ws/releases/tag/v0.1.0) - 2018-07-10 - Initial release.
