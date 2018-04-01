<?php

namespace App\OAuth\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

final class Client implements ClientEntityInterface
{
    use EntityTrait;
    use ClientTrait;

    /**
     * The secret of this client.
     *
     * @var null|string
     */
    protected $secret;

    /**
     * Initializes a new instance of this class.
     *
     * @param string $identifier
     * @param null|string $secret
     * @param string $name
     * @param array $redirectUri
     */
    public function __construct(string $identifier, $secret, string $name, array $redirectUri)
    {
        $this->identifier = $identifier;
        $this->secret = $secret;
        $this->name = $name;
        $this->redirectUri = $redirectUri;
    }

    /**
     * @return null|string
     */
    public function getClientSecret()
    {
        return $this->secret;
    }
}
