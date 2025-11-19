<?php

declare(strict_types=1);

namespace Volunteersystem\Controllers\Admin;

use Volunteersystem\Controllers\BaseController;
use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Http\Request;
use Volunteersystem\Http\Response;
use Volunteersystem\Models\LogEntry;
use Volunteersystem\Models\User\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Psr\Log\LogLevel;

class LogsController extends BaseController
{
    /** @var array<string> */
    protected array $permissions = [
        'admin_log',
    ];

    protected array $levels = [
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::DEBUG,
        LogLevel::EMERGENCY,
        LogLevel::ERROR,
        LogLevel::INFO,
        LogLevel::NOTICE,
        LogLevel::WARNING,
    ];

    public function __construct(protected LogEntry $log, protected Response $response, protected Authenticator $auth)
    {
    }

    public function index(Request $request): Response
    {
        $searchUserId = (int) $request->input('search_user_id') ?: null;
        $search = $request->input('search');
        $level = $request->input('level');
        $userId = $this->auth->user()?->id;

        if ($this->auth->can('logs.all')) {
            $userId = $searchUserId;
        }

        if (!in_array($level, $this->levels)) {
            $level = null;
        }

        $entries = $this->log->filter($search, $userId, $level);

        /** @var Collection $users */
        $users = User::with('personalData')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (User $u) {
                return [$u->id => $u->displayName];
            });

        $levels = array_combine($this->levels, $this->levels);
        foreach ($levels as $k => $v) {
            $levels[$k] = Str::ucfirst($v);
        }

        return $this->response->withView(
            'admin/log.twig',
            [
                'entries' => $entries,
                'search' => $search,
                'users' => $users,
                'search_user_id' => $searchUserId,
                'level' => $level,
                'levels' => $levels,
            ]
        );
    }
}
