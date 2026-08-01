# Release notes — 1.0.2

No change to how the plugin behaves. This release adds a regression test that
pins the block's resource-title rendering.

Titles in this block have always been passed through Moodle's text filters, so
a title written with the multilang filter has always collapsed to the language
the viewer is reading in rather than showing the raw markup — but nothing in
the test suite held that in place. The sibling Exchange blocks each had exactly
this bug and were fixed in their own releases; the new test makes sure the
block that was already correct cannot quietly regress into it. It also pins the
escaping, so a title containing `&` is escaped exactly once.

The installable plugin code is unchanged from 1.0.1 apart from the version
metadata.

No database changes; no action required after upgrading beyond the usual
`admin/cli/upgrade.php`.
