<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\DTOs\Workflow;

/**
 * A single actionable set from the workflow dashboard / review queue.
 *
 * `GET /internal/actionable-sets` returns **flat rows**, not JSON:API resource
 * objects — each row carries `todo_id`, `set_id`, `set_name`, `genre`, `year`,
 * `step`, `priority`, `card_count`, `has_sources`, `notes`, `updated_at`.
 * {@see fromObject()} therefore preserves the whole row as `attributes` when
 * the JSON:API keys are absent, rather than discarding it. The JSON:API shape
 * is still parsed when present, so a future API change back to resource
 * objects keeps working.
 *
 * `attributes` is exposed as the decoded object so callers keep access to the
 * full attribute set without the DTO having to enumerate every workflow
 * attribute the API may add.
 */
class ActionableSet
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly object $attributes,
        public readonly ?string $todoId = null,
    ) {}

    public static function fromObject(object $data): self
    {
        $isJsonApi = isset($data->id) || isset($data->type) || isset($data->attributes);

        if ($isJsonApi) {
            $attributes = $data->attributes ?? (object) [];

            return new self(
                id: (string) ($data->id ?? ''),
                type: $data->type ?? 'sets',
                attributes: $attributes,
                todoId: self::readTodoId($data) ?? self::readTodoId($attributes),
            );
        }

        // Flat row from the real API — keep the whole row as attributes so the
        // caller can recover every field, and derive the id from set_id.
        return new self(
            id: (string) ($data->set_id ?? ''),
            type: 'sets',
            attributes: $data,
            todoId: self::readTodoId($data),
        );
    }

    private static function readTodoId(object $source): ?string
    {
        return isset($source->todo_id) ? (string) $source->todo_id : null;
    }
}
