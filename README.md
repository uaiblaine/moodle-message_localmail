# Local Mail notification plugin for Moodle

[![ci](https://github.com/uaiblaine/moodle-message_localmail/actions/workflows/ci.yml/badge.svg)](https://github.com/uaiblaine/moodle-message_localmail/actions/workflows/ci.yml)

This plugin allows using the [Local Mail plugin](https://moodle.org/plugins/local_mail) as a message consumer.

Currently, due to limitations of Local Mail, it only processes notifications that meet the following conditions:
- The notification belongs to a course.
- Course is not the front page.
- Sender and recipient are real, non-deleted users.
- Sender and recipient are not the same user.

Two further limitations are worth knowing before enabling it:
- A message delivered to somebody who cannot use mail in that course is stored but
  is not visible in their mailbox.
- Because a notification is delivered as mail, a copy stays in the sender's Sent
  folder, in the same way as any message they send themselves.

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
