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

## Repository

Submit your plugin or skin to [Packagist](https://packagist.org/).

## Installation

 * clone Roundcube
 * `cp composer.json-dist composer.json`
 * add your plugin in the `require` section of composer.json
 * `composer.phar install`

Read the whole story at [plugins.roundcube.net](http://plugins.roundcube.net/about).
