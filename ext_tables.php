<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
//	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('tools','txrealurlM1','',\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY).'mod1/');

	// Add Web>Info module:
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_info',
		'Tx\\Realurl\\Controller\\InfoModuleController',
		'',
		'LLL:EXT:realurl/Resources/Private/Language/locallang_db.xml:moduleFunction.tx_realurl_modfunc1',
		'function',
		'online'
	);
}

if (version_compare(TYPO3_branch, '6.1', '<')) {
	\TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA('pages');
}
$TCA['pages']['columns'] += array(
	'tx_realurl_pathsegment' => array(
		'label' => 'LLL:EXT:realurl/Resources/Private/Language/locallang_db.xml:pages.tx_realurl_pathsegment',
		'displayCond' => 'FIELD:tx_realurl_exclude:!=:1',
		'exclude' => 1,
		'config' => array (
			'type' => 'input',
			'max' => 255,
			'eval' => 'trim,nospace,lower'
		),
	),
	'tx_realurl_pathoverride' => array(
		'label' => 'LLL:EXT:realurl/Resources/Private/Language/locallang_db.xml:pages.tx_realurl_path_override',
		'exclude' => 1,
		'config' => array (
			'type' => 'check',
			'items' => array(
				array('', '')
			)
		)
	),
	'tx_realurl_exclude' => array(
		'label' => 'LLL:EXT:realurl/Resources/Private/Language/locallang_db.xml:pages.tx_realurl_exclude',
		'exclude' => 1,
		'config' => array (
			'type' => 'check',
			'items' => array(
				array('', '')
			)
		)
	),
	'tx_realurl_nocache' => array(
		'label' => 'LLL:EXT:realurl/Resources/Private/Language/locallang_db.xml:pages.tx_realurl_nocache',
		'exclude' => 1,
		'config' => array (
			'type' => 'check',
			'items' => array(
				array('', ''),
			),
		),
	)
);

$TCA['pages']['ctrl']['requestUpdate'] .= ',tx_realurl_exclude';

$TCA['pages']['palettes']['137'] = array(
	'showitem' => 'tx_realurl_pathoverride'
);

if (\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('4.3')) {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', '3', 'tx_realurl_nocache', 'after:cache_timeout');
}
if (\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('4.2')) {
	// For 4.2 or new add fields to advanced page only
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_realurl_pathsegment;;137;;,tx_realurl_exclude', '1', 'after:nav_title');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_realurl_pathsegment;;137;;,tx_realurl_exclude', '4,199,254', 'after:title');
}
else {
	// Put it for standard page
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_realurl_pathsegment;;137;;,tx_realurl_exclude', '2', 'after:nav_title');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_realurl_pathsegment;;137;;,tx_realurl_exclude', '1,5,4,199,254', 'after:title');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('pages','EXT:realurl/Resources/Private/Language/locallang_csh.xml');

$TCA['pages_language_overlay']['columns'] += array(
	'tx_realurl_pathsegment' => array(
		'label' => 'LLL:EXT:realurl/Resources/Private/Language/locallang_db.xml:pages.tx_realurl_pathsegment',
		'exclude' => 1,
		'config' => array (
			'type' => 'input',
			'max' => 255,
			'eval' => 'trim,nospace,lower'
		),
	),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages_language_overlay', 'tx_realurl_pathsegment', '', 'after:nav_title');

?>