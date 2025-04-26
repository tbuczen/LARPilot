<?php

namespace App\Security;

use App\Domain\Account\UseCase\AddSocialAccountToUser\AddSocialAccountToUserCommand;
use App\Domain\Account\UseCase\AddSocialAccountToUser\AddSocialAccountToUserHandler;
use App\Domain\Account\UseCase\RegisterUser\RegisterUserCommand;
use App\Domain\Account\UseCase\RegisterUser\RegisterUserHandler;
use App\Entity\Enum\SocialAccountProvider;
use App\Entity\User;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\Provider\GoogleClient;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntrypointInterface
{

    public function __construct(
        private readonly ClientRegistry                $clientRegistry,
        private readonly RouterInterface               $router,
        private readonly RegisterUserHandler           $registerUserHandler,
        private readonly AddSocialAccountToUserHandler $addSocialAccountToUserHandler,
        private readonly Security                      $security,
    )
    {
    }

    public function supports(Request $request): ?bool
    {
        // continue ONLY if the current ROUTE matches the check ROUTE
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        /** @var GoogleClient $client */
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function () use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);
                $providerEnum = SocialAccountProvider::Google;

                /** @var User|null $currentUser */
                $currentUser = $this->security->getUser();
                if ($currentUser) {
                    return $this->addSocialAccountToUser($googleUser, $currentUser);
                }

                return $this->registerNewUser($providerEnum, $googleUser);
            })
        );
    }

    /** @see OAuthGoogleController::connectGoogleCheck */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $session = $request->getSession();
        $targetUrl =  $session->get('redirect_to_after_login') ?? $this->router->generate('public_larp_list');
        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        return new Response($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            '/connect/', // might be the site, where users choose their oauth provider
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    private function addSocialAccountToUser(GoogleUser $googleUser, UserInterface|User $currentUser): UserInterface
    {
        $command = new AddSocialAccountToUserCommand(
            provider: SocialAccountProvider::Google,
            providerUserId: $googleUser->getId(),
            email: $googleUser->getEmail(),
            userId: $currentUser->getId()->toRfc4122(),
            username: $googleUser->getName() . '(' . $googleUser->getId() . ')',
            displayName: $googleUser->getEmail()
        );

        return $this->addSocialAccountToUserHandler->handle($command);
    }

    private function registerNewUser(SocialAccountProvider $providerEnum, GoogleUser $googleUser): UserInterface
    {
        $command = new RegisterUserCommand(
            provider: $providerEnum,
            providerUserId: $googleUser->getId(),
            email: $googleUser->getEmail(),
            username: $googleUser->getName() . '(' . $googleUser->getId() . ')',
            displayName: $googleUser->getEmail()
        );

        return $this->registerUserHandler->handle($command);
    }
}