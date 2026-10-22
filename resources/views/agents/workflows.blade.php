@extends('layouts.unified')
@section('title', 'Agent Workflows')
@section('breadcrumb')
 <li class="hover:text-gray-700"><a href="{{ route('agents.dashboard') }}">Agents</a></li>
 <li class="text-gray-900 font-medium">Workflows</li>
@endsection

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Agent Workflows</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Chain multiple AI agents together to accomplish complex tasks automatically.</p>
    </div>

    <!-- Workflow Templates Header -->
    <div class="bg-white rounded-xl shadow-sm border-2 border-indigo-300 dark:bg-gray-800 dark:border-indigo-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-project-diagram mr-2"></i>Workflow Templates</h3>
            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ count($workflows ?? []) }} workflows</span>
        </div>
        <div class="p-6">
            <p class="text-gray-500 dark:text-gray-400">
                Workflows chain multiple AI agents together to accomplish complex tasks automatically.
            </p>
        </div>
    </div>

    <!-- Workflow Templates Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @forelse($workflows ?? [] as $index => $workflow)
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 {{ $loop->even ? 'border-blue-200 dark:border-blue-800' : 'border-green-200 dark:border-green-800' }}">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-project-diagram mr-2"></i>{{ ucwords(str_replace('_', ' ', $workflow['name'])) }}
                </h3>
                <span class="{{ count($workflow['required_features'] ?? []) <= 1 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' }} text-xs font-medium px-2.5 py-0.5 rounded-full">
                    {{ count($workflow['required_features'] ?? []) }} features
                </span>
            </div>
            <div class="p-6">
                <p>{{ $workflow['description'] }}</p>
                
                <div class="mb-3">
                    <strong><i class="fas fa-cogs mr-1"></i>Steps:</strong>
                    <div class="mt-2">
                        @foreach($workflow['steps'] ?? [] as $stepIndex => $step)
                        <div class="flex items-center mb-2">
                            <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-indigo-900 dark:text-indigo-300 mr-2" style="width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">{{ $stepIndex + 1 }}</span>
                            <span>{{ str_replace('_', ' ', ucwords($step)) }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong><i class="fas fa-puzzle-piece mr-1"></i>Required Features:</strong>
                    <div class="mt-1">
                        @foreach($workflow['required_features'] ?? [] as $feature)
                         <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-gray-600 dark:text-gray-300">{{ str_replace('_', ' ', ucwords($feature)) }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex flex-wrap gap-2">
                <button class="bg-green-600 text-white px-3 py-1.5 rounded-lg hover:bg-green-700 inline-flex items-center gap-1 text-sm font-medium transition-colors" onclick="runWorkflow('{{ $workflow['name'] }}')" id="btn-{{ $index }}">
                    <i class="fas fa-play mr-1"></i> Run Workflow
                </button>
                <button class="border border-blue-500 text-blue-500 px-3 py-1.5 rounded-lg hover:bg-blue-500 hover:text-white inline-flex items-center gap-1 text-sm font-medium transition-colors" onclick="viewWorkflowDetails('{{ $workflow['name'] }}')">
                    <i class="fas fa-info-circle mr-1"></i> Details
                </button>
            </div>
        </div>
        @empty
        <div class="lg:col-span-2">
            <div class="bg-blue-50 text-blue-800 border border-blue-200 rounded-lg p-4 mb-4">
                <i class="fas fa-info-circle mr-2"></i> No workflow templates available.
            </div>
        </div>
        @endforelse
    </div>

    <!-- Execution History -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-history mr-2"></i>Workflow Execution History</h3>
            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">{{ count($executions ?? []) }} executions</span>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Execution ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Workflow</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Progress</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Started</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Duration</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($executions ?? [] as $exec)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white"><code>{{ Str::limit($exec->execution_id ?? 'N/A', 15) }}</code></td>
                            <td class="px-4 py-3">
                                <span class="bg-gray-800 text-gray-100 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-gray-900 dark:text-gray-300">{{ ucwords(str_replace('_', ' ', $exec->workflow_name ?? 'N/A')) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $status = $exec->status ?? 'unknown';
                                    $statusClass = match($status) {
                                        'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                        'failed', 'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                        'running' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                        default => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                    };
                                @endphp
                                <span class="{{ $statusClass }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ ucfirst($status) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $progress = ($exec->steps_total ?? 0) > 0 
                                        ? round(($exec->steps_completed ?? 0) / $exec->steps_total * 100) 
                                        : 0;
                                @endphp
                                <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700" style="width: 100px;">
                                    <div class="h-1.5 rounded-full {{ $progress >= 100 ? 'bg-green-500' : 'bg-indigo-500' }}" style="width: {{ $progress }}%"></div>
                                </div>
                                <small class="text-gray-500 dark:text-gray-400">{{ $exec->steps_completed ?? 0 }}/{{ $exec->steps_total ?? 0 }}</small>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $exec->started_at ? \Carbon\Carbon::parse($exec->started_at)->format('M d, H:i') : 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                @if($exec->duration_ms)
                                    {{ number_format($exec->duration_ms / 1000, 1) }}s
                                @elseif($exec->started_at)
                                    {{ \Carbon\Carbon::parse($exec->started_at)->diffForHumans() }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($status === 'pending' || $status === 'running')
                                    <span class="text-yellow-600 dark:text-yellow-400"><i class="fas fa-spinner fa-spin mr-1"></i> Running...</span>
                                @elseif($status === 'success')
                                    <span class="text-green-600 dark:text-green-400"><i class="fas fa-check mr-1"></i> Complete</span>
                                @else
                                    <span class="text-red-600 dark:text-red-400"><i class="fas fa-times mr-1"></i> Failed</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-inbox text-2xl mb-2"></i>
                                <p>No workflow executions recorded yet. Run a workflow to see it here.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Workflow Execution Result Modal -->
<div class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" id="resultModal" tabindex="-1" role="dialog">
    <div class="max-w-4xl w-full mx-4" role="document">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white"><i class="fas fa-clipboard-check mr-2"></i>Workflow Result</h5>
                <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" onclick="this.closest('.fixed').classList.add('hidden')" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="p-6" id="resultContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Processing workflow...</p>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                <button type="button" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors" onclick="this.closest('.fixed').classList.add('hidden')">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
  async function runWorkflow(workflowName) {
    if (!confirm('Run the "' + workflowName.replace(/_/g, ' ') + '" workflow?')) return;
    
    const btn = document.querySelector('button[onclick*="' + workflowName + '"]');
    dmsaas.setLoading(btn, true);
    
    try {
      const response = await dmsaas.request('/agents/run-workflow', {
        method: 'POST',
        body: JSON.stringify({
          workflow_name: workflowName,
          async: true,
        })
      });
      const data = await response.json();
      if (data.success) {
        dmsaas.toast('Workflow started successfully!');
        setTimeout(() => location.reload(), 2000);
      } else {
        dmsaas.toast(data.message || 'Failed to start workflow.', 'error');
      }
    } catch (err) {
      dmsaas.toast('Network error. Please try again.', 'error');
    } finally {
      dmsaas.setLoading(btn, false);
    }
  }

  function viewWorkflowDetails(workflowName) {
    const workflows = @json($workflows ?? []);
    const workflow = workflows.find(w => w.name === workflowName);
    
    if (workflow) {
      let stepsHtml = workflow.steps.map((step, i) => 
        `<div class="flex items-center mb-2">
          <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-indigo-900 dark:text-indigo-300 mr-2">${i + 1}</span>
          <span>${step.replace(/_/g, ' ')}</span>
        </div>`
      ).join('');
      
      document.getElementById('resultContent').innerHTML = `
        <h5>${workflow.name.replace(/_/g, ' ')}</h5>
        <p class="text-gray-500 dark:text-gray-400">${workflow.description}</p>
        <hr>
        <h6>Workflow Steps:</h6>
        ${stepsHtml}
        <hr>
        <h6>Required Features:</h6>
        <div>${workflow.required_features.map(f => `<span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300 mr-1">${f.replace(/_/g, ' ')}</span>`).join('')}</div>
      `;
      document.getElementById('resultModal').classList.remove('hidden');
    }
  }
</script>
@endpush
