<?php

declare(strict_types=1);

namespace App\Models;

class Role
{
    public function all(): array
    {
        $cfg = config('roles') ?: [];
        $out = [];
        foreach ($cfg as $key => $meta) {
            if (!is_array($meta)) {
                continue;
            }
            $out[$key] = array_merge(['key' => $key], $meta);
        }
        return $out;
    }

    public function assignableBy(?string $actorRole = null): array
    {
        $actorRole = $actorRole ?: (auth()->role() ?? '');
        $all = $this->all();
        $allowed = $all[$actorRole]['assignable'] ?? [];
        $out = [];
        foreach ((array) $allowed as $key) {
            if (isset($all[$key])) {
                $out[$key] = $all[$key];
            }
        }
        return $out;
    }
}
