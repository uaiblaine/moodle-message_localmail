# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com), and this
project adheres to [Semantic Versioning](https://semver.org).

## [Unreleased]

### Added

- Declared `$plugin->supported = [405, 502]`. The ceiling tracks the Local Mail
  dependency, which declares the same range, rather than the newest core release.

## [1.2] - 2025-05-08

### Fixed

- Compatibility with Local Mail v2.15+.

## [1.1] - 2025-04-14

### Fixed

- Compatibility with Local Mail v2.13+.

## [1.0] - 2024-01-30

### Added

- Process notifications originated from courses and sent by real users.
