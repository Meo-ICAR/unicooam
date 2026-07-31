<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar // , LogsActivity
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable;

    protected $connection = 'mysql';

    protected $orderBy = 'name';

    protected $orderDirection = 'asc';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            // Se l'utente in fase di creazione non ha una password impostata (es. tramite Socialite)
            if (empty($user->password)) {
                $user->password = Hash::make('password');
            }
        });
    }

    /**
     * Autorizza l'accesso al pannello Filament.
     * Tutti gli utenti registrati possono accedere.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if ($this->avatar_url) {
            return $this->avatar_url;
        }

        $socialUser = $this->socialiteUsers()->whereNotNull('avatar')->first();
        if ($socialUser) {
            return $socialUser->avatar;
        }

        return null;
    }

    public function socialiteUsers(): HasMany
    {
        return $this->hasMany(SocialiteUser::class);
    }

    /*
      public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->useLogName('user')
            ->setDescriptionForEvent(fn (string $eventName) => "Utente {$this->name} ha effettuato l'evento: {$eventName}");
    }
            */

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'user_id', 'id');
    }

    /**
     * Verifica i permessi dell'utente passando per i ruoli del suo Employee.
     */
    public function hasPermission(string $resource, string $action = 'viewAny'): bool
    {
        // Carica la relazione se non è ancora presente
        $employee = $this->employee;

        // Se l'utente non ha un profilo Employee o non ha ruoli assegnati
        if (! $employee || empty($employee->employee_roles)) {
            return false;
        }

        // Verifica se almeno uno dei ruoli dell'impiegato ha il permesso attivo
        return EmployeeTypePermission::query()
            ->whereHas('employeeType', function ($query) use ($employee) {
                $query->whereIn('name', $employee->employee_roles);
            })
            ->where('resource', $resource)
            ->where('action', $action)
            ->exists();
    }
}
