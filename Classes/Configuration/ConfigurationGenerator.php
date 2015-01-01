<?php
namespace Tx\Realurl\Configuration;

/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Dmitry Dulepov (dmitry@typo3.org)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Class for generating of automatic RealURL configuration
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 * @package realurl
 * @subpackage tx_realurl
 */
class ConfigurationGenerator {

	const AUTOCONFIGURTION_FILE = 'typo3conf/realurl_autoconf.php';

	/**
	 * @var \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected $databaseConnection;

	/**
	 * @var bool
	 */
	protected $hasStaticInfoTables;

	/**
	 * Generates configuration. Locks configuration file for exclusive access to avoid collisions. Will not be stabe on Windows.
	 *
	 * @return	void
	 */
	public function generateConfiguration() {
		$fileName = PATH_site . self::AUTOCONFIGURTION_FILE;

		$lockObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Locking\\Locker', $fileName, $GLOBALS['TYPO3_CONF_VARS']['SYS']['lockingMode']);
		/** @var \TYPO3\CMS\Core\Locking\Locker $lockObject */
		$lockObject->setEnableLogging(FALSE);
		$lockObject->acquireExclusiveLock();
		$fd = @fopen($fileName, 'a+');
		if ($fd) {
			// Check size
			fseek($fd, 0, SEEK_END);
			if (ftell($fd) == 0) {
				$this->doGenerateConfiguration($fd);
			}
			fclose($fd);
			\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($fileName);
		}
		$lockObject->release();
	}

	/**
	 * Performs actual generation.
	 *
	 * @param	resource		$fd	FIle descriptor to write to
	 * @return	void
	 */
	protected function doGenerateConfiguration(&$fd) {

		$this->databaseConnection = $GLOBALS['TYPO3_DB'];

		$this->hasStaticInfoTables = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables');

		$conf = array();
		$template = $this->getTemplate();

		// Find all domains
		$domains = $this->databaseConnection->exec_SELECTgetRows('pid,domainName,redirectTo', 'sys_domain', 'hidden=0',
				'', '', '', 'domainName');
		if (count($domains) == 0) {
			$conf['_DEFAULT'] = $template;
			$rows = $this->databaseConnection->exec_SELECTgetRows('uid', 'pages',
						'deleted=0 AND hidden=0 AND is_siteroot=1', '', '', '1');
			if (count($rows) > 0) {
				$conf['_DEFAULT']['pagePath']['rootpage_id'] = $rows[0]['uid'];
			}
		}
		else {
			foreach ($domains as $domain) {
				if ($domain['redirectTo'] != '') {
					// Redirects to another domain, see if we can make a shortcut
					$parts = parse_url($domain['redirectTo']);
					if (isset($domains[$parts['host']]) && ($domains['path'] == '/' || $domains['path'] == '')) {
						// Make a shortcut
						if ($conf[$parts['host']] != $domain['domainName']) {
							// Here if there were no redirect from this domain to source domain
							$conf[$domain['domainName']] = $parts['host'];
						}
						continue;
					}
				}
				// Make entry
				$conf[$domain['domainName']] = $template;
				$conf[$domain['domainName']]['pagePath']['rootpage_id'] = $domain['pid'];
			}
		}

		// Post process generated configuration
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/realurl/class.tx_realurl_autoconfgen.php']['postProcessConfiguration'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/realurl/class.tx_realurl_autoconfgen.php']['postProcessConfiguration'] as $userFunc) {
				$parameters = array(
					'config' => &$conf,
				);
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($userFunc, $parameters, $this);
			}
		}

		fwrite($fd, '<' . '?php' . LF . '$GLOBALS[\'TYPO3_CONF_VARS\'][\'EXTCONF\'][\'realurl\']=' .
			\TYPO3\CMS\Core\Utility\ArrayUtility::arrayExport($conf) . ';' . chr(10)
		);

	}

	/**
	 * Creates common configuration template.
	 *
	 * @return	array		Template
	 */
	protected function getTemplate() {
		$confTemplate = array(
			'init' => array(
				'enableCHashCache' => true,
				'appendMissingSlash' => 'ifNotFile,redirect',
				'adminJumpToBackend' => true,
				'enableUrlDecodeCache' => true,
				'enableUrlEncodeCache' => true,
				'emptyUrlReturnValue' => \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_PATH')
			),
			'pagePath' => array(
				'type' => 'user',
				'userFunc' => 'Tx\\Realurl\\UriGeneratorAndResolver->main',
				'spaceCharacter' => '-',
				'languageGetVar' => 'L',
			),
			'fileName' => array(
				'defaultToHTMLsuffixOnPrev' => 0,
				'acceptHTMLsuffix' => 1,
			)
		);

		// Add print feature if TemplaVoila is not loaded
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('templavoila')) {
			$confTemplate['fileName']['index']['print'] = array(
					'keyValues' => array(
						'type' => 98,
					)
				);
		}

		// Add respectSimulateStaticURLs if SimulateStatic is loaded
		if(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('simulatestatic')) {
			$confTemplate['init']['respectSimulateStaticURLs'] = true;
		}

		$this->addLanguages($confTemplate);

		// Add from extensions
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/realurl/class.tx_realurl_autoconfgen.php']['extensionConfiguration'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/realurl/class.tx_realurl_autoconfgen.php']['extensionConfiguration'] as $extKey => $userFunc) {
				$params = array(
					'config' => $confTemplate,
					'extKey' => $extKey
				);
				$var = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($userFunc, $params, $this);
				if ($var) {
					$confTemplate = $var;
				}
			}
		}

		return $confTemplate;
	}

	/**
	 * Adds languages to configuration
	 *
	 * @param	array		$conf	Configuration (passed as reference)
	 * @return	void
	 */
	protected function addLanguages(&$conf) {
		if ($this->hasStaticInfoTables) {
			$languages = $this->databaseConnection->exec_SELECTgetRows('t1.uid AS uid,t2.lg_iso_2 AS lg_iso_2', 'sys_language t1, static_languages t2', 't2.uid=t1.static_lang_isocode AND t1.hidden=0');
		}
		else {
			$languages = $this->databaseConnection->exec_SELECTgetRows('t1.uid AS uid,t1.uid AS lg_iso_2', 'sys_language t1', 't1.hidden=0');
		}
		if (count($languages) > 0) {
			$conf['preVars'] = array(
				0 => array(
					'GETvar' => 'L',
					'valueMap' => array(
					),
					'noMatch' => 'bypass'
				),
			);
			foreach ($languages as $lang) {
				$conf['preVars'][0]['valueMap'][strtolower($lang['lg_iso_2'])] = $lang['uid'];
			}
		}
	}
}
