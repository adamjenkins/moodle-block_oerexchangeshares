# Release notes — 1.0.4

- The Japanese language pack (lang/ja) is no longer included: releases ship the English strings
  only, as the Moodle Plugins directory expects. Japanese is provided through Moodle's language
  packs.
- Installing with Composer no longer caps the Moodle version: `composer.json` now requires
  `moodle/moodle` `^5.0` (was `>=5.0 <5.4`), so later Moodle 5.x releases are not excluded.
- Continuous integration now tests against the released Moodle 5.3 (`MOODLE_503_STABLE`) instead of
  Moodle's development branch.

No database or capability changes. No action is required after upgrading.
