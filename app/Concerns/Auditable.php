<?php

namespace App\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Adds create/update/delete auditing via spatie/activitylog. Every model
 * using this trait must declare auditableFields(): an explicit allowlist,
 * never all columns, so a field added later (e.g. a future sensitive
 * column) is never logged unless deliberately added to that list.
 */
trait Auditable
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->auditableFields())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return array<int, string>
     */
    abstract protected function auditableFields(): array;
}
