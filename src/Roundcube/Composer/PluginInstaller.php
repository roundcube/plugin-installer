<?php

namespace Roundcube\Composer;

use Composer\Installer\LibraryInstaller;
use Composer\Package\Version\VersionParser;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\ProcessExecutor;

/**
 * @category Plugins
 * @package  PluginInstaller
 * @author   Till Klampaeckel <till@php.net>
 * @author   Thomas Bruederli <thomas@roundcube.net>
 * @license  GPL-3.0+
 * @version  GIT: <git_id>
 * @link     http://github.com/roundcube/plugin-installer
 */
class PluginInstaller extends LibraryInstaller
{
    const PLUGIN_PACKAGE = 'roundcube-plugin';
    const SKIN_PACKAGE   = 'roundcube-skin';

    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        $package_type = $package->getType();
        $vendorDir    = $this->getVendorDir($package_type);

        return sprintf('%s/%s', $vendorDir, $this->getPackageName($package));
    }

    /**
     * {@inheritDoc}
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $this->rcubeVersionCheck($package);
        parent::install($repo, $package);

        // post-install: activate package in Roundcube config
        $config_file  = $this->rcubeConfigFile();
        $package_name = $this->getPackageName($package);
        $package_type = $package->getType();
        $package_dir  = $this->getVendorDir($package_type) . DIRECTORY_SEPARATOR . $package_name;

        $extra = $package->getExtra();

        if (is_writeable($config_file) && php_sapi_name() == 'cli') {
            $type   = $package_type == self::SKIN_PACKAGE ? 'skin' : 'plugin';
            $answer = $this->io->askConfirmation("Do you want to activate the $type $package_name? [N|y] ", false);
            if (true === $answer) {
                $this->rcubeAlterConfig($package_name, true, $package_type);
            }
        }

        // copy config.inc.php.dist -> config.inc.php
        if (is_file($package_dir . DIRECTORY_SEPARATOR . 'config.inc.php.dist') && !is_file($package_dir . DIRECTORY_SEPARATOR . 'config.inc.php') && is_writeable($package_dir)) {
            $this->io->write("<info>Creating package config file</info>");
            copy($package_dir . DIRECTORY_SEPARATOR . 'config.inc.php.dist', $package_dir . DIRECTORY_SEPARATOR . 'config.inc.php');
        }

        // initialize database schema
        if (!empty($extra['roundcube']['sql-dir'])) {
            if ($sqldir = realpath($package_dir . DIRECTORY_SEPARATOR . $extra['roundcube']['sql-dir'])) {
                $this->io->write("<info>Running database initialization script for $package_name</info>");
                system(getcwd() . "/vendor/bin/rcubeinitdb.sh --package=$package_name --dir=$sqldir");
            }
        }

        // run post-install script
        if (!empty($extra['roundcube']['post-install-script'])) {
            $this->rcubeRunScript($extra['roundcube']['post-install-script'], $package);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $this->rcubeVersionCheck($target);
        parent::update($repo, $initial, $target);

        $extra = $target->getExtra();

        // trigger updatedb.sh
        if (!empty($extra['roundcube']['sql-dir'])) {
            $package_name = $this->getPackageName($target);
            $package_type = $target->getType();
            $package_dir  = $this->getVendorDir($package_type) . DIRECTORY_SEPARATOR . $package_name;

            if ($sqldir = realpath($package_dir . DIRECTORY_SEPARATOR . $extra['roundcube']['sql-dir'])) {
                $this->io->write("<info>Updating database schema for $package_name</info>");
                system(getcwd() . "/bin/updatedb.sh --package=$package_name --dir=$sqldir", $res);
            }
        }

        // run post-update script
        if (!empty($extra['roundcube']['post-update-script'])) {
            $this->rcubeRunScript($extra['roundcube']['post-update-script'], $target);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::uninstall($repo, $package);

        // post-uninstall: deactivate package
        $package_name = $this->getPackageName($package);
        $package_type = $package->getType();
        $this->rcubeAlterConfig($package_name, false, $package_type);

        // run post-uninstall script
        $extra = $package->getExtra();
        if (!empty($extra['roundcube']['post-uninstall-script'])) {
            $this->rcubeRunScript($extra['roundcube']['post-uninstall-script'], $package);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return $packageType === self::PLUGIN_PACKAGE || $packageType === self::SKIN_PACKAGE;
    }

    /**
     * Setup vendor directory to one of these two:
     *  ./plugins
     *  ./skins
     *
     * @return string
     */
    public function getVendorDir($package_type)
    {
        $package_dir  = getcwd();
        $package_dir .= $package_type == self::SKIN_PACKAGE ? '/skins' : '/plugins';

        return $package_dir;
    }

    /**
     * Extract the (valid) package name from the package object
     */
    private function getPackageName(PackageInterface $package)
    {
        @list($vendor, $packageName) = explode('/', $package->getPrettyName());

        return strtr($packageName, '-', '_');
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
        } else {
            throw new \Exception("Unable to find a Roundcube installation in $rootdir");
        }

        $extra = $package->getExtra();

        if (!empty($extra['roundcube'])) {
            foreach (array('min-version' => '>=', 'max-version' => '<=') as $key => $operator) {
                if (!empty($extra['roundcube'][$key])) {
                    $version = $parser->normalize(str_replace('-git', '.999', $extra['roundcube'][$key]));
                    if (!self::versionCompare($rcubeVersion, $version, $operator)) {
                        throw new \Exception("Version check failed! " . $package->getName() . " requires Roundcube version $operator $version, $rcubeVersion was detected.");
                    }
                }
            }
        }
    }

    /**
     * Add or remove the given package to the Roundcube config.
     */
    private function rcubeAlterConfig($package_name, $add, $package_type)
    {
        $config_file = $this->rcubeConfigFile();
        @include($config_file);
        $success = false;
        $varname = '$config';

        if (empty($config) && !empty($rcmail_config)) {
            $config  = $rcmail_config;
            $varname = '$rcmail_config';
        }

        if (is_array($config) && is_writeable($config_file)) {
            $config_templ = @file_get_contents($config_file) ?: '';

            if ($package_type == self::SKIN_PACKAGE) {
                $cur_config = !empty($config['skin']) ? $config['skin'] : null;
                $new_config = $cur_config;

                if ($add && $new_config != $package_name) {
                    $new_config = $package_name;
                } elseif (!$add && $new_config == $package_name) {
                    $new_config = null;
                }

                $config_name = 'skin';
                $config_val = !empty($new_config) ? "'$new_config';" : null;
            }
            else {
                $cur_config = !empty($config['plugins']) ? ((array) $config['plugins']) : array();
                $new_config = $cur_config;

                if ($add && !in_array($package_name, $new_config)) {
                    $new_config[] = $package_name;
                } elseif (!$add && ($i = array_search($package_name, $new_config)) !== false) {
                    unset($new_config[$i]);
                }

                $config_name = 'plugins';
                $config_val = count($new_config) > 0 ? "array(\n\t'" . join("',\n\t'", $new_config) . "',\n);" : "array();";
            }

            if ($new_config != $cur_config) {
                $count = 0;

                if (empty($config_val)) {
                    $new_config = preg_replace(
                        "/(\\$varname\['$config_name'\])\s+=\s+(.+);/Uims",
                        "",
                        $config_templ, -1, $count);
                }
                else {
                    $new_config = preg_replace(
                        "/(\\$varname\['$config_name'\])\s+=\s+(.+);/Uims",
                        "\\1 = " . $config_val,
                        $config_templ, -1, $count);
                }

                // config option does not exist yet, add it...
                if (!$count) {
                    $var_txt    = "\n{$varname}['$config_name'] = $config_val\n";
                    $new_config = str_replace('?>', $var_txt . '?>', $config_templ, $count);

                    if (!$count) {
                        $new_config = $config_templ . $var_txt;
                    }
                }

                $success = file_put_contents($config_file, $new_config);
            }
        }

        if ($success && php_sapi_name() == 'cli') {
            $this->io->write("<info>Updated local config at $config_file</info>");
        }

        return $success;
    }

    /**
     * Helper method to get an absolute path to the local Roundcube config file
     */
    private function rcubeConfigFile()
    {
        return realpath(getcwd() . '/config/config.inc.php');
    }

    /**
     * Run the given script file
     */
    private function rcubeRunScript($script, PackageInterface $package)
    {
        $package_name = $this->getPackageName($package);
        $package_type = $package->getType();
        $package_dir  = $this->getVendorDir($package_type) . DIRECTORY_SEPARATOR . $package_name;

        // check for executable shell script
        if (($scriptfile = realpath($package_dir . DIRECTORY_SEPARATOR . $script)) && is_executable($scriptfile)) {
            $script = $scriptfile;
        }

        // run PHP script in Roundcube context
        if ($scriptfile && preg_match('/\.php$/', $scriptfile)) {
            $incdir = realpath(getcwd() . '/program/include');
            include_once($incdir . '/iniset.php');
            include($scriptfile);
        }
        // attempt to execute the given string as shell commands
        else {
            $process = new ProcessExecutor($this->io);
            $exitCode = $process->execute($script, null, $package_dir);
            if ($exitCode !== 0) {
                throw new \RuntimeException('Error executing script: '. $process->getErrorOutput(), $exitCode);
            }
        }
    }

    /**
     * version_compare() wrapper, originally from composer/semver
     */
    private static function versionCompare($a, $b, $operator, $compareBranches = false)
    {
        $aIsBranch = 'dev-' === substr($a, 0, 4);
        $bIsBranch = 'dev-' === substr($b, 0, 4);

        if ($aIsBranch && $bIsBranch) {
            return $operator === '==' && $a === $b;
        }

        // when branches are not comparable, we make sure dev branches never match anything
        if (!$compareBranches && ($aIsBranch || $bIsBranch)) {
            return false;
        }

        return version_compare($a, $b, $operator);
    }
}
