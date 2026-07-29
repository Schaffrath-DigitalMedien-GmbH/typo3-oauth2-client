<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title'            => 'OAuth2 Client',
    'description'      => 'TYPO3 OAuth2 Login Client (backend and frontend)',
    'category'         => 'auth',
    'author'           => 'Oliver Eglseder; waldhacker',
    'author_email'     => 'support@co-stack.com',
    'author_company'   => 'co-stack.com, Inh. Oliver Eglseder; waldhacker UG (haftungsbeschränkt)',
    'state'            => 'stable',
    'version'          => '5.0.0',
    'constraints'      => [
        'depends' => [
            'typo3' => '14.0.0-14.3.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Waldhacker\\Oauth2Client\\' => 'Classes',
        ],
    ],
];
