<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    /**
     * Boot the trait
     */
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->logActivity('created');
        });

        static::updated(function ($model) {
            if ($model->wasChanged() && !$model->wasRecentlyCreated) {
                $model->logActivity('updated');
            }
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }

    /**
     * Log the activity
     */
    public function logActivity(string $event, ?string $customDescription = null): void
    {
        // Skip logging if explicitly disabled
        if (property_exists($this, 'disableActivityLogging') && $this->disableActivityLogging) {
            return;
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'subject_type' => get_class($this),
            'subject_id' => $this->id ?? null,
            'event' => $event,
            'description' => $customDescription ?? $this->getActivityDescription($event),
            'properties' => $this->getActivityProperties($event),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Get human-readable activity description
     */
    protected function getActivityDescription(string $event): string
    {
        $modelName = class_basename($this);
        $identifier = $this->getActivityIdentifier();

        return match($event) {
            'created' => "Created new {$modelName}" . ($identifier ? ": {$identifier}" : ''),
            'updated' => "Updated {$modelName}" . ($identifier ? ": {$identifier}" : ''),
            'deleted' => "Deleted {$modelName}" . ($identifier ? ": {$identifier}" : ''),
            default => "{$event} {$modelName}" . ($identifier ? ": {$identifier}" : ''),
        };
    }

    /**
     * Get identifier for the model (name, title, etc.)
     */
    protected function getActivityIdentifier(): ?string
    {
        // Try common identifier fields
        foreach (['name', 'title', 'email', 'slug'] as $field) {
            if (isset($this->{$field})) {
                return $this->{$field};
            }
        }

        return null;
    }

    /**
     * Get activity properties (old/new values)
     */
    protected function getActivityProperties(string $event): ?array
    {
        if ($event === 'updated') {
            $changes = [];
            foreach ($this->getChanges() as $key => $newValue) {
                // Skip timestamps and sensitive fields
                if (in_array($key, ['updated_at', 'password', 'remember_token'])) {
                    continue;
                }

                $changes[$key] = [
                    'old' => $this->getOriginal($key),
                    'new' => $newValue,
                ];
            }

            return $changes ?: null;
        }

        if ($event === 'created' || $event === 'deleted') {
            $attributes = $this->getAttributes();
            // Remove sensitive fields
            unset($attributes['password'], $attributes['remember_token']);
            return $attributes;
        }

        return null;
    }
}
