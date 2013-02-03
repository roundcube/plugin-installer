<?php
namespace RoundCube\Composer;

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
     public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
     {
         $this->rcubeVersionCheck($package);
         parent::install($repo, $package);
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
}
