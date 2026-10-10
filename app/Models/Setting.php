<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Setting extends Model
{
    protected string $table = 'settings';
    protected array $fillable = ['setting_key', 'setting_value'];
    protected bool $timestamps = false;

    public function get(string $key, mixed $default = null): mixed
    {
        $row = $this->findBy('setting_key', $key);
        return $row['setting_value'] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $row = $this->findBy('setting_key', $key);
        if ($row) {
            $this->db->update('settings', ['setting_value' => (string) $value], 'setting_key = ?', [$key]);
        } else {
            $this->db->insert('settings', [
                'setting_key' => $key,
                'setting_value' => (string) $value,
            ]);
        }
    }

    public function allMap(): array
    {
        $map = [];
        foreach ($this->all('id ASC') as $row) {
            $map[$row['setting_key']] = $row['setting_value'];
        }
        return $map;
    }
}
