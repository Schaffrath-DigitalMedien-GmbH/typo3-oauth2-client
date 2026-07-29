<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

(static function (): void {
    ExtensionManagementUtility::addStaticFile('oauth2_client', 'Configuration/TypoScript', 'OAuth2 templates');
})();
