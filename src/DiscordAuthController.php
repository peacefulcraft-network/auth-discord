<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * -----
 * pcnnet\discord:
 * Adapted by Parsonswy <parsonswy@gmail.com> June 29th, 2019
 * PeacefulCraft Network
 * -----
 */

namespace pcnnet\discord;

use Exception;
use Flarum\Forum\Auth\Registration;
use Flarum\Forum\Auth\ResponseFactory;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Wohali\OAuth2\Client\Provider\Discord;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\RedirectResponse;

class DiscordAuthController implements RequestHandlerInterface
{
    /**
     * @var ResponseFactory
     */
    protected $response;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param ResponseFactory $response
     * @param SettingsRepositoryInterface $settings
     * @param UrlGenerator $url
     */
    public function __construct(ResponseFactory $response, SettingsRepositoryInterface $settings, UrlGenerator $url)
    {
        $this->response = $response;
        $this->settings = $settings;
        $this->url = $url;
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(Request $request): ResponseInterface
    {
        $redirectUri = $this->url->to('forum')->route('auth.discord');

        $provider = new Discord([
            'clientId' => $this->settings->get('pcnnet.flarum-auth-discord.client_id'),
            'clientSecret' => $this->settings->get('pcnnet.flarum-auth-discord.client_secret'),
            'redirectUri' => $redirectUri
        ]);

        $session = $request->getAttribute('session');
        $queryParams = $request->getQueryParams();

        $code = $queryParams['code'];

        if (! $code) {
            $authUrl = $provider->getAuthorizationUrl(['scope' => ['identify', 'email']]);
            $session->put('oauth2state', $provider->getState());

            return new RedirectResponse($authUrl.'&display=popup');
        }

        $state = $queryParams['state'];

        if (! $state || $state !== $session->get('oauth2state')) {
            $session->remove('oauth2state');

            throw new Exception('Invalid state');
        }

        $error = $queryParams['error'];
        $error_description = $queryParams['error_description'];

        if($error){
            $session->remove('oauth2state');
            throw new Exception("oAuth Flow Error - " . $error . " : " . $error_description);
        }

        $token = $provider->getAccessToken('authorization_code', compact('code'));

        /** @var DiscordResourceOwner $user */
        $user = $provider->getResourceOwner($token);

        return $this->response->make(
            'discord', $user->getId(),
            function (Registration $registration) use ($user, $provider, $token) {
                $registration
                    ->provideTrustedEmail($user->getEmail() ?: $this->getEmailFromApi($provider, $token))
                    ->provideAvatar($user->getAvatarHash() ?: "")
                    ->suggestUsername($user->getUsername())
                    ->setPayload($user->toArray());
            }
        );
    }

    private function getEmailFromApi(Discord $provider, AccessToken $token)
    {
        $url = $provider->apiDomain.'/user/emails';

        $response = $provider->getResponse(
            $provider->getAuthenticatedRequest('GET', $url, $token)
        );

        $emails = json_decode($response->getBody(), true);

        foreach ($emails as $email) {
            if ($email['primary'] && $email['verified']) {
                return $email['email'];
            }
        }
    }
}
