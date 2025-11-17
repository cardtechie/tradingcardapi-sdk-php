<?php

namespace CardTechie\TradingCardApiSdk;

use CardTechie\TradingCardApiSdk\Resources\Attribute;
use CardTechie\TradingCardApiSdk\Resources\Brand;
use CardTechie\TradingCardApiSdk\Resources\Card;
use CardTechie\TradingCardApiSdk\Resources\CardImage;
use CardTechie\TradingCardApiSdk\Resources\Genre;
use CardTechie\TradingCardApiSdk\Resources\Manufacturer;
use CardTechie\TradingCardApiSdk\Resources\ObjectAttribute;
use CardTechie\TradingCardApiSdk\Resources\Player;
use CardTechie\TradingCardApiSdk\Resources\Playerteam;
use CardTechie\TradingCardApiSdk\Resources\Set;
use CardTechie\TradingCardApiSdk\Resources\SetSource;
use CardTechie\TradingCardApiSdk\Resources\Stats;
use CardTechie\TradingCardApiSdk\Resources\Team;
use CardTechie\TradingCardApiSdk\Resources\Year;
use GuzzleHttp\Client;

/**
 * Class TradingCardApi
 */
class TradingCardApi
{
    /**
     * The client to make API requests
     *
     * @var Client
     */
    private $client;

    /**
     * Authentication type ('oauth2' or 'pat')
     *
     * @var string
     */
    private $authType = 'oauth2';

    /**
     * Personal Access Token (for PAT auth mode)
     *
     * @var string|null
     */
    private $personalAccessToken;

    /**
     * Constructor
     *
     * @param  array<string, mixed>  $options  Optional configuration overrides
     * @return void
     */
    public function __construct(array $options = [])
    {
        $config = config('tradingcardapi') ?: [];
        $mergedConfig = array_merge($config, $options);

        $this->client = new Client([
            'verify' => $mergedConfig['ssl_verify'] ?? true,
            'base_uri' => $mergedConfig['url'] ?? '',
        ]);

        // Set auth type and token if provided
        if (isset($mergedConfig['auth_type'])) {
            $this->authType = $mergedConfig['auth_type'];
        }

        if (isset($mergedConfig['personal_access_token'])) {
            $this->personalAccessToken = $mergedConfig['personal_access_token'];
        }
    }

    /**
     * Create a new instance using Personal Access Token authentication
     *
     * @param  string  $token  The personal access token
     */
    public static function withPersonalAccessToken(string $token): self
    {
        return new self([
            'auth_type' => 'pat',
            'personal_access_token' => $token,
        ]);
    }

    /**
     * Create a new instance using OAuth2 Client Credentials authentication
     *
     * @param  string  $clientId  The OAuth2 client ID
     * @param  string  $clientSecret  The OAuth2 client secret
     */
    public static function withClientCredentials(string $clientId, string $clientSecret): self
    {
        return new self([
            'auth_type' => 'oauth2',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);
    }

    /**
     * Get the authentication type
     */
    public function getAuthType(): string
    {
        return $this->authType;
    }

    /**
     * Get the personal access token
     */
    public function getPersonalAccessToken(): ?string
    {
        return $this->personalAccessToken;
    }

    /**
     * Create a resource instance with auth information
     *
     * @template T of object
     *
     * @param  class-string<T>  $resourceClass
     * @return T
     */
    private function createResource(string $resourceClass): object
    {
        $resource = new $resourceClass($this->client);

        // Set auth information on the resource if it uses ApiRequest trait
        if (method_exists($resource, 'setAuthInfo')) {
            $resource->setAuthInfo($this->authType, $this->personalAccessToken);
        }

        return $resource;
    }

    /**
     * Retrieve the genre resource.
     */
    public function genre(): Genre
    {
        return $this->createResource(Genre::class);
    }

    /**
     * Retrieve the set resource.
     */
    public function set(): Set
    {
        return $this->createResource(Set::class);
    }

    /**
     * Retrieve the set source resource.
     */
    public function setSource(): SetSource
    {
        return $this->createResource(SetSource::class);
    }

    /**
     * Retrieve the card resource.
     */
    public function card(): Card
    {
        return $this->createResource(Card::class);
    }

    /**
     * Retrieve the card image resource.
     */
    public function cardImage(): CardImage
    {
        return $this->createResource(CardImage::class);
    }

    /**
     * Retrieve the player resource.
     */
    public function player(): Player
    {
        return $this->createResource(Player::class);
    }

    /**
     * Retrieve the team resource.
     */
    public function team(): Team
    {
        return $this->createResource(Team::class);
    }

    /**
     * Retrieve the playerteam resource.
     */
    public function playerteam(): Playerteam
    {
        return $this->createResource(Playerteam::class);
    }

    /**
     * Retrieve the attribute resource.
     */
    public function attribute(): Attribute
    {
        return $this->createResource(Attribute::class);
    }

    /**
     * Retrieve the brand resource.
     */
    public function brand(): Brand
    {
        return $this->createResource(Brand::class);
    }

    /**
     * Retrieve the manufacturer resource.
     */
    public function manufacturer(): Manufacturer
    {
        return $this->createResource(Manufacturer::class);
    }

    /**
     * Retrieve the year resource.
     */
    public function year(): Year
    {
        return $this->createResource(Year::class);
    }

    /**
     * Retrieve the object attribute resource.
     */
    public function objectAttribute(): ObjectAttribute
    {
        return $this->createResource(ObjectAttribute::class);
    }

    /**
     * Retrieve the stats resource.
     */
    public function stats(): Stats
    {
        return $this->createResource(Stats::class);
    }
}
