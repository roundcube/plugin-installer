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
class PluginInstaller extends ExtensionInstaller
{
    protected $composer_type = 'roundcube-plugin';

    public static function getPath()
    {
        $package_dir  = getcwd();
        $package_dir .= DIRECTORY_SEPARATOR . 'plugins';

        return $package_dir;
    }

    public function getVendorDir()
    {
        return PluginInstaller::getPath();
    }

    protected function confirmInstall($package_name)
    {
        $config = $this->composer->getConfig()->get('roundcube');

        if (is_bool($config['enable-plugin']) === true) {
            $answer = $config['enable-plugin'];
        }
        else {
            $answer = $this->io->askConfirmation("Do you want to activate the plugin $package_name? [N|y] ", false);
        }

        return $answer;
    }

    protected function getConfig($package_name, $config, $add )
    {
        $cur_config = !empty($config['plugins']) ? ((array) $config['plugins']) : array();
        $new_config = $cur_config;

        if ($add && !in_array($package_name, $new_config)) {
            $new_config[] = $package_name;
        }
        elseif (!$add && ($i = array_search($package_name, $new_config)) !== false) {
            unset($new_config[$i]);
        }

        if ($new_config != $cur_config) {
            $config_val = count($new_config) > 0 ? "array(\n\t'" . join("',\n\t'", $new_config) . "',\n);" : "array();";
            $result = array('plugins', $config_val);
        }
        else {
            $result = false;
        }

        return $result;
    }
}
