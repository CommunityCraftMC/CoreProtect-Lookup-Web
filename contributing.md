# Contributing

Thank you for your interest in improving the CommunityCraft rework of
CoreProtect Lookup Web Interface. This project maintains a practical web
interface for CoreProtect database lookups, with current compatibility focused
on CoreProtect 23.2.

Contributions should preserve the reliability of live lookup operations,
protect server data, and keep the project maintainable for administrators who
deploy it in production environments.

## Project Scope

Accepted contributions should generally support one of these goals:

- Improve compatibility with CoreProtect 23.2 and newer database layouts.
- Fix lookup correctness, performance, or reliability issues.
- Improve metadata decoding or display without requiring Java at runtime.
- Improve documentation for installation, operation, or troubleshooting.
- Improve tests, query safety, or maintainability.

Changes outside this scope may still be considered, but should include a clear
business or operational benefit.

## Security And Access Control

This rework does not include built-in authentication. Production deployments
are expected to use external protection such as a reverse proxy, VPN, IP
allowlist, or platform-level access control.

Contributions must not add secrets, credentials, live database exports, or
private server details to the repository. Use placeholders in examples and keep
all test fixtures free of sensitive data.

Database access should remain lookup-only. Features that require write access
must be discussed before implementation.

## Development Standards

Follow the existing project style unless a change explicitly improves
maintainability:

- Use spaces, not tabs.
- Use four-space indentation for PHP and JavaScript.
- Keep HTML indentation readable and consistent with nearby markup.
- Use clear names for classes, methods, variables, and tests.
- Keep changes focused; avoid unrelated refactors in the same pull request.
- Prefer safe DOM APIs such as `textContent` for database or user-provided
  content.
- Keep SQL parameterized. Do not introduce string-built user input into SQL.
- Add comments only where they clarify non-obvious behavior.

## Testing Requirements

All behavior changes should include tests where practical. At minimum, run the
available PHP syntax checks and test files before opening a pull request.

Recommended verification command set:

```sh
php -l index.php
php -l lookup.php
php -l res/php/StatementPreparer.class.php
php -l res/php/CoreProtectMetadataDecoder.class.php
php -l res/php/QuerySafety.class.php

php tests/auth_removed_tests.php
php tests/action_definitions_tests.php
php tests/frontend_action_controls_tests.php
php tests/frontend_rendering_tests.php
php tests/query_builder_tests.php
php tests/pdo_statement_executor_tests.php
php tests/schema_capabilities_tests.php
php tests/lookup_request_tests.php
php tests/lookup_row_normalizer_tests.php
php tests/metadata_decoder_tests.php
php tests/query_safety_tests.php
```

If `php` is not available on your PATH, use the PHP executable installed on your
development machine or update your PATH before running the commands.

## Pull Request Process

Before submitting a pull request:

1. Create a focused branch from `main`.
2. Describe the problem or business need being addressed.
3. Keep the change set limited to that purpose.
4. Update documentation when setup, operation, supported behavior, or security
   expectations change.
5. Add or update tests for new behavior.
6. Run the verification commands and include the result in the pull request.

Pull requests should include:

- Summary of the change.
- Reason for the change.
- Testing performed.
- Any deployment or configuration impact.
- Any remaining risks or follow-up work.

## Issue Reporting

When reporting a bug, include:

- CoreProtect version.
- Database type and version, for example MariaDB or SQLite.
- Relevant table prefix, for example `co_`.
- Lookup action used.
- Filters used, with sensitive values removed.
- Expected behavior.
- Actual behavior.
- Browser-visible error and, if available, the JSON response from `lookup.php`.

Do not post database credentials, private hostnames, player personal data, or
large raw database exports in public issues.

## Release Expectations

Release work should keep the public-facing version aligned with supported
CoreProtect compatibility. For the current CommunityCraft release line, use the
CoreProtect compatibility version, for example `v23.2`, rather than the
original upstream pre-release numbering.

Release notes should clearly state:

- Supported CoreProtect version.
- Important new lookup behavior.
- Security or access-control notes.
- Configuration changes.
- Known limitations.

## License And Attribution

This project preserves the original CoreProtect Lookup Web Interface attribution
to Simon Chuu / chuu.sh. CommunityCraft rework contributions should retain that
credit while documenting new CommunityCraft ownership and maintenance where
appropriate.

By contributing, you agree that your contribution can be distributed under the
project license.
