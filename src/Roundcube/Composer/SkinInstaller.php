<?php

namespace Roundcube\Composer;

use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

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

    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::install($repo, $package);

        // copy any plugin skin files to the plugins
        $package_name = $this->getPackageName($package);
        $this->copyPluginSkins($package_name);
    }

    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        parent::update($repo, $initial, $target);

        // copy any plugin skin files to the plugins
        $package_name = $this->getPackageName($target);
        $this->copyPluginSkins($package_name);
    }

    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        // remove any plugin skin files from the plugins
        $package_name = $this->getPackageName($package);
        $this->copyPluginSkins($package_name, false);

        parent::uninstall($repo, $package);
    }

    public static function getPath()
    {
        $package_dir  = getcwd();
        $package_dir .= DIRECTORY_SEPARATOR . 'skins';

        return $package_dir;
    }

    public function getVendorDir()
    {
        return SkinInstaller::getPath();
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
        }
        elseif (!$add && $new_config == $package_name) {
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

    private function copyPluginSkins($package_name, $install = true)
    {
        $skins_dir  = $this->getVendorDir() . DIRECTORY_SEPARATOR . $package_name . DIRECTORY_SEPARATOR . 'plugins';
        $plugin_dir = PluginInstaller::getPath();

        if (file_exists($skins_dir)) {
            $dir = scandir($skins_dir);
            foreach ($dir as $plugin) {
                if ($plugin != '.' && $plugin != '..') {
                    $target = $plugin_dir . DIRECTORY_SEPARATOR . $plugin;
                    if (file_exists($target)) {
                        // the jqueryui plugin uses a custom folder name
                        $skin_dir = $plugin == 'jqueryui' ? 'themes' : 'skins';

                        $src = $skins_dir . DIRECTORY_SEPARATOR . $plugin;
                        $dst = $target . DIRECTORY_SEPARATOR . $skin_dir . DIRECTORY_SEPARATOR . $package_name;

                        if ($install) {
                            $this->recursiveCopy($src, $dst);
                        }
                        elseif (file_exists($dst)) {
                            $this->recursiveDelete($dst);
                        }
                    }
                }
            }
        }
    }

    private function recursiveCopy($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);

        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..') {
                if (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
                    $this->recursiveCopy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
                }
                else {
                    copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
                }
            }
        }

        closedir($dir);
    }

    private function recursiveDelete($path)
    {
        if (is_dir($path)) {
            $dir = opendir($path);

            while ($file = readdir($dir)) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($path . DIRECTORY_SEPARATOR . $file)) {
                        $this->recursiveDelete($path . DIRECTORY_SEPARATOR . $file);
                    }
                    else {
                        unlink($path . DIRECTORY_SEPARATOR . $file);
                    }
                }
            }

            closedir($dir);
            rmdir($path);
        }
    }
}
