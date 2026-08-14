# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com), and this
project adheres to [Semantic Versioning](https://semver.org).

## [Unreleased]

### Added

- **Message provenance.** Delivered notifications now record the component that asked for
  them — `mod_forum`, `mod_assign`, `moodle` — on the Local Mail message itself. This is what
  lets the mailbox tell generated mail from mail a person wrote, and it is the whole of this
  plugin's share of Local Mail's categories and retention policy; everything else about that
  feature lives there. The originating component is stored rather than this plugin's own name,
  because the mailbox does not care which transport delivered the message. Local Mail keeps
  the field write-once, so a reply to a delivered notification stays ordinary human mail.
  Requires Local Mail 2026081302 or later, and the declared dependency has been raised
  accordingly: against an older release the new assignment would create a dynamic property
  and emit a deprecation on every delivery.

- **System sender account** setting. Core sends many genuinely course-scoped notifications
  from a placeholder user — course completion, quiz submission confirmations, analytics
  insights all set a real course but send from noreply or support — and those were dropped.
  Configure a username and they are delivered from that account instead. Empty by default,
  so an upgrade changes nothing until an administrator opts in.

### Changed

- A recipient who cannot use mail in the course is now skipped instead of having a message
  written into a mailbox that cannot show it. Local Mail scopes every listing to the courses
  a user is actively enrolled in, so such a row was visible to nobody.
- Every skip now reports through `debugging()` at developer level, naming the provider, so a
  site can measure what it would lose before reducing its other message outputs.

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
