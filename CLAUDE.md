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
**4.5 through 5.3** (`$plugin->requires = 2024100700`,
`$plugin->supported = [405, 503]`). CI is the moodle-an-hochschulen reusable
workflow, one job per supported branch in `.github/workflows/ci.yml` — **update
those jobs when `supported` changes**. This repo is mounted into m405, m501, m502
and m503 at `message/output/localmail` (see `~/dev/moodle-dev/plugins.conf`).

## Agent orchestration budget (fleet rule, repeated here on purpose)

Section 6 of `~/dev/CLAUDE.md` (`moodle-dev/CLAUDE.fleet.md`) is the authority and says why.
This short copy reaches sessions that do not load that file: cloud sessions and checkouts
outside `~/dev`. Every subagent gets the model and effort of its role from its agent
definition, and none runs on the session model.

| Role | model | effort | agent |
|---|---|---|---|
| Mechanical sweeps, greps, renames, counts, log reading | `haiku` | `medium` | `fleet-sweeper` |
| Checklists against evidence (handoff counts, spec lines against a sweep log, lang lockstep) | `haiku` | `high` | `fleet-checker` |
| Readers, measurers, graders | `sonnet` | `medium` | `fleet-reader` |
| Refuters and verifiers of a blocking finding | `sonnet` | `high` | `fleet-verifier` |
| Well-scoped implementation (established cause, settled design, written recipe) | `sonnet` | `medium` | `fleet-fixer` |
| Non-trivial implementation (open design, several files, long tasks) | `opus` | `high` | `fleet-implementer` |
| Consolidators, critics, estimators, ADR and documentation drafters | `opus` | `high` | `fleet-synthesist` |

- Launch the `Agent` tool with `subagent_type: "fleet-*"`; it has no `effort` parameter, so
  the role's effort comes from that definition (`mdl claude-setup` installs them). Where they
  are not installed, pass `model`. Aliases only; never `fable`; `xhigh` only for a long-horizon
  implementer whose prompt says why; never `xhigh`/`max` on Sonnet or Haiku.
- No long command inside a subagent: `mdl ci --matrix`, `mdl mutate` and Behat run from the
  main session in a background Bash command; a subagent runs the fast gate its prompt names and
  reports the command with its counts.
- Workflows only on the user's opt-in, every `agent()` with `agentType: 'fleet-*'`, under 10
  agents. Advisor off by default.

## This is a fork — and upstream is a reference, not a constraint

Upstream is the UNIMOODLE / Albert Gasset plugin; commits up to `f47c561` are theirs. The
fork evolves on its own terms: **do not shape a design around whether upstream could take
it back.** The `upstream` remote is for reading what they add, not a target to stay
mergeable with. Adding settings, tables or dependencies upstream never had is fine.

The decisions below are **deliberate**; do not "align" them away.

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
  `supported = [405, 503]`; this plugin declares the same. Bumping this plugin to
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
- **Local Mail cannot represent a site-wide notice, and no setting changes that.**
  `enrol_get_all_users_courses()` ends its SQL with `WHERE c.id <> SITEID`
  (`lib/enrollib.php:1135`), *upstream of any capability check*, so granting
  `local/mail:usemail` on the front page achieves nothing and `can_use_mail(SITEID)` is
  false for every user including admins. `\local_mail\course::get(SITEID)` throws on top of
  that, and `message_search::get_base_sql()` scopes every unscoped listing to
  `course::get_by_user()` — so even a row written by force would be shown to nobody.
  Supporting system notices means changing local_mail across five layers (scope, search,
  navigation, capabilities, UI), not patching this plugin.
- **A user with no active enrolment has no mailbox at all**, not an empty one:
  `local_mail/view.php:77` gates the whole app and `lib.php:122` hides the navbar envelope.
  So a skipped delivery to such a user is not a degraded experience, it is the only
  possible one.
- **A recipient who cannot use mail in the course is skipped.** The predicate is the same
  pair the recipient picker uses (active enrolment + `local/mail:usemail`), so the plugin
  refuses exactly what the compose form refuses. This diverges from upstream, which wrote
  the row and let it be invisible.
- **Placeholder senders are substituted, not skipped.** Core sends many course-scoped
  notifications from noreply or support — course completion
  (`completion/completion_completion.php:191`), quiz submission confirmations
  (`mod/quiz/locallib.php:1291`), analytics insights. local_mail validates *nothing* about
  a sender (`message_data::new()` and `message::create()` check no enrolment and no
  capability), so the configured `systemsender` account stands in. Empty setting keeps the
  old skip behaviour, so upgrades change nothing until an admin opts in.
- **The Default notification preferences matrix advertises this processor for providers it
  cannot serve, and no plugin-side fix exists.** An administrator can tick Local Mail for a
  site-wide provider and get silence. Core computes a `supportsprocessor` flag
  (`message/renderer.php:156-160`) that the template
  (`default_notification_preferences.mustache:111`) uses to omit the toggle *entirely* — but
  the check is hardcoded to `$processor->name === 'sms'`, and the whole `message_output`
  contract (12 methods) is global to the processor, with nothing per-provider. The clean fix
  is to generalise that flag into a `message_output::supports_provider()` method —
  `core_message\helper::supports_sms_notifications()` already implements exactly the policy
  this plugin needs, returning false for `component === 'moodle'` and deferring to a component
  callback otherwise. **That is a core change and this fork does not patch core**, so the
  mitigation is the README note for administrators plus the `debugging()` on every skip.
  Recorded so nobody re-derives it; revisit only if core generalises the hook upstream.
- **Notification retention deliberately does NOT apply — do not "fix" this.** `message_output`
  offers `cleanup_all_notifications()` and `cleanup_read_notifications()`
  (`message/output/lib.php:131,142`) and this plugin overrides neither, on purpose: once
  delivered, the row is the user's *mail*, not a notification with a deadline. Purging is the
  user deleting it, or an administrative policy inside local_mail — which now exists, and this
  plugin's entire share of it is **one line**: `$data->component = $eventdata->component;` in
  `send_message()`. The tray, the retention policy, the scheduled task and the UI all live in
  local_mail. Two things to know about that line. It stores the **originating** component
  (`mod_forum`, `mod_assign`, `moodle`), never `message_localmail`: the mailbox does not care
  which transport delivered the mail, and naming the transport would say nothing the day a
  second one exists. And local_mail keeps the field **write-once** — absent from the record
  its `update()` builds — so a person replying to a delivered notification is writing their
  own mail, and nothing here has to undo the stamp afterwards.
- **`$plugin->dependencies['local_mail']` must not drift below 2026081302.** That is the
  version that added the `component` field to `message_data`. Against anything older the
  assignment above creates a PHP 8.2 dynamic property on a class that declares only typed
  ones — a deprecation that `phpunit --fail-on-warning` turns red in CI and a notice on every
  delivered notification in production. The pin is the mechanism that prevents installing the
  two plugins in the wrong order; do not relax it to make a build pass.
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
