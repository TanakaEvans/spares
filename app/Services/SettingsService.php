<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class SettingsService
{
    private const CACHE_KEY = 'settings.all';

    /** @var array<string, array>|null */
    private ?array $registry = null;

    /** @var array<string, array<int|string, string|null>>|null key => [branch_id|'global' => raw value] */
    private ?array $values = null;

    /**
     * Resolve a setting: branch override → global row → declared default.
     */
    public function get(string $key, Branch|int|null $branch = null): mixed
    {
        $definition = $this->definition($key);
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        $values = $this->loadValues();

        if ($branchId !== null
            && ($definition['per_branch'] ?? false)
            && array_key_exists($key, $values)
            && array_key_exists($branchId, $values[$key])) {
            return $this->cast($values[$key][$branchId], $definition['type']);
        }

        if (array_key_exists($key, $values) && array_key_exists('global', $values[$key])) {
            return $this->cast($values[$key]['global'], $definition['type']);
        }

        return $this->castDefault($definition);
    }

    /**
     * Persist a value. $branch null = global row.
     */
    public function set(string $key, mixed $value, Branch|int|null $branch = null, ?int $userId = null): void
    {
        $definition = $this->definition($key);
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        if ($branchId !== null && ! ($definition['per_branch'] ?? false)) {
            throw new InvalidArgumentException("Setting [{$key}] does not allow per-branch overrides.");
        }

        Setting::updateOrCreate(
            ['key' => $key, 'branch_id' => $branchId],
            ['value' => $this->serialize($value, $definition['type']), 'updated_by' => $userId]
        );

        $this->flush();
    }

    /**
     * Remove a branch override so the branch falls back to the global value.
     */
    public function revertToGlobal(string $key, Branch|int $branch): void
    {
        $this->definition($key);
        $branchId = $branch instanceof Branch ? $branch->id : $branch;

        Setting::where('key', $key)->where('branch_id', $branchId)->delete();

        $this->flush();
    }

    /**
     * Full registry with resolved values for the settings screen.
     * Each entry: definition + global_value + branch_value (+ is_overridden) for the given branch.
     */
    public function all(Branch|int|null $branch = null): array
    {
        $branchId = $branch instanceof Branch ? $branch->id : $branch;
        $values = $this->loadValues();
        $out = [];

        foreach ($this->registry() as $key => $definition) {
            $globalRaw = $values[$key]['global'] ?? null;
            $hasGlobal = array_key_exists($key, $values) && array_key_exists('global', $values[$key]);
            $globalValue = $hasGlobal ? $this->cast($globalRaw, $definition['type']) : $this->castDefault($definition);

            $entry = [
                'key' => $key,
                'label' => $definition['label'],
                'group' => $definition['group'],
                'type' => $definition['type'],
                'options' => $definition['options'] ?? null,
                'help' => $definition['help'] ?? null,
                'per_branch' => $definition['per_branch'] ?? false,
                'sensitive' => $definition['sensitive'] ?? false,
                'default' => $definition['default'],
                'global_value' => $globalValue,
                'value' => $globalValue,
                'is_overridden' => false,
            ];

            if ($branchId !== null && $entry['per_branch']
                && array_key_exists($key, $values)
                && array_key_exists($branchId, $values[$key])) {
                $entry['value'] = $this->cast($values[$key][$branchId], $definition['type']);
                $entry['is_overridden'] = true;
            }

            $out[] = $entry;
        }

        return $out;
    }

    public function definition(string $key): array
    {
        $registry = $this->registry();

        if (! array_key_exists($key, $registry)) {
            throw new InvalidArgumentException("Unknown setting key [{$key}].");
        }

        return $registry[$key];
    }

    public function registry(): array
    {
        return $this->registry ??= config('settings_registry', []);
    }

    public function flush(): void
    {
        $this->values = null;
        Cache::forget(self::CACHE_KEY);
    }

    private function loadValues(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        return $this->values = Cache::remember(self::CACHE_KEY, 3600, function () {
            $map = [];
            foreach (Setting::all(['key', 'branch_id', 'value']) as $row) {
                $map[$row->key][$row->branch_id ?? 'global'] = $row->value;
            }

            return $map;
        });
    }

    private function castDefault(array $definition): mixed
    {
        return $this->cast($this->serialize($definition['default'], $definition['type']), $definition['type']);
    }

    private function cast(?string $raw, string $type): mixed
    {
        if ($raw === null) {
            return null;
        }

        return match ($type) {
            'int' => (int) $raw,
            'decimal', 'percent', 'money' => (float) $raw,
            'bool' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($raw, true),
            default => $raw,
        };
    }

    private function serialize(mixed $value, string $type): string
    {
        return match ($type) {
            'bool' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }
}
