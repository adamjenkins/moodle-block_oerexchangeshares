# block_oerexchangeshares

A Moodle Dashboard block for the **OER Exchange** platform — an
open-educational-resources sharing platform built on Moodle. Shows the
logged-in Exchange account's own shared resources so they don't need to
navigate anywhere to see "what have I shared and how is it doing."

## What it does

- Lists your own shares from the catalogue, most recent first: the
  resource's cover-image thumbnail (a neutral panel of the same size where
  there is no cover), title, publish status, and how many versions have been
  uploaded (failed uploads don't count). Every title links to its detail page whatever its status — the
  catalogue admits a resource's own creator even for hidden or taken-down
  resources, and that page carries the author's own controls (unhide,
  replace the file, delete).
- Shows a friendly message instead of a blank block if you haven't shared
  anything yet, and links to sharing a new resource from its footer.
- Read-only presentation layer: all data comes from the companion
  [`local_oerexchange`](https://github.com/adamjenkins/moodle-local_oerexchange)
  plugin, which this block depends on and cannot be installed without.

## Requirements

- Moodle 5.0–5.3 (`$plugin->supported`).
- [`local_oerexchange`](https://github.com/adamjenkins/moodle-local_oerexchange)
  installed on the same site (declared via `$plugin->dependencies`).

## Installation

```bash
git clone https://github.com/adamjenkins/moodle-block_oerexchangeshares.git blocks/oerexchangeshares
php admin/cli/upgrade.php
```

Then add the "OER Exchange: your shares" block to your Dashboard via
"Add a block."

## License

GPL-3.0-or-later, see [LICENSE](LICENSE).
