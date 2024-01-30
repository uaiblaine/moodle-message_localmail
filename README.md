# Local Mail notification plugin for Moodle

This plugin allows using the [Local Mail plugin](https://moodle.org/plugins/local_mail) as a message consumer.

Currently, due to limitations of Local Mail, it only processes notifications that meet the following conditions:
- Course is not the front page.
- Sender and recipient are real users.
- Sender and recipient are not the same user.

By default, all notification preferences are disabled and locked. They need to be enabled at the site administration.

## Installation

Unpack archive inside `/path/to/moodle/message/output/localmail`

For general instructions on installing plugins see:
https://docs.moodle.org/401/en/Installing_plugins

## Contributing

See: [CONTRIBUTING.md](CONTRIBUTING.md)

## Credits

Maintainer: Albert Gasset <albertgasset@fsfe.org>

Implemented by the "Recovery, Transformation and Resilience Plan". Funded by the European Union - Next Generation EU. Produced by the UNIMOODLE University Group: Universities of Valladolid, Complutense de Madrid, UPV/EHU, León, Salamanca, Illes Balears, València, Rey Juan Carlos, La Laguna, Zaragoza, Málaga, Córdoba, Extremadura, Vigo, Las Palmas de Gran Canaria and Burgos.

## Copyright

© 2024 Proyecto UNIMOODLE <direccion.area.estrategia.digital@uva.es>

## License

This plugin is distributed under the terms of the GNU General Public License,
version 3 or later.

See the [LICENSES/GPL-3.0-or-later.txt](LICENSES/GPL-3.0-or-later.txt) file for details.
