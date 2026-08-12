# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com), and this
project adheres to [Semantic Versioning](https://semver.org).

## [Unreleased]

### Added

- Declared `$plugin->supported = [405, 502]`. The ceiling tracks the Local Mail
  dependency, which declares the same range, rather than the newest core release.
- Privacy provider. The plugin stores no personal data of its own, so it declares
  `null_provider`: every row it produces is written through the Local Mail API into
  tables that plugin's own provider already exports and deletes.
- Brazilian Portuguese language pack.
- GitHub Actions CI, one job per supported Moodle branch.

### Fixed

- A notification carrying no course id crashed the sending task. `$courseid` arrives
  as `null` rather than `0`, which the site-course guard did not catch and Local Mail's
  `course::get()` rejected with a `TypeError` — not a `moodle_exception`, so
  `message_send()` could not catch it either. Affected the async backup notice, the
  failed-task callbacks and the activity due-date reminders, and also suppressed
  e-mail delivery for the same notification.
- Guards now run before the lookups they protect, so notifications from internal
  users such as noreply are skipped instead of throwing.
- Deleted and guest users are detected by their deleted flag rather than by a
  non-positive id, which Local Mail never returns for them.
- A failed delivery no longer leaves its database transaction open for the rest of
  the request, which previously discarded every write that followed it.
- An attachment dropped by Local Mail over its size limit now reports a debugging
  notice instead of disappearing silently.
- The processor no longer fails when no user is authenticated.

## [1.2] - 2025-05-08

### Fixed

- Compatibility with Local Mail v2.15+.

## [1.1] - 2025-04-14

### Fixed

- Compatibility with Local Mail v2.13+.

## [1.0] - 2024-01-30

### Added

- Process notifications originated from courses and sent by real users.
