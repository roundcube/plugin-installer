<?php
namespace RoundCube\Composer;

use \Composer\Installer\LibraryInstaller;
use \Composer\Package\PackageInterface;
use \Composer\Downloader\DownloadManager;
use \Composer\IO\IOInterface;
use \Composer\Repository\InstalledRepositoryInterface;
use \Composer\Util\Svn as SvnUtil;

/**
 * @category Plugins
 * @package  PluginInstaller
 * @author   Till Klampaeckel <till@php.net>
 * @license  New BSD Licnese
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
}
