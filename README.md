# Local Mail notification plugin for Moodle

[![ci](https://github.com/uaiblaine/moodle-message_localmail/actions/workflows/ci.yml/badge.svg)](https://github.com/uaiblaine/moodle-message_localmail/actions/workflows/ci.yml)

This plugin allows using the [Local Mail plugin](https://moodle.org/plugins/local_mail) as a message consumer.

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
| Moodle | 4.5 through 5.2 |
| Local Mail | v2.15 or later |

The plugin installs on Moodle 4.1 and later, but is only tested and supported on
the range above — which tracks what Local Mail itself supports, since that is the
real ceiling.

## Installation

Unpack archive inside `/path/to/moodle/message/output/localmail`
(`/path/to/moodle/public/message/output/localmail` on Moodle 5.1 and later, where
the web root moved into `public/`).

For general instructions on installing plugins see:
https://docs.moodle.org/en/Installing_plugins

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

See the [LICENSES/GPL-3.0-or-later.txt](LICENSES/GPL-3.0-or-later.txt) file for details.
