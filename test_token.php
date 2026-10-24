<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$u = \App\Models\User::where("email", "uat20261024-052450@brightagency.test")->first();
$token = $u->createToken("debug")->plainTextToken;

$request = \Illuminate\Http\Request::create("/api/v1/agency/billing", "GET");
$request->headers->set("Authorization", "Bearer $token");

$personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
echo "PAT found: " . ($personalAccessToken ? "yes" : "no") . "\n";
if ($personalAccessToken) {
    $tokenUser = $personalAccessToken->tokenable;
    echo "Tokenable user: " . $tokenUser->email . "\n";
    echo "Tokenable role attr: " . var_export($tokenUser->role, true) . "\n";
    echo "Tokenable original role: " . var_export($tokenUser->getOriginal("role"), true) . "\n";
    echo "Tokenable attributes keys: " . implode(",", array_keys($tokenUser->getAttributes())) . "\n";
}

// Now run through kernel
$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Body: " . $response->getContent() . "\n";