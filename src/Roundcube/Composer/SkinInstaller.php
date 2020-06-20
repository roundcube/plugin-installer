<?php

namespace Roundcube\Composer;

/**
 * @category Plugins
 * @package  PluginInstaller
 * @author   Philip Weir <roundcube@tehinterweb.co.uk>
 * @license  GPL-3.0+
 * @version  GIT: <git_id>
 * @link     http://github.com/roundcube/plugin-installer
 */
class SkinInstaller extends ExtensionInstaller
{
    protected $composer_type = 'roundcube-skin';

    public function getVendorDir($package_type)
    {
        $package_dir  = getcwd();
        $package_dir .= '/skins';

        return $package_dir;
    }

    protected function confirmInstall($package_name)
    {
        $config = $this->composer->getConfig()->get('roundcube');

        if (is_bool($config['enable-skin']) === true) {
            $answer = $config['enable-skin'];
        }
        else {
            $answer = $this->io->askConfirmation("Do you want to activate the skin $package_name? [N|y] ", false);
        }

        return $answer;
    }

    protected function getConfig($package_name, $config, $add)
    {
        $cur_config = !empty($config['skin']) ? $config['skin'] : null;
        $new_config = $cur_config;

        if ($add && $new_config != $package_name) {
            $new_config = $package_name;
        } elseif (!$add && $new_config == $package_name) {
            $new_config = null;
        }

        if ($new_config != $cur_config) {
            $config_val = !empty($new_config) ? "'$new_config';" : null;
            $result = array('skin', $config_val);
        }
        else {
            $result = false;
        }

        return $result;
    }
}
