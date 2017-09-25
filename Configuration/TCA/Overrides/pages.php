<?php
$GLOBALS['TCA']['pages']['columns'] += array(
    'tx_realurl_pathsegment' => array(
        'label' => 'LLL:EXT:realurl/Resources/Private/Language/locallang_db.xml:pages.tx_realurl_pathsegment',
        'displayCond' => 'FIELD:tx_realurl_exclude:!=:1',
        'exclude' => 1,
        'config' => array(
            'type' => 'input',
            'max' => 255,
            'eval' => 'trim,nospace,lower'
        ),
    ),
    'tx_realurl_pathoverride' => array(
        'label' => 'LLL:EXT:realurl/Resources/Private/Language/locallang_db.xml:pages.tx_realurl_path_override',
        'displayCond' => 'FIELD:tx_realurl_exclude:!=:1',
        'exclude' => 1,
        'config' => array(
            'type' => 'check',
            'items' => array(
                array('', '')
            )
        )
    ),
    'tx_realurl_exclude' => array(
        'label' => 'LLL:EXT:realurl/Resources/Private/Language/locallang_db.xml:pages.tx_realurl_exclude',
        'exclude' => 1,
        'config' => array(
            'type' => 'check',
            'items' => array(
                array('', '')
            )
        )
    ),
    'tx_realurl_nocache' => array(
        'label' => 'LLL:EXT:realurl/Resources/Private/Language/locallang_db.xml:pages.tx_realurl_nocache',
        'exclude' => 1,
        'config' => array(
            'type' => 'check',
            'items' => array(
                array('', ''),
            ),
        ),
    )
);

$GLOBALS['TCA']['pages']['ctrl']['requestUpdate'] .= ',tx_realurl_exclude';

$GLOBALS['TCA']['pages']['palettes']['realurl'] = array(
    'showitem' => '
        tx_realurl_pathsegment,
        --linebreak--,
        tx_realurl_pathoverride,
        --linebreak--,
        tx_realurl_exclude
    '
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', '--palette--;LLL:EXT:realurl/Resources/Private/Language/locallang_db.xml:pages.palette.realurl;realurl', '1,5,4,199,254', 'after:title');

$GLOBALS['TCA']['pages_language_overlay']['columns'] += array(
    'tx_realurl_pathsegment' => array(
        'label' => 'LLL:EXT:realurl/Resources/Private/Language/locallang_db.xml:pages.tx_realurl_pathsegment',
        'exclude' => 1,
        'config' => array(
            'type' => 'input',
            'max' => 255,
            'eval' => 'trim,nospace,lower'
        ),
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages_language_overlay', 'tx_realurl_pathsegment', '', 'after:nav_title');
