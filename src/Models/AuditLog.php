<?php

declare(strict_types=1);

namespace CardTechie\TradingCardApiSdk\Models;

/**
 * Class AuditLog
 *
 * Represents an audit-log entry in the Trading Card API.
 *
 * @property string $id Audit log UUID
 * @property string|null $auditable_id UUID of the audited record
 * @property string|null $auditable_type Type of the audited record
 * @property string|null $event_type Event type (created|updated|deleted)
 * @property string|null $user_id UUID of the acting user
 * @property string|null $old_values Previous values (JSON-encoded)
 * @property string|null $new_values New values (JSON-encoded)
 * @property string|null $ip_address Originating IP address
 * @property string|null $created_at Creation timestamp
 * @property string|null $updated_at Last update timestamp
 */
class AuditLog extends Model {}
