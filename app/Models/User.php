<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains('slug', $role) || ($this->roles->isEmpty() && $this->legacyRole() === $role);
    }

    public function hasAnyRole(array $roles): bool
    {
        return collect($roles)->contains(fn (string $role) => $this->hasRole($role));
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(['moderator', 'admin']);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isModerator(): bool
    {
        return $this->hasRole('moderator');
    }

    private function legacyRole(): string
    {
        return $this->role === 'member' ? 'registered' : (string) $this->role;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'bio',
        'email',
        'password',
        'roleplay_sample',
        'avatar_path',
        'discord_username',
        'facebook_url',
        'instagram_url',
        'pronouns',
        'age',
        'location',
        'timezone',
        'hide_personal_info',
        'hide_current_page',
        'is_absent',
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
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'hide_personal_info' => 'boolean',
            'hide_current_page' => 'boolean',
            'is_absent' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
