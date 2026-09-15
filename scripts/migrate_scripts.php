<?php
// Script to migrate inline scripts in agents/workflows.blade.php

$file = 'resources/views/agents/workflows.blade.php';
$content = file_get_contents($file);

// Find and replace the script block
$pattern = '/<script>.*?<\/script>/s';
$replacement = '<script>
    async function runWorkflow(workflowName) {
        if (!confirm(\'Run the "\' + workflowName.replace(/_/g, \' \') + \'" workflow?\')) return;
        
        const btn = document.querySelector(\'button[onclick*="' . "'" . '\' + workflowName + \'"' . "'" . ']\');
        dmsaas.setLoading(btn, true);
        
        try {
            const response = await dmsaas.request(\'' . route('agents.run-workflow') . '\', {
                method: \'POST\',
                body: JSON.stringify({
                    workflow_name: workflowName,
                    async: true,
                    _token: \'' . csrf_token() . '\'
                })
            });
            const data = await response.json();
            if (data.success) {
                dmsaas.toast(\'Workflow started successfully!\');
            } else {
                dmsaas.toast(data.message || \'Failed to start workflow.\', \'error\');
            }
        } catch (err) {
            dmsaas.toast(\'Network error. Please try again.\', \'error\');
        } finally {
            dmsaas.setLoading(btn, false);
        }
    }
</script>';

$content = preg_replace($pattern, $replacement, $content);
file_put_contents($file, $content);
echo "Done\n";
