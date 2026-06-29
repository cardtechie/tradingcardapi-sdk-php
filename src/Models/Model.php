<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Models;

use Illuminate\Support\Str;
use stdClass;

/**
 * Class Model
 */
class Model
{
    /**
     * @var array<string, mixed>
     */
    public array $attributes = [];

    /**
     * @var array<string, mixed>
     */
    public array $relationships = [];

    /**
     * Per-result top-level JSON:API meta for the parse that produced this model.
     *
     * Carried on the instance (not shared static state) so concurrent or
     * sequential parses cannot bleed meta into one another.
     */
    public object $meta;

    /**
     * Per-result top-level JSON:API links for the parse that produced this model.
     *
     * Carried on the instance (not shared static state) so concurrent or
     * sequential parses cannot bleed links into one another.
     */
    public object $links;

    /**
     * The JSON:API per-resource relationships linkage map, keyed by relationship
     * name => ['type' => ..., 'id' => ...]. Populated by Response when parsing a
     * resource's `data.relationships` block; defaults to empty so direct-construction
     * callers (and tests) that never set linkage keep working unchanged.
     *
     * @var array<string, array{type?: string|null, id?: string|null}>
     */
    public array $linkage = [];

    /**
     * Model constructor.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->meta = new stdClass;
        $this->links = new stdClass;
    }

    /**
     * Set the relationships for the object
     *
     * @param  array<string, mixed>  $relationships
     */
    public function setRelationships(array $relationships): void
    {
        $this->relationships = $relationships;
    }

    /**
     * Return the array of relationships
     *
     * @return array<string, mixed>
     */
    public function getRelationships(): array
    {
        return $this->relationships;
    }

    /**
     * Set the per-result meta for this parsed model.
     */
    public function setMeta(object $meta): void
    {
        $this->meta = $meta;
    }

    /**
     * Return the per-result meta for this parsed model.
     *
     * This is the cross-parse-safe way to read a parse's meta — unlike the
     * static Response::getMeta(), it reflects this specific result and cannot
     * be clobbered by another parse.
     */
    public function getMeta(): object
    {
        return $this->meta;
    }

    /**
     * Set the per-result links for this parsed model.
     */
    public function setLinks(object $links): void
    {
        $this->links = $links;
    }

    /**
     * Return the per-result links for this parsed model.
     *
     * This is the cross-parse-safe way to read a parse's links — unlike the
     * static Response::getLinks(), it reflects this specific result and cannot
     * be clobbered by another parse.
     */
    public function getLinks(): object
    {
        return $this->links;
    }

    /**
     * Set the JSON:API per-resource relationships linkage map for the object.
     *
     * @param  array<string, array{type?: string|null, id?: string|null}>  $linkage
     */
    public function setLinkage(array $linkage): void
    {
        $this->linkage = $linkage;
    }

    /**
     * Return the JSON:API per-resource relationships linkage map.
     *
     * @return array<string, array{type?: string|null, id?: string|null}>
     */
    public function getLinkage(): array
    {
        return $this->linkage;
    }

    /**
     * Helper function to get a relationship of the model.
     *
     * @return mixed|null
     */
    protected function getRelationship(string $key): mixed
    {
        if (array_key_exists($key, $this->relationships)) {
            if (is_array($this->relationships[$key])) {
                return $this->relationships[$key][0];
            } else {
                return $this->relationships[$key];
            }
        }

        return null;
    }

    /**
     * Helper function to get the relationship and return it as an array
     *
     * @return mixed|null
     */
    protected function getRelationshipAsArray(string $key): mixed
    {
        if (array_key_exists($key, $this->relationships)) {
            return $this->relationships[$key];
        }

        return null;
    }

    /**
     * Magic method to get attribute values from the attributes array.
     */
    public function __get(string $name): mixed
    {
        $method = 'get'.Str::studly($name).'Attribute';
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return null;
    }

    /**
     * Magic method to see if the class variable exists.
     *
     * @param  string  $name
     */
    public function __isset($name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Magic method invoked for inaccessible/undefined instance methods.
     *
     * Previously this was a silent no-op that returned null, so a typo like
     * `$player->team()` (when `team()` lives on Playerteam, not Player) returned
     * null instead of failing. Throw so unknown method calls surface loudly.
     *
     * @param  array<int, mixed>  $arguments
     *
     * @throws \BadMethodCallException Always, for any undefined method.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function __call(string $methodName, array $arguments): never
    {
        throw new \BadMethodCallException(
            sprintf('Call to undefined method %s::%s()', static::class, $methodName)
        );
    }

    /**
     * Convert the model to a string.
     */
    public function __toString(): string
    {
        $output = [];
        $output = $this->attributes;

        foreach ($this->relationships as $type => $relations) {
            if (is_object($relations) && property_exists($relations, 'attributes')) {
                $output[$type] = $relations->attributes;

                continue;
            }

            foreach ($relations as $relation) {
                if (is_array($relation) && array_key_exists('attributes', $relation)) {
                    $output[$type][] = $relation['attributes'];
                } elseif (is_object($relation) && property_exists($relation, 'attributes')) {
                    $output[$type][] = $relation->attributes;
                }
            }
        }

        return json_encode($output) ?: '{}';
    }
}
