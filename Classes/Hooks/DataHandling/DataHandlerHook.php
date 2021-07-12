<?php
namespace Tx\Realurl\Hooks\DataHandling;

/***************************************************************
*  Copyright notice
*
*  (c) 2010 Dmitry Dulepov (dmitry@typo3.org)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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

use Tx\Realurl\Configuration\ConfigurationGenerator;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * TCEmain hook to update various caches when data is modified in TYPO3 Backend
 *
 * @author	Dmitry Dulepov <dmitry@typo3.org>
 */
class DataHandlerHook implements SingletonInterface
{

    /**
     * RealURL configuration for the current host
     *
     * @var array
     */
    protected $config;

    /**
     * Removes autoconfiguration file if table name is sys_domain
     *
     * @param string $tableName
     * @return void
     */
    protected function clearAutoConfiguration($tableName)
    {
        if ($tableName === 'sys_domain') {
            @unlink(PATH_site . ConfigurationGenerator::AUTOCONFIGURTION_FILE);
        }
    }

    /**
     * Clears RealURL caches if necessary
     *
     * @param string $command
     * @param string $tableName
     * @param int $recordId
     * @return void
     */
    protected function clearCaches($command, $tableName, $recordId)
    {
        if ($this->isTableForCache($tableName)) {
            if ($command === 'delete' || $command === 'move') {
                list($pageId, ) = $this->getPageData($tableName, $recordId);
                $this->fetchRealURLConfiguration($pageId);
                if ($command === 'delete') {
                    $this->clearPathCache($pageId);
                } else {
                    $this->expirePathCacheForAllLanguages($pageId);
                }
                $this->clearOtherCaches($pageId);
            }
        }
    }

    /**
     * Clears URL decode and encode caches for the given page
     *
     * @param $pageId
     * @return void
     */
    protected function clearOtherCaches($pageId)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_realurl_urldecodecache',
            'page_id=' . intval($pageId));
        /** @noinspection PhpUndefinedMethodInspection */
        $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_realurl_urlencodecache',
            'page_id=' . intval($pageId));
    }

    /**
     * Clears path cache for the given page id
     *
     * @param int $pageId
     * @return void
     */
    protected function clearPathCache($pageId)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_realurl_pathcache',
            'page_id=' . intval($pageId));
    }

    /**
     * Removes unique alias in case if the record is deleted from the table
     *
     * @param string $command
     * @param string $tableName
     * @param mixed $recordId
     * @return void
     */
    protected function clearUniqueAlias($command, $tableName, $recordId)
    {
        if ($command === 'delete') {
            /** @noinspection PhpUndefinedMethodInspection */
            $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_realurl_uniqalias',
                'tablename=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($tableName, 'tx_realurl_uniqalias') .
                ' AND value_id=' . intval($recordId));
        }
    }

    /**
     * Expires record in the path cache
     *
     * @param int $pageId
     * @param int $languageId
     * @return void
     */
    protected function expirePathCache($pageId, $languageId)
    {
        $expirationTime = $this->getExpirationTime();
        $pageIds = $this->getChildPages($pageId);
        $pageIds[] = intval($pageId);

        /** @noinspection PhpUndefinedMethodInspection */
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_realurl_pathcache',
            'page_id IN (' . implode(',', $pageIds) . ') AND language_id=' . intval($languageId) . ' AND expire=0',
            array(
                'expire' => $expirationTime
            ));
    }

    /**
     * Expires record in the path cache
     *
     * @param int $pageId
     * @return void
     */
    protected function expirePathCacheForAllLanguages($pageId)
    {
        $expirationTime = $this->getExpirationTime();
        $pageIds = $this->getChildPages($pageId);
        $pageIds[] = intval($pageId);

        /** @noinspection PhpUndefinedMethodInspection */
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_realurl_pathcache',
            'page_id IN (' . implode(',', $pageIds) . ') AND expire=0',
            array(
                'expire' => $expirationTime
            ));
    }

    /**
     * Fetches RealURl configuration for the given page
     *
     * @param int $pageId
     * @return void
     */
    protected function fetchRealURLConfiguration($pageId)
    {
        $rootLine = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($pageId);
        $rootPageId = $rootLine[1]['uid'];
        $this->config = array();
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl'] as $config) {
                if (is_array($config) && $config['pagePath']['rootpage_id'] == $rootPageId) {
                    $this->config = $config;
                    return;
                }
            }
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT'])) {
                $this->config = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['_DEFAULT'];
            }
        } else {
            \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('RealURL is not configured! Please, configure it or uninstall.', 'RealURL', 3);
        }
    }

    /**
     * Returns the IDs of all child pages of a given $pageID.
     *
     * @param $pageId integer Page ID to start searching
     * @return int[] Child pages
     */
    protected function getChildPages($pageId)
    {
        $children  = array();

        /** @var $tree \TYPO3\CMS\Backend\Tree\View\PageTreeView */
        $tree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\View\\PageTreeView');
        $tree->init('AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1));
        $tree->makeHTML = false;
        $tree->getTree($pageId, 99, '');

        foreach ($tree->tree as $data) {
            $children[] = intval($data['row']['uid']);
        }

        return $children;
    }

    /**
     * Obtains expiration time for the path cache records
     *
     * @return int
     */
    protected function getExpirationTime()
    {
        $timeOffset = (isset($this->config['pagePath']['expireDays']) ? $this->config['pagePath']['expireDays'] : 60) * 24 * 3600;
        $date = getdate(time() + $timeOffset);
        return mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']);
    }

    /**
     * Obtains real page id and language from the table name and passed id of the record in the table.
     *
     * @param $tableName
     * @param $id
     * @return array First member is page id, second is language
     */
    protected static function getPageData($tableName, $id)
    {
        if ($tableName === 'pages_language_overlay') {
            $result = self::getInfoFromOverlayPid($id);
        } else {
            $result = array($id, 0);
        }
        return $result;
    }

    /**
     * Retrieves field list to check for modification
     *
     * @param string $tableName
     * @return	array
     */
    protected function getFieldList($tableName)
    {
        if ($tableName === 'pages_language_overlay') {
            $fieldList = TX_REALURL_SEGTITLEFIELDLIST_PLO;
        } else {
            if (isset($this->config['pagePath']['segTitleFieldList'])) {
                $fieldList = $this->config['pagePath']['segTitleFieldList'];
            } else {
                $fieldList = TX_REALURL_SEGTITLEFIELDLIST_DEFAULT;
            }
        }
        $fieldList .= ',hidden';
        return array_unique(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $fieldList, true));
    }

    /**
     * Retrieves real page id given its overlay id
     *
     * @param	int		$pid	Page id
     * @return	array		Array with two members: real page uid and sys_language_uid
     */
    protected static function getInfoFromOverlayPid($pid)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        list($rec) = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('pid,sys_language_uid',
            'pages_language_overlay', 'uid=' . intval($pid));
        return array($rec['pid'], $rec['sys_language_uid']);
    }

    /**
     * Checks if the update table can affect cache entries
     *
     * @param string $tableName
     * @return bool
     */
    protected static function isTableForCache($tableName)
    {
        return ($tableName === 'pages' || $tableName === 'pages_language_overlay');
    }

    /**
     * @param array $arguments
     */
    public function flushCacheTables(array $arguments)
    {
        if (isset($arguments['cacheCmd'])) {
            $GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('tx_realurl_urlencodecache');
            $GLOBALS['TYPO3_DB']->exec_TRUNCATEquery('tx_realurl_urldecodecache');
        }
    }

    /**
     * A TCEMain hook to update caches when something happens to a page or
     * language overlay.
     *
     * @param string $command
     * @param string $tableName
     * @param mixed $recordId
     * @return void
     */
    public function processCmdmap_postProcess($command, $tableName, $recordId)
    {
        $this->clearCaches($command, $tableName, $recordId);
        $this->clearAutoConfiguration($tableName);
        $this->clearUniqueAlias($command, $tableName, $recordId);
    }

    /**
     * A TCEmain hook to expire old records and add new ones
     *
     * @param string $status 'new' (ignoring) or 'update'
     * @param string $tableName
     * @param int $recordId
     * @param array $databaseData
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     * @return void
     * @todo Expire unique alias cache: how to get the proper timeout value easily here?
     */
    public function processDatamap_afterDatabaseOperations($status, $tableName, $recordId, array $databaseData, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler)
    {
        $this->processContentUpdates($status, $tableName, $recordId, $databaseData, $dataHandler);
        $this->clearAutoConfiguration($tableName);
    }

    /**
     * Hook function for clearing page cache
     *
     * @param array $params Params for hook
     * @return void
     */
    public function clearPageRelatedUrlCaches($params)
    {
        $pageIdArray = $params['table'] === 'pages' ? array($params['uid']) : $params['pageIdArray'];
        if (is_array($pageIdArray) && count($pageIdArray) > 0) {
            /** @noinspection PhpUndefinedMethodInspection */
            $pageIdList = implode(',', $GLOBALS['TYPO3_DB']->cleanIntArray($pageIdArray));
            /** @noinspection PhpUndefinedMethodInspection */
            $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_realurl_urlencodecache', 'page_id IN (' . $pageIdList . ')');
            /** @noinspection PhpUndefinedMethodInspection */
            $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_realurl_urldecodecache', 'page_id IN (' . $pageIdList . ')');
            /** @noinspection PhpUndefinedMethodInspection */
            $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_realurl_pathcache', 'page_id IN (' . $pageIdList . ') AND expire>0 AND expire<=' . time());
        }
    }

    /**
     * Processes page and content changes in regard to RealURL caches.
     *
     * @param string $status
     * @param string $tableName
     * @param int $recordId
     * @param array $databaseData
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     * @return void
     * @todo Handle changes to tx_realurl_exclude recursively
     */
    protected function processContentUpdates($status, $tableName, $recordId, array $databaseData, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler)
    {
        if ($tableName !== 'pages' || $status === 'update') {
            if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($recordId)) {
                $recordId = intval($dataHandler->substNEWwithIDs[$recordId]);
            }
            list($pageId, $languageId) = static::getPageData($tableName, $recordId);
            $this->fetchRealURLConfiguration($pageId);
            if ($this->shouldFixCaches($tableName, $databaseData)) {
                if (isset($databaseData['alias'])) {
                    $this->expirePathCacheForAllLanguages($pageId);
                } else {
                    $this->expirePathCache($pageId, $languageId);
                }
                $this->clearOtherCaches($pageId);
            }
            $this->clearOtherCaches($pageId);
        }
    }

    /**
     * Checks if we need to fix caches
     *
     * @param string $tableName
     * @param array $databaseData
     * @return bool
     */
    protected function shouldFixCaches($tableName, array $databaseData)
    {
        $result = false;
        if (self::isTableForCache($tableName)) {
            $interestingFields = $this->getFieldList($tableName);
            $result = count(array_intersect($interestingFields, array_keys($databaseData))) > 0;
        }
        return $result;
    }
}
