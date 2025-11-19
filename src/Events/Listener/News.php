<?php

declare(strict_types=1);

namespace Volunteersystem\Events\Listener;

use Volunteersystem\Mail\VolunteersystemMailer;
use Volunteersystem\Models\News as NewsModel;
use Volunteersystem\Models\User\Settings as UserSettings;
use Illuminate\Database\Eloquent\Collection;
use Psr\Log\LoggerInterface;

class News
{
    public function __construct(
        protected LoggerInterface $log,
        protected VolunteersystemMailer $mailer,
        protected UserSettings $settings
    ) {
    }

    public function created(NewsModel $news, bool $sendNotification = true): void
    {
        $this->sendMail($news, 'notification.news.new', 'emails/news-new', $sendNotification);
    }

    public function updated(NewsModel $news, bool $sendNotification = true): void
    {
        $this->sendMail($news, 'notification.news.updated', 'emails/news-updated', $sendNotification);
    }

    protected function sendMail(NewsModel $news, string $subject, string $template, bool $sendNotification = true): void
    {
        if (!$sendNotification) {
            return;
        }

        /** @var UserSettings[]|Collection $recipients */
        $recipients = $this->settings
            ->with('user.personalData')
            ->where('email_news', true)
            ->get();

        foreach ($recipients as $recipient) {
            $this->mailer->sendViewTranslated(
                $recipient->user,
                $subject,
                $template,
                ['title' => $news->title, 'news' => $news, 'username' => $recipient->user->displayName]
            );
        }
    }
}
