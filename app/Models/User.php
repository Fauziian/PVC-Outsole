<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model: User (PENGGUNA)
 * Sesuai ERD Proposal: id_pengguna, nama, username, password_hash, role
 *
 * Role yang valid: admin | hr | warehouse | management
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'username',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'is_active'     => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    // ─── Helper Methods ──────────────────────────────────────────────────────────

    /**
     * Cek apakah user memiliki role tertentu.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Cek apakah user memiliki salah satu dari beberapa role.
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    public function isAdmin(): bool      { return $this->role === 'admin'; }
    public function isHr(): bool         { return $this->role === 'hr'; }
    public function isWarehouse(): bool  { return $this->role === 'warehouse'; }
    public function isManagement(): bool { return $this->role === 'management'; }

    // ─── Relationships ────────────────────────────────────────────────────────────

    /**
     * Pengguna dapat terhubung ke satu data karyawan (opsional).
     */
    public function karyawan(): HasOne
    {
        return $this->hasOne(Karyawan::class, 'id_pengguna');
    }
}
