<?php

namespace App\Action;

use App\OAuth\Entity\Client;
use App\OAuth\Entity\User;
use Exception;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface as ServerMiddlewareInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\Stream;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig_Error_Loader;

final class OAuthAuthorize implements ServerMiddlewareInterface
{
    /**
     * The url to redirect to when the user is not authenticated.
     *
     * @var string
     */
    private $authenticateUrl;

    /**
     * The name of the cookie used to store the authentication session.
     *
     * @var string
     */
    private $authenticationCookie;

    /**
     * The url used to check if the user is authenticated.
     *
     * @var string
     */
    private $checkAuthenticationUrl;

    /**
     * The OAuth authorization server.
     *
     * @var AuthorizationServer
     */
    private $oauthServer;

    /**
     * The template renderer.
     *
     * @var TemplateRendererInterface
     */
    private $template;

    /**
     * The user data of the currently authenticated user.
     *
     * @var array
     */
    private $userData;


    private $scopeNames = [
        'preference:read' => 'Read preferences',
        'preference:write' => 'Write preferences',
        'email:read' => 'Read email address',
        'challenge:read' => 'Read incoming challenges',
        'challenge:write' => 'Create, accept, decline challenges',
        'study:read' => 'Read private studies and broadcasts',
        'study:write' => 'Create, update, delete studies and broadcasts',
        'tournament:write' => 'Create tournaments',
        'team:write' => 'Join, leave, and manage teams',
        'msg:write' => 'Send private messages to other players',
        'bot:play' => 'Play games with the bot API',
        'board:play' => 'Play games with the board API',
        'puzzle:read' => 'Read puzzle activity'
        // deprecated
        'game:read' => 'Download all games'
    ];

    /**
     * Initializes a new instance of this class.
     *
     * @param string $authenticateUrl
     * @param string $authenticationCookie
     * @param string $checkAuthenticationUrl
     * @param AuthorizationServer $oauthServer
     * @param TemplateRendererInterface|null $template
     */
    public function __construct(
        string $authenticateUrl,
        string $authenticationCookie,
        string $checkAuthenticationUrl,
        AuthorizationServer $oauthServer,
        TemplateRendererInterface $template = null
    ) {
        $this->authenticateUrl = $authenticateUrl;
        $this->authenticationCookie = $authenticationCookie;
        $this->checkAuthenticationUrl = $checkAuthenticationUrl;
        $this->oauthServer = $oauthServer;
        $this->template = $template;
        $this->userData = [];
    }

    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return \Psr\Http\Message\ResponseInterface|Response|HtmlResponse|RedirectResponse|static
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        if (!$this->isAuthenticated($request)) {
            $url = sprintf($this->authenticateUrl, urlencode($request->getUri()));

            return new RedirectResponse($url);
        }

        $response = new Response();

        try {
            /** @var AuthorizationRequest $AuthorizationRequest */
            $authorizationRequest = $this->oauthServer->validateAuthorizationRequest($request);
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse($response);
        } catch (Exception $exception) {
            $stream = new Stream('php://temp', 'w');
            $stream->write($exception->getMessage());

            return $response->withBody($stream)->withStatus(500);
        }

        if ($request->getMethod() === 'POST') {
            $postData = $request->getParsedBody();
            $approved = array_key_exists('authorize', $postData);

            $authorizationRequest->setAuthorizationApproved($approved);
            $authorizationRequest->setUser(new User($this->getUserIdentifier()));

            try {
                return $this->oauthServer->completeAuthorizationRequest($authorizationRequest, $response);
            } catch (OAuthServerException $exception) {
                return $exception->generateHttpResponse($response);
            } catch (Exception $exception) {
                $stream = new Stream('php://temp', 'w');
                $stream->write($exception->getMessage());

                return $response->withBody($stream)->withStatus(500);
            }
        }

        $client = $authorizationRequest->getClient();

        $parameters = [
            'applicationName' => $client->getName(),
            'redirectUri' => $client->getRedirectUri()[0],
            'client' => $client,
            'scopes' => $this->getScopeNames($authorizationRequest->getScopes()),
            'user' => $this->userData,
        ];

        try {
            $html = $this->template->render('app::oauth-authorize-custom', $parameters);
        } catch (Twig_Error_Loader $e) {
            $html = $this->template->render('app::oauth-authorize', $parameters);
        }

        return new HtmlResponse($html);
    }

    function getScopeNames(array $scopes) {
        return array_map(function($scope) {
            $id = $scope->getIdentifier();
            return isset($this->scopeNames[$id]) ? $this->scopeNames[$id] : $id;
        }, $scopes);
    }

    /**
     * @param ServerRequestInterface $request
     * @return bool
     * @throws \Laminas\Http\Exception\InvalidArgumentException
     */
    private function isAuthenticated(ServerRequestInterface $request)
    {
        $cookies = $request->getCookieParams();

        if (!array_key_exists($this->authenticationCookie, $cookies)) {
            return false;
        }

        $httpClient = new \Laminas\Http\Client($this->checkAuthenticationUrl, [
            'encodecookies' => false,
        ]);
        $httpClient->setHeaders([
            'Accept' => 'application/vnd.lichess.v3+json',
            'User-Agent' => 'chesszebra/lichess-oauth-server',
        ]);
        $httpClient->setCookies([
            $this->authenticationCookie => $cookies[$this->authenticationCookie],
        ]);

        $response = $httpClient->send();

        $json = json_decode($response->getBody(), true);

        if (!$json || array_key_exists('error', $json)) {
            return false;
        }

        $this->userData = $json;

        return true;
    }

    private function getUserIdentifier()
    {
        return $this->userData['id'];
    }
}
