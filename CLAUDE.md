# Claude instructions for `message_localmail`

This file is auto-loaded as context whenever Claude works in this plugin's
directory tree. **Fleet-wide standards live in `~/dev/CLAUDE.md`** (coding
style, CI gates, lang-string rules, the `mdl` environment, git rules) — do not
repeat them here. This file keeps only what is true for this plugin.

Plugin context: a Moodle **message output** plugin ("Local Mail") that delivers
core notifications into the `local_mail` plugin's per-course mailbox. It owns
**no database tables** — every row it produces is written through the Local Mail
API into that plugin's tables and file areas. It has no settings, no
capabilities, no web services, no templates and no JavaScript. Supports Moodle
**4.5 through 5.2** (`$plugin->requires = 2022112800`,
`$plugin->supported = [405, 502]`). CI is the moodle-an-hochschulen reusable
workflow, one job per supported branch in `.github/workflows/ci.yml` — **update
those jobs when `supported` changes**. This repo is mounted into m405, m501 and
m502 at `message/output/localmail` (see `~/dev/moodle-dev/plugins.conf`).

## This is a fork — documented divergences from the fleet standard

Upstream is the UNIMOODLE / Albert Gasset plugin; commits up to `f47c561` are
theirs. These divergences are **deliberate**; do not "align" them away.

- **File headers keep upstream `@copyright` and `@author`.** The fleet header is
  `@copyright 2026 Anderson Blaine` with no `@author`, but this is a GPL work and
  a mechanical sweep would erase attribution — a licensing problem, not a style
  one. Files this fork modifies gain an *additional* copyright line. Files this
  fork wrote from scratch (`classes/privacy/provider.php`, `lang/pt_br/`) carry
  only the fleet header, because UNIMOODLE did not write them.
- **No `moodle-release.yml`.** The moodle.org plugin entry belongs to upstream, so
  a `v*` tag firing the release workflow would try to publish this fork into a
  directory entry it does not own. Ship `ci.yml` only. Revisit if a separate
  moodle.org entry is ever registered.
- **`lang/ca`, `lang/es`, `lang/eu`, `lang/gl` are upstream's and stay.** The
  en ↔ pt_br lockstep rule applies to those two packs only; the four upstream
  packs will lag on new keys, which is harmless — Moodle merges each language
  over `en`, so a missing key falls back rather than rendering an identifier.
- **`LICENSES/` and `CONTRIBUTING.md` are upstream artefacts and ship in the
  release zip**, because `README.md` links to both. They are not `export-ignore`d.
- **`phpcs.xml` keeps `moodle-extra`**, which is upstream's opt-in and is strictly
  additive. Note it only affects a local `phpcs .` run: moodle-plugin-ci invokes
  phpcs with a hardcoded `--standard=moodle`, which suppresses ruleset
  auto-discovery, so CI never reads that file.
- **No `.stylelintrc.json`.** The plugin ships no CSS or JS, and config no linter
  reads is exactly the dead-config defect the audit found in the old `.phpcs.xml`.

## Commands

```sh
mdl ci moodle-message_localmail          # full CI locally before any push
mdl phpunit m502 message_localmail       # targeted tests
```

There is no `mdl grunt` step (no `amd/`) and no Behat suite.

## Code layout

```
message_output_localmail.php   The entire plugin: one message_output subclass.
db/install.php                 Inserts the message_processors row. No schema.
classes/privacy/provider.php   null_provider — no data of its own.
lang/{ca,en,es,eu,gl,pt_br}/   Two user-facing strings plus the privacy reason.
tests/                         PHPUnit only.
```

## Architecture gotchas

- **The supported ceiling is the dependency's, not core's.** `local_mail` declares
  `supported = [405, 502]`; this plugin declares the same. Bumping this plugin to
  a newer branch before `local_mail` gets there mounts it on a stack where its own
  dependency cannot install.
- **`$eventdata->courseid` can be `null`.** `\core\message\message` declares
  `private $courseid` with no default and core never validates or fills it, so
  `get_eventobject_for_processor()` hands over `null` for any provider that did not
  set it — the async backup notice, `lib/classes/task/failed_task_callbacks.php`,
  `tool_langimport`, and both `notification_helper` classes in `mod/assign` and
  `mod/quiz`. `null == SITEID` is **false**, and `\local_mail\course::get()` takes a
  non-nullable `int`, so an unnormalised `null` becomes a `TypeError` — which is not
  a `moodle_exception` and therefore escapes the `catch` in `message_send()`
  entirely. **Normalise with `(int)` before any guard.**
- **Nothing catches a processor's exceptions.** `\core\message\manager::call_processors()`
  has no try/catch, and `message_send()` only catches `\moodle_exception`. Anything
  that escapes this class kills its caller, which is usually an adhoc or scheduled
  task — and a throwing adhoc task is retried forever. Never let a `Throwable` out
  of `send_message()`.
- **Processors run in `name DESC` order** (`get_message_processors()`), which puts
  this plugin between `popup` and `email`. Throwing here means the user never gets
  the e-mail either.
- **`local_mail` returns deleted and guest users with their original, positive id**
  and a `deleted` flag set (`user::get_many()` builds
  `(object) ['id' => $id, 'deleted' => 1]` for anyone the query excluded). Testing
  `id <= 0` cannot see them; test `->deleted`.
- **A delegated transaction opened here must be rolled back explicitly.** If it
  throws, `message_send()` swallows the exception, so no handler calls
  `abort_all_db_transactions()` — the transaction stays on `$DB->transactions`,
  every later notification and event is buffered instead of delivered, and the
  shutdown rollback discards every write made after the failure.
- **Delivery needs a real `$USER`.** `\local_mail\message_data::new()` calls
  `file_get_unused_draft_itemid()`, which throws `noguest` for a guest or
  unauthenticated session, and the draft area belongs to whoever is running the
  request. `file_save_draft_area_files()` resolves the same `$USER` context on
  local_mail's side, so the write and the read agree — but neither works without
  one.
- **A recipient who cannot use mail in the course still gets a row.** The message is
  written but is invisible in their mailbox. This is upstream behaviour, documented
  in `README.md`; changing it to a skip is a product decision, not a bug fix.
- **CI tests against `local_mail`'s `main` branch, deliberately.** This plugin has
  been broken twice by Local Mail API changes (see `CHANGELOG.md` 1.1 and 1.2), so a
  red build caused by a *dependency* commit is the intended drift alarm, not a
  flake. A pinned tag would hide the third break until a site upgraded.

## Testing notes

- The fixture in `tests/message_output_test.php` **enrols both users**. An unenrolled
  recipient cannot use mail in the course, so a message delivered to them is written
  but invisible — every delivery assertion would pass while the recipient saw nothing.
- Build event data through `\core\message\message` +
  `get_eventobject_for_processor()`, never a hand-built `stdClass`. Only the real
  path reproduces properties arriving as `null`.
- Count with `$DB->count_records('local_mail_messages', ['courseid' => ...])` for
  negative assertions. A `message_search` bound to one user reports zero for a
  message delivered to somebody else, which makes the assertion vacuous.
- Every "nothing was delivered" test carries a control that *does* deliver, so it
  cannot pass by doing nothing.

## When in doubt

Follow the patterns in existing files. The codebase is internally
consistent — if a new file feels like it matches no existing shape,
re-examine the approach.
