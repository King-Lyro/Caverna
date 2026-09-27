<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    public function isStaff(): bool
    {
        return in_array($this->role, ['moderator', 'admin'], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
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
