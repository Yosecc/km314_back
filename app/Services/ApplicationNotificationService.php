<?php

namespace App\Services;

use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Delivers one application event through both supported channels:
 * Filament's database inbox and Firebase push notifications.
 *
 * Administrative recipients are resolved from Shield permissions. Roles are
 * deliberately not used here as an access shortcut.
 */
class ApplicationNotificationService
{
    /**
     * @param iterable<User>|User $recipients
     * @param array<string, scalar|null> $data
     */
    public function send(iterable|User $recipients, string $title, string $body, array $data = [], ?string $url = null, string $icon = 'heroicon-o-bell'): void
    {
        $users = $recipients instanceof User
            ? collect([$recipients])
            : collect($recipients);

        $users
            ->filter(fn ($user) => $user instanceof User && $user->exists)
            ->unique('id')
            ->each(function (User $user) use ($title, $body, $data, $url, $icon): void {
                try {
                    $notification = Notification::make()
                        ->title($title)
                        ->body($body)
                        ->icon($icon);

                    if ($url) {
                        $notification->actions([
                            Action::make('ver')
                                ->label('Ver')
                                ->url($url),
                        ]);
                    }

                    $notification->sendToDatabase($user);
                } catch (\Throwable $exception) {
                    Log::warning('No se pudo guardar una notificación de aplicación.', [
                        'user_id' => $user->id,
                        'title' => $title,
                        'exception' => $exception->getMessage(),
                    ]);
                }

                app(FirebaseCloudMessaging::class)->sendToUser($user, $title, $body, $data);
            });
    }

    /**
     * Finds users who have one of the supplied Shield permissions, whether it
     * was assigned directly or through a role.
     *
     * @param array<int, string> $permissions
     * @return Collection<int, User>
     */
    public function usersWithAnyPermission(array $permissions): Collection
    {
        $permissions = array_values(array_filter($permissions));

        if ($permissions === []) {
            return collect();
        }

        return User::query()
            ->where(function (Builder $query) use ($permissions): void {
                $query->whereHas('permissions', fn (Builder $permissionQuery) => $permissionQuery->whereIn('name', $permissions))
                    ->orWhereHas('roles.permissions', fn (Builder $permissionQuery) => $permissionQuery->whereIn('name', $permissions));
            })
            ->get();
    }

    /**
     * @param array<int, string> $permissions
     * @param array<string, scalar|null> $data
     */
    public function sendToPermissionHolders(array $permissions, string $title, string $body, array $data = [], ?string $url = null, string $icon = 'heroicon-o-bell'): void
    {
        $this->send($this->usersWithAnyPermission($permissions), $title, $body, $data, $url, $icon);
    }

    /**
     * Sends an operational alert to Shield-authorized backoffice users. Owners
     * can have resource permissions to manage their own data, but must not
     * receive the administrative review alert that they originated.
     *
     * @param array<int, string> $permissions
     * @param array<string, scalar|null> $data
     */
    public function sendToAdministrativePermissionHolders(array $permissions, string $title, string $body, array $data = [], ?string $url = null, string $icon = 'heroicon-o-bell'): void
    {
        $recipients = $this->usersWithAnyPermission($permissions)
            ->reject(fn (User $user) => $user->hasRole('owner'));

        $this->send($recipients, $title, $body, $data, $url, $icon);
    }
}
