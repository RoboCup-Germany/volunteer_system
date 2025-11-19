<?php

declare(strict_types=1);

namespace Volunteersystem\Models\Shifts;

use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\BaseModel;
use Volunteersystem\Models\Location;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @property int             $id
 * @property int|null        $location_id
 * @property int|null        $shift_id
 * @property int|null        $shift_type_id
 * @property int             $volunteer_type_id
 * @property int             $count
 *
 * @property-read Location|null  $location
 * @property-read Shift|null $shift
 * @property-read ShiftType|null $shiftType
 * @property-read VolunteerType  $volunteerType
 *
 * @method static QueryBuilder|NeededVolunteerType[] whereId($value)
 * @method static QueryBuilder|NeededVolunteerType[] whereLocationId($value)
 * @method static QueryBuilder|NeededVolunteerType[] whereShiftId($value)
 * @method static QueryBuilder|NeededVolunteerType[] whereShiftTypeId($value)
 * @method static QueryBuilder|NeededVolunteerType[] whereVolunteerTypeId($value)
 * @method static QueryBuilder|NeededVolunteerType[] whereCount($value)
 */
class NeededVolunteerType extends BaseModel
{
    use HasFactory;

    /** @var array<string, null> default attributes */
    protected $attributes = [ // phpcs:ignore
        'location_id'  => null,
        'shift_id' => null,
        'shift_type_id' => null,
    ];

    /** @var array<string> */
    protected $fillable = [ // phpcs:ignore
        'location_id',
        'shift_id',
        'shift_type_id',
        'volunteer_type_id',
        'count',
    ];

    /** @var array<string, string> */
    protected $casts = [ // phpcs:ignore
        'location_id' => 'integer',
        'shift_id' => 'integer',
        'shift_type_id' => 'integer',
        'volunteer_type_id' => 'integer',
        'count' => 'integer',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function shiftType(): BelongsTo
    {
        return $this->belongsTo(ShiftType::class);
    }

    public function volunteerType(): BelongsTo
    {
        return $this->belongsTo(VolunteerType::class);
    }
}
