<?php

/*********************************************************************
* Extension configuration file for ext "realurl".
*
* Generated by ext 17-02-2014 12:13 UTC
*
* https://github.com/t3elmar/Ext
*********************************************************************/

$EM_CONF[$_EXTKEY] = array(
    'title' => 'RealURL: speaking paths for TYPO3',
    'description' => 'Creates nice looking URLs for TYPO3 pages: converts http://example.com/index.phpid=12345&L=2 to http://example.com/path/to/your/page/. Please, ask for free support in TYPO3 mailing lists or contact the maintainer for paid support.',
    'category' => 'fe',
    'shy' => 0,
    'dependencies' => '',
    'conflicts' => 'cooluri,simulatestatic',
    'priority' => '',
    'loadOrder' => '',
    'module' => '',
    'state' => 'alpha',
    'internal' => 0,
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => 'pages,sys_domain,pages_language_overlay,sys_template',
    'clearCacheOnLoad' => 1,
    'lockType' => '',
    'author' => 'Dmitry Dulepov',
    'author_email' => 'dmitry.dulepov@gmail.com',
    'author_company' => '',
    'CGLcompliance' => '',
    'CGLcompliance_note' => '',
    'version' => '2.1.2',
    '_md5_values_when_last_written' => '',
    'constraints' => array(
        'depends' => array(
            'php' => '5.5-7.1.999',
            'typo3' => '6.2.6-8.2.99',
        ),
        'conflicts' => array(
            'cooluri' => '',
            'simulatestatic' => '',
        ),
        'suggests' => array(
            'static_info_tables' => '2.0.2-',
        ),
    ),
);
