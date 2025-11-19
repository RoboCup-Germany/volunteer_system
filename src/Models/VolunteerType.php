<?php

declare(strict_types=1);

namespace Volunteersystem\Models;

use Volunteersystem\Models\Shifts\NeededVolunteerType;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property int                               $id
 * @property string                            $name
 * @property string                            $description
 * @property string                            $contact_name
 * @property string                            $contact_dect
 * @property string                            $contact_email
 * @property boolean                           $restricted # If users need an introduction
 * @property boolean                           $requires_driver_license # If users must have a driver license
 * @property boolean                           $requires_ifsg_certificate # If users must have a ifsg certificate
 * @property boolean                           $shift_self_signup # Users can sign up for shifts
 * @property boolean                           $show_on_dashboard # Show on public dashboard
 * @property boolean                           $hide_register # Hide from registration page
 * @property boolean                           $hide_on_shift_view # Hide from shift page
 *
 * @property-read Collection|NeededVolunteerType[] $neededBy
 * @property-read UserVolunteerType                $pivot
 * @property-read Collection|ShiftEntry[]      $shiftEntries
 * @property-read Collection|User[]            $userVolunteerTypes
 *
 * @method static QueryBuilder|VolunteerType[] whereId($value)
 * @method static QueryBuilder|VolunteerType[] whereName($value)
 * @method static QueryBuilder|VolunteerType[] whereDescription($value)
 * @method static QueryBuilder|VolunteerType[] whereContactName($value)
 * @method static QueryBuilder|VolunteerType[] whereContactDect($value)
 * @method static QueryBuilder|VolunteerType[] whereContactEmail($value)
 * @method static QueryBuilder|VolunteerType[] whereRestricted($value)
 * @method static QueryBuilder|VolunteerType[] whereRequiresDriverLicense($value)
 * @method static QueryBuilder|VolunteerType[] whereRequiresIfsgCertificate($value)
 * @method static QueryBuilder|VolunteerType[] whereNoSelfSignup($value)
 * @method static QueryBuilder|VolunteerType[] whereShowOnDashboard($value)
 * @method static QueryBuilder|VolunteerType[] whereHideRegister($value)
 */
class VolunteerType extends BaseModel
{
    use HasFactory;

    /** @var array Default attributes */
    protected $attributes = [ // phpcs:ignore
        'description'               => '',
        'contact_name'              => '',
        'contact_dect'              => '',
        'contact_email'             => '',
        'restricted'                => true,
        'requires_driver_license'   => false,
        'requires_ifsg_certificate' => false,
        'shift_self_signup'         => true,
        'show_on_dashboard'         => true,
        'hide_register'             => false,
        'hide_on_shift_view'        => false,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [ // phpcs:ignore
        'name',
        'description',

        'contact_name',
        'contact_dect',
        'contact_email',

        'restricted',
        'requires_driver_license',
        'requires_ifsg_certificate',
        'shift_self_signup',
        'show_on_dashboard',
        'hide_register',
        'hide_on_shift_view',
    ];

    /** @var array<string, string> */
    protected $casts = [ // phpcs:ignore
        'restricted'                => 'boolean',
        'requires_driver_license'   => 'boolean',
        'requires_ifsg_certificate' => 'boolean',
        'shift_self_signup'         => 'boolean',
        'show_on_dashboard'         => 'boolean',
        'hide_register'             => 'boolean',
        'hide_on_shift_view'        => 'boolean',
    ];

    public function neededBy(): HasMany
    {
        return $this->hasMany(NeededVolunteerType::class);
    }

    public function shiftEntries(): HasMany
    {
        return $this->hasMany(ShiftEntry::class);
    }

    public function userVolunteerTypes(): BelongsToMany
    {
        return $this
            ->belongsToMany(User::class, 'user_volunteer_type')
            ->using(UserVolunteerType::class)
            ->withPivot(UserVolunteerType::getPivotAttributes());
    }

    public function hasContactInfo(): bool
    {
        return !empty($this->contact_name)
            || !empty($this->contact_dect)
            || !empty($this->contact_email);
    }

    /**
     * @codeCoverageIgnore For some reasons parent::boot get s ignored here 0o
     */
    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope('order', fn(Builder $builder) => $builder->orderBy('name'));
    }
}
