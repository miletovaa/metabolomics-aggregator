<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'permissions'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLES = [
        'user' => 'User',
        'admin' => 'Admin',
    ];

    /**
     * Resources with their own View / Add-Edit / Delete toggles (see PERMISSION_ACTIONS).
     * For resources that carry per-record ownership (projects, samples, samplings,
     * experiments) a user can *always* view/edit/delete their own or assigned records —
     * these permissions only extend access to records that aren't theirs. For resources
     * with no ownership concept (compounds, options, history, users) the permission gates
     * the resource outright.
     */
    public const PERMISSION_RESOURCES = [
        'projects' => 'Projects',
        'samples' => 'Samples',
        'samplings' => 'Samplings',
        'experiments' => 'Experiments',
        'compounds' => 'Compounds',
        'options' => 'Predefined Values',
        'history' => 'Activity Log',
        'users' => 'Users',
    ];

    public const PERMISSION_ACTIONS = [
        'view' => 'View',
        'edit' => 'Add/Edit',
        'delete' => 'Delete',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Admins have every permission on every resource; everyone else needs it explicitly granted. */
    public function hasPermission(string $resource, string $action): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array("{$resource}.{$action}", $this->permissions ?? [], true);
    }

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
            'permissions' => 'array',
        ];
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function responsibleSamples()
    {
        return $this->hasMany(Sample::class, 'responsible_analyst_id');
    }
}

