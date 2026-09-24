<?php

namespace Database\Factories;

use App\Models\Reseller;
use App\Models\WhiteLabelDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

class WhiteLabelDomainFactory extends Factory
{
    protected $model = WhiteLabelDomain::class;

    public function definition(): array
    {
        $domain = fake()->domainName();

        return [
            'reseller_id' => Reseller::factory(),
            'domain' => $domain,
            'is_verified' => false,
            'verification_token' => 'dms-verify=' . md5($domain . config('app.key')),
            'ssl_status' => WhiteLabelDomain::SSL_STATUS_PENDING,
            'status' => WhiteLabelDomain::STATUS_PENDING,
        ];
    }

    public function verified(): static
    {
        return $this->state([
            'is_verified' => true,
            'status' => WhiteLabelDomain::STATUS_ACTIVE,
            'ssl_status' => WhiteLabelDomain::SSL_STATUS_ACTIVE,
        ]);
    }

    public function active(): static
    {
        return $this->state([
            'status' => WhiteLabelDomain::STATUS_ACTIVE,
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'ssl_status' => WhiteLabelDomain::SSL_STATUS_FAILED,
        ]);
    }
}
