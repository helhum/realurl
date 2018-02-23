<?php
defined('TYPO3_MODE') or die('Access denied.');

call_user_func(function ($extensionConfiguration) {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc']['tx_realurl'] = 'Tx\\Realurl\\Hooks\\UrlRewritingHook->encodeSpURL';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['typoLink_PostProc']['tx_realurl'] = 'Tx\\Realurl\\Hooks\\UrlRewritingHook->encodeSpURL_urlPrepend';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc']['tx_realurl'] = 'Tx\\Realurl\\Hooks\\UrlRewritingHook->decodeSpURL';

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['tx_realurl'] = 'Tx\\Realurl\\Hooks\\DataHandling\\DataHandlerHook->flushCacheTables';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearPageCacheEval']['tx_realurl'] = 'Tx\\Realurl\\Hooks\\DataHandling\\DataHandlerHook->clearPageRelatedUrlCaches';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tx_realurl'] = 'Tx\\Realurl\\Hooks\\DataHandling\\DataHandlerHook';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['tx_realurl'] = 'Tx\\Realurl\\Hooks\\DataHandling\\DataHandlerHook';

    $GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ',tx_realurl_pathsegment,tx_realurl_exclude,tx_realurl_pathoverride';
    $GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields'] .= ',tx_realurl_pathsegment';

    // Include configuration file
    $_realurl_conf = unserialize($extensionConfiguration);
    if (is_array($_realurl_conf)) {
        $_realurl_conf_file = trim($_realurl_conf['configFile']);
        if ($_realurl_conf_file && file_exists(PATH_site . $_realurl_conf_file)) {
            /** @noinspection PhpIncludeInspection */
            require_once PATH_site . $_realurl_conf_file;
        }
    }

    $_realurl_conf_file = PATH_site . \Tx\Realurl\Configuration\ConfigurationGenerator::AUTOCONFIGURTION_FILE;
    if ($_realurl_conf['enableAutoConf'] && !isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']) && file_exists($_realurl_conf_file)) {
        require_once $_realurl_conf_file;
    }

    define('TX_REALURL_SEGTITLEFIELDLIST_DEFAULT', 'tx_realurl_pathsegment,alias,nav_title,title,uid');
    define('TX_REALURL_SEGTITLEFIELDLIST_PLO', 'tx_realurl_pathsegment,nav_title,title,uid');
    if (!defined('TYPO3_db_host')) {
        define('TYPO3_db_host', isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host'])
              ? $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['Default']['host']
              : 'localhost');
    }
}, $_EXTCONF);
