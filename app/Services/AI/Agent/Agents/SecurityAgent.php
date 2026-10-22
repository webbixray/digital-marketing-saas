<?php

namespace App\Services\AI\Agent\Agents;

use App\Http\Controllers\Auth\TwoFactorController;
use App\Models\Agency;
use App\Models\User;
use App\Services\AI\Agent\AbstractAgent;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentResult;
use App\Services\AI\Agent\AgentTask;
use App\Services\AI\Gateway\AiRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class SecurityAgent extends AbstractAgent
{
    protected string $name = 'security_agent';

    /**
     * @var array<string>
     */
    protected array $supportedTaskTypes = [
        'security_audit',
        'input_scan',
        'auth_check',
        'vulnerability_scan',
    ];

    /**
     * Common vulnerability patterns to scan for.
     */
    private const SUSPICIOUS_PATTERNS = [
        'unvalidated_request' => ['\$request->input\(', '\$request->get\(', '\$request->all\(\)', '\$request->only\(', '\$request->except\('],
        'raw_sql' => ['DB::raw\(', 'DB::select\(', 'whereRaw\(', 'orderByRaw\('],
        'eval_usage' => ['eval\(', 'exec\(', 'system\(', 'passthru\(', 'shell_exec\('],
        'unserialized' => ['unserialize\('],
        'file_inclusion' => ['include\(', 'require\(', 'include_once\(', 'require_once\('],
        'hardcoded_secrets' => ['password\s*=\s*[\'"]', 'secret\s*=\s*[\'"]', 'api_key\s*=\s*[\'"]', 'token\s*=\s*[\'"]', 'PRIVATE KEY'],
        'xss_risk' => ['echo\s+\$', '{{{', '\{\{\!\!', 'raw\('],
        'csrf_risk' => ['csrf', 'token', 'verify'],
        'mass_assignment' => ['::create\(\$', '::update\(\$', 'fill\(\$'],
    ];

    /**
     * {@inheritdoc}
     */
    public function execute(AgentTask $task, AgentContext $context): AgentResult
    {
        $startTime = microtime(true);

        if (! $this->canHandle($task->type)) {
            return AgentResult::failure(
                taskId: $task->id,
                agentName: $this->name,
                error: "Unsupported task type: {$task->type}"
            );
        }

        $agency = $context->agency;

        try {
            $result = match ($task->type) {
                'security_audit' => $this->handleSecurityAudit($task, $context),
                'input_scan' => $this->handleInputScan($task, $context),
                'auth_check' => $this->handleAuthCheck($task, $context),
                'vulnerability_scan' => $this->handleVulnerabilityScan($task, $context),
                default => null,
            };

            if ($result === null) {
                return AgentResult::failure(
                    taskId: $task->id,
                    agentName: $this->name,
                    error: "Failed to execute task: {$task->type}"
                );
            }

            $executionTime = (microtime(true) - $startTime) * 1000;
            $result = new AgentResult(
                taskId: $result->taskId,
                agentName: $result->agentName,
                success: $result->success,
                output: $result->output,
                costUsd: $result->costUsd,
                tokensUsed: $result->tokensUsed,
                executionTimeMs: $executionTime,
                error: $result->error,
                metadata: $result->metadata,
                timestamp: $result->timestamp
            );

            $this->recordExecution($task->type, $result);
            $this->persistAgencyResults($context, $task->type, $result);

            return $result;
        } catch (\Exception $e) {
            Log::error("SecurityAgent execution failed: {$e->getMessage()}", [
                'task_id' => $task->id,
                'task_type' => $task->type,
            ]);

            $executionTime = (microtime(true) - $startTime) * 1000;
            $result = AgentResult::failure(
                taskId: $task->id,
                agentName: $this->name,
                error: $e->getMessage(),
                metadata: ['task_type' => $task->type]
            );

            $this->recordExecution($task->type, $result);

            return $result;
        }
    }

    /**
     * Get the security vulnerability discovery rate.
     */
    public function getVulnerabilityDiscoveryRate(): float
    {
        $findings = $this->executionStats['vulnerability_findings'] ?? [];

        if (empty($findings)) {
            return 0.0;
        }

        $highSeverity = count(array_filter($findings, fn ($f) => ($f['severity'] ?? '') === 'high'));
        $mediumSeverity = count(array_filter($findings, fn ($f) => ($f['severity'] ?? '') === 'medium'));

        // Weighted by severity
        return min(($highSeverity * 2 + $mediumSeverity) / count($findings), 1.0);
    }

    /**
     * Get learned vulnerability patterns.
     *
     * @return array<string>
     */
    public function getLearnedVulnerabilityPatterns(): array
    {
        return $this->executionStats['learned_vulnerability_patterns'] ?? [];
    }

    /**
     * Handle comprehensive security audit.
     */
    private function handleSecurityAudit(AgentTask $task, AgentContext $context): AgentResult
    {
        $auditScope = $task->data['scope'] ?? 'all';
        $auditResults = [
            'timestamp' => now()->toIso8601String(),
            'scope' => $auditScope,
            'findings' => [],
            'summary' => [],
        ];

        // Run all audit checks
        $auditResults['findings']['auth'] = $this->auditAuthentication();
        $auditResults['findings']['input_validation'] = $this->auditInputValidation();
        $auditResults['findings']['middleware'] = $this->auditMiddlewareCoverage();
        $auditResults['findings']['tenant_isolation'] = $this->auditTenantIsolation();
        $auditResults['findings']['vulnerabilities'] = $this->auditVulnerabilities();

        // Calculate risk score
        $totalFindings = 0;
        $highRisk = 0;
        $mediumRisk = 0;
        $lowRisk = 0;

        foreach ($auditResults['findings'] as $category => $findings) {
            if (is_array($findings)) {
                foreach ($findings as $finding) {
                    $totalFindings++;
                    $severity = $finding['severity'] ?? 'low';
                    match ($severity) {
                        'high', 'critical' => $highRisk++,
                        'medium' => $mediumRisk++,
                        default => $lowRisk++,
                    };
                }
            }
        }

        $riskScore = $totalFindings > 0 ? (($highRisk * 10) + ($mediumRisk * 5) + ($lowRisk * 1)) / $totalFindings : 0;

        $auditResults['summary'] = [
            'total_findings' => $totalFindings,
            'high_risk' => $highRisk,
            'medium_risk' => $mediumRisk,
            'low_risk' => $lowRisk,
            'risk_score' => min($riskScore, 100),
            'risk_level' => $riskScore >= 70 ? 'critical' : ($riskScore >= 40 ? 'high' : ($riskScore >= 20 ? 'medium' : 'low')),
        ];

        // Record findings
        $this->recordVulnerabilityFindings($auditResults['findings']);

        $meta = [
            'scope' => $auditScope,
            'total_findings' => $totalFindings,
            'risk_level' => $auditResults['summary']['risk_level'],
            'risk_score' => $auditResults['summary']['risk_score'],
        ];

        return AgentResult::success(
            taskId: $task->id,
            agentName: $this->name,
            output: json_encode($auditResults, JSON_PRETTY_PRINT),
            costUsd: 0,
            tokensUsed: 0,
            executionTimeMs: 0,
            metadata: $meta,
        );
    }

    /**
     * Handle input validation scanning.
     */
    private function handleInputScan(AgentTask $task, AgentContext $context): AgentResult
    {
        $targetControllers = $task->data['controllers'] ?? [];
        $scanResults = [
            'scanned_controllers' => [],
            'findings' => [],
            'stats' => [
                'total_methods' => 0,
                'unvalidated_methods' => 0,
            ],
        ];

        $controllers = $this->getTargetControllers($targetControllers);

        foreach ($controllers as $controllerClass) {
            $result = $this->scanControllerForUnvalidatedInputs($controllerClass);
            $scanResults['scanned_controllers'][] = $controllerClass;
            $scanResults['stats']['total_methods'] += $result['total_methods'];
            $scanResults['stats']['unvalidated_methods'] += $result['unvalidated_methods'];
            $scanResults['findings'] = array_merge($scanResults['findings'], $result['findings']);
        }

        // Learn new patterns
        $this->learnVulnerabilityPatterns($scanResults['findings']);

        $meta = [
            'controllers_scanned' => count($scanResults['scanned_controllers']),
            'total_methods' => $scanResults['stats']['total_methods'],
            'unvalidated_methods' => $scanResults['stats']['unvalidated_methods'],
            'findings_count' => count($scanResults['findings']),
        ];

        return AgentResult::success(
            taskId: $task->id,
            agentName: $this->name,
            output: json_encode($scanResults, JSON_PRETTY_PRINT),
            costUsd: 0,
            tokensUsed: 0,
            executionTimeMs: 0,
            metadata: $meta,
        );
    }

    /**
     * Handle authentication and authorization check.
     */
    private function handleAuthCheck(AgentTask $task, AgentContext $context): AgentResult
    {
        $checkScope = $task->data['scope'] ?? 'routes';

        $results = [
            'scope' => $checkScope,
            'route_protection' => $this->checkRouteProtection(),
            'middleware_gaps' => $this->detectMiddlewareGaps(),
            'permission_issues' => $this->checkPermissionConfiguration(),
            'recommendations' => [],
        ];

        // Generate recommendations based on findings
        if (! empty($results['middleware_gaps'])) {
            foreach ($results['middleware_gaps'] as $gap) {
                $results['recommendations'][] = [
                    'severity' => 'high',
                    'issue' => "Route group '{$gap['group']}' lacks 'auth' middleware",
                    'recommendation' => "Add 'auth' middleware to routes in group '{$gap['group']}'",
                ];
            }
        }

        if (! empty($results['permission_issues'])) {
            foreach ($results['permission_issues'] as $issue) {
                $results['recommendations'][] = [
                    'severity' => 'medium',
                    'issue' => $issue['description'],
                    'recommendation' => $issue['recommendation'],
                ];
            }
        }

        $totalIssues = count($results['middleware_gaps']) + count($results['permission_issues']);

        $meta = [
            'scope' => $checkScope,
            'middleware_gaps' => count($results['middleware_gaps']),
            'permission_issues' => count($results['permission_issues']),
            'total_issues' => $totalIssues,
        ];

        return AgentResult::success(
            taskId: $task->id,
            agentName: $this->name,
            output: json_encode($results, JSON_PRETTY_PRINT),
            costUsd: 0,
            tokensUsed: 0,
            executionTimeMs: 0,
            metadata: $meta,
        );
    }

    /**
     * Handle vulnerability scanning with AI-powered analysis.
     */
    private function handleVulnerabilityScan(AgentTask $task, AgentContext $context): AgentResult
    {
        $scanResults = [
            'static_analysis' => $this->performStaticAnalysis(),
            'pattern_matches' => $this->matchKnownPatterns(),
            'learned_patterns' => $this->matchLearnedPatterns(),
        ];

        // Use AI to analyze patterns and identify potential issues
        $findingsForAi = $this->prepareFindingsForAiAnalysis($scanResults);

        if (! empty($findingsForAi)) {
            $prompt = "Review the following code patterns and identify potential security vulnerabilities:\n\n";
            $prompt .= json_encode($findingsForAi, JSON_PRETTY_PRINT);
            $prompt .= "\n\nFor each finding, provide:\n";
            $prompt .= "1. Vulnerability type\n";
            $prompt .= "2. Severity (critical/high/medium/low)\n";
            $prompt .= "3. Potential impact\n";
            $prompt .= "4. Recommended fix\n";
            $prompt .= "5. CWE ID if applicable\n\n";
            $prompt .= 'Return as a JSON array of findings.';

            try {
                $request = AiRequest::analysis(
                    prompt: $prompt,
                    systemPrompt: 'You are a security expert who analyzes code for vulnerabilities. You identify security risks and provide actionable remediation advice.'
                );

                // Use a generic agency context if none available
                $agency = $context->agency;
                if ($agency) {
                    $response = $this->callAi($request, $agency);
                    $scanResults['ai_analysis'] = $this->parseJsonResponse($response->content);
                } else {
                    $scanResults['ai_analysis'] = ['skipped' => 'No agency context for AI call'];
                }
            } catch (\Exception $e) {
                $scanResults['ai_analysis'] = ['error' => $e->getMessage()];
            }
        }

        // Combine all findings
        $allFindings = array_merge(
            $scanResults['static_analysis'],
            $scanResults['pattern_matches'],
            $scanResults['learned_patterns'],
            $scanResults['ai_analysis'] ?? []
        );

        // Learn from new patterns
        $this->learnVulnerabilityPatterns($allFindings);
        $this->recordVulnerabilityFindings(['scan' => $allFindings]);

        $meta = [
            'static_findings' => count($scanResults['static_analysis']),
            'pattern_matches' => count($scanResults['pattern_matches']),
            'learned_matches' => count($scanResults['learned_patterns']),
            'total_findings' => count($allFindings),
            'scan_timestamp' => now()->toIso8601String(),
        ];

        return AgentResult::success(
            taskId: $task->id,
            agentName: $this->name,
            output: json_encode($scanResults, JSON_PRETTY_PRINT),
            costUsd: 0,
            tokensUsed: 0,
            executionTimeMs: 0,
            metadata: $meta,
        );
    }

    /**
     * Scan a controller for unvalidated inputs.
     */
    private function scanControllerForUnvalidatedInputs(string $controllerClass): array
    {
        $result = [
            'total_methods' => 0,
            'unvalidated_methods' => 0,
            'findings' => [],
        ];

        if (! class_exists($controllerClass)) {
            return $result;
        }

        try {
            $reflection = new ReflectionClass($controllerClass);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            $filename = $reflection->getFileName();

            if (! $filename || ! file_exists($filename)) {
                return $result;
            }

            $content = file_get_contents($filename);
            $lines = explode("\n", $content);

            foreach ($methods as $method) {
                if ($method->class !== $controllerClass) {
                    continue;
                }

                $result['total_methods']++;
                $methodStartLine = $method->getStartLine();
                $methodEndLine = $method->getEndLine();

                if (! $methodStartLine || ! $methodEndLine) {
                    continue;
                }

                $methodContent = implode("\n", array_slice($lines, $methodStartLine - 1, $methodEndLine - $methodStartLine + 1));

                $hasRequestInput = str_contains($methodContent, '$request') &&
                    (str_contains($methodContent, 'input(') || str_contains($methodContent, 'get(') || str_contains($methodContent, 'all('));

                $hasValidation = str_contains($methodContent, 'validate(') ||
                    str_contains($methodContent, '$request->validate(') ||
                    str_contains($methodContent, 'FormRequest') ||
                    str_contains($methodContent, 'Rule::');

                if ($hasRequestInput && ! $hasValidation) {
                    $result['unvalidated_methods']++;
                    $result['findings'][] = [
                        'controller' => $controllerClass,
                        'method' => $method->getName(),
                        'line' => $methodStartLine,
                        'severity' => 'medium',
                        'type' => 'unvalidated_input',
                        'description' => "Method '{$method->getName()}' uses \$request input without validation",
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning("SecurityAgent: failed to scan controller {$controllerClass}: {$e->getMessage()}");
        }

        return $result;
    }

    /**
     * Check route protection middleware.
     */
    private function checkRouteProtection(): array
    {
        $routes = Route::getRoutes();
        $unprotectedRoutes = [];

        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();
            $uri = $route->uri();
            $methods = $route->methods();

            // Skip non-HTTP routes
            if (in_array('HEAD', $methods) && count($methods) === 1) {
                continue;
            }

            // Skip public routes
            if (Str::startsWith($uri, ['login', 'register', 'password', 'oauth', '_debugbar', 'sanctum'])) {
                continue;
            }

            $hasAuth = false;
            $hasApiAuth = false;

            foreach ($middleware as $m) {
                if (Str::contains($m, ['auth', 'authenticate'])) {
                    $hasAuth = true;
                }
                if (Str::contains($m, ['auth:sanctum', 'auth:api', 'api'])) {
                    $hasApiAuth = true;
                }
            }

            if (! $hasAuth && ! $hasApiAuth) {
                $unprotectedRoutes[] = [
                    'uri' => $uri,
                    'methods' => $methods,
                    'middleware' => $middleware,
                    'severity' => 'high',
                ];
            }
        }

        return $unprotectedRoutes;
    }

    /**
     * Detect middleware gaps in route groups.
     */
    private function detectMiddlewareCoverage(): array
    {
        $gaps = [];

        $routes = Route::getRoutes();
        $groupStack = [];

        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();
            $uri = $route->uri();

            // Check for missing tenant isolation middleware
            if (Str::startsWith($uri, 'api/') && ! in_array('tenant', $middleware)) {
                $gaps[] = [
                    'type' => 'missing_tenant_middleware',
                    'route' => $uri,
                    'severity' => 'medium',
                ];
            }
        }

        return $gaps;
    }

    private function detectMiddlewareGaps(): array
    {
        return $this->detectMiddlewareCoverage();
    }

    /**
     * Check permission configuration.
     */
    private function checkPermissionConfiguration(): array
    {
        $issues = [];

        // Check if spatie permissions table exists
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            $issues[] = [
                'description' => 'Permission tables are missing. Role-based access control is not configured.',
                'recommendation' => 'Run migrations for spatie/laravel-permission package.',
            ];
        }

        // Check if User model has HasRoles trait
        try {
            $userReflection = new ReflectionClass(User::class);
            $usesTraits = $userReflection->getTraitNames();

            if (! in_array('Spatie\\Permission\\Traits\\HasRoles', $usesTraits)) {
                $issues[] = [
                    'description' => 'User model is missing HasRoles trait.',
                    'recommendation' => 'Add "use HasRoles;" trait to User model.',
                ];
            }
        } catch (\Exception $e) {
            // User model not found or error
        }

        return $issues;
    }

    /**
     * Audit input validation across controllers.
     */
    private function auditInputValidation(): array
    {
        $findings = [];
        $controllers = $this->getTargetControllers();

        foreach ($controllers as $controller) {
            $result = $this->scanControllerForUnvalidatedInputs($controller);
            if ($result['unvalidated_methods'] > 0) {
                $findings = array_merge($findings, $result['findings']);
            }
        }

        return $findings;
    }

    /**
     * Audit authentication configuration.
     */
    private function auditAuthentication(): array
    {
        $findings = [];

        // Check if auth middleware is applied globally
        $routes = Route::getRoutes();
        $protectedCount = 0;
        $unprotectedCount = 0;

        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware();
            $uri = $route->uri();

            if (Str::startsWith($uri, ['login', 'register', 'password', 'oauth', '_debugbar', 'sanctum', 'health'])) {
                continue;
            }

            if (empty($middleware)) {
                $unprotectedCount++;
            } else {
                $protectedCount++;
            }
        }

        if ($unprotectedCount > $protectedCount) {
            $findings[] = [
                'severity' => 'high',
                'type' => 'insufficient_auth_coverage',
                'description' => "More unprotected routes ({$unprotectedCount}) than protected ones ({$protectedCount})",
                'recommendation' => 'Apply auth middleware globally and exclude only public routes.',
            ];
        }

        // Check for 2FA configuration
        if (! class_exists(TwoFactorController::class)) {
            $findings[] = [
                'severity' => 'medium',
                'type' => 'missing_2fa',
                'description' => 'Two-factor authentication is not implemented',
                'recommendation' => 'Consider implementing 2FA for enhanced security.',
            ];
        }

        return $findings;
    }

    /**
     * Audit middleware coverage.
     */
    private function auditMiddlewareCoverage(): array
    {
        $findings = [];
        $gaps = $this->detectMiddlewareGaps();

        foreach ($gaps as $gap) {
            $findings[] = [
                'severity' => $gap['severity'] ?? 'medium',
                'type' => $gap['type'] ?? 'unknown',
                'description' => "Middleware gap detected: {$gap['type']} on route {$gap['route']}",
                'recommendation' => "Add appropriate middleware to route {$gap['route']}",
            ];
        }

        return $findings;
    }

    /**
     * Audit tenant isolation (agency_id scoping).
     */
    private function auditTenantIsolation(): array
    {
        $findings = [];

        // Check if models have agency scoping
        $models = $this->getAgencyScopedModels();
        foreach ($models as $model) {
            if (! $this->modelHasAgencyScope($model)) {
                $findings[] = [
                    'severity' => 'critical',
                    'type' => 'missing_tenant_scope',
                    'description' => "Model {$model} does not enforce agency_id scoping",
                    'recommendation' => "Add global scope or middleware to enforce agency_id filtering on {$model}.",
                ];
            }
        }

        return $findings;
    }

    /**
     * Audit for common vulnerabilities.
     */
    private function auditVulnerabilities(): array
    {
        return $this->performStaticAnalysis();
    }

    /**
     * Perform static analysis on controllers.
     */
    private function performStaticAnalysis(): array
    {
        $findings = [];
        $controllers = $this->getTargetControllers();

        foreach ($controllers as $controller) {
            if (! class_exists($controller)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($controller);
                $filename = $reflection->getFileName();

                if (! $filename || ! file_exists($filename)) {
                    continue;
                }

                $content = file_get_contents($filename);

                // Check for raw SQL usage
                if (preg_match('/DB::raw\(|whereRaw\(|orderByRaw\(/', $content)) {
                    $findings[] = [
                        'severity' => 'medium',
                        'type' => 'raw_sql',
                        'file' => $filename,
                        'description' => 'Raw SQL usage detected - potential SQL injection risk',
                    ];
                }

                // Check for eval/exec
                if (preg_match('/eval\(|exec\(|system\(/', $content)) {
                    $findings[] = [
                        'severity' => 'critical',
                        'type' => 'code_execution',
                        'file' => $filename,
                        'description' => 'Dangerous function usage detected (eval/exec/system)',
                    ];
                }

                // Check for mass assignment
                if (preg_match('/::create\(\$|->fill\(\$request->all/', $content)) {
                    $findings[] = [
                        'severity' => 'medium',
                        'type' => 'mass_assignment',
                        'file' => $filename,
                        'description' => 'Potential mass assignment vulnerability - validate fillable fields',
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("SecurityAgent: failed to analyze {$controller}: {$e->getMessage()}");
            }
        }

        return $findings;
    }

    /**
     * Match known vulnerability patterns.
     */
    private function matchKnownPatterns(): array
    {
        $matches = [];
        $controllers = $this->getTargetControllers();

        foreach ($controllers as $controller) {
            if (! class_exists($controller)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($controller);
                $filename = $reflection->getFileName();

                if (! $filename || ! file_exists($filename)) {
                    continue;
                }

                $content = file_get_contents($filename);

                foreach (self::SUSPICIOUS_PATTERNS as $category => $patterns) {
                    foreach ($patterns as $pattern) {
                        if (preg_match_all('/'.$pattern.'/i', $content, $patternMatches, PREG_OFFSET_CAPTURE)) {
                            foreach ($patternMatches[0] as $match) {
                                $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                                $severity = in_array($category, ['eval_usage', 'hardcoded_secrets']) ? 'critical' : 'medium';
                                $matches[] = [
                                    'severity' => $severity,
                                    'type' => $category,
                                    'file' => $filename,
                                    'line' => $line,
                                    'pattern' => $match[0],
                                    'description' => "Potential {$category} issue detected",
                                ];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("SecurityAgent: pattern matching failed for {$controller}: {$e->getMessage()}");
            }
        }

        return $matches;
    }

    /**
     * Match learned vulnerability patterns from past scans.
     */
    private function matchLearnedPatterns(): array
    {
        $matches = [];
        $learnedPatterns = $this->getLearnedVulnerabilityPatterns();

        if (empty($learnedPatterns)) {
            return $matches;
        }

        $controllers = $this->getTargetControllers();

        foreach ($controllers as $controller) {
            if (! class_exists($controller)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($controller);
                $filename = $reflection->getFileName();

                if (! $filename || ! file_exists($filename)) {
                    continue;
                }

                $content = file_get_contents($filename);

                foreach ($learnedPatterns as $pattern) {
                    if (preg_match('/'.preg_quote($pattern['regex'], '/').'/', $content)) {
                        $matches[] = [
                            'severity' => $pattern['severity'] ?? 'medium',
                            'type' => $pattern['type'] ?? 'learned_pattern',
                            'file' => $filename,
                            'pattern_id' => $pattern['id'] ?? 'unknown',
                            'description' => "Learned pattern match: {$pattern['type']}",
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning("SecurityAgent: learned pattern matching failed: {$e->getMessage()}");
            }
        }

        return $matches;
    }

    /**
     * Learn new vulnerability patterns from findings.
     */
    private function learnVulnerabilityPatterns(array $findings): void
    {
        $learned = $this->executionStats['learned_vulnerability_patterns'] ?? [];

        foreach ($findings as $finding) {
            if (! isset($finding['type'])) {
                continue;
            }

            $patternKey = md5($finding['type'].($finding['pattern'] ?? ''));

            // Skip if already learned
            $exists = false;
            foreach ($learned as $existing) {
                if (($existing['id'] ?? '') === $patternKey) {
                    $exists = true;
                    $existing['occurrences'] = ($existing['occurrences'] ?? 0) + 1;
                    $existing['last_seen'] = now()->toIso8601String();
                    break;
                }
            }

            if (! $exists && count($learned) < 100) {
                $learned[] = [
                    'id' => $patternKey,
                    'type' => $finding['type'],
                    'severity' => $finding['severity'] ?? 'medium',
                    'regex' => $finding['pattern'] ?? '',
                    'occurrences' => 1,
                    'first_seen' => now()->toIso8601String(),
                    'last_seen' => now()->toIso8601String(),
                ];
            }
        }

        $this->executionStats['learned_vulnerability_patterns'] = $learned;
        $this->persistMemory();
    }

    /**
     * Record vulnerability findings.
     */
    private function recordVulnerabilityFindings(array $findings): void
    {
        $existing = $this->executionStats['vulnerability_findings'] ?? [];

        foreach ($findings as $category => $categoryFindings) {
            if (is_array($categoryFindings)) {
                foreach ($categoryFindings as $finding) {
                    if (is_array($finding) && isset($finding['type'])) {
                        $existing[] = array_merge($finding, [
                            'category' => $category,
                            'timestamp' => now()->toIso8601String(),
                        ]);
                    }
                }
            }
        }

        // Keep last 200 findings
        if (count($existing) > 200) {
            $existing = array_slice($existing, -200);
        }

        $this->executionStats['vulnerability_findings'] = $existing;
        $this->persistMemory();
    }

    /**
     * Prepare findings for AI analysis.
     */
    private function prepareFindingsForAiAnalysis(array $scanResults): array
    {
        $findings = [];

        foreach (['static_analysis', 'pattern_matches', 'learned_patterns'] as $key) {
            foreach ($scanResults[$key] ?? [] as $finding) {
                if (isset($finding['file'])) {
                    $findings[] = [
                        'file' => basename($finding['file']),
                        'type' => $finding['type'] ?? 'unknown',
                        'line' => $finding['line'] ?? null,
                        'description' => $finding['description'] ?? '',
                    ];
                }
            }
        }

        return array_slice($findings, 0, 50); // Limit to 50 for AI analysis
    }

    /**
     * Parse JSON from AI response.
     */
    private function parseJsonResponse(string $content): array
    {
        if (preg_match('/```json\s*(.+?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        } elseif (preg_match('/```\s*(.+?)\s*```/s', $content, $matches)) {
            $content = $matches[1];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get controllers to scan.
     */
    private function getTargetControllers(array $specific = []): array
    {
        if (! empty($specific)) {
            return $specific;
        }

        $controllers = [];
        $controllerPath = app_path('Http/Controllers');

        if (! is_dir($controllerPath)) {
            return $controllers;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($controllerPath)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $relativePath = str_replace($controllerPath.'/', '', $file->getPathname());
                $classPath = str_replace('/', '\\', str_replace('.php', '', $relativePath));
                $className = 'App\\Http\\Controllers\\'.$classPath;

                if (class_exists($className)) {
                    $controllers[] = $className;
                }
            }
        }

        return $controllers;
    }

    /**
     * Get models that should have agency scoping.
     */
    private function getAgencyScopedModels(): array
    {
        $models = [];
        $modelPath = app_path('Models');

        if (! is_dir($modelPath)) {
            return $models;
        }

        $files = glob($modelPath.'/*.php');

        foreach ($files as $file) {
            $className = 'App\\Models\\'.basename($file, '.php');

            if (class_exists($className)) {
                $models[] = $className;
            }
        }

        return $models;
    }

    /**
     * Check if a model has agency scope.
     */
    private function modelHasAgencyScope(string $modelClass): bool
    {
        try {
            $reflection = new ReflectionClass($modelClass);
            $filename = $reflection->getFileName();

            if (! $filename || ! file_exists($filename)) {
                return false;
            }

            $content = file_get_contents($filename);

            // Check for common agency scoping patterns
            $patterns = [
                'agency_id',
                'agency\(\)',
                'BelongsTo.*Agency',
                'where.*agency',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match('/'.$pattern.'/i', $content)) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            // Class not found or error
        }

        return false;
    }
}
