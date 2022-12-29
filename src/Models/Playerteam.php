<?php

namespace CardTechie\TradingCardApiSdk\Models;

use CardTechie\TradingCardApiSdk\Facades\TradingCardApi;
use CardTechie\TradingCardApiSdk\Models\Traits\OnCardable;
use Exception;

/**
 * Class Playerteam
 */
class Playerteam extends Model implements Taxonomy
{
    use OnCardable;

    /**
     * Return the onCardable configuration array for this model.
     */
    public function onCardable() : array
    {
        return [
            'name' => 'Player/Team',
        ];
    }

    /**
     * Build the taxonomy object
     *
     * @param Object $taxonomy
     * @param array $data
     *
     * @return Object
     */
    public static function build(Object $taxonomy, array $data) : Object
    {
        $playerId = $taxonomy->player_id;
        $teamId = $taxonomy->team_id;

        $playerMatch = null;
        foreach ($data['player'] as $player) {
            if ($playerId === $player->id) {
                $playerMatch = $player;
            }
        }
        $taxonomy->relationships['player'] = $playerMatch;

        $teamMatch = null;
        foreach ($data['team'] as $team) {
            if ($teamId === $team->id) {
                $teamMatch = $team;
            }
        }
        $taxonomy->relationships['team'] = $teamMatch;

        return $taxonomy;
    }

    /**
     * Get the playerteam from the api
     *
     * @param array $params
     *
     * @return Object
     */
    public static function getFromApi(array $params) : Object
    {
        // TODO Handle multiple players returned
        $player = Player::getFromApi([
            'player' => $params['player'],
        ]);

        $team = Team::getFromApi([
            'team' => $params['team'],
        ]);

        $playerteam = TradingCardApi::playerteam()->getList([
            'player_id' => $player->id,
            'team_id' => $team->id,
        ]);

        if ($playerteam->isEmpty()) {
            return TradingCardApi::playerteam()->create([
                'player_id' => $player->id,
                'team_id' => $team->id,
            ]);
        }

        return $playerteam->first();
    }

    /**
     * Prepare the on card relationships and return the object that matches the passed in data.
     *
     * @param array $data
     *
     * @return Playerteam|null
     */
    public static function prepare($data) : ?Object
    {
        if (null === $data['player'] && null === $data['team']) {
            return null;
        }

        /*$playerService = resolve(PlayerService::class);
        $teamService = resolve(TeamService::class);

        if (StringHelpers::isValidUuid($data['player'])) {
            $playerUuid = $data['player'];
            // TODO Lookup the uuid to make sure it is a valid player
        } else {
            // We probably have a name of the player so we just need to look it up
            $player = $playerService->getByName($data['player']);
            $playerUuid = $player->id;
        }

        if (StringHelpers::isValidUuid($data['team'])) {
            $teamUuid = $data['team'];
            // TODO Lookup the uuid to make sure it is a valid team
        } else {
            // We probably have a name of the player so we just need to look it up
            $team = $teamService->getByName($data['team']);
            $teamUuid = $team->id;
        }

        return self::lookup($playerUuid, $teamUuid);*/
    }

    /**
     * Look up a player/team by player and team uuids.
     *
     * @param string $player The player uuid
     * @param string $team   The team uuid
     */
    public static function lookup($player, $team) : Playerteam
    {
        $results = PlayerTeam::where([
            ['player_id', '=', $player],
            ['team_id', '=', $team],
        ])->get();

        if ($results->isEmpty()) {
            $playerteam = new PlayerTeam([
                'player_id' => $player,
                'team_id' => $team,
            ]);
            $playerteam->save();
            return $playerteam;
        }

        if ($results->count() > 1) {
            // TODO prevent this from happening
            throw new Exception('Multiple PlayerTeams using same player and team');
        }

        return $results->first();
    }

    /**
     * Retrieve the player.
     *
     * @return Player|null
     */
    public function player() : ?Player
    {
        return $this->getRelationshipAsArray('player');
    }

    /**
     * Retrieve the team.
     *
     * @return Team|null
     */
    public function team() : ?Team
    {
        return $this->getRelationshipAsArray('team');
    }
}
