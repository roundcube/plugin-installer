# Plugin Installer for RoundCube

This installer ensures that plugins end up in the correct directory:

 * `roundcube/plugins/plugin-name`

## Minimum setup

 * create a `composer.json` file in your plugin's repository
 * add the following contents

### sample composer.json for plugins

    {
        "name": "yourprefix/plugin-name",
        "license": "the license",
        "description": "tell the world what your plugin is good at",
        "type": "roundcube-plugin",
        "repositories": [
            {
                "type": "composer",
                "url": "http://plugins.roundcube.net"
            }
        ]
        "require": {
            "roundcube/plugin-installer": "*"
        },
        "minimum-stability": "dev-master"
    }

## Installation

 * clone RoundCube
 * `cp composer.json-dist composer.json`
 * add your plugin in `require`
 * `composer.phar install`
