<?php

declare(strict_types=1);

namespace Volunteersystem\Events\Listener;

use Carbon\Carbon;
use Volunteersystem\Mail\VolunteersystemMailer;
use Volunteersystem\Models\Shifts\Shift;
use Volunteersystem\Models\Shifts\ShiftEntry;
use Volunteersystem\Models\Worklog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

class Shifts
{
    public function __construct(
        protected LoggerInterface $log,
        protected VolunteersystemMailer $mailer
    ) {
    }

    public function deletingCreateWorklogs(Shift $shift): void
    {
        foreach ($shift->shiftEntries as $entry) {
            if ($entry->freeloaded_by || $shift->start > Carbon::now()) {
                continue;
            }

            $worklog = new Worklog();
            $worklog->user()->associate($entry->user);
            $worklog->creator()->associate(auth()->user());
            $worklog->worked_at = $shift->start->copy()->startOfDay();
            $worklog->hours =
                (($shift->end->timestamp - $shift->start->timestamp) / 60 / 60)
                * $shift->getNightShiftMultiplier();
            $worklog->description = Str::substr(sprintf(
                __('%s (%s as %s) in %s, %s - %s'),
                $shift->shiftType->name,
                $shift->title,
                $entry->volunteerType->name,
                $shift->location->name,
                $shift->start->format(__('general.datetime')),
                $shift->end->format(__('general.datetime'))
            ), 0, 200);
            $worklog->save();

            $this->log->info(
                'Created worklog entry from shift for {user} ({uid}): {worklog})',
                ['user' => $worklog->user->name, 'uid' => $worklog->user->id, 'worklog' => $worklog->description]
            );
        }
    }

    public function deletingSendEmails(Shift $shift): void
    {
        foreach ($shift->shiftEntries as $entry) {
            if (!$entry->user->settings->email_shiftinfo) {
                continue;
            }

            $this->mailer->sendViewTranslated(
                $entry->user,
                'notification.shift.deleted',
                'emails/worklog-from-shift',
                [
                    'shift' => $shift,
                    'entry' => $entry,
                    'username' => $entry->user->displayName,
                ]
            );
        }
    }

    public function updatedSendEmail(Shift $shift, Shift $oldShift): void
    {
        // Only send e-mail on relevant changes
        if (
            $oldShift->shift_type_id == $shift->shift_type_id
            && $oldShift->title == $shift->title
            && $oldShift->start == $shift->start
            && $oldShift->end == $shift->end
            && $oldShift->location_id == $shift->location_id
        ) {
            return;
        }

        $shift->load(['shiftType', 'location']);
        $oldShift->load(['shiftType', 'location']);
        /** @var ShiftEntry[]|Collection $shiftEntries */
        $shiftEntries = $shift->shiftEntries()
            ->with(['volunteerType', 'user.settings'])
            ->get();

        foreach ($shiftEntries as $shiftEntry) {
            $user = $shiftEntry->user;
            $volunteerType = $shiftEntry->volunteerType;

            if (
                !$user->settings->email_shiftinfo
                || $shift->end < Carbon::now() && $oldShift->end < Carbon::now()
            ) {
                continue;
            }

            $this->mailer->sendViewTranslated(
                $user,
                'notification.shift.updated',
                'emails/updated-shift',
                [
                    'shift' => $shift,
                    'oldShift' => $oldShift,
                    'volunteerType' => $volunteerType,
                    'username' => $user->displayName,
                ]
            );
        }
    }
}
