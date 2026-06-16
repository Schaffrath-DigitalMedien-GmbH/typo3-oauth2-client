<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addTCAcolumns('be_users', [
    'tx_oauth2_client_configs' => [
        'label' => 'LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:tx_oauth2_client_config',
        'exclude' => true,
        'config' => [
            'type' => 'inline',
            'renderType' => 'oauth2providers',
            'foreign_table' => 'tx_oauth2_beuser_provider_configuration',
            'foreign_field' => 'parentid',
        ],
    ],
]);
ExtensionManagementUtility::addToAllTCAtypes('be_users', 'tx_oauth2_client_configs', '', 'before:avatar');

ExtensionManagementUtility::addUserSetting(
    'tx_oauth2_client_configs',
    [
        'label' => 'LLL:EXT:oauth2_client/Resources/Private/Language/locallang_be.xlf:userSettings.label',
        'config' => [
            'type' => 'user',
            'renderType' => 'oauth2manageprovidersbutton',
        ],
    ],
    'after:mfaProviders'
);
