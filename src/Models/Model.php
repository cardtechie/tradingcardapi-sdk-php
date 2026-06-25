<?php

namespace CardTechie\TradingCardApiSdk\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use stdClass;

/**
 * Class Model
 */
class Model
{
    public array $attributes = [];

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
     * Model constructor.
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->meta = new stdClass;
        $this->links = new stdClass;
    }

    /**
     * Set the relationships for the object
     */
    public function setRelationships(array $relationships): void
    {
        $this->relationships = $relationships;
    }

    /**
     * Return the array of relationships
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
     *
     * @return mixed|null
     */
    public function __get($name)
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
     */
    public function __isset($name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Magic method to get a relationship.
     *
     * @return Collection|mixed
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function __call($methodName, $arguments)
    {
        //
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

        return json_encode($output);
    }
}
