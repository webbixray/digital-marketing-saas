<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditObserver
{
    public function created(Model $model): void
    {
        $this->log('created', $model);
    }

    public function updated(Model $model): void
    {
        $this->log('updated', $model, $model->getChanges());
    }

    public function deleted(Model $model): void
    {
        $this->log('deleted', $model);
    }

    public function restored(Model $model): void
    {
        $this->log('restored', $model);
    }

    private function log(string $action, Model $model, array $changes = []): void
    {
        $user = Auth::user();

        ActivityLog::create([
            'agency_id' => $model->agency_id ?? $user?->agency_id,
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'changes' => $changes ? json_encode($changes) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
