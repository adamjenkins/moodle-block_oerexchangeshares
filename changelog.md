# Changelog

All notable changes to this project are documented in this file, in
[Keep a Changelog](https://keepachangelog.com/) format.

## [1.0.0] - 2026-07-29

First stable release. `$plugin->maturity` is now `MATURITY_STABLE`.

No functional change since 0.1.2 — the whole OER Exchange suite moves to 1.0.0
together, so a site never has a stable plugin depending on an alpha one.

## [0.1.2] - 2026-07-27

### Added

- Each of your shared resources leads with its cover-image thumbnail
  (`local_oerexchange\local\cover_image::listitem()`), with a neutral
  equally sized panel where a resource has no cover so rows stay aligned.

### Changed

- Rows are laid out thumbnail-left, text-right. The thumbnail links to the
  same resource page as the title but is hidden from assistive technology,
  so it widens the click target without announcing the same destination
  twice.
- Thumbnail URLs for the whole block are resolved in one batch query.

## [0.1.1] - 2026-07-27

### Fixed

- Every share links to its detail page regardless of status. The old
  behaviour (non-published titles unlinked) assumed the detail page 404s
  for the creator; in fact `resource.php` admits a resource's own creator
  for every status, and that page carries the author's own controls.
- `modhidden` (moderator takedown) now renders as a translated label —
  previously the raw machine token appeared, untranslated, in exactly the
  case where the author most needs a comprehensible status.
- Japanese pack: added the missing `status_pending` label.
- Version counts exclude `failed` uploads, which never became a servable
  version.
- `$plugin->requires` corrected from Moodle 4.5 to 5.0 (2025041400).

### Changed

- Dropped the unjustified `RISK_SPAM | RISK_XSS` bitmask on `addinstance`.
- Titles pass an explicit system context to `format_string()`.

### Added

- PHPUnit: all-statuses-linked, translated-labels, escaping (titles and
  unknown statuses), footer share link, and failed-upload count tests;
  `@covers` migrated to attributes. Behat asserts the footer link.

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
