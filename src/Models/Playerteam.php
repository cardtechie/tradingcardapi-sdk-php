<?php

namespace CardTechie\TradingCardApiSdk\Models;

use CardTechie\TradingCardApiSdk\Facades\TradingCardApiSdk;
use CardTechie\TradingCardApiSdk\Models\Traits\OnCardable;
use CardTechie\TradingCardApiSdk\Utils\StringHelpers;

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
        $player = Player::getFromApi([
            'player' => $params['player'],
        ]);

        // Handle case where Player::getFromApi() might return a collection
        if ($player instanceof \Illuminate\Support\Collection) {
            if ($player->isEmpty()) {
                throw new \InvalidArgumentException("No player found with name: {$params['player']}");
            }
            if ($player->count() > 1) {
                throw new \InvalidArgumentException("Multiple players found with name: {$params['player']}. Please be more specific.");
            }
            $player = $player->first();
        }

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
        if (($data['player'] === null || $data['player'] === '') &&
            ($data['team'] === null || $data['team'] === '')) {
            return null;
        }

        $playerUuid = null;
        $teamUuid = null;

        // Handle player lookup
        if (! empty($data['player'])) {
            if (StringHelpers::isValidUuid($data['player'])) {
                $playerUuid = $data['player'];
                // Validate that this player UUID exists in the API
                if (! self::validatePlayerExists($playerUuid)) {
                    throw new \InvalidArgumentException("Player with UUID {$playerUuid} not found in API");
                }
            } else {
                // We have a name, look up the player
                try {
                    $player = Player::getFromApi(['player' => $data['player']]);
                    if ($player instanceof \Illuminate\Support\Collection) {
                        if ($player->isEmpty()) {
                            throw new \InvalidArgumentException("No player found with name: {$data['player']}");
                        }
                        $player = $player->first();
                    }
                    $playerUuid = $player->id;
                } catch (\Exception $e) {
                    throw new \InvalidArgumentException("Failed to find player '{$data['player']}': ".$e->getMessage());
                }
            }
        }

        // Handle team lookup
        if (! empty($data['team'])) {
            if (StringHelpers::isValidUuid($data['team'])) {
                $teamUuid = $data['team'];
                // Validate that this team UUID exists in the API
                if (! self::validateTeamExists($teamUuid)) {
                    throw new \InvalidArgumentException("Team with UUID {$teamUuid} not found in API");
                }
            } else {
                // We have a name, look up the team
                try {
                    $team = Team::getFromApi(['team' => $data['team']]);
                    $teamUuid = $team->id;
                } catch (\Exception $e) {
                    throw new \InvalidArgumentException("Failed to find team '{$data['team']}': ".$e->getMessage());
                }
            }
        }

        return self::lookup($playerUuid, $teamUuid);
    }

    /**
     * Validate that a player UUID exists in the API
     *
     * @param  string  $playerUuid  The player UUID to validate
     * @return bool True if player exists, false otherwise
     */
    protected static function validatePlayerExists(string $playerUuid): bool
    {
        try {
            $players = TradingCardApiSdk::player()->getList(['id' => $playerUuid]);

            return ! $players->isEmpty();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate that a team UUID exists in the API
     *
     * @param  string  $teamUuid  The team UUID to validate
     * @return bool True if team exists, false otherwise
     */
    protected static function validateTeamExists(string $teamUuid): bool
    {
        try {
            $teams = TradingCardApiSdk::team()->getList(['id' => $teamUuid]);

            return ! $teams->isEmpty();
        } catch (\Exception $e) {
            return false;
        }
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
