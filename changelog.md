# Changelog

All notable public release changes for the CommunityCraft CoreProtect Lookup
Web Interface are documented here.

## v23.2 - 2026-05-18

CommunityCraft release targeting CoreProtect **v23.2** database compatibility.

### Added

- CoreProtect v23.2 lookup support for sign, item, inventory, `+session`,
  `-session`, `+item`, and `-item` actions.
- Backend action metadata source used by both PHP-rendered controls and the
  JavaScript lookup request builder.
- Schema capability detection for v23.2 tables and columns, returning clear
  JSON errors when a database does not support a selected action.
- Normalized lookup rows with `source`, `actionGroup`, `actionLabel`,
  `targetType`, `rolledBack`, and structured `metadata`.
- Sign result display for text lines, face, waxed state, and color.
- Raw metadata display for `block.meta`, `block.blockdata`,
  `container.metadata`, and `item.data`.
- PHP-only CoreProtect metadata decoder for Java-serialized Bukkit item
  metadata summaries, including display name, lore, enchantments, item flags,
  public Bukkit values, and armor trim.
- Query safety controls for maximum result count, coordinate volume, and
  timeout handling.
- JSON timeout and oversized-query responses for large lookups instead of
  leaking PHP warning HTML into AJAX responses.
- Automated PHP test coverage for action definitions, removed authentication,
  frontend action rendering, lookup requests, row normalization, metadata
  decoding, PDO parameter binding, query building, query safety, and schema
  capabilities.

### Changed

- Reworked the lookup endpoint around request parsing, schema capabilities,
  statement execution, row normalization, and metadata decoding helpers.
- Updated frontend result rendering to understand modern CoreProtect action
  groups and metadata blocks.
- Preserved legacy CoreProtect lookup behavior for block, click, kill,
  container, chat, command, session, and username records where possible.
- Updated branding, footer links, and documentation for the CommunityCraft
  release while preserving credit for original CoLWI creator Simon Chuu /
  chuu.sh.
- Updated default safety settings in `config.php` for modern lookup limits and
  timeout behavior.

### Removed

- Built-in site authentication, login page, session handling, and account
  configuration. Deployments should protect access externally with a reverse
  proxy, web server controls, VPN, or private network.
- Private development paths, local machine references, and server-specific test
  fixture identifiers from the public release.
- IDE project metadata and other hidden development folders from the publish
  repository.

### Security

- Reduced XSS exposure in the main lookup render path by avoiding raw HTML
  assignment for database/user-controlled lookup content.
- Added JSON-safe error handling for fatal lookup timeout cases.
- Documented least-privilege database access and external access-control
  expectations.

## Legacy CoLWI History

Historical entries below are retained from the original project for context.

### v1.0.0-pre2 - 2020-05-18

- Extended support down to CoreProtect 2.12.0 for Minecraft 1.8.

### v1.0.0-pre1 - 2020-05-17

- First pre-release.
- Known limitations at the time:
  - Autocomplete not implemented.
  - Custom locale not available.
  - Login supported credentials only from `config.php`.
  - Sign data could not be loaded yet.

### v1.0.0-SNAPSHOT - 2020-05-06

- Restarted rewrite of the program.

### v0.9.3 - 2016-08-24

- Latest original version built on Bootstrap v4-alpha.

### 2015-09-11

- Initial GitHub commit.
