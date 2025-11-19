<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Metrics;

use Carbon\Carbon;
use Volunteersystem\Database\Database;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\EventConfig;
use Volunteersystem\Models\Faq;
use Volunteersystem\Models\Location;
use Volunteersystem\Models\LogEntry;
use Volunteersystem\Models\Message;
use Volunteersystem\Models\News;
use Volunteersystem\Models\NewsComment;
use Volunteersystem\Models\OAuth;
use Volunteersystem\Models\Question;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftType;
use Volunteersystem\Models\User\License;
use Volunteersystem\Models\User\PasswordReset;
use Volunteersystem\Models\User\PersonalData;
use Volunteersystem\Models\User\Settings;
use Volunteersystem\Models\User\State;
use Volunteersystem\Models\User\User;
use Volunteersystem\Models\UserVolunteerType;
use Volunteersystem\Models\Worklog;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression as QueryExpression;
use Illuminate\Support\Collection;

class Stats
{
    public function __construct(protected Database $db)
    {
    }

    /**
     * The number of users that arrived/not arrived and/or did some work
     *
     */
    public function usersState(?bool $working = null, bool $arrived = true): int
    {
        $query = State::whereArrived($arrived);

        if (!is_null($working)) {
            $query
                ->leftJoin('worklogs', 'worklogs.user_id', '=', 'users_state.user_id')
                ->leftJoin('shift_entries', 'shift_entries.user_id', '=', 'users_state.user_id')
                ->distinct();

            $query->where(function ($query) use ($working): void {
                /** @var QueryBuilder $query */
                if ($working) {
                    $query
                        ->whereNotNull('shift_entries.shift_id')
                        ->orWhereNotNull('worklogs.hours');

                    return;
                }

                $query
                    ->whereNull('shift_entries.shift_id')
                    ->whereNull('worklogs.hours');
            });
        }

        return $query->count('users_state.user_id');
    }

    public function usersInfo(): int
    {
        return State::query()
            ->whereNotNull('user_info')
            ->whereNot('user_info', '')
            ->count();
    }

    public function forceActiveUsers(): int
    {
        return State::whereForceActive(true)->count();
    }

    public function forceFoodUsers(): int
    {
        return State::whereForceFood(true)->count();
    }

    public function usersPronouns(): int
    {
        return PersonalData::query()->where('pronoun', '!=', '')->count();
    }

    public function email(string $type): int
    {
        return match ($type) {
            'system' => Settings::whereEmailShiftinfo(true)->count(),
            'humans' => Settings::whereEmailHuman(true)->count(),
            'goodie'  => Settings::whereEmailGoodie(true)->count(),
            'news'   => Settings::whereEmailNews(true)->count(),
            default  => 0,
        };
    }

    /**
     * The number of currently working users
     *
     */
    public function currentlyWorkingUsers(?bool $freeloaded = null): int
    {
        $query = User::query()
            ->join('shift_entries', 'shift_entries.user_id', '=', 'users.id')
            ->join('shifts', 'shifts.id', '=', 'shift_entries.shift_id')
            ->where('shifts.start', '<=', Carbon::now())
            ->where('shifts.end', '>', Carbon::now());

        if (!is_null($freeloaded)) {
            $freeloaded
                ? $query->whereNotNull('shift_entries.freeloaded_by')
                : $query->whereNull('shift_entries.freeloaded_by');
        }

        return $query->count();
    }

    protected function vouchersQuery(): Builder
    {
        return State::query();
    }

    public function vouchers(): int
    {
        return (int) $this->vouchersQuery()->sum('got_voucher');
    }

    public function vouchersBuckets(array $buckets): array
    {
        $return = [];
        foreach ($buckets as $bucket) {
            $query = $this->vouchersQuery();

            if ($bucket !== '+Inf') {
                $query->where('got_voucher', '<=', $bucket);
            }

            $return[$bucket] = $query->count('got_voucher');
        }

        return $return;
    }

    public function goodies(): int
    {
        return State::whereGotGoodie(true)->count();
    }

    public function tshirtSizes(): Collection
    {
        return PersonalData::query()
            ->select(['shirt_size', $this->raw('COUNT(shirt_size) AS count')])
            ->whereNotNull('shirt_size')
            ->groupBy(['shirt_size'])
            ->get();
    }

    public function languages(): Collection
    {
        return Settings::query()
            ->select(['language', $this->raw('COUNT(language) AS count')])
            ->groupBy(['language'])
            ->get();
    }

    public function themes(): Collection
    {
        return Settings::query()
            ->select(['theme', $this->raw('COUNT(theme) AS count')])
            ->groupBy(['theme'])
            ->get();
    }

    public function licenses(string $license, bool $confirmed = false): int
    {
        $mapping = [
            'has_car'   => ['has_car', null],
            'forklift' => ['drive_forklift', 'drive_confirmed'],
            'car' => ['drive_car', 'drive_confirmed'],
            '3.5t' => ['drive_3_5t', 'drive_confirmed'],
            '7.5t' => ['drive_7_5t', 'drive_confirmed'],
            '12t' => ['drive_12t', 'drive_confirmed'],
            'ifsg_light' => ['ifsg_certificate_light', 'ifsg_confirmed'],
            'ifsg' => ['ifsg_certificate', 'ifsg_confirmed'],
        ];

        $query = (new License())
            ->getQuery()
            ->where($mapping[$license][0], true);
        if (!is_null($mapping[$license][1])) {
            $query->where($mapping[$license][1], $confirmed);
        }

        return $query->count();
    }

    /**
     *
     * @codeCoverageIgnore because it is only used in functions that use TIMESTAMPDIFF
     */
    protected function workSecondsQuery(?bool $done = null, ?bool $freeloaded = null): QueryBuilder
    {
        $query = $this
            ->getQuery('shift_entries')
            ->join('shifts', 'shifts.id', '=', 'shift_entries.shift_id');

        if (!is_null($freeloaded)) {
            $freeloaded
                ? $query->whereNotNull('freeloaded_by')
                : $query->whereNull('freeloaded_by');
        }

        if (!is_null($done)) {
            $query->where('end', ($done ? '<' : '>='), Carbon::now());
        }

        return $query;
    }

    /**
     * The amount of worked seconds
     *
     *
     * @codeCoverageIgnore as TIMESTAMPDIFF is not implemented in SQLite
     */
    public function workSeconds(?bool $done = null, ?bool $freeloaded = null): int
    {
        $query = $this->workSecondsQuery($done, $freeloaded);

        return (int) $query->sum($this->raw('TIMESTAMPDIFF(MINUTE, start, end) * 60'));
    }

    /**
     * The number of worked shifts
     *
     *
     * @codeCoverageIgnore as TIMESTAMPDIFF is not implemented in SQLite
     */
    public function workBuckets(array $buckets, ?bool $done = null, ?bool $freeloaded = null): array
    {
        return $this->getBuckets(
            $buckets,
            $this->workSecondsQuery($done, $freeloaded),
            'user_id',
            'SUM(TIMESTAMPDIFF(MINUTE, start, end) * 60)',
            'SUM(TIMESTAMPDIFF(MINUTE, start, end) * 60)'
        );
    }

    protected function getBuckets(
        array $buckets,
        BuilderContract $basicQuery,
        string $groupBy,
        string $having,
        string $count
    ): array {
        $return = [];

        foreach ($buckets as $bucket) {
            $query = clone $basicQuery;
            $query->groupBy($groupBy);

            if ($bucket !== '+Inf') {
                $query->having($this->raw($having), '<=', $bucket);
            }

            $return[$bucket] = count($query->get($this->raw($count)));
        }

        return $return;
    }

    public function worklogSeconds(): int
    {
        return (int) Worklog::query()
            ->sum($this->raw('hours * 60 * 60'));
    }

    public function worklogBuckets(array $buckets): array
    {
        return $this->getBuckets(
            $buckets,
            Worklog::query(),
            'user_id',
            'SUM(hours * 60 * 60)',
            'SUM(hours * 60 * 60)'
        );
    }

    public function locations(): int
    {
        return Location::query()
            ->count();
    }

    public function shiftTypes(): int
    {
        return ShiftType::query()
            ->count();
    }

    public function volunteerTypesSum(): int
    {
        return VolunteerType::query()->count();
    }

    public function volunteerTypes(): array
    {
        $volunteerTypes = [];
        $rawVolunteerTypes = VolunteerType::query()->select(['id', 'name', 'restricted'])->orderBy('name')->get();
        foreach ($rawVolunteerTypes as $volunteerType) {
            $restricted = $volunteerType->restricted;
            $userVolunteerTypeQuery = UserVolunteerType::query()
                ->where('volunteer_type_id', $volunteerType->id);

            $members = $userVolunteerTypeQuery->count();
            $supporters = (clone $userVolunteerTypeQuery)->where('supporter', true)->count();
            $confirmed = $members - $supporters;
            $unconfirmed = 0;
            if ($restricted) {
                $confirmed = (clone $userVolunteerTypeQuery)->whereNotNull('confirm_user_id')->count() - $supporters;
                $unconfirmed = $members - ($supporters + $confirmed);
            }

            $volunteerTypes[] = [
                'name' => $volunteerType->name,
                'restricted' => $restricted,
                'unconfirmed' => $unconfirmed,
                'supporters' => $supporters,
                'confirmed' => $confirmed,
            ];
        }
        return $volunteerTypes;
    }

    public function shifts(): int
    {
        return Shift::query()->count();
    }

    public function announcements(?bool $meeting = null): int
    {
        $query = is_null($meeting) ? News::query() : News::whereIsMeeting($meeting);

        return $query->count();
    }

    public function comments(): int
    {
        return NewsComment::query()
            ->count();
    }

    public function questions(?bool $answered = null): int
    {
        $query = Question::query();
        if (!is_null($answered)) {
            if ($answered) {
                $query->whereNotNull('answerer_id');
            } else {
                $query->whereNull('answerer_id');
            }
        }

        return $query->count();
    }

    public function faq(): int
    {
        return Faq::query()->count();
    }

    public function messages(): int
    {
        return Message::query()->count();
    }

    public function sessions(): int
    {
        return $this
            ->getQuery('sessions')
            ->count();
    }

    public function oauth(): Collection
    {
        return OAuth::query()
            ->select(['provider', $this->raw('COUNT(provider) AS count')])
            ->groupBy(['provider'])
            ->get();
    }

    public function databaseRead(): float
    {
        $start = microtime(true);

        (new EventConfig())->findOrNew('last_metrics');

        return microtime(true) - $start;
    }

    public function databaseWrite(): float
    {
        $config = (new EventConfig())->findOrNew('last_metrics');
        $config
            ->setAttribute('name', 'last_metrics')
            ->setAttribute('value', new Carbon());

        $start = microtime(true);

        $config->save();

        return microtime(true) - $start;
    }

    public function logEntries(?string $level = null): int
    {
        $query = is_null($level) ? LogEntry::query() : LogEntry::whereLevel($level);

        return $query->count();
    }

    public function passwordResets(): int
    {
        return PasswordReset::query()->count();
    }

    protected function getQuery(string $table): QueryBuilder
    {
        return $this->db
            ->getConnection()
            ->table($table);
    }

    protected function raw(mixed $value): QueryExpression
    {
        return $this->db->getConnection()->raw($value);
    }
}
