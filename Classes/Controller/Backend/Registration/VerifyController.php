<?php

declare(strict_types=1);

namespace Waldhacker\Oauth2Client\Controller\Backend\Registration;

use Doctrine\DBAL\Exception;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotCreatedException;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Waldhacker\Oauth2Client\Repository\BackendUserRepository;
use Waldhacker\Oauth2Client\Service\Oauth2ProviderManager;
use Waldhacker\Oauth2Client\Service\Oauth2Service;
use Waldhacker\Oauth2Client\Session\SessionManager;

readonly class VerifyController
{
    public function __construct(
        private Oauth2Service $oauth2Service,
        private BackendUserRepository $backendUserRepository,
        private SessionManager $sessionManager,
        private UriBuilder $uriBuilder,
        private ResponseFactoryInterface $responseFactory,
        private Oauth2ProviderManager $oauth2ProviderManager,
        private Context $context,
        private FlashMessageService $flashMessageService
    ) {}

    /**
     * @throws AspectNotFoundException
     * @throws AspectPropertyNotFoundException
     * @throws SessionNotCreatedException
     * @throws RouteNotFoundException
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $postParameters = is_array($request->getParsedBody()) ? $request->getParsedBody() : [];
        if ($postParameters === []) {
            return $this->redirectWithWarning($request);
        }
        $providerId = (string)($postParameters['oauth2-provider'] ?? '');
        $code = (string)($postParameters['oauth2-code'] ?? '');
        $state = (string)($postParameters['oauth2-state'] ?? '');
        /** @var UserAspect $backendUser */
        $backendUser = $this->context->getAspect('backend.user');

        if (
            !$backendUser->isLoggedIn()
            || ($providerId === '' || $providerId === '0')
            || ($code === '' || $code === '0')
            || ($state === '' || $state === '0')
            || !$this->oauth2ProviderManager->hasBackendProvider($providerId)
        ) {
            return $this->redirectWithWarning($request);
        }

        $callbackUrl = (string)$this->uriBuilder->buildUriFromRoute(
            'oauth2_registration_authorize',
            [
                'oauth2-provider' => $providerId,
                'action' => 'callback',
            ],
            UriBuilder::ABSOLUTE_URL
        );

        $provider = $this->oauth2Service->buildGetResourceOwnerProvider(
            $state,
            $providerId,
            $callbackUrl,
            $request
        );
        if (!$provider instanceof AbstractProvider) {
            return $this->redirectWithWarning($request);
        }
        $accessToken = $this->oauth2Service->buildGetResourceOwnerAccessToken(
            $provider,
            $code
        );
        if (!$accessToken instanceof AccessToken) {
            return $this->redirectWithWarning($request);
        }
        $remoteUser = $this->oauth2Service->getResourceOwner($provider, $accessToken);
        $userid = (int)$backendUser->get('id');

        if ($remoteUser instanceof ResourceOwnerInterface) {
            try {
                $this->backendUserRepository->persistIdentityForUser($providerId, (string)$remoteUser->getId(), $userid);
            } catch (Exception) {
                return $this->redirectWithWarning($request);
            }
        } else {
            return $this->redirectWithWarning($request);
        }


        $this->sessionManager->removeSessionData($request);
        $this->addFlashMessage(
            $this->getLanguageService()->sL('oauth2_client.be:flash.providerConfigurationAdded.description'),
            $this->getLanguageService()->sL('oauth2_client.be:flash.providerConfigurationAdded.title'),
            ContextualFeedbackSeverity::OK
        );

        $response = $this->responseFactory
            ->createResponse(302, 'OAuth2: Done. Redirection to original requested location')
            ->withHeader('location', (string)$this->uriBuilder->buildUriFromRoute('oauth2_manage_providers'));

        return $this->sessionManager->appendRemoveOAuth2CookieToResponse($response, $request);
    }

    /**
     * @throws RouteNotFoundException
     */
    private function redirectWithWarning(ServerRequestInterface $request): ResponseInterface
    {

        $this->sessionManager->removeSessionData($request);
        $this->addFlashMessage(
            $this->getLanguageService()->sL('oauth2_client.be:flash.providerConfigurationFailed.description'),
            $this->getLanguageService()->sL('oauth2_client.be:flash.providerConfigurationFailed.title'),
            ContextualFeedbackSeverity::WARNING
        );

        $response = $this->responseFactory
            ->createResponse(302, 'OAuth2: Not logged in or invalid data')
            ->withHeader('location', (string)$this->uriBuilder->buildUriFromRoute('oauth2_manage_providers'));

        return $this->sessionManager->appendRemoveOAuth2CookieToResponse($response, $request);
    }

    protected function addFlashMessage(
        string $message,
        string $title = '',
        ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::INFO
    ): void {
        $flashMessageService = $this->flashMessageService;
        $flashMessageService->getMessageQueueByIdentifier()->enqueue(
            GeneralUtility::makeInstance(FlashMessage::class, $message, $title, $severity, true)
        );
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
