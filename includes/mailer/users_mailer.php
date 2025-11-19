<?php

use Volunteersystem\Models\User\User;

/**
 * @param User $user
 * @return bool
 */
function mail_user_delete($user)
{
    return volunteersystem_email_to_user(
        $user,
        __('Your account has been deleted.'),
        __(
            'Your %s account has been deleted. If you have any questions regarding your account deletion, please contact heaven.',
            [config('app_name')]
        )
    );
}
