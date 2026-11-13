<?php

namespace App\Models;

use App\Services\CloudinaryService;
use App\Services\OptionsService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function booted(): void
    {
        // Starter categories / classifications are created once, when the account is created
        static::created(function (User $user): void {
            PersonalExpenseCategory::ensureDefaultsFor($user->id);
            app(OptionsService::class)->ensureClassifications($user->id);
        });
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'profile_photo',
        'timezone',
        'currency',
        'is_active',
        'last_login_at',
        'preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'preferences' => 'array',
            'password' => 'hashed',
        ];
    }

    /**
     * Public URL of the uploaded profile photo, if any.
     */
    public function profilePhotoUrl(): ?string
    {
        return CloudinaryService::url($this->profile_photo);
    }

    /**
     * Square, face-centred version of the profile photo for home-screen / tab icons.
     */
    public function profilePhotoIconUrl(int $size = 192): ?string
    {
        $url = $this->profilePhotoUrl();

        if ($url && str_contains($url, 'res.cloudinary.com') && str_contains($url, '/image/upload/')) {
            return str_replace('/image/upload/', "/image/upload/c_fill,g_face,w_{$size},h_{$size},f_png/", $url);
        }

        return $url;
    }

    /**
     * Whether the profile photo should replace the "LT" app logo.
     */
    public function usesProfilePhotoAsLogo(): bool
    {
        return filled($this->profile_photo) && (bool) data_get($this->preferences, 'use_photo_as_logo', false);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('Admin');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', function ($q) use ($permission) {
                $q->where('name', $permission);
            })->exists();
    }

    public function dailyRecords(): HasMany
    {
        return $this->hasMany(DailyRecord::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function friends(): HasMany
    {
        return $this->hasMany(Friend::class);
    }

    public function friendTransactions(): HasMany
    {
        return $this->hasMany(FriendTransaction::class);
    }

    public function friendSplits(): HasMany
    {
        return $this->hasMany(FriendSplit::class);
    }

    public function foodEntries(): HasMany
    {
        return $this->hasMany(FoodEntry::class);
    }

    public function scooterTrips(): HasMany
    {
        return $this->hasMany(ScooterTrip::class);
    }

    public function fuelEntries(): HasMany
    {
        return $this->hasMany(FuelEntry::class);
    }

    public function mistakes(): HasMany
    {
        return $this->hasMany(Mistake::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function customAnswers(): HasMany
    {
        return $this->hasMany(CustomAnswer::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }
}
