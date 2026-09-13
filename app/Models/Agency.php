<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Permission;
use App\Models\AiContentLog;
use App\Models\ActivityLog;

class Agency extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'email',
        'logo',
        'website',
        'description',
        'timezone',
        'currency',
        'phone',
        'address',
        'status',
        'subscription_plan',
        'subscription_start',
        'subscription_end',
        'subscription_status',
        'subscription_payment_method',
        'customer_id',
        'subscription_id',
        'posts_count',
        'ai_requests_count',
        'ai_generations_count',
        'campaigns_count',
        'clients_count',
        'users_count',
        'social_accounts_count',
        'landing_pages_count',
        'forms_count',
        'custom_settings',
        'branding',
    ];

    protected $casts = [
        'custom_settings' => 'array',
        'branding' => 'array',
        'subscription_start' => 'datetime',
        'subscription_end' => 'datetime',
    ];

    protected $appends = ['is_active'];

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && $this->subscription_status !== 'cancelled';
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function usersByRole(string $role): HasMany
    {
        return $this->users()->where('role', $role);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function aiContentLogs(): HasMany
    {
        return $this->hasMany(AiContentLog::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function hasFeature(string $featureCode): bool
    {
        $agency = $this;

        // Check if it's an unlimited plan
        if ($this->subscription_plan === 'enterprise') {
            return true;
        }

        // Check if agency has the feature enabled
        $feature = Permission::where('name', $featureCode)->first();
        if (! $feature) {
            return false;
        }

        return $this->hasPermissionTo($feature);
    }

    public function isFeatureAvailable(string $featureCode): bool
    {
        // Enterprise always has everything
        if ($this->subscription_plan === 'enterprise') {
            return true;
        }

        // Get plan features
        $plan = config("platform.plans.{$this->subscription_plan}");
        if (! $plan) {
            return false;
        }

        $features = $plan['features'] ?? [];

        return in_array($featureCode, $features);
    }

    public function getPlanConfig(): array
    {
        return config("platform.plans.{$this->subscription_plan}") ?? config('platform.plans.free');
    }

    public function canPublishPost(): bool
    {
        $plan = $this->getPlanConfig();

        if ($plan['posts_per_month'] === -1) {
            return true;
        }

        return $this->posts_count < $plan['posts_per_month'];
    }

    public function canGenerateAiContent(): bool
    {
        $plan = $this->getPlanConfig();

        if ($plan['ai_generations_per_month'] === -1) {
            return true;
        }

        return $this->ai_generations_count < $plan['ai_generations_per_month'];
    }

    public function canAddSocialAccount(): bool
    {
        $plan = $this->getPlanConfig();

        if ($plan['social_accounts'] === -1) {
            return true;
        }

        return $this->social_accounts_count < $plan['social_accounts'];
    }

    public function canAddCampaign(): bool
    {
        $plan = $this->getPlanConfig();

        if ($plan['campaigns'] === -1) {
            return true;
        }

        return $this->campaigns_count < $plan['campaigns'];
    }

    public function canAddClient(): bool
    {
        $plan = $this->getPlanConfig();

        if ($plan['clients'] === -1) {
            return true;
        }

        return $this->clients_count < $plan['clients'];
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByPlan($query, string $plan)
    {
        return $query->where('subscription_plan', $plan);
    }

    public function featureFlags(): HasMany
    {
        return $this->hasMany(FeatureFlag::class);
    }

    public function incrementCount(string $count): void
    {
        $this->increment($count);
    }

    public function decrementCount(string $count): void
    {
        $this->decrement($count);
    }
}
