<?php

declare(strict_types=1);

namespace Waldhacker\Oauth2Client\Backend\LoginProvider;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Controller\LoginController;
use TYPO3\CMS\Backend\LoginProvider\LoginProviderInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Fluid\View\FluidViewAdapter;
use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;

#[Autoconfigure(public: true)]
class Oauth2LoginProvider implements LoginProviderInterface
{
    public const PROVIDER_ID = '1616569531';

    public function __construct(
        private readonly Oauth2ProviderManager $oauth2ProviderManager,
        private readonly ExtensionConfiguration $extensionConfiguration
    ) {
    }

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     */
    public function render(PageRenderer $pageRenderer, LoginController $loginController): void
    {
        //$extensionConfiguration = $this->extensionConfiguration->get('oauth2_client');
        //
        //$view = $this->backendViewFactory->create()
        //$view->setLayoutRootPaths(array_merge(
        //    $view->getLayoutRootPaths(),
        //    ['EXT:oauth2_client/Resources/Private/Layouts/Backend/'],
        //    $extensionConfiguration['view']['layoutRootPaths'] ?? []
        //));
        //
        //$view->setTemplateRootPaths(array_merge(
        //    $view->getTemplateRootPaths(),
        //    ['EXT:oauth2_client/Resources/Private/Templates/Backend/'],
        //    $extensionConfiguration['view']['templateRootPaths'] ?? []
        //));
        //
        //$view->setPartialRootPaths(array_merge(
        //    $view->getPartialRootPaths(),
        //    ['EXT:oauth2_client/Resources/Private/Partials/Backend/'],
        //    $extensionConfiguration['view']['partialRootPaths'] ?? []
        //));
        //
        //$view->setTemplate($extensionConfiguration['view']['template'] ?? 'Oauth2LoginProvider');
        //
        //$view->assign('providers', $this->oauth2ProviderManager->getConfiguredBackendProviders());
    }

    public function modifyView(ServerRequestInterface $request, ViewInterface $view): string
    {
        if ($view instanceof FluidViewAdapter) {
            $templatePaths = $view->getRenderingContext()->getTemplatePaths();
            $templateRootPaths = $templatePaths->getTemplateRootPaths();
            $templateRootPaths[] = 'EXT:oauth2_client/Resources/Private/Templates/Backend/';
            $templatePaths->setTemplateRootPaths($templateRootPaths);
        }

        $view->assign('providers', $this->oauth2ProviderManager->getConfiguredBackendProviders());

        return 'Oauth2LoginProvider';
    }
}
