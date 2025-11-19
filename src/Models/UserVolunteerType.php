<?php

declare(strict_types=1);

namespace Volunteersystem\Models;

use Volunteersystem\Models\User\User;
use Volunteersystem\Models\User\UsesUserModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @mixin Builder
 *
 * @property int            $id
 * @property int            $volunteer_type_id
 * @property int|null       $confirm_user_id
 * @property bool           $supporter
 *
 * @property-read VolunteerType $volunteerType
 * @property-read User|null $confirmUser
 * @property-read bool      $isConfirmed
 *
 * @method static QueryBuilder|UserVolunteerType[] whereId($value)
 * @method static QueryBuilder|UserVolunteerType[] whereVolunteerTypeId($value)
 * @method static QueryBuilder|UserVolunteerType[] whereConfirmUserId($value)
 * @method static QueryBuilder|UserVolunteerType[] whereSupporter($value)
 */
class UserVolunteerType extends Pivot
{
    use HasFactory;
    use UsesUserModel;

    /** @var bool Increment the IDs */
    public $incrementing = true; // phpcs:ignore

    /** @var bool Disable timestamps */
    public $timestamps = false; // phpcs:ignore

    /** @var array<string, null|bool> default attributes */
    protected $attributes = [ // phpcs:ignore
        'confirm_user_id' => null,
        'supporter'       => false,
    ];

    /** @var array<string> */
    protected $fillable = [ // phpcs:ignore
        'user_id',
        'volunteer_type_id',
        'confirm_user_id',
        'supporter',
    ];

    /** @var array<string> */
    protected $casts = [ // phpcs:ignore
        'user_id'         => 'integer',
        'volunteer_type_id'   => 'integer',
        'confirm_user_id' => 'integer',
        'supporter'       => 'boolean',
    ];

    /**
     * Returns a list of attributes that can be requested for this pivot table
     *
     * @return string[]
     */
    public static function getPivotAttributes(): array
    {
        return ['id', 'confirm_user_id', 'supporter'];
    }

    public function volunteerType(): BelongsTo
    {
        return $this->belongsTo(VolunteerType::class);
    }

    public function confirmUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirm_user_id');
    }

    public function getIsConfirmedAttribute(): bool
    {
        return !$this->volunteerType->restricted || $this->confirm_user_id;
    }
}
