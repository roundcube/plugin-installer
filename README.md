# Plugin Installer for Roundcube

This installer ensures that plugins and skins end up in the correct directory:

 * Plugins - `<roundcube-root>/plugins/plugin-name`
 * Skins - `<roundcube-root>/Skins/skin-name`

## Minimum setup

 * create a `composer.json` file in your plugin's repository
 * add the following contents

### sample composer.json for plugins

    {
        "name": "<your-vendor-name>/<plugin-name>",
        "type": "roundcube-plugin",
        "license": "GPL-3.0+",
        "require": {
            "roundcube/plugin-installer": ">=0.1.6"
        }
    }

### sample composer.json for skins

    {
        "name": "<your-vendor-name>/<skin-name>",
        "type": "roundcube-skin",
        "license": "GPL-3.0+",
        "require": {
            "roundcube/plugin-installer": ">=0.1.11"
        }
    }

To include skins for plugins create a directory in your skin called `plugins` with a sub-directory for each plugin, e.g.:
```
plugins
    jaueryui
        jquery-ui.css
    managesieve
        templates
            managesieve.html
        managesieve.css
```
These files will be automatically copied into the relevant plugin directory (if the plugin exists)

## Roundcube specifc composer.json params

For both plugins and skins you can, optionally, add the following section to your `composer.json` file. All properties are optional and provided below with example values.

    "extra": {
        "roundcube": {
            "min-version": "1.4.0",
            "sql-dir": "./SQL",
            "post-install-script": "./bin/install.sh",
            "post-update-script": "./bin/update.sh"
        }
    }

## Configuration

This installer will ask if you want to enable each plugin or skin as it is installed. To always enable all plugins or skins add the following to the `composer.json` in the root of your Roundcube directory.

    "config": {
        "roundcube": {
            "enable-plugin": true,
            "enable-skin": true
        }
    }

## Repository

Submit your plugin or skin to [Packagist](https://packagist.org/).

## Installation

 * clone Roundcube
 * `cp composer.json-dist composer.json`
 * add your plugin in the `require` section of composer.json
 * `composer.phar install`

Read the whole story at [plugins.roundcube.net](http://plugins.roundcube.net/about).
