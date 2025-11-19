<?php

declare(strict_types=1);

namespace Volunteersystem\Models\Shifts;

use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\BaseModel;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\User\UsesUserModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property int            $id
 * @property int            $shift_id
 * @property int            $volunteer_type_id
 * @property string         $user_comment
 * @property int|null       $freeloaded_by
 * @property string         $freeloaded_comment
 *
 * @property-read Shift     $shift
 * @property-read VolunteerType $volunteerType
 * @property-read User|null $freeloadedBy
 *
 * @method static QueryBuilder|ShiftEntry[] whereId($value)
 * @method static QueryBuilder|ShiftEntry[] whereShiftId($value)
 * @method static QueryBuilder|ShiftEntry[] whereVolunteerTypeId($value)
 * @method static QueryBuilder|ShiftEntry[] whereUserComment($value)
 * @method static QueryBuilder|ShiftEntry[] whereFreeloadedBy($value)
 * @method static QueryBuilder|ShiftEntry[] whereFreeloadedComment($value)
 */
class ShiftEntry extends BaseModel
{
    use HasFactory;
    use UsesUserModel;

    /** @var array<string, string|null> default attributes */
    protected $attributes = [ // phpcs:ignore
        'user_comment'       => '',
        'freeloaded_by'      => null,
        'freeloaded_comment' => '',
    ];

    /** @var array<string> */
    protected $fillable = [ // phpcs:ignore
        'shift_id',
        'volunteer_type_id',
        'user_id',
        'user_comment',
        'freeloaded_by',
        'freeloaded_comment',
    ];

    /** @var array<string, string> */
    protected $casts = [ // phpcs:ignore
        'shift_id' => 'integer',
        'volunteer_type_id' => 'integer',
        'user_id' => 'integer',
        'freeloaded_by' => 'integer',
    ];

    /** @var array<string> Attributes which should not be serialized */
    protected $hidden = [ // phpcs:ignore
        'freeloaded_comment',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function volunteerType(): BelongsTo
    {
        return $this->belongsTo(VolunteerType::class);
    }

    public function freeloadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freeloaded_by');
    }
}
