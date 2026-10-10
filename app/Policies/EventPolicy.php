<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * イベントの主催者だけが、イベントを編集できる。
     */
    public function update(User $user, Event $event): bool
    {
        return $event->isOwnedBy($user);
    }

    /**
     * イベントの主催者だけが、イベントを削除できる。
     */
    public function delete(User $user, Event $event): bool
    {
        return $event->isOwnedBy($user);
    }

    /**
     * イベントの主催者だけが、イベントを複製できる。
     */
    public function duplicate(User $user, Event $event): bool
    {
        return $event->isOwnedBy($user);
    }
}
