# Legacy code — not used by the active plugin

This directory contains the old **PrivatePluginBase** bootstrap and related code. It is **not loaded** when the plugin runs.

## Active plugin entry and bootstrap

- **Entry point:** `aio-page-builder.php` (plugin root)
- **Bootstrap:** `AIOPageBuilder\Bootstrap\Plugin` (`src/Bootstrap/Plugin.php`)
- **Activation / deactivation:** `Plugin::activate()` and `Plugin::deactivate()` delegate to `AIOPageBuilder\Bootstrap\Lifecycle_Manager`

No file in this `legacy/` directory is required or autoloaded by the active plugin. Do not reference or require these files from production code.

## Contents of this directory

| Path | Purpose (legacy only) |
|------|------------------------|
| `PrivatePluginBase/Bootstrap.php` | Old bootstrap; wired activation, deactivation, init. |
| `PrivatePluginBase/Activation.php` | Old activation handler (capabilities, options). |
| `PrivatePluginBase/Deactivation.php` | Old deactivation (flush rewrite rules). |
| `PrivatePluginBase/Options.php` | Old options/version storage. |
| `PrivatePluginBase/Security/Capabilities.php` | Old capability constant; active plugin uses `AIOPageBuilder\Infrastructure\Config\Capabilities`. |
| `PrivatePluginBase/Rest/NamespaceController.php` | Old REST namespace; active plugin registers its own REST routes elsewhere. |
| `PrivatePluginBase/Admin/Menu.php` | Old admin menu; active plugin uses `AIOPageBuilder\Admin\Admin_Menu`. |
| `PrivatePluginBase/Admin/Settings/Page.php` | Old settings page config. |
| `PrivatePluginBase/Settings/Registrar.php` | Old settings API registration. |
| `PrivatePluginBase/Reporting/Service.php` | Old reporting scaffold. |
| `PrivatePluginBase/Diagnostics/Logger.php` | Old diagnostics logger. |

## Tests

`tests/BootstrapTest.php` exercises the legacy classes for regression only. It loads these files explicitly from `legacy/`; it does not run as part of the active plugin bootstrap.

## Removal

This directory may be removed in a future release once the legacy code is no longer needed for reference or tests.
