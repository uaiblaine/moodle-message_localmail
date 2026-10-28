# Local Mail notification plugin for Moodle

[![ci](https://github.com/uaiblaine/moodle-message_localmail/actions/workflows/ci.yml/badge.svg)](https://github.com/uaiblaine/moodle-message_localmail/actions/workflows/ci.yml)

This plugin allows using the Local Mail plugin (`local_mail`) as a message consumer. It needs
this fork's build of Local Mail, not the one on moodle.org: see *Requirements*.

Local Mail is scoped to courses people are enrolled in, so this plugin only processes
notifications that meet the following conditions:
- The notification belongs to a course.
- Course is not the front page. Moodle strips the site course in
  `enrol_get_all_users_courses()`, so nobody — administrators included — can hold a
  site-course mailbox. Site-wide notices therefore need another output enabled.
- The recipient is a real, non-deleted user who can use mail in that course, meaning an
  active enrolment plus `local/mail:usemail`. This is the same pair the Local Mail
  recipient picker applies, so the plugin refuses exactly what the compose form refuses.
- Sender and recipient are not the same user.

The sender may be a placeholder. Core sends many genuinely course-scoped notifications —
course completion, quiz submission confirmations, analytics insights — from the noreply or
support user. Set **System sender account** in the plugin settings to the username of an
account to show as the sender for those; while it is empty they are skipped. The account
needs no enrolment and no capability, and a copy of each notification is kept in its Sent
folder, so a dedicated account is preferable to a real person.

One more thing worth knowing: because a notification is delivered as mail, a copy stays in
the sender's Sent folder, in the same way as any message they send themselves.

Every skip above is reported with `debugging()` at developer level, so a site can measure
what it would lose before reducing the other message outputs.

> **Note for administrators.** *Default notification preferences* lists Local Mail as a
> column for **every** notification provider, including site-wide ones such as backup
> completion, failed tasks and available updates. Enabling it there has no effect: those
> notifications carry no course, so they have no mailbox to be delivered into. Moodle offers
> no way for a message output to declare which providers it serves — the mechanism exists but
> is reserved for SMS — so the column cannot be hidden. Keep another output enabled for
> site-wide providers.

By default, all notification preferences are disabled and locked. They need to be enabled at the site administration.

## Requirements

| | |
|---|---|
| Moodle | 4.5 through 5.3 |
| Local Mail (`local_mail`) | [uaiblaine/moodle-local_mail](https://github.com/uaiblaine/moodle-local_mail) version 2026102901 or later |

The range tracks what Local Mail itself supports, since that is the real ceiling;
Local Mail requires Moodle 4.5, and so does this plugin.

The dependency is declared in `version.php` and must be the fork named above. Each
delivered notification records its originating component on the Local Mail message, a
field the [moodle.org release of Local Mail](https://moodle.org/plugins/local_mail) does
not have, and that release's version number is below the one this plugin requires, so
Moodle refuses to install the pair.

## Settings

- `message_localmail/systemsender` (*System sender account*, empty by default): the
  username of the account shown as the sender of course notifications that core sends
  from a placeholder user such as noreply or support. While it is empty those
  notifications are skipped.

Delivery itself is enabled per notification provider under *Site administration >
General > Messaging > Default notification preferences*.

## Capabilities

The plugin defines no capabilities. It reads Local Mail's `local/mail:usemail` to decide
whether a recipient can use mail in the course.

## Data stored

The plugin has no database tables and stores no personal data of its own; its privacy
provider declares exactly that. Each notification it delivers becomes an ordinary Local
Mail message, written through the Local Mail API into Local Mail's own tables
(`local_mail_messages`, `local_mail_message_users`, `local_mail_message_refs`,
`local_mail_message_labels`) and file area, with the originating component recorded on
the message. Those messages stay in the recipient's inbox and in the sender's Sent folder
until the users delete them or Local Mail's retention policy removes them, and Local
Mail's privacy provider exports and deletes them on a data request. A data request to
this plugin itself therefore exports nothing and a deletion removes nothing.

An attachment is copied into a draft area of the user running the delivery and that draft
area is deleted before the delivery returns, delivered or not. The only stored setting is
the system sender's username.

## Installation

Unpack archive inside `/path/to/moodle/message/output/localmail`
(`/path/to/moodle/public/message/output/localmail` on Moodle 5.1 and later, where
the web root moved into `public/`).

For general instructions on installing plugins see:
https://docs.moodle.org/en/Installing_plugins

## Translations

The English strings ship in the package. The Catalan, Spanish, Basque and Galician
translations from upstream and the Brazilian Portuguese translation of this fork are kept
in this repository (`lang/`) and reach sites through [AMOS](https://lang.moodle.org/), as
the plugins directory requires; they are left out of the release package.

## Contributing

See: [CONTRIBUTING.md](CONTRIBUTING.md)

## Credits

Upstream maintainer: Albert Gasset <albertgasset@fsfe.org>

This repository is a fork. Report issues with **this** fork at
https://github.com/uaiblaine/moodle-message_localmail/issues — please do not send
them to the upstream maintainer.

Implemented by the "Recovery, Transformation and Resilience Plan". Funded by the European Union - Next Generation EU. Produced by the UNIMOODLE University Group: Universities of Valladolid, Complutense de Madrid, UPV/EHU, León, Salamanca, Illes Balears, València, Rey Juan Carlos, La Laguna, Zaragoza, Málaga, Córdoba, Extremadura, Vigo, Las Palmas de Gran Canaria and Burgos.

## Copyright

© 2024 Proyecto UNIMOODLE <direccion.area.estrategia.digital@uva.es>
© 2025 Albert Gasset <albertgasset@fsfe.org>

## License

This plugin is distributed under the terms of the GNU General Public License,
version 3 or later.

See the [LICENSE](LICENSE) file for details; the same text ships as `COPYING.txt`, and
upstream's REUSE copy as [LICENSES/GPL-3.0-or-later.txt](LICENSES/GPL-3.0-or-later.txt).
