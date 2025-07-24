<?php

namespace CardTechie\TradingCardApiSdk\Models;

use CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk;
use CardTechie\TradingCardApiSdk\Models\Traits\OnCardable;

/**
 * Class Playerteam
 */
class Playerteam extends Model implements Taxonomy
{
    use OnCardable;

    /**
     * Return the onCardable configuration array for this model.
     */
    public function onCardable(): array
    {
        return [
            'name' => 'Player/Team',
        ];
    }

    /**
     * Build the taxonomy object
     */
    public static function build(object $taxonomy, array $data): object
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
     */
    public static function getFromApi(array $params): object
    {
        // TODO Handle multiple players returned
        $player = Player::getFromApi([
            'player' => $params['player'],
        ]);

        $team = Team::getFromApi([
            'team' => $params['team'],
        ]);

        $playerteam = TradingCardApiSdk::playerteam()->getList([
            'player_id' => $player->id,
            'team_id' => $team->id,
        ]);

        if ($playerteam->isEmpty()) {
            return TradingCardApiSdk::playerteam()->create([
                'player_id' => $player->id,
                'team_id' => $team->id,
            ]);
        }

        return $playerteam->first();
    }

    /**
     * Prepare the on card relationships and return the object that matches the passed in data.
     *
     * @param  array  $data
     * @return Playerteam|null
     */
    public static function prepare($data): ?object
    {
        if ($data['player'] === null && $data['team'] === null) {
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

        // TODO: Implement prepare() method properly
        return null;
    }

    /**
     * Look up a player/team by player and team uuids.
     *
     * @param  string  $player  The player uuid
     * @param  string  $team  The team uuid
     */
    public static function lookup($player, $team): Playerteam
    {
        // TODO: Implement database lookup when Eloquent integration is added
        // This method currently uses undefined Eloquent methods
        /*
        $results = Playerteam::where([
            ['player_id', '=', $player],
            ['team_id', '=', $team],
        ])->get();

        if ($results->isEmpty()) {
            $playerteam = new Playerteam([
                'player_id' => $player,
                'team_id' => $team,
            ]);
            $playerteam->save();

            return $playerteam;
        }

        return $results->first();
        */

        // For now, return a new instance
        return new self(['player_id' => $player, 'team_id' => $team]);
    }

    /**
     * Retrieve the player.
     */
    public function player(): ?Player
    {
        return $this->getRelationshipAsArray('player');
    }

    /**
     * Retrieve the team.
     */
    public function team(): ?Team
    {
        return $this->getRelationshipAsArray('team');
    }
}
