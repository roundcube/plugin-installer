<?php
namespace Roundcube\Composer;

use \Composer\Installer\LibraryInstaller;
use \Composer\Package\Version\VersionParser;
use \Composer\Package\LinkConstraint\VersionConstraint;
use \Composer\Package\PackageInterface;
use \Composer\Repository\InstalledRepositoryInterface;

/**
 * @category Plugins
 * @package  PluginInstaller
 * @author   Till Klampaeckel <till@php.net>
 * @author   Thomas Bruederli <thomas@roundcube.net>
 * @license  GPLv3+
 * @version  GIT: <git_id>
 * @link     http://github.com/roundcube/plugin-installer
 */
class PluginInstaller extends LibraryInstaller
{
    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        static $vendorDir;
        if (null === $vendorDir) {
            $vendorDir = $this->getVendorDir();
        }

        $name = $package->getName();
        list($vendor, $pluginName) = explode('/', $name);

        return sprintf('%s/%s', $vendorDir, $pluginName);
    }

    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $this->rcubeVersionCheck($package);
        parent::install($repo, $package);

        // post-install: activate plugin in Roundcube config
        $config_file = $this->rcubeConfigFile();

        if (is_writeable($config_file) && php_sapi_name() == 'cli') {
            @list($vendor, $plugin_name) = explode('/', $package->getPrettyName());
            echo "Do you want to activate the plugin $plugin_name? [N|y]\n";
            $answer = trim(fgets(STDIN));
            if (strtolower($answer) == 'y' || strtolower($answer) == 'yes') {
                $this->rcubeAlterConfig($plugin_name, true);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $this->rcubeVersionCheck($target);
        parent::update($repo, $initial, $target);
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::uninstall($repo, $package);

        // post-uninstall: deactivate plugin
        @list($vendor, $plugin_name) = explode('/', $package->getPrettyName());
        $this->rcubeAlterConfig($plugin_name, false);
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return 'roundcube-plugin' === $packageType;
    }

    /**
     * Setup vendor directory to one of these two:
     *  ./plugins
     *
     * @return string
     */
    public function getVendorDir()
    {
        $pluginDir  = getcwd();
        $pluginDir .= '/plugins';

        return $pluginDir;
    }

    /**
     * Check version requirements from the "extra" block of a package
     * against the local Roundcube version
     */
    private function rcubeVersionCheck($package)
    {
        $parser = new VersionParser;

        // read rcube version from iniset
        $rootdir = getcwd();
        $iniset = @file_get_contents($rootdir . '/program/include/iniset.php');
        if (preg_match('/define\(.RCMAIL_VERSION.,\s*.([0-9.]+[a-z-]*)?/', $iniset, $m)) {
            $rcubeVersion = $parser->normalize(str_replace('-git', '.999', $m[1]));
        }
        else {
            throw new \Exception("Unable to find a Roundcube installation in $rootdir");
        }

        $extra = $package->getExtra();

        if (!empty($extra['roundcube'])) {
            foreach (array('min-version' => '>=', 'max-version' => '<=') as $key => $operator) {
                if (!empty($extra['roundcube'][$key])) {
                    $version = $parser->normalize($extra['roundcube'][$key]);
                    $constraint = new VersionConstraint($version, $operator);
                    if (!$constraint->versionCompare($rcubeVersion, $version, $operator)) {
                        throw new \Exception("Version check failed! " . $package->getName() . " requires Roundcube version $operator $version, $rcubeVersion was detected.");
                    }
                }
            }
        }
    }

    /**
     * Add or remove the given plugin to the list of active plugins in the Roundcube config.
     */
    private function rcubeAlterConfig($plugin_name, $add)
    {
        $config_file = $this->rcubeConfigFile();
        @include($config_file);
        $success = false;

        if (is_array($rcmail_config) && is_writeable($config_file)) {
            $config_templ = @file_get_contents($config_file);
            $active_plugins = (array)$rcmail_config['plugins'];
            if ($add && !in_array($plugin_name, $active_plugins)) {
                $active_plugins[] = $plugin_name;
            }
            else if (!$add && ($i = array_search($plugin_name, $active_plugins)) !== false) {
                unset($active_plugins[$i]);
            }

            if ($active_plugins != $rcmail_config['plugins']) {
                $var_export = "array(\n\t'" . join("',\n\t'", $active_plugins) . "',\n);";
                $new_config = preg_replace(
                    '/(\$rcmail_config\[\'plugins\'\])\s+=\s+(.+);/Uimse',
                    "'\\1 = ' . \$var_export",
                    $config_templ);
                $success = file_put_contents($config_file, $new_config);
            }
        }

        if ($success && php_sapi_name() == 'cli') {
            echo "Updated local config at $config_file\n";
        }

        return $success;
    }

    /**
     * Helper method to get an absolute path to the local Roundcube config file
     */
    private function rcubeConfigFile()
    {
        return realpath(getcwd() . '/config/main.inc.php');
    }
}
