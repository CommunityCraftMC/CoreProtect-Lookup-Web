[CoreProtect Lookup Web Interface (CoLWI)](https://github.com/CommunityCraftMC/CoreProtect-Lookup-Web-Interface)
===============================================================================
*A flexible lookup web interface for CoreProtect 23.2*

![Gyazo](https://i.gyazo.com/df7a36e2799ef8f349afdafae44264b6.png)

**Version:** CommunityCraft rework for CoreProtect **23.2** (2026)

**Release tag:** `v23.2`

*[Changelog](changelog.md) | [Contributing](contributing.md)*

This is a _feature-packed_ web application that gives you the power to look up
anything CoreProtect is capable of logging in the most efficient way.
[CoreProtect, a Minecraft plugin,](https://www.spigotmc.org/resources/8631/)
is developed by Intellii, rework by CommunityCraft.

This CommunityCraft rework targets CoreProtect **v23.2** database compatibility
while keeping legacy CoreProtect 2 lookup behavior where possible. Original
CoLWI was first created by Simon Chuu / chuu.sh.

This web app is capable of looking up logged data as if doing it from the game.
Some filters are ported to this plugin, such as:

* Lookup by action
* Lookup by username
* Lookup by block name
* Lookup by time

In addition, this plugin makes it possible to:

* Lookup data by coordinates and world
* View more than four results per page
* Filter out rolled back data
* View sign lookup records
* View modern item and inventory lookup records
* View raw and decoded CoreProtect metadata summaries
* Search by keywords

## CommunityCraft Rework Highlights

This rework adds support for the CoreProtect v23.2 lookup surface:

* Legacy block, click, kill, container, chat, command, session, and username
  lookups.
* v23.2 sign, item, inventory, `+session`, `-session`, `+item`, and `-item`
  lookups.
* Schema capability detection so unsupported tables/columns return clear JSON
  errors instead of raw SQL errors.
* Normalized JSON rows with `source`, `actionGroup`, `actionLabel`,
  `targetType`, `rolledBack`, and `metadata`.
* Sign metadata display for text lines, face, waxed state, and color.
* Raw metadata display for `block.meta`, `block.blockdata`,
  `container.metadata`, and `item.data`.
* PHP-only metadata summaries for Java-serialized Bukkit item metadata. No Java
  runtime is required. The decoder extracts safe summaries such as display
  name, lore, enchantments, item flags, public Bukkit values, and armor trim.
* Query safety guards for oversized limits, oversized coordinate volumes, and
  timeout JSON errors.
* Built-in authentication has been removed. Protect this site with your web
  server, reverse proxy, VPN, or another external access-control layer.

# Setup

## Prerequisites

- A web server with **PHP 8.4** is used for current
  development verification.
    - Required extensions: PDO, plus PDO-SQLITE or PDO-MYSQL for the selected
      database type.
- A CoreProtect database used by **CoreProtect v23.2** or above.
    - If using SQLite in real-time, the web server must be on the same machine
      as the Minecraft server.

## CoreProtect v23.2 Database Support

The v23.2 lookup path expects these CoreProtect tables for full functionality:

* `co_block`
* `co_container`
* `co_item`
* `co_sign`
* `co_chat`
* `co_command`
* `co_session`
* `co_username_log`
* `co_user`
* `co_world`
* `co_material_map`
* `co_entity_map`
* optional `co_blockdata_map`

The table prefix is configurable in `config.php`; `co_` is the default.

The web interface detects schema capabilities per request and returns them in
`lookup.php` JSON metadata. If a database lacks a new v23 table required by a
selected action, the request returns a clear unsupported-schema error instead of
a raw SQL failure.

Detected optional columns and tables include:

* `co_item`
* `co_sign`
* `co_blockdata_map`
* `co_block.meta`
* `co_block.blockdata`
* `co_container.metadata`
* `co_item.data`
* chat/command coordinate columns

Minimum MySQL/MariaDB permissions for lookup-only use:

```sql
GRANT SELECT ON coreprotect_database.* TO 'lookup_user'@'web_host';
```

Use a least-privilege database user for production. The web interface does not
need write permissions for lookups.

## Download

- **Option 1:** `git clone`
    - This option makes it easier to update the web app.
    - Run the following command in somewhere on the web server.
```sh
git clone https://github.com/CommunityCraftMC/CoreProtect-Lookup-Web-Interface.git
```

- **Option 2:** Download
    - Download the
      [latest release `.zip` file](https://github.com/CommunityCraftMC/CoreProtect-Lookup-Web-Interface/releases/latest).
    - Extract the .zip file somewhere on the web server.

## Configuration

Edit all the necessary configuration from `config.php`.  All fields are
documented in the configuration file.

Example MySQL database entry:

```php
'server' => [
    'type'        => 'mysql',
    'host'        => 'localhost:3306',
    'database'    => 'minecraft',
    'username'    => 'lookup_user',
    'password'    => 'password',
    'flags'       => '',
    'prefix'      => 'co_',
    'preBlockName'=> true,
    'mapLink'     => '',
],
```

Important form safety settings:

```php
'form' => [
    'count' => 30,                  // default lookup limit
    'moreCount' => 10,              // default "load more" limit
    'max' => 300,                   // maximum accepted query limit
    'maxCoordinateVolume' => 5000000, // maximum x/y/z selection volume; 0 disables
    'timeoutSeconds' => 20,         // PHP and DB statement timeout hint; 0 disables
],
```

Oversized lookups return JSON errors such as `Query too large` or
`Query timed out`; they should not produce HTML PHP warning output in AJAX
responses.

## Security / Access Control

This CommunityCraft rework does not include built-in login/authentication.
Run it behind external protection when exposing it outside trusted networks.
Recommended options:

* reverse-proxy authentication
* VPN or private network only
* web-server IP allowlists
* platform-level SSO/access control

Use a read-only database account. Lookup-only access only needs `SELECT`.

Example SQLite database entry:

```php
'server' => [
    'type'        => 'sqlite',
    'path'        => '/path/to/database.db',
    'prefix'      => 'co_',
    'preBlockName'=> true,
    'mapLink'     => '',
],
```

## Local PHP 8.4 Run

If PHP is available on your PATH, start the local development server with:

```sh
php -v
php -S 127.0.0.1:18080
```

Open `http://127.0.0.1:18080/index.php`.

If `pdo_mysql` is not loaded, enable it in your PHP configuration before using
MySQL/MariaDB. You can confirm loaded modules with:

```sh
php -m
```

## Verification

Run the included syntax and behavior checks after changes:

```sh
php -l index.php
php -l lookup.php
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

## Updating

If you used the **option 1** to download the web app, you can run:
```sh
git stash
git pull
git stash pop
```

- `git stash` stashes uncommitted changes
- `git pull` downloads and updates the repository with the latest changes
- `git stash pop` applies the stashed changes into the repository.

If you see this message after running `git stash pop`:
```
CONFLICT (content): Merge conflict in config.php
```

then you must edit the file manually (look for `<<<<<<<`, `=======`, and
`>>>>>>>`) then run:
```sh
git add config.php
```

If you used the **option 2**, then you must re-download the `.zip` file and
manually migrate the `config.php` file over.

CommunityCraft