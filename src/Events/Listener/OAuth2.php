<?php

declare(strict_types=1);

namespace Volunteersystem\Events\Listener;

use Volunteersystem\Config\Config;
use Volunteersystem\Helpers\Authenticator;
use Volunteersystem\Models\VolunteerType;
use Volunteersystem\Models\User\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

class OAuth2
{
    protected array $config;

    public function __construct(Config $config, protected LoggerInterface $log, protected Authenticator $auth)
    {
        $this->config = $config->get('oauth');
    }

    /**
     * @param string     $provider OAuth provider name
     * @param Collection $data OAuth userdata
     */
    public function login(string $event, string $provider, Collection $data): void
    {
        $user = $this->auth->user();
        $ssoTeams = $this->getSsoTeams($provider);
        $groupsKey = ($this->config[$provider] ?? [])['groups'] ?? 'groups';
        $userGroups = $data->get($groupsKey, []);

        foreach ($userGroups as $groupName) {
            if (!isset($ssoTeams[$groupName])) {
                continue;
            }

            $this->syncTeams($provider, $user, $ssoTeams[$groupName]);
        }
    }

    public function getSsoTeams(string $provider): array
    {
        $config = $this->config[$provider] ?? [];

        $teams = [];
        foreach ($config['teams'] ?? [] as $ssoName => $conf) {
            $conf = Arr::wrap($conf);
            $teamId = $conf['id'] ?? $conf[0];
            $isSupporter = $conf['supporter'] ?? false;

            $teams[$ssoName] = ['id' => $teamId, 'supporter' => $isSupporter];
        }

        return $teams;
    }

    protected function syncTeams(string $providerName, User $user, array $ssoTeam): void
    {
        $currentUserVolunteertypes = $user->userVolunteerTypes;
        $volunteerType = VolunteerType::find($ssoTeam['id']);
        /** @var VolunteerType $userVolunteertype */
        $userVolunteertype = $currentUserVolunteertypes->where('pivot.volunteer_type_id', $ssoTeam['id'])->first();
        $supporter = $ssoTeam['supporter'];
        $confirmed = $supporter ? $user->id : null;

        if (!$userVolunteertype) {
            $this->log->info(
                'SSO {provider}: Added to volunteer type {volunteertype}, confirmed: {confirmed}, supporter: {supporter}',
                [
                    'provider'  => $providerName,
                    'volunteertype' => $volunteerType->name,
                    'confirmed' => $confirmed ? 'yes' : 'no',
                    'supporter' => $supporter ? 'yes' : 'no',
                ]
            );

            $user->userVolunteerTypes()->attach($volunteerType, ['supporter' => $supporter, 'confirm_user_id' => $confirmed]);

            return;
        }

        if (!$supporter) {
            return;
        }

        if ($userVolunteertype->pivot->supporter != $supporter) {
            $userVolunteertype->pivot->supporter = $supporter;
            $userVolunteertype->pivot->save();

            $this->log->info(
                'SSO {provider}: Set supporter state for volunteertype {volunteertype}',
                [
                    'provider'  => $providerName,
                    'volunteertype' => $userVolunteertype->pivot->volunteerType->name,
                ]
            );
        }

        if (!$userVolunteertype->pivot->confirm_user_id) {
            $userVolunteertype->pivot->confirmUser()->associate($user);
            $userVolunteertype->pivot->save();
            $this->log->info(
                'SSO {provider}: Set confirmed state for volunteertype {volunteertype}',
                [
                    'provider'  => $providerName,
                    'volunteertype' => $userVolunteertype->pivot->volunteerType->name,
                ]
            );
        }
    }
}
