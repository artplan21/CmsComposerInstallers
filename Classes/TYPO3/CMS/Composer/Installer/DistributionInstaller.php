<?php
namespace TYPO3\CMS\Composer\Installer;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Christian Opitz <christian.opitz at netresearch.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * TYPO3 Core installer
 *
 * @author Christian Opitz <christian.opitz at netresearch.de>
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 * @author Stephan Jorek <stephan.jorek@artplan21.de>
 */
class DistributionInstaller implements \Composer\Installer\InstallerInterface {

	protected $symlinks = array();

	/**
	 * @var \Composer\IO\IOInterface
	 */
	protected $io;

	/**
	 * @var \Composer\Composer
	 */
	protected $composer;

	/**
	 * @var \Composer\Downloader\DownloadManager
	 */
	protected $downloadManager;

	/**
	 * @var Util\Filesystem
	 */
	protected $filesystem;

	/**
	 * @var array
	 */
	public static $extraInstallerPathFilter = array(
		'type:typo3-cms-distribution',
		'name:typo3-cms-distribution',
		'type:typo3-cms-extension',
		'type:typo3-cms-core',
		'typo3/cms'
	);

	/**
	 * @param \Composer\Composer $composer
	 * @param Util\Filesystem $filesystem
	 */
	public function __construct(\Composer\IO\IOInterface $io, \Composer\Composer $composer, Util\Filesystem $filesystem) {

		$this->io = $io;
		$this->composer = $composer;
		$this->downloadManager = $composer->getDownloadManager();
		$this->filesystem = $filesystem;
		$this->symlinks = array(
			CoreInstaller::TYPO3_SRC_DIR . DIRECTORY_SEPARATOR . CoreInstaller::TYPO3_INDEX_PHP
				=> CoreInstaller::TYPO3_INDEX_PHP,
			CoreInstaller::TYPO3_SRC_DIR . DIRECTORY_SEPARATOR . CoreInstaller::TYPO3_DIR
				=> CoreInstaller::TYPO3_DIR
		);
	}

	/**
	 * Returns if this installer can install that package type
	 *
	 * @param string $packageType
	 * @return boolean
	 */
	public function supports($packageType) {
		return $packageType === 'typo3-cms-distribution';
	}

	/**
	 * Checks that provided package is installed.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param \Composer\Package\PackageInterface $package package instance
	 *
	 * @return bool
	 */
	public function isInstalled(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $package) {
		$this->log(__METHOD__ . $package->getName());
		return $repo->hasPackage($package)
			&& is_readable($this->getInstallPath($package))
			&& $this->filesystem->allFilesExist($this->getSymlinks($package));
	}

	/**
	 * Installs specific package.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param \Composer\Package\PackageInterface $package package instance
	 */
	public function install(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $package) {
		$this->log(__METHOD__ . $package->getName());

		$symlinks = $this->getSymlinks($package);

		if ($this->filesystem->someFilesExist($symlinks)) {
			$this->filesystem->removeSymlinks($symlinks);
		}

		$this->filesystem->establishSymlinks($symlinks);

		if (!$repo->hasPackage($package)) {
			$repo->addPackage(clone $package);
		}
	}

	/**
	 * Updates specific package.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param \Composer\Package\PackageInterface $initial already installed package version
	 * @param \Composer\Package\PackageInterface $target updated version
	 */
	public function update(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $initial, \Composer\Package\PackageInterface $target) {
		$this->log(__METHOD__ . $target->getName());

		$symlinks = $this->getSymlinks($initial);
		if ($this->filesystem->someFilesExist($symlinks)) {
			$this->filesystem->removeSymlinks($symlinks);
		}

		$symlinks = $this->getSymlinks($target);
		if ($this->filesystem->someFilesExist($symlinks)) {
			$this->filesystem->removeSymlinks($symlinks);
		}

		$this->filesystem->establishSymlinks($symlinks);

		$repo->removePackage($initial);
		if (!$repo->hasPackage($target)) {
			$repo->addPackage(clone $target);
		}
	}

	/**
	 * Uninstalls specific package.
	 *
	 * @param \Composer\Repository\InstalledRepositoryInterface $repo repository in which to check
	 * @param \Composer\Package\PackageInterface $package package instance
	 */
	public function uninstall(\Composer\Repository\InstalledRepositoryInterface $repo, \Composer\Package\PackageInterface $package) {
		$this->log(__METHOD__ . $package->getName());
		if (!$repo->hasPackage($package)) {
			throw new \InvalidArgumentException('Package is not installed: '.$package);
		}
		$symlinks = $this->getSymlinks($package);
		if ($this->filesystem->someFilesExist($symlinks)) {
			$this->filesystem->removeSymlinks($symlinks);
		}

		$repo->removePackage($package);
	}

	/**
	 * Returns the installation path of a package
	 *
	 * @param  \Composer\Package\PackageInterface $package
	 * @return string
	 */
	public function getInstallPath(\Composer\Package\PackageInterface $package) {
		$this->log(__METHOD__ . $package->getName());
		return Util\Composer::getExtraInstallerPath(
			array($package, $this->composer->getPackage()),
			self::$extraInstallerPathFilter
		);
	}

	/**
	 *
	 * @param \Composer\Package\PackageInterface $package
	 * @return array
	 */
	protected function getSymlinks(\Composer\Package\PackageInterface $package) {
		$packages = array($package, $this->composer->getPackage());
		$sourcePrefix = Util\Composer::getExtraInstallerPath($packages, CoreInstaller::$extraInstallerPathFilter);
		$targetPrefix = Util\Composer::getExtraInstallerPath($packages, self::$extraInstallerPathFilter);

		$symlinks = array();
		foreach($this->symlinks as $sourcePath => $targetPath) {
			$symlinks[$sourcePrefix . $sourcePath] = $targetPrefix . $targetPath;
		}
		return $symlinks;
	}

	/**
	 * @param string $message
	 * @return void
	 */
	protected function log($message) {
		if ($this->io->isVerbose()) {
			$this->io->write($message);
		}
	}
}

?>