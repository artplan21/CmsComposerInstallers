<?php
namespace TYPO3\CMS\Composer\Installer\Util;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Stephan Jorek <stephan.jorek@artplan21.de>
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
 * Utilities for composer
 */
final class Composer {

	/**
	 *
	 * @param array<\Composer\Package\PackageInterface> $packages
	 * @param array $filter
	 * @return string
	 */
	static public function getExtraInstallerPath(array $packages, array $filters) {
		foreach($packages as /** @var \Composer\Package\PackageInterface */ $package) {
			$extra = $package->getExtra();
			echo PHP_EOL; var_dump($extra); echo PHP_EOL;
			if (empty($extra) || !isset($extra['installer-paths']) || empty($extra['installer-paths'])) {
				continue ;
			}
			foreach($filters as $filter) {
				foreach($extra['installer-paths'] as $path => $targets) {
					if (in_array($filter, $targets)) {
						return $path;
					}
				}
			}
		}
		return '';
	}
}
