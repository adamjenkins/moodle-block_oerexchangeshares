# Changelog

All notable changes to this project are documented in this file, in
[Keep a Changelog](https://keepachangelog.com/) format.

## [0.1.0] - 2026-07-19

### Added

- `block_oerexchangeshares` Dashboard block: lists the logged-in account's
  own shared resources from `local_oerexchange_resources`, scoped to
  `creatorid`, most recent first, each linking to the catalogue's
  `resource.php` detail page.
- Publish status (published/hidden/removed) and per-resource version count
  (from `local_oerexchange_versions`) shown alongside each title.
- Friendly empty state when the account has shared nothing yet.
- Depends on `local_oerexchange` via `$plugin->dependencies` — installer
  enforces the parent plugin being present.
