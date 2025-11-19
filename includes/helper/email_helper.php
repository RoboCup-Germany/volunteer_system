<?php

use Volunteersystem\Mail\VolunteersystemMailer;
use Volunteersystem\Models\User\User;

/**
 * @param User   $recipientUser
 * @param string $title
 * @param string $message
 * @param bool   $notIfItsMe
 * @return bool
 */
function volunteersystem_email_to_user($recipientUser, $title, $message, $notIfItsMe = false)
{
    if ($notIfItsMe && auth()->user()->id == $recipientUser->id) {
        return true;
    }

    /** @var VolunteersystemMailer $mailer */
    $mailer = app('mailer');
    $status = $mailer->sendViewTranslated(
        $recipientUser,
        $title,
        'emails/mail',
        ['username' => $recipientUser->displayName, 'message' => $message]
    );

    if (!$status) {
        error(sprintf(__('User %s could not be notified by e-mail due to an error.'), $recipientUser->displayName));
        volunteersystem_log(sprintf('User %s could not be notified by e-mail due to an error.', $recipientUser->name));
    }

    return $status;
}
