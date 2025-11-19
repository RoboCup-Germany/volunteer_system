<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers;

use Volunteersystem\Http\Exceptions\HttpNotFound;
use Volunteersystem\Http\Request;
use Volunteersystem\Http\Response;
use Volunteersystem\Mail\VolunteersystemMailer;
use Volunteersystem\Models\User\PasswordReset;
use Volunteersystem\Models\User\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PasswordResetController extends BaseController
{
    use HasUserNotifications;

    /** @var array<string, string> */
    protected array $permissions = [
        'reset'             => 'login',
        'postReset'         => 'login',
        'resetPassword'     => 'login',
        'postResetPassword' => 'login',
    ];

    public function __construct(
        protected Response $response,
        protected SessionInterface $session,
        protected VolunteersystemMailer $mail,
        protected LoggerInterface $log
    ) {
    }

    public function reset(): Response
    {
        return $this->showView('pages/password/reset');
    }

    public function postReset(Request $request): Response
    {
        $data = $this->validate($request, [
            'email' => 'required|email',
        ]);

        /** @var User $user */
        $user = User::whereEmail($data['email'])->first();
        if ($user) {
            $reset = (new PasswordReset())->findOrNew($user->id);
            $reset->user_id = $user->id;
            $reset->token = bin2hex(random_bytes(16));
            $reset->save();

            $this->log->info(
                sprintf('Password recovery for %s (%u)', $user->name, $user->id),
                ['user' => $user->toJson()]
            );

            $this->mail->sendViewTranslated(
                $user,
                'Password recovery',
                'emails/password-reset',
                ['username' => $user->displayName, 'reset' => $reset]
            );
        }

        return $this->showView('pages/password/reset-success', ['type' => 'email']);
    }

    public function resetPassword(Request $request): Response
    {
        $this->requireToken($request);

        return $this->showView(
            'pages/password/reset-form',
            ['min_length' => config('password_min_length')]
        );
    }

    public function postResetPassword(Request $request): Response
    {
        $reset = $this->requireToken($request);

        $data = $this->validate($request, [
            'password'              => 'required|length:' . config('password_min_length'),
            'password_confirmation' => 'required',
        ]);

        if ($data['password'] !== $data['password_confirmation']) {
            $this->addNotification('validation.password.confirmed', NotificationType::ERROR);

            return $this->showView('pages/password/reset-form');
        }

        auth()->setPassword($reset->user, $data['password']);
        $reset->delete();

        $reset->user->sessions()->getQuery()->delete();

        return $this->showView('pages/password/reset-success', ['type' => 'reset']);
    }

    protected function showView(string $view = 'pages/password/reset', array $data = []): Response
    {
        return $this->response->withView($view, $data);
    }

    protected function requireToken(Request $request): PasswordReset
    {
        $token = $request->getAttribute('token');

        /** @var PasswordReset|null $reset */
        $reset = PasswordReset::whereToken($token)->first();

        if (!$reset) {
            throw new HttpNotFound();
        }

        return $reset;
    }
}
