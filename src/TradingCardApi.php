<?php

namespace CardTechie\TradingCardApiSdk;

use CardTechie\TradingCardApiSdk\Resources\Attribute;
use CardTechie\TradingCardApiSdk\Resources\Card;
use CardTechie\TradingCardApiSdk\Resources\Genre;
use CardTechie\TradingCardApiSdk\Resources\Player;
use CardTechie\TradingCardApiSdk\Resources\Playerteam;
use CardTechie\TradingCardApiSdk\Resources\Set;
use CardTechie\TradingCardApiSdk\Resources\Team;
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
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $config = config('tradingcardapi') ?: [];

        $this->client = new Client([
            'verify' => $config['ssl_verify'] ?? true,
            'base_uri' => $config['url'] ?? '',
        ]);
    }

    /**
     * Retrieve the genre resource.
     */
    public function genre(): Genre
    {
        return new Genre($this->client);
    }

    /**
     * Retrieve the set resource.
     */
    public function set(): Set
    {
        return new Set($this->client);
    }

    /**
     * Retrieve the card resource.
     */
    public function card(): Card
    {
        return new Card($this->client);
    }

    /**
     * Retrieve the player resource.
     */
    public function player(): Player
    {
        return new Player($this->client);
    }

    /**
     * Retrieve the team resource.
     */
    public function team(): Team
    {
        return new Team($this->client);
    }

    /**
     * Retrieve the playerteam resource.
     */
    public function playerteam(): Playerteam
    {
        return new Playerteam($this->client);
    }

    /**
     * Retrieve the attribute resource.
     */
    public function attribute(): Attribute
    {
        return new Attribute($this->client);
    }
}
