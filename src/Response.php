<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use stdClass;

/**
 * Class Response
 *
 * Parse the JSON API response and make the objects easily accessible to display in the UI.
 */
class Response
{
    /**
     * Whitelist of allowed model types for dynamic instantiation.
     * This prevents arbitrary class instantiation from API responses.
     *
     * @var array<string>
     */
    private const ALLOWED_MODEL_TYPES = [
        'Attribute',
        'AuditLog',
        'Brand',
        'Card',
        'CardImage',
        'Genre',
        'Manufacturer',
        'ObjectAttribute',
        'Oncard',
        'Player',
        'Playerteam',
        'Set',
        'SetSource',
        'Taxonomy',
        'Team',
        'Year',
    ];

    private object $response;

    public $mainObject;

    public $relationships;

    private static object $meta;

    private static object $links;

    /**
     * Response constructor.
     */
    public function __construct(string $json)
    {
        $this->response = json_decode($json);

        $this->instantiateMainObject();
        $this->parseIncluded();
        $this->objectifyRelationships();

        $this->mainObject->setLinkage(self::extractLinkage($this->response->data));
        $this->mainObject->setRelationships($this->relationships);
    }

    /**
     * Create the main object with all its attributes.
     */
    private function instantiateMainObject(): void
    {
        $attributes = (array) $this->response->data->attributes;
        $attributes['id'] = $this->response->data->id;

        $type = self::normalizeType($this->response->data->type);
        $class = '\\CardTechie\\TradingCardApiSdk\\Models\\'.$type;
        $this->mainObject = new $class($attributes);
    }

    /**
     * Extract the JSON:API per-resource relationships linkage block from a data
     * object into a plain map of relationship name => ['type' => ..., 'id' => ...].
     *
     * After tradingcardapi-api#1491 removed the flat FK attributes (e.g. genre_id)
     * from the Set response, this linkage block is the only signal tying a resource
     * to its included relationships, so it must be carried through to the model.
     *
     * @return array<string, array{type?: string|null, id?: string|null}>
     */
    private static function extractLinkage(object $data): array
    {
        $linkage = [];

        if (! property_exists($data, 'relationships') || ! is_object($data->relationships)) {
            return $linkage;
        }

        foreach ($data->relationships as $name => $relationship) {
            if (! is_object($relationship) || ! property_exists($relationship, 'data')) {
                continue;
            }

            $resourceIdentifier = $relationship->data;

            // Skip to-many linkage (an array of identifiers) — only to-one is needed today.
            if (! is_object($resourceIdentifier)) {
                continue;
            }

            $linkage[$name] = [
                'type' => $resourceIdentifier->type ?? null,
                'id' => $resourceIdentifier->id ?? null,
            ];
        }

        return $linkage;
    }

    /**
     * Normalize the type string to a class name.
     * Handles hyphenated types like "set-sources" -> "SetSource"
     *
     * @throws \InvalidArgumentException If the type is not in the allowed whitelist
     */
    private static function normalizeType(string $type): string
    {
        // Handle special cases
        if ($type === 'parentset' || $type === 'subset' || $type === 'subsets') {
            return 'Set';
        }
        if ($type === 'checklist') {
            return 'Card';
        }

        // Convert hyphenated types (e.g., "set-sources" -> "SetSource")
        $singular = Str::singular($type);
        $normalizedType = Str::studly($singular);

        // Validate against whitelist to prevent arbitrary class instantiation
        if (! in_array($normalizedType, self::ALLOWED_MODEL_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Unknown model type "%s" in API response. Expected one of: %s',
                    $type,
                    implode(', ', self::ALLOWED_MODEL_TYPES)
                )
            );
        }

        return $normalizedType;
    }

    /**
     * Add any included objects and make them really easy to retrieve.
     */
    private function parseIncluded(): void
    {
        $includes = [];
        if (! property_exists($this->response, 'included')) {
            $this->relationships = $includes;

            return;
        }

        foreach ($this->response->included as $included) {
            $object = [];
            $object['id'] = $included->id;
            foreach ($included->attributes as $key => $value) {
                $object[$key] = $value;
            }
            $includes[$included->type][] = $object;
        }

        $this->relationships = $includes;
    }

    /**
     * Convert the relationships into models.
     */
    private function objectifyRelationships(): void
    {
        foreach ($this->relationships as $type => $theObjects) {
            foreach ($theObjects as $index => $attributes) {
                $theType = self::normalizeType($type);
                $class = '\\CardTechie\\TradingCardApiSdk\\Models\\'.$theType;
                $object = new $class($attributes);

                $this->relationships[$type][$index] = $object;
            }
        }
    }

    /**
     * Get the meta data from the most recent parse() in this process.
     *
     * WARNING: this static accessor reflects only the most recent parse() call
     * and is NOT safe across interleaved or concurrent parses — a later parse
     * overwrites it. For cross-parse-safe access, read the per-result meta off
     * the parsed model instead: `$model->getMeta()`.
     */
    public static function getMeta(): object
    {
        return self::$meta;
    }

    /**
     * Get the links data from the most recent parse() in this process.
     *
     * WARNING: this static accessor reflects only the most recent parse() call
     * and is NOT safe across interleaved or concurrent parses — a later parse
     * overwrites it. For cross-parse-safe access, read the per-result links off
     * the parsed model instead: `$model->getLinks()`.
     */
    public static function getLinks(): object
    {
        return self::$links;
    }

    /**
     * Parse the JSON and convert it into an object
     *
     * @return Collection|object
     */
    public static function parse(string $json)
    {
        $response = json_decode($json);

        // Compute meta/links locally for this parse so they travel with the
        // parsed result rather than living in a shared static slot.
        $meta = self::parseMeta($response);
        $links = self::parseLinks($response);

        // Best-effort, single-threaded-only convenience: record this parse's
        // meta/links into the static fields so the historic
        // Response::parse(); Response::getMeta(); pattern keeps working. These
        // statics reflect only the most recent parse — use the per-result
        // $model->getMeta()/getLinks() for cross-parse-safe access.
        self::$meta = $meta;
        self::$links = $links;

        if (is_array($response->data)) {
            $objects = [];
            foreach ($response->data as $data) {
                $object = self::parseDataObject($data);
                $object->setLinkage(self::extractLinkage($data));
                $object->setRelationships(self::getIncluded($response));
                $object->setMeta($meta);
                $object->setLinks($links);
                $objects[] = $object;
            }

            return collect($objects);
        } else {
            $object = self::parseDataObject($response->data);
            $object->setLinkage(self::extractLinkage($response->data));
            $object->setRelationships(self::getIncluded($response));
            $object->setMeta($meta);
            $object->setLinks($links);

            return $object;
        }
    }

    /**
     * Retrieve the main object with all its attributes.
     */
    private static function parseDataObject(object $data): object
    {
        $attributes = (array) $data->attributes;
        $attributes['id'] = $data->id;

        $type = self::normalizeType($data->type);
        $class = '\\CardTechie\\TradingCardApiSdk\\Models\\'.$type;

        return new $class($attributes);
    }

    /**
     * Get the included objects as an array
     */
    private static function getIncluded(object $data): array
    {
        $includes = [];
        if (! property_exists($data, 'included')) {
            return $includes;
        }

        foreach ($data->included as $included) {
            $attributes = [];
            $attributes['id'] = $included->id;
            foreach ($included->attributes as $key => $value) {
                $attributes[$key] = $value;
            }

            $type = self::normalizeType($included->type);
            $class = '\\CardTechie\\TradingCardApiSdk\\Models\\'.$type;
            $object = new $class($attributes);

            $includes[$included->type][] = $object;
        }

        return $includes;
    }

    /**
     * Parse the meta from the decoded response and return it.
     *
     * Returns an empty stdClass when the response has no top-level `meta` key,
     * preserving the historic empty-object behavior. Pure helper — it does not
     * write any shared static state, so the caller owns the returned object.
     */
    private static function parseMeta($data): stdClass
    {
        if (! property_exists($data, 'meta')) {
            return new stdClass;
        }

        return $data->meta;
    }

    /**
     * Parse the links from the decoded response and return them.
     *
     * Returns an empty stdClass when the response has no top-level `links` key,
     * preserving the historic empty-object behavior. Pure helper — it does not
     * write any shared static state, so the caller owns the returned object.
     */
    private static function parseLinks($data): stdClass
    {
        if (! property_exists($data, 'links')) {
            return new stdClass;
        }

        return $data->links;
    }
}
