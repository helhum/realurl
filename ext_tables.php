<?php
defined('TYPO3_MODE') or die('Access denied.');

if (TYPO3_MODE === 'BE') {
    // Add Web>Info module:
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
        'web_info',
        'Tx\\Realurl\\View\\AdministrationModuleFunction',
        '',
        'LLL:EXT:realurl/Resources/Private/Language/locallang_db.xml:moduleFunction.tx_realurl_modfunc1',
        'function',
        'online'
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('pages', 'EXT:realurl/Resources/Private/Language/locallang_csh.xml');
}

