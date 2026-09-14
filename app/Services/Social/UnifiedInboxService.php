<?php

namespace App\Services\Social;

use App\Enums\InboxMessageStatus;
use App\Models\Agency;
use App\Models\InboxMessage;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UnifiedInboxService
{
    private const CACHE_TTL = 300;

    /**
     * Get unified inbox for an agency.
     */
    public function getInbox(Agency $agency, array $filters = []): array
    {
        $cacheKey = "inbox:{$agency->id}:".md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($agency, $filters) {
            $query = InboxMessage::where('agency_id', $agency->id);

            // Apply filters
            if (! empty($filters['platform'])) {
                $query->byPlatform($filters['platform']);
            }
            if (! empty($filters['type'])) {
                $query->byType($filters['type']);
            }
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (! empty($filters['search'])) {
                $query->where('content', 'like', '%'.$filters['search'].'%');
            }
            if (! empty($filters['date_from'])) {
                $query->whereDate('received_at', '>=', $filters['date_from']);
            }
            if (! empty($filters['date_to'])) {
                $query->whereDate('received_at', '<=', $filters['date_to']);
            }

            $messages = $query->orderBy('received_at', 'desc')
                ->paginate($filters['per_page'] ?? 25);

            return [
                'messages' => $messages,
                'unread_count' => $this->getUnreadCount($agency),
                'total_count' => $this->getTotalCount($agency),
                'by_platform' => $this->getByPlatform($agency),
                'by_type' => $this->getByType($agency),
            ];
        });
    }

    /**
     * Get unread message count.
     */
    public function getUnreadCount(Agency $agency): int
    {
        return Cache::remember("inbox:{$agency->id}:unread", self::CACHE_TTL, function () use ($agency) {
            return InboxMessage::where('agency_id', $agency->id)
                ->unread()
                ->count();
        });
    }

    /**
     * Get total message count.
     */
    public function getTotalCount(Agency $agency): int
    {
        return InboxMessage::where('agency_id', $agency->id)->count();
    }

    /**
     * Get message count by platform.
     */
    public function getByPlatform(Agency $agency): array
    {
        return Cache::remember("inbox:{$agency->id}:by_platform", self::CACHE_TTL, function () use ($agency) {
            return InboxMessage::where('agency_id', $agency->id)
                ->selectRaw('platform, count(*) as count')
                ->groupBy('platform')
                ->pluck('count', 'platform')
                ->toArray();
        });
    }

    /**
     * Get message count by type.
     */
    public function getByType(Agency $agency): array
    {
        return Cache::remember("inbox:{$agency->id}:by_type", self::CACHE_TTL, function () use ($agency) {
            return InboxMessage::where('agency_id', $agency->id)
                ->selectRaw('message_type, count(*) as count')
                ->groupBy('message_type')
                ->pluck('count', 'message_type')
                ->toArray();
        });
    }

    /**
     * Get a single message.
     */
    public function getMessage(Agency $agency, int $messageId): ?InboxMessage
    {
        return InboxMessage::where('agency_id', $agency->id)
            ->where('id', $messageId)
            ->first();
    }

    /**
     * Mark message as read.
     */
    public function markAsRead(Agency $agency, int $messageId): bool
    {
        $message = $this->getMessage($agency, $messageId);
        if (! $message) {
            return false;
        }

        InboxMessage::markRead($message);
        $this->clearCache($agency);

        return true;
    }

    /**
     * Mark all messages as read.
     */
    public function markAllAsRead(Agency $agency): int
    {
        $count = InboxMessage::where('agency_id', $agency->id)
            ->unread()
            ->update([
                'status' => InboxMessageStatus::READ->value,
                'read_at' => now(),
            ]);

        $this->clearCache($agency);

        return $count;
    }

    /**
     * Reply to a message.
     */
    public function replyToMessage(Agency $agency, int $messageId, string $content, ?User $user = null): bool
    {
        $message = $this->getMessage($agency, $messageId);
        if (! $message) {
            return false;
        }

        InboxMessage::markReplied($message, $content, $user);
        $this->clearCache($agency);

        return true;
    }

    /**
     * Delete a message.
     */
    public function deleteMessage(Agency $agency, int $messageId): bool
    {
        $message = $this->getMessage($agency, $messageId);
        if (! $message) {
            return false;
        }

        $message->delete();
        $this->clearCache($agency);

        return true;
    }

    /**
     * Get connected platforms for the agency.
     */
    public function getConnectedPlatforms(Agency $agency): array
    {
        return SocialAccount::where('agency_id', $agency->id)
            ->where('is_active', true)
            ->distinct('platform')
            ->pluck('platform')
            ->toArray();
    }

    /**
     * Clear inbox cache.
     */
    public function clearCache(Agency $agency): void
    {
        Cache::forget("inbox:{$agency->id}:unread");
        Cache::forget("inbox:{$agency->id}:by_platform");
        Cache::forget("inbox:{$agency->id}:by_type");
    }
}
