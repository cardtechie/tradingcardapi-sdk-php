<?php

namespace CardTechie\TradingCardApiSdk;

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
        'Brand',
        'Card',
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
     * Normalize the type string to a class name.
     * Handles hyphenated types like "set-sources" -> "SetSource"
     *
     * @throws \InvalidArgumentException If the type is not in the allowed whitelist
     */
    private static function normalizeType(string $type): string
    {
        // Handle special cases
        if ($type === 'parentset' || $type === 'subset') {
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
     * Get the meta data from the response.
     */
    public static function getMeta(): object
    {
        return self::$meta;
    }

    /**
     * Get the links data from the response.
     */
    public static function getLinks(): object
    {
        return self::$links;
    }

    /**
     * Parse the JSON and convert it into an object
     *
     * @return \Illuminate\Support\Collection|object
     */
    public static function parse(string $json)
    {
        $response = json_decode($json);

        self::parseMeta($response);
        self::parseLinks($response);

        if (is_array($response->data)) {
            $objects = [];
            foreach ($response->data as $data) {
                $object = self::parseDataObject($data);
                $object->setRelationships(self::getIncluded($response));
                $objects[] = $object;
            }

            return collect($objects);
        } else {
            $object = self::parseDataObject($response->data);
            $object->setRelationships(self::getIncluded($response));

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
     * Parse the meta from the response and set the $meta field of this class.
     */
    private static function parseMeta($data): void
    {
        $meta = new stdClass;
        if (! property_exists($data, 'meta')) {
            self::$meta = $meta;

            return;
        }

        self::$meta = $data->meta;
    }

    /**
     * Parse the links from the response and set the $links field of this class.
     */
    private static function parseLinks($data): void
    {
        $links = new stdClass;
        if (! property_exists($data, 'links')) {
            self::$links = $links;

            return;
        }

        self::$links = $data->links;
    }
}
