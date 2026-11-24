<?php

namespace Roundcube\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class RoundcubeInstaller implements PluginInterface
{
    private $extensions = [PluginInstaller::class, SkinInstaller::class];
    private $installers = [];

    #[\Override]
    public function activate(Composer $composer, IOInterface $io)
    {
        foreach ($this->extensions as $extension) {
            $installer = new $extension($io, $composer);
            $composer->getInstallationManager()->addInstaller($installer);
            $this->installers[] = $installer;
        }
    }

    #[\Override]
    public function deactivate(Composer $composer, IOInterface $io)
    {
        foreach ($this->installers as $installer) {
            $composer->getInstallationManager()->removeInstaller($installer);
        }
    }

    #[\Override]
    public function uninstall(Composer $composer, IOInterface $io) {}
}
