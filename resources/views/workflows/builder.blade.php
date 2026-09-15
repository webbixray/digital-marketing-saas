@extends('layouts.unified')
@section('title', 'Workflow Builder')

@section('styles')
<style>
    * { box-sizing: border-box; }
    body { margin: 0; overflow: hidden; font-family: 'Inter', 'Source Sans Pro', sans-serif; }
    
    .workflow-builder {
        display: flex;
        height: calc(100vh - 56px);
        overflow: hidden;
        background: #1a1a2e;
    }
    
    /* Node Palette */
    .node-palette {
        width: 260px;
        background: linear-gradient(180deg, #16213e 0%, #1a1a2e 100%);
        color: #e0e0e0;
        overflow-y: auto;
        padding: 16px;
        border-right: 1px solid #2a2a4a;
        flex-shrink: 0;
    }
    
    .palette-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid #2a2a4a;
    }
    
    .palette-header i {
        font-size: 20px;
        color: #6c63ff;
    }
    
    .palette-header h3 {
        font-size: 14px;
        font-weight: 700;
        margin: 0;
        color: #fff;
        letter-spacing: 0.5px;
    }
    
    .palette-section { margin-bottom: 20px; }
    .palette-section h4 {
        font-size: 10px;
        text-transform: uppercase;
        color: #8892b0;
        margin-bottom: 10px;
        letter-spacing: 1px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .palette-section h4::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #2a2a4a;
    }
    
    .palette-node {
        display: flex;
        align-items: center;
        padding: 10px 12px;
        margin-bottom: 6px;
        background: #252547;
        border-radius: 8px;
        cursor: grab;
        transition: all 0.2s ease;
        font-size: 12px;
        border: 1px solid transparent;
        position: relative;
        overflow: hidden;
    }
    
    .palette-node::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        border-radius: 8px 0 0 8px;
        transition: all 0.2s;
    }
    
    .palette-node.trigger::before { background: #10b981; }
    .palette-node.action::before { background: #6366f1; }
    .palette-node.condition::before { background: #f59e0b; }
    .palette-node.util::before { background: #6b7280; }
    
    .palette-node:hover {
        background: #2d2d5e;
        border-color: #6c63ff;
        transform: translateX(4px);
        box-shadow: 0 4px 12px rgba(108, 99, 255, 0.2);
    }
    
    .palette-node i {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        margin-right: 10px;
        font-size: 14px;
    }
    .palette-node.trigger i { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .palette-node.action i { background: rgba(99, 102, 241, 0.15); color: #6366f1; }
    .palette-node.condition i { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
    .palette-node.util i { background: rgba(107, 114, 128, 0.15); color: #9ca3af; }
    
    .palette-node-info {
        display: flex;
        flex-direction: column;
    }
    
    .palette-node-info span:first-child {
        font-weight: 600;
        color: #fff;
        font-size: 12px;
    }
    
    .palette-node-info span:last-child {
        font-size: 10px;
        color: #8892b0;
    }
    
    /* Canvas */
    .canvas-container {
        flex: 1;
        position: relative;
        background: #0f0f23;
        overflow: hidden;
        background-image: 
            linear-gradient(rgba(108, 99, 255, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(108, 99, 255, 0.03) 1px, transparent 1px),
            linear-gradient(rgba(108, 99, 255, 0.06) 1px, transparent 1px),
            linear-gradient(90deg, rgba(108, 99, 255, 0.06) 1px, transparent 1px);
        background-size: 
            20px 20px,
            20px 20px,
            100px 100px,
            100px 100px;
    }
    
    #connections-layer {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
    }
    
    /* Workflow Nodes */
    .workflow-node {
        position: absolute;
        min-width: 220px;
        background: #1e1e3f;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(108, 99, 255, 0.1);
        cursor: move;
        user-select: none;
        transition: box-shadow 0.2s, transform 0.15s;
        z-index: 10;
        border: 1px solid #2a2a4a;
        overflow: hidden;
    }
    
    .workflow-node:hover { 
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(108, 99, 255, 0.3);
        transform: translateY(-2px);
    }
    .workflow-node.selected {
        border-color: #6c63ff;
        box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.3), 0 8px 30px rgba(0, 0, 0, 0.5);
    }
    .workflow-node.executing {
        border-color: #10b981;
        animation: nodePulse 1.5s infinite;
    }
    .workflow-node.failed { border-color: #ef4444; }
    .workflow-node.dragging { opacity: 0.85; z-index: 100; box-shadow: 0 12px 40px rgba(0, 0, 0, 0.6); }
    .workflow-node.success { border-color: #10b981; }
    
    @keyframes nodePulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
        50% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    }
    
    .node-header {
        padding: 12px 16px;
        display: flex;
        align-items: center;
        color: white;
        font-weight: 600;
        font-size: 13px;
        position: relative;
    }
    .node-header.trigger { background: linear-gradient(135deg, #059669, #10b981); }
    .node-header.action { background: linear-gradient(135deg, #4f46e5, #6366f1); }
    .node-header.condition { background: linear-gradient(135deg, #d97706, #f59e0b); }
    .node-header.util { background: linear-gradient(135deg, #4b5563, #6b7280); }
    .node-header i { margin-right: 10px; font-size: 14px; }
    
    .node-header-badge {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: rgba(255,255,255,0.3);
    }
    
    .node-header-badge.connected { background: #10b981; box-shadow: 0 0 6px #10b981; }
    
    .node-body {
        padding: 12px 16px;
        font-size: 11px;
        color: #8892b0;
        background: #1e1e3f;
    }
    
    .node-status {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-top: 6px;
        font-size: 10px;
    }
    
    .node-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #6b7280;
    }
    
    .node-status-dot.ready { background: #6b7280; }
    .node-status-dot.running { background: #f59e0b; animation: blink 1s infinite; }
    .node-status-dot.success { background: #10b981; }
    .node-status-dot.error { background: #ef4444; }
    
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.3; }
    }
    
    /* Node Ports */
    .node-port {
        position: absolute;
        width: 16px;
        height: 16px;
        background: #1e1e3f;
        border: 2px solid #6c63ff;
        border-radius: 50%;
        cursor: crosshair;
        z-index: 20;
        transition: all 0.2s;
    }
    .node-port:hover { 
        background: #6c63ff; 
        transform: scale(1.5); 
        box-shadow: 0 0 0 6px rgba(108, 99, 255, 0.3);
    }
    .node-port.input { left: -8px; top: 50%; transform: translateY(-50%); }
    .node-port.output { right: -8px; top: 50%; transform: translateY(-50%); }
    .node-port.connected { background: #10b981; border-color: #10b981; }
    .node-port.connecting { background: #f59e0b; border-color: #f59e0b; }
    
    /* Toolbar */
    .builder-toolbar {
        position: absolute;
        top: 16px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(30, 30, 63, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(108, 99, 255, 0.2);
        display: flex;
        padding: 6px;
        z-index: 100;
    }
    
    .toolbar-group {
        display: flex;
        align-items: center;
        padding: 0 4px;
    }
    
    .toolbar-group + .toolbar-group {
        border-left: 1px solid #2a2a4a;
        margin-left: 4px;
        padding-left: 8px;
    }
    
    .toolbar-btn {
        padding: 8px 12px;
        border: none;
        background: transparent;
        border-radius: 8px;
        cursor: pointer;
        font-size: 12px;
        color: #8892b0;
        transition: all 0.15s;
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }
    .toolbar-btn:hover { background: rgba(108, 99, 255, 0.15); color: #fff; }
    .toolbar-btn.primary { background: #6c63ff; color: white; }
    .toolbar-btn.primary:hover { background: #5a52d9; }
    .toolbar-btn.success { background: #10b981; color: white; }
    .toolbar-btn.success:hover { background: #059669; }
    .toolbar-btn.danger { background: #ef4444; color: white; }
    .toolbar-btn.danger:hover { background: #dc2626; }
    .toolbar-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .toolbar-btn i { font-size: 14px; }
    
    /* Properties Panel */
    .properties-panel {
        width: 320px;
        background: linear-gradient(180deg, #16213e 0%, #1a1a2e 100%);
        border-left: 1px solid #2a2a4a;
        overflow-y: auto;
        flex-shrink: 0;
    }
    
    .properties-panel-header {
        padding: 16px 20px;
        border-bottom: 1px solid #2a2a4a;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .properties-panel-header i {
        color: #6c63ff;
        font-size: 16px;
    }
    
    .properties-panel-header h4 {
        font-size: 14px;
        margin: 0;
        color: #fff;
        font-weight: 600;
    }
    
    .properties-panel-body {
        padding: 20px;
    }
    
    .properties-empty {
        text-align: center;
        padding: 40px 20px;
        color: #8892b0;
    }
    
    .properties-empty i {
        font-size: 48px;
        color: #2a2a4a;
        margin-bottom: 16px;
    }
    
    .properties-empty h5 {
        font-size: 14px;
        margin-bottom: 8px;
        color: #fff;
    }
    
    .properties-empty p {
        font-size: 12px;
        line-height: 1.5;
    }
    
    .property-section {
        margin-bottom: 20px;
    }
    
    .property-section-title {
        font-size: 10px;
        text-transform: uppercase;
        color: #6c63ff;
        letter-spacing: 1px;
        font-weight: 700;
        margin-bottom: 10px;
    }
    
    .form-group { margin-bottom: 14px; }
    .form-group label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: #8892b0;
        margin-bottom: 6px;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #2a2a4a;
        border-radius: 8px;
        font-size: 12px;
        background: #252547;
        color: #fff;
        transition: all 0.15s;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #6c63ff;
        outline: none;
        box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.2);
    }
    .form-group textarea { min-height: 70px; resize: vertical; }
    
    .node-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .node-type-badge.trigger { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .node-type-badge.action { background: rgba(99, 102, 241, 0.15); color: #6366f1; }
    .node-type-badge.condition { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
    .node-type-badge.util { background: rgba(107, 114, 128, 0.15); color: #9ca3af; }
    
    /* Execution Log */
    .execution-log {
        position: absolute;
        bottom: 16px;
        right: 16px;
        width: 400px;
        max-height: 240px;
        background: rgba(30, 30, 63, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(108, 99, 255, 0.2);
        overflow: hidden;
        display: none;
        z-index: 100;
    }
    .execution-log.show { display: block; }
    .log-header {
        padding: 12px 16px;
        background: #252547;
        color: white;
        font-size: 12px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #2a2a4a;
    }
    .log-body { max-height: 190px; overflow-y: auto; padding: 12px 16px; }
    .log-entry {
        font-size: 11px;
        padding: 6px 0;
        border-bottom: 1px solid #2a2a4a;
        display: flex;
        align-items: center;
        gap: 8px;
        color: #e0e0e0;
    }
    .log-entry:last-child { border-bottom: none; }
    .log-entry .timestamp { color: #8892b0; font-size: 10px; }
    .log-entry.success i { color: #10b981; }
    .log-entry.error i { color: #ef4444; }
    .log-entry.info i { color: #6366f1; }
    
    /* Zoom Controls */
    .zoom-controls {
        position: absolute;
        bottom: 16px;
        left: 16px;
        background: rgba(30, 30, 63, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(108, 99, 255, 0.2);
        display: flex;
        align-items: center;
        z-index: 100;
        padding: 4px;
    }
    .zoom-btn {
        padding: 8px 10px;
        border: none;
        background: transparent;
        cursor: pointer;
        font-size: 14px;
        color: #8892b0;
        border-radius: 8px;
        transition: all 0.15s;
    }
    .zoom-btn:hover { background: rgba(108, 99, 255, 0.15); color: #fff; }
    .zoom-level {
        padding: 6px 12px;
        font-size: 11px;
        color: #fff;
        min-width: 55px;
        text-align: center;
        font-weight: 600;
        border-left: 1px solid #2a2a4a;
        border-right: 1px solid #2a2a4a;
    }
    
    /* Info Bar */
    .info-bar {
        position: absolute;
        bottom: 16px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(30, 30, 63, 0.9);
        backdrop-filter: blur(10px);
        color: #8892b0;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 11px;
        z-index: 100;
        display: flex;
        align-items: center;
        gap: 16px;
        border: 1px solid #2a2a4a;
    }
    .info-bar span { display: flex; align-items: center; gap: 6px; }
    .info-bar i { color: #6c63ff; }
    
    /* Connection path styles */
    .connection-path {
        fill: none;
        stroke: #6c63ff;
        stroke-width: 3;
        cursor: pointer;
        transition: stroke 0.15s;
    }
    .connection-path:hover { stroke: #ef4444; stroke-width: 4; }
    .connection-path.active { stroke: #10b981; }
    
    /* Connection hit area (invisible, wider) */
    .connection-hit-area {
        fill: none;
        stroke: transparent;
        stroke-width: 14;
        cursor: pointer;
    }
    
    /* Templates dropdown */
    .templates-dropdown {
        position: absolute;
        top: 70px;
        left: 16px;
        width: 340px;
        max-height: calc(100vh - 200px);
        background: rgba(30, 30, 63, 0.98);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(108, 99, 255, 0.2);
        overflow-y: auto;
        display: none;
        z-index: 100;
    }
    .templates-dropdown.show { display: block; }
    .templates-header {
        padding: 14px 18px;
        border-bottom: 1px solid #2a2a4a;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .templates-header h5 { font-size: 13px; margin: 0; color: #fff; font-weight: 600; }
    .template-card {
        padding: 14px 18px;
        border-bottom: 1px solid #2a2a4a;
        cursor: pointer;
        transition: all 0.15s;
    }
    .template-card:hover { background: rgba(108, 99, 255, 0.1); border-left: 3px solid #6c63ff; }
    .template-card h6 { font-size: 12px; margin-bottom: 4px; color: #fff; font-weight: 600; }
    .template-card p { font-size: 11px; color: #8892b0; margin: 0; }
    .template-card .badge { font-size: 10px; margin-top: 6px; }
    
    /* Modal */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(4px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
    .modal-overlay.show { display: flex; }
    .modal-content {
        background: #1e1e3f;
        border-radius: 16px;
        padding: 28px;
        width: 420px;
        max-width: 90%;
        border: 1px solid #2a2a4a;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }
    .modal-content h4 { 
        margin-bottom: 20px; 
        color: #fff; 
        font-size: 16px; 
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .modal-content h4 i { color: #6c63ff; }
    .modal-content .form-group { margin-bottom: 14px; }
    .modal-content label { display: block; font-size: 11px; font-weight: 600; color: #8892b0; margin-bottom: 6px; }
    .modal-content input, .modal-content textarea { 
        width: 100%; 
        padding: 10px 12px; 
        border: 1px solid #2a2a4a; 
        border-radius: 8px; 
        background: #252547;
        color: #fff;
        font-size: 13px;
    }
    .modal-content input:focus, .modal-content textarea:focus { border-color: #6c63ff; outline: none; }
    .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
    
    /* Loading spinner */
    .spinner {
        display: inline-block;
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    
    /* Toast notifications */
    .toast-container {
        position: fixed;
        top: 70px;
        right: 20px;
        z-index: 2000;
    }
    .toast {
        background: #1e1e3f;
        border-radius: 10px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(108, 99, 255, 0.2);
        padding: 14px 18px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 300px;
        animation: slideIn 0.3s ease;
        border: 1px solid #2a2a4a;
    }
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    .toast.success { border-left: 4px solid #10b981; }
    .toast.error { border-left: 4px solid #ef4444; }
    .toast.info { border-left: 4px solid #6366f1; }
    .toast i { font-size: 18px; }
    .toast.success i { color: #10b981; }
    .toast.error i { color: #ef4444; }
    .toast.info i { color: #6366f1; }
    .toast span { color: #e0e0e0; font-size: 13px; }
    
    /* Minimap */
    .minimap {
        position: absolute;
        bottom: 70px;
        right: 16px;
        width: 200px;
        height: 130px;
        background: rgba(30, 30, 63, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 10px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(108, 99, 255, 0.2);
        overflow: hidden;
        z-index: 100;
        border: 1px solid #2a2a4a;
    }
    .minimap canvas { width: 100%; height: 100%; }
    .minimap-viewport {
        position: absolute;
        border: 2px solid #6c63ff;
        background: rgba(108, 99, 255, 0.1);
        pointer-events: none;
    }
    
    /* Selection box */
    .selection-box {
        position: absolute;
        border: 2px dashed #6c63ff;
        background: rgba(108, 99, 255, 0.05);
        pointer-events: none;
        z-index: 50;
    }
    
    /* Keyboard shortcuts hint */
    .keyboard-hint {
        position: absolute;
        bottom: 70px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(30, 30, 63, 0.9);
        backdrop-filter: blur(10px);
        color: #8892b0;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 10px;
        z-index: 100;
        opacity: 0;
        transition: opacity 0.3s;
        border: 1px solid #2a2a4a;
    }
    .keyboard-hint.show { opacity: 1; }
    .keyboard-hint kbd {
        background: #252547;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 10px;
        color: #fff;
        border: 1px solid #2a2a4a;
    }
    
    /* Empty state */
    .empty-state {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        color: #8892b0;
        pointer-events: none;
    }
    
    .empty-state-icon {
        width: 120px;
        height: 120px;
        margin: 0 auto 24px;
        background: linear-gradient(135deg, rgba(108, 99, 255, 0.1), rgba(99, 102, 241, 0.05));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px dashed #2a2a4a;
    }
    
    .empty-state-icon i {
        font-size: 48px;
        color: #6c63ff;
    }
    
    .empty-state h3 {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 8px;
        color: #fff;
    }
    
    .empty-state p {
        font-size: 14px;
        color: #8892b0;
        max-width: 400px;
        line-height: 1.6;
        margin-bottom: 24px;
    }
    
    .empty-state-hint {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: rgba(108, 99, 255, 0.1);
        border-radius: 8px;
        font-size: 12px;
        color: #6c63ff;
        border: 1px solid rgba(108, 99, 255, 0.2);
    }
    
    /* Context menu */
    .context-menu {
        position: fixed;
        background: #1e1e3f;
        border-radius: 10px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(108, 99, 255, 0.2);
        padding: 6px 0;
        min-width: 180px;
        z-index: 1000;
        display: none;
    }
    
    .context-menu.show { display: block; }
    
    .context-menu-item {
        padding: 10px 16px;
        font-size: 12px;
        color: #e0e0e0;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.1s;
    }
    
    .context-menu-item:hover { background: rgba(108, 99, 255, 0.15); }
    .context-menu-item i { width: 16px; color: #8892b0; }
    .context-menu-item.danger { color: #ef4444; }
    .context-menu-item.danger i { color: #ef4444; }
    
    .context-menu-divider {
        height: 1px;
        background: #2a2a4a;
        margin: 4px 0;
    }
    
    /* Help panel */
    .help-panel {
        position: absolute;
        top: 70px;
        right: 16px;
        width: 280px;
        background: rgba(30, 30, 63, 0.98);
        backdrop-filter: blur(10px);
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(108, 99, 255, 0.2);
        padding: 18px;
        z-index: 100;
        display: none;
    }
    
    .help-panel.show { display: block; }
    
    .help-panel h5 {
        font-size: 13px;
        color: #fff;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .help-panel h5 i { color: #6c63ff; }
    
    .help-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 0;
        font-size: 11px;
        color: #8892b0;
    }
    
    .help-item kbd {
        background: #252547;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 10px;
        color: #fff;
        border: 1px solid #2a2a4a;
        min-width: 28px;
        text-align: center;
    }
</style>
@endsection

@section('content')
<div class="space-y-6">
<div class="workflow-builder" id="workflowBuilder">
    <!-- Node Palette -->
    <div class="node-palette">
        <div class="palette-header">
            <i class="fas fa-project-diagram"></i>
            <h3>Workflow Nodes</h3>
        </div>
        
        <div class="palette-section">
            <h4>Triggers</h4>
            <div class="palette-node trigger" draggable="true" data-type="trigger" data-subtype="post_published">
                <i class="fas fa-pen-fancy"></i>
                <div class="palette-node-info">
                    <span>Post Published</span>
                    <span>When a post goes live</span>
                </div>
            </div>
            <div class="palette-node trigger" draggable="true" data-type="trigger" data-subtype="comment_received">
                <i class="fas fa-comment"></i>
                <div class="palette-node-info">
                    <span>Comment Received</span>
                    <span>New comment on post</span>
                </div>
            </div>
            <div class="palette-node trigger" draggable="true" data-type="trigger" data-subtype="mention_received">
                <i class="fas fa-at"></i>
                <div class="palette-node-info">
                    <span>Mention Received</span>
                    <span>Brand mentioned</span>
                </div>
            </div>
            <div class="palette-node trigger" draggable="true" data-type="trigger" data-subtype="message_received">
                <i class="fas fa-envelope"></i>
                <div class="palette-node-info">
                    <span>DM Received</span>
                    <span>Direct message</span>
                </div>
            </div>
            <div class="palette-node trigger" draggable="true" data-type="trigger" data-subtype="schedule">
                <i class="fas fa-clock"></i>
                <div class="palette-node-info">
                    <span>Schedule</span>
                    <span>Time-based trigger</span>
                </div>
            </div>
            <div class="palette-node trigger" draggable="true" data-type="trigger" data-subtype="webhook">
                <i class="fas fa-globe"></i>
                <div class="palette-node-info">
                    <span>Webhook</span>
                    <span>External trigger</span>
                </div>
            </div>
        </div>
        
        <div class="palette-section">
            <h4>Actions</h4>
            <div class="palette-node action" draggable="true" data-type="action" data-subtype="send_notification">
                <i class="fas fa-bell"></i>
                <div class="palette-node-info">
                    <span>Send Notification</span>
                    <span>Alert the team</span>
                </div>
            </div>
            <div class="palette-node action" draggable="true" data-type="action" data-subtype="auto_reply">
                <i class="fas fa-reply"></i>
                <div class="palette-node-info">
                    <span>Auto Reply</span>
                    <span>Respond automatically</span>
                </div>
            </div>
            <div class="palette-node action" draggable="true" data-type="action" data-subtype="create_post">
                <i class="fas fa-plus"></i>
                <div class="palette-node-info">
                    <span>Create Post</span>
                    <span>Publish new content</span>
                </div>
            </div>
            <div class="palette-node action" draggable="true" data-type="action" data-subtype="schedule_post">
                <i class="fas fa-calendar"></i>
                <div class="palette-node-info">
                    <span>Schedule Post</span>
                    <span>Queue for later</span>
                </div>
            </div>
            <div class="palette-node action" draggable="true" data-type="action" data-subtype="ai_generate">
                <i class="fas fa-robot"></i>
                <div class="palette-node-info">
                    <span>AI Generate</span>
                    <span>Create with AI</span>
                </div>
            </div>
            <div class="palette-node action" draggable="true" data-type="action" data-subtype="tag_client">
                <i class="fas fa-tag"></i>
                <div class="palette-node-info">
                    <span>Tag Client</span>
                    <span>Organize contacts</span>
                </div>
            </div>
            <div class="palette-node action" draggable="true" data-type="action" data-subtype="send_email">
                <i class="fas fa-paper-plane"></i>
                <div class="palette-node-info">
                    <span>Send Email</span>
                    <span>Email campaign</span>
                </div>
            </div>
            <div class="palette-node action" draggable="true" data-type="action" data-subtype="webhook_call">
                <i class="fas fa-external-link-alt"></i>
                <div class="palette-node-info">
                    <span>Webhook Call</span>
                    <span>HTTP request</span>
                </div>
            </div>
        </div>
        
        <div class="palette-section">
            <h4>Logic</h4>
            <div class="palette-node condition" draggable="true" data-type="condition" data-subtype="if">
                <i class="fas fa-question"></i>
                <div class="palette-node-info">
                    <span>Condition</span>
                    <span>Branch logic</span>
                </div>
            </div>
            <div class="palette-node util" draggable="true" data-type="util" data-subtype="delay">
                <i class="fas fa-hourglass-half"></i>
                <div class="palette-node-info">
                    <span>Delay</span>
                    <span>Wait before next</span>
                </div>
            </div>
            <div class="palette-node util" draggable="true" data-type="util" data-subtype="loop">
                <i class="fas fa-redo"></i>
                <div class="palette-node-info">
                    <span>Loop</span>
                    <span>Repeat actions</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Canvas -->
    <div class="canvas-container" id="canvasContainer">
        <svg id="connectionsLayer"></svg>
        <div id="nodesLayer"></div>
        
        <!-- Empty State -->
        <div class="empty-state" id="emptyState">
            <div class="empty-state-icon">
                <i class="fas fa-project-diagram"></i>
            </div>
            <h3>Build Your Workflow</h3>
            <p>Drag nodes from the palette on the left to create your automated workflow. Connect nodes to define the flow of actions.</p>
            <div class="empty-state-hint">
                <i class="fas fa-lightbulb"></i>
                <span>Try a template from the Templates button above</span>
            </div>
        </div>
        
        <!-- Toolbar -->
        <div class="builder-toolbar">
            <div class="toolbar-group">
                <button class="toolbar-btn" onclick="builder.undo()" title="Undo (Ctrl+Z)"><i class="fas fa-undo"></i></button>
                <button class="toolbar-btn" onclick="builder.redo()" title="Redo (Ctrl+Y)"><i class="fas fa-redo"></i></button>
            </div>
            <div class="toolbar-group">
                <button class="toolbar-btn" onclick="builder.zoomOut()"><i class="fas fa-minus"></i></button>
                <span class="zoom-level" id="zoomLevel">100%</span>
                <button class="toolbar-btn" onclick="builder.zoomIn()"><i class="fas fa-plus"></i></button>
                <button class="toolbar-btn" onclick="builder.fitView()" title="Fit to screen"><i class="fas fa-expand"></i></button>
            </div>
            <div class="toolbar-group">
                <button class="toolbar-btn" onclick="builder.showTemplates()"><i class="fas fa-layer-group"></i> Templates</button>
                <button class="toolbar-btn" onclick="builder.testWorkflow()"><i class="fas fa-play"></i> Test</button>
                <button class="toolbar-btn" onclick="builder.showHelp()"><i class="fas fa-question-circle"></i></button>
            </div>
            <div class="toolbar-group">
                <button class="toolbar-btn danger" onclick="builder.clearCanvas()"><i class="fas fa-trash"></i></button>
            </div>
            <div class="toolbar-group">
                <button class="toolbar-btn primary" onclick="builder.openSaveModal()"><i class="fas fa-save"></i> Save</button>
                <button class="toolbar-btn success" onclick="builder.activateWorkflow()"><i class="fas fa-power-off"></i> Activate</button>
            </div>
        </div>
        
        <!-- Zoom Controls (Floating) -->
        <div class="zoom-controls">
            <button class="zoom-btn" onclick="builder.zoomOut()"><i class="fas fa-minus"></i></button>
            <span class="zoom-level" id="zoomLevel2">100%</span>
            <button class="zoom-btn" onclick="builder.zoomIn()"><i class="fas fa-plus"></i></button>
        </div>
        
        <!-- Info Bar -->
        <div class="info-bar">
            <span><i class="fas fa-cube"></i> <span id="nodeCount">0</span> nodes</span>
            <span><i class="fas fa-link"></i> <span id="connectionCount">0</span> connections</span>
        </div>
        
        <!-- Minimap -->
        <div class="minimap" id="minimap">
            <canvas id="minimapCanvas"></canvas>
            <div class="minimap-viewport" id="minimapViewport"></div>
        </div>
        
        <!-- Keyboard Hint -->
        <div class="keyboard-hint" id="keyboardHint">
            <span><kbd>Del</kbd> Delete • <kbd>Ctrl+Z</kbd> Undo • <kbd>Ctrl+C</kbd> Copy • <kbd>Ctrl+V</kbd> Paste • <kbd>Ctrl+A</kbd> Select All</span>
        </div>
        
        <!-- Help Panel -->
        <div class="help-panel" id="helpPanel">
            <h5><i class="fas fa-keyboard"></i> Keyboard Shortcuts</h5>
            <div class="help-item"><kbd>Del</kbd> Delete selected node</div>
            <div class="help-item"><kbd>Ctrl+Z</kbd> Undo</div>
            <div class="help-item"><kbd>Ctrl+Y</kbd> Redo</div>
            <div class="help-item"><kbd>Ctrl+C</kbd> Copy node</div>
            <div class="help-item"><kbd>Ctrl+V</kbd> Paste node</div>
            <div class="help-item"><kbd>Ctrl+A</kbd> Select all</div>
            <div class="help-item"><kbd>Esc</kbd> Deselect</div>
            <div class="help-item"><kbd>Scroll</kbd> Zoom in/out</div>
        </div>
        
        <!-- Templates Dropdown -->
        <div class="templates-dropdown" id="templatesDropdown">
            <div class="templates-header">
                <h5><i class="fas fa-layer-group"></i> Templates</h5>
                <button class="toolbar-btn" onclick="builder.hideTemplates()"><i class="fas fa-times"></i></button>
            </div>
            <div id="templatesList">
                <!-- Templates loaded dynamically -->
            </div>
        </div>
        
        <!-- Execution Log -->
        <div class="execution-log" id="executionLog">
            <div class="log-header">
                <span><i class="fas fa-terminal"></i> Execution Log</span>
                <button class="toolbar-btn" onclick="builder.hideLog()"><i class="fas fa-times"></i></button>
            </div>
            <div class="log-body" id="logBody"></div>
        </div>
    </div>
    
    <!-- Properties Panel -->
    <div class="properties-panel" id="propertiesPanel">
        <div class="properties-panel-header">
            <i class="fas fa-sliders-h"></i>
            <h4>Properties</h4>
        </div>
        <div class="properties-panel-body" id="propertiesBody">
            <div class="properties-empty" id="propertiesEmpty">
                <i class="fas fa-mouse-pointer"></i>
                <h5>Select a Node</h5>
                <p>Click on a node in the canvas to view and edit its properties here.</p>
            </div>
        </div>
    </div>
</div>

<!-- Save Modal -->
<div class="modal-overlay" id="saveModal">
    <div class="modal-content">
        <h4><i class="fas fa-save"></i> Save Workflow</h4>
        <div class="form-group">
            <label>Workflow Name *</label>
            <input type="text" id="workflowName" placeholder="My Workflow">
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea id="workflowDescription" placeholder="What does this workflow do?"></textarea>
        </div>
        <div class="modal-actions">
            <button class="toolbar-btn" onclick="builder.closeSaveModal()">Cancel</button>
            <button class="toolbar-btn primary" onclick="builder.saveWorkflow()" id="saveBtn">Save</button>
        </div>
    </div>
</div>

<!-- Connection preview line -->
<div id="connectionPreview" style="display:none; position:absolute; z-index:1000; pointer-events:none;">
    <svg style="overflow:visible;">
        <path id="previewPath" fill="none" stroke="#6c63ff" stroke-width="2" stroke-dasharray="5,5" />
    </svg>
</div>

<!-- Existing Workflow Data (for loading into builder) -->
@if(isset($existingWorkflow) && $existingWorkflow)
<script type="application/json" id="existingWorkflowData">@json($existingWorkflow)</script>
@endif

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Context Menu -->
<div class="context-menu" id="contextMenu">
    <div class="context-menu-item" onclick="builder.contextCopy()"><i class="fas fa-copy"></i> Copy</div>
    <div class="context-menu-item" onclick="builder.contextPaste()"><i class="fas fa-paste"></i> Paste</div>
    <div class="context-menu-item" onclick="builder.contextDuplicate()"><i class="fas fa-clone"></i> Duplicate</div>
    <div class="context-menu-divider"></div>
    <div class="context-menu-item" onclick="builder.contextTestNode()"><i class="fas fa-play"></i> Test This Node</div>
    <div class="context-menu-divider"></div>
    <div class="context-menu-item danger" onclick="builder.contextDelete()"><i class="fas fa-trash"></i> Delete</div>
</div>
</div>
@endsection


@push('scripts')
<script>
class WorkflowBuilder {
    constructor() {
        this.nodes = [];
        this.connections = [];
        this.selectedNode = null;
        this.isDragging = false;
        this.isConnecting = false;
        this.connectingFrom = null;
        this.dragOffset = { x: 0, y: 0 };
        this.zoom = 1;
        this.pan = { x: 0, y: 0 };
        this.history = [];
        this.historyIndex = -1;
        this.nodeIdCounter = 0;
        this.workflowId = null;
        this.templates = [];
        this.copiedNode = null;
        this.isSelecting = false;
        this.selectionStart = { x: 0, y: 0 };
        this.selectionEnd = { x: 0, y: 0 };
        this.selectedNodes = new Set();
        this.autoSaveInterval = null;
        this.contextMenuTarget = null;
        
        this.container = document.getElementById('canvasContainer');
        this.nodesLayer = document.getElementById('nodesLayer');
        this.connectionsLayer = document.getElementById('connectionsLayer');
        this.propertiesBody = document.getElementById('propertiesBody');
        this.emptyState = document.getElementById('emptyState');
        
        this.init();
    }
    
    init() {
        this.resizeCanvas();
        window.addEventListener('resize', () => this.resizeCanvas());
        
        // Drag and drop from palette
        document.querySelectorAll('.palette-node').forEach(node => {
            node.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('nodeType', node.dataset.type);
                e.dataTransfer.setData('nodeSubtype', node.dataset.subtype);
            });
        });
        
        this.container.addEventListener('dragover', (e) => e.preventDefault());
        this.container.addEventListener('drop', (e) => this.handleDrop(e));
        
        // Canvas interactions
        this.container.addEventListener('mousedown', (e) => this.handleMouseDown(e));
        this.container.addEventListener('mousemove', (e) => this.handleMouseMove(e));
        this.container.addEventListener('mouseup', (e) => this.handleMouseUp(e));
        this.container.addEventListener('contextmenu', (e) => this.handleContextMenu(e));
        this.container.addEventListener('wheel', (e) => this.handleWheel(e));
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => this.handleKeyDown(e));
        
        // Close context menu on click
        document.addEventListener('click', () => this.hideContextMenu());
        
        // Load templates
        this.loadTemplates();
        
        // Load existing workflow if editing
        this.loadExistingWorkflow();
        
        // Start auto-save
        this.startAutoSave();
        
        // Show keyboard hint
        setTimeout(() => {
            document.getElementById('keyboardHint').classList.add('show');
            setTimeout(() => document.getElementById('keyboardHint').classList.remove('show'), 6000);
        }, 1500);
        
        // Draw initial state
        this.draw();
        this.updateEmptyState();
    }
    
    resizeCanvas() {
        const rect = this.container.getBoundingClientRect();
        this.connectionsLayer.setAttribute('width', rect.width);
        this.connectionsLayer.setAttribute('height', rect.height);
        this.draw();
        this.drawMinimap();
    }
    
    handleDrop(e) {
        e.preventDefault();
        const rect = this.container.getBoundingClientRect();
        const x = Math.round(((e.clientX - rect.left - this.pan.x) / this.zoom) / 10) * 10;
        const y = Math.round(((e.clientY - rect.top - this.pan.y) / this.zoom) / 10) * 10;
        
        const nodeType = e.dataTransfer.getData('nodeType');
        const nodeSubtype = e.dataTransfer.getData('nodeSubtype');
        
        this.addNode(nodeType, nodeSubtype, x, y);
    }
    
    addNode(type, subtype, x, y, config = null) {
        const id = ++this.nodeIdCounter;
        const node = {
            id: id,
            type: type,
            subtype: subtype,
            x: x,
            y: y,
            config: config || {},
            label: this.getNodeLabel(type, subtype),
            status: 'ready'
        };
        
        this.nodes.push(node);
        this.renderNode(node);
        this.saveHistory();
        this.updateInfoBar();
        this.selectNode(node);
        this.drawMinimap();
        this.updateEmptyState();
    }
    
    getNodeLabel(type, subtype) {
        const labels = {
            trigger: { post_published: 'Post Published', comment_received: 'Comment Received', mention_received: 'Mention Received', message_received: 'DM Received', schedule: 'Schedule', webhook: 'Webhook' },
            action: { send_notification: 'Send Notification', auto_reply: 'Auto Reply', create_post: 'Create Post', schedule_post: 'Schedule Post', ai_generate: 'AI Generate', tag_client: 'Tag Client', send_email: 'Send Email', webhook_call: 'Webhook Call' },
            condition: { if: 'Condition' },
            util: { delay: 'Delay', loop: 'Loop' }
        };
        return labels[type]?.[subtype] || 'Unknown';
    }
    
    renderNode(node) {
        const el = document.createElement('div');
        el.className = 'workflow-node';
        el.id = `node-${node.id}`;
        el.style.left = `${node.x}px`;
        el.style.top = `${node.y}px`;
        
        const typeLabels = { trigger: 'Trigger', action: 'Action', condition: 'Logic', util: 'Utility' };
        
        el.innerHTML = `
            ${node.type !== 'trigger' ? `<div class="node-port input" data-node="${node.id}" data-port="input"></div>` : ''}
            <div class="node-header ${node.type}">
                <i class="fas ${this.getNodeIcon(node.type, node.subtype)}"></i>
                <span>${node.label}</span>
                <div class="node-header-badge" id="node-badge-${node.id}"></div>
            </div>
            <div class="node-body" id="node-body-${node.id}">
                <div>${Object.keys(node.config).length > 0 ? this.getConfigSummary(node) : 'Double-click to configure'}</div>
                <div class="node-status">
                    <div class="node-status-dot ${node.status}" id="node-status-${node.id}"></div>
                    <span id="node-status-text-${node.id}">${node.status}</span>
                </div>
            </div>
            ${node.type !== 'condition' ? `<div class="node-port output" data-node="${node.id}" data-port="output"></div>` : ''}
        `;
        
        this.nodesLayer.appendChild(el);
    }
    
    getConfigSummary(node) {
        const config = node.config;
        if (config.platform) return `Platform: ${config.platform}`;
        if (config.message) return config.message.substring(0, 30) + (config.message.length > 30 ? '...' : '');
        if (config.cron) return `Cron: ${config.cron}`;
        if (config.prompt) return config.prompt.substring(0, 30) + (config.prompt.length > 30 ? '...' : '');
        if (config.url) return `URL: ${config.url.substring(0, 30)}...`;
        if (config.to) return `To: ${config.to}`;
        if (config.subject) return `Subject: ${config.subject}`;
        if (config.seconds) return `Wait: ${config.seconds}s`;
        if (config.iterations) return `Loop: ${config.iterations}x`;
        if (config.field) return `${config.field} ${config.operator || ''} ${config.value || ''}`;
        return 'Configured';
    }
    
    getNodeIcon(type, subtype) {
        const icons = {
            trigger: { post_published: 'fa-pen-fancy', comment_received: 'fa-comment', mention_received: 'fa-at', message_received: 'fa-envelope', schedule: 'fa-clock', webhook: 'fa-globe' },
            action: { send_notification: 'fa-bell', auto_reply: 'fa-reply', create_post: 'fa-plus', schedule_post: 'fa-calendar', ai_generate: 'fa-robot', tag_client: 'fa-tag', send_email: 'fa-paper-plane', webhook_call: 'fa-external-link-alt' },
            condition: { if: 'fa-question' },
            util: { delay: 'fa-hourglass-half', loop: 'fa-redo' }
        };
        return icons[type]?.[subtype] || 'fa-cube';
    }
    
    handleMouseDown(e) {
        if (e.button === 2) return; // Right click handled by contextmenu
        
        const port = e.target.closest('.node-port');
        const nodeEl = e.target.closest('.workflow-node');
        
        if (port && port.dataset.port === 'output') {
            this.isConnecting = true;
            this.connectingFrom = parseInt(port.dataset.node);
            port.classList.add('connecting');
            this.showConnectionPreview(e);
        } else if (nodeEl) {
            const node = this.nodes.find(n => n.id === parseInt(nodeEl.id.replace('node-', '')));
            if (node) {
                if (e.ctrlKey || e.metaKey) {
                    if (this.selectedNodes.has(node.id)) {
                        this.selectedNodes.delete(node.id);
                    } else {
                        this.selectedNodes.add(node.id);
                    }
                    this.updateSelection();
                } else {
                    this.selectNode(node);
                }
                this.isDragging = true;
                nodeEl.classList.add('dragging');
                const rect = nodeEl.getBoundingClientRect();
                this.dragOffset = { x: e.clientX - rect.left, y: e.clientY - rect.top };
            }
        } else {
            this.isSelecting = true;
            const rect = this.container.getBoundingClientRect();
            this.selectionStart = { x: e.clientX - rect.left, y: e.clientY - rect.top };
            this.selectionEnd = { x: e.clientX - rect.left, y: e.clientY - rect.top };
            this.selectedNode = null;
            this.selectedNodes.clear();
            this.updateSelection();
            this.showPropertiesEmpty();
        }
    }
    
    handleMouseMove(e) {
        if (this.isDragging && this.selectedNode) {
            const rect = this.container.getBoundingClientRect();
            const x = Math.round(((e.clientX - rect.left - this.dragOffset.x - this.pan.x) / this.zoom) / 10) * 10;
            const y = Math.round(((e.clientY - rect.top - this.dragOffset.y - this.pan.y) / this.zoom) / 10) * 10;
            
            this.selectedNode.x = Math.max(0, x);
            this.selectedNode.y = Math.max(0, y);
            
            const el = document.getElementById(`node-${this.selectedNode.id}`);
            el.style.left = `${this.selectedNode.x}px`;
            el.style.top = `${this.selectedNode.y}px`;
            
            this.draw();
            this.drawMinimap();
        } else if (this.isConnecting) {
            this.updateConnectionPreview(e);
        } else if (this.isSelecting) {
            const rect = this.container.getBoundingClientRect();
            this.selectionEnd = { x: e.clientX - rect.left, y: e.clientY - rect.top };
            this.updateSelectionBox();
        }
    }
    
    handleMouseUp(e) {
        if (this.isConnecting) {
            const port = e.target.closest('.node-port');
            if (port && port.dataset.port === 'input') {
                const toNodeId = parseInt(port.dataset.node);
                if (toNodeId !== this.connectingFrom) {
                    this.addConnection(this.connectingFrom, toNodeId);
                }
            }
            this.hideConnectionPreview();
            document.querySelectorAll('.node-port.connecting').forEach(p => p.classList.remove('connecting'));
            this.isConnecting = false;
            this.connectingFrom = null;
        }
        
        if (this.isDragging) {
            document.querySelectorAll('.workflow-node.dragging').forEach(el => el.classList.remove('dragging'));
            this.saveHistory();
            this.draw();
        }
        this.isDragging = false;
        
        if (this.isSelecting) {
            this.isSelecting = false;
            this.hideSelectionBox();
            this.selectNodesInBox();
        }
    }
    
    handleContextMenu(e) {
        e.preventDefault();
        const nodeEl = e.target.closest('.workflow-node');
        
        if (nodeEl) {
            const node = this.nodes.find(n => n.id === parseInt(nodeEl.id.replace('node-', '')));
            if (node) {
                this.selectNode(node);
                this.contextMenuTarget = node;
                this.showContextMenu(e.clientX, e.clientY);
            }
        } else {
            this.contextMenuTarget = null;
            this.showContextMenu(e.clientX, e.clientY);
        }
    }
    
    handleWheel(e) {
        e.preventDefault();
        const delta = e.deltaY > 0 ? -0.1 : 0.1;
        this.zoom = Math.max(0.5, Math.min(2, this.zoom + delta));
        this.updateZoom();
    }
    
    handleKeyDown(e) {
        if (e.key === 'Delete' && this.selectedNode) {
            this.deleteNode(this.selectedNode);
        }
        if (e.ctrlKey && e.key === 'z') {
            e.preventDefault();
            this.undo();
        }
        if (e.ctrlKey && e.key === 'y') {
            e.preventDefault();
            this.redo();
        }
        if (e.ctrlKey && e.key === 'c' && this.selectedNode) {
            e.preventDefault();
            this.copyNode(this.selectedNode);
        }
        if (e.ctrlKey && e.key === 'v' && this.copiedNode) {
            e.preventDefault();
            this.pasteNode();
        }
        if (e.ctrlKey && e.key === 'a') {
            e.preventDefault();
            this.selectAll();
        }
        if (e.key === 'Escape') {
            this.selectedNode = null;
            this.selectedNodes.clear();
            this.updateSelection();
        }
    }
    
    selectNode(node) {
        this.selectedNode = node;
        this.selectedNodes.clear();
        this.selectedNodes.add(node.id);
        this.updateSelection();
        this.showPropertiesPanel(node);
    }
    
    updateSelection() {
        document.querySelectorAll('.workflow-node').forEach(el => el.classList.remove('selected'));
        this.selectedNodes.forEach(nodeId => {
            const el = document.getElementById(`node-${nodeId}`);
            if (el) el.classList.add('selected');
        });
    }
    
    selectAll() {
        this.selectedNodes = new Set(this.nodes.map(n => n.id));
        this.updateSelection();
    }
    
    copyNode(node) {
        this.copiedNode = JSON.parse(JSON.stringify(node));
        this.toast('Node copied', 'info');
    }
    
    pasteNode() {
        if (!this.copiedNode) return;
        const newNode = JSON.parse(JSON.stringify(this.copiedNode));
        newNode.id = ++this.nodeIdCounter;
        newNode.x += 30;
        newNode.y += 30;
        this.nodes.push(newNode);
        this.renderNode(newNode);
        this.saveHistory();
        this.updateInfoBar();
        this.selectNode(newNode);
        this.drawMinimap();
        this.updateEmptyState();
        this.toast('Node pasted', 'success');
    }
    
    showPropertiesEmpty() {
        this.propertiesBody.innerHTML = `
            <div class="properties-empty" id="propertiesEmpty">
                <i class="fas fa-mouse-pointer"></i>
                <h5>Select a Node</h5>
                <p>Click on a node in the canvas to view and edit its properties here.</p>
            </div>
        `;
    }
    
    showPropertiesPanel(node) {
        const configFields = this.getNodeConfigFields(node);
        const typeLabels = { trigger: 'Trigger', action: 'Action', condition: 'Logic', util: 'Utility' };
        
        this.propertiesBody.innerHTML = `
            <div class="property-section">
                <div class="node-type-badge ${node.type}">${typeLabels[node.type]}</div>
            </div>
            <div class="property-section">
                <div class="property-section-title">General</div>
                <div class="form-group">
                    <label>Label</label>
                    <input type="text" value="${node.label}" onchange="builder.updateNodeLabel(this.value)">
                </div>
            </div>
            <div class="property-section">
                <div class="property-section-title">Configuration</div>
                ${configFields}
            </div>
            <div class="property-section">
                <button class="toolbar-btn danger" style="width:100%;justify-content:center" onclick="builder.deleteNode(builder.selectedNode)">
                    <i class="fas fa-trash"></i> Delete Node
                </button>
            </div>
        `;
    }
    
    getNodeConfigFields(node) {
        const configs = {
            trigger: {
                post_published: `<div class="form-group"><label>Platform</label><select onchange="builder.updateNodeConfig('platform', this.value)"><option value="">Any</option><option value="facebook">Facebook</option><option value="instagram">Instagram</option><option value="twitter">Twitter</option><option value="linkedin">LinkedIn</option></select></div>`,
                schedule: `<div class="form-group"><label>Cron Expression</label><input type="text" value="${node.config.cron || ''}" placeholder="0 9 * * *" onchange="builder.updateNodeConfig('cron', this.value)"></div>`,
                webhook: `<div class="form-group"><label>Webhook URL</label><input type="url" value="${node.config.url || ''}" placeholder="https://..." onchange="builder.updateNodeConfig('url', this.value)"></div>`
            },
            action: {
                send_notification: `<div class="form-group"><label>Message</label><textarea rows="2" onchange="builder.updateNodeConfig('message', this.value)">${node.config.message || 'New post published!'}</textarea></div>`,
                auto_reply: `<div class="form-group"><label>Reply Message</label><textarea rows="2" onchange="builder.updateNodeConfig('message', this.value)">${node.config.message || 'Thanks for reaching out!'}</textarea></div>`,
                create_post: `<div class="form-group"><label>Content</label><textarea rows="3" onchange="builder.updateNodeConfig('content', this.value)">${node.config.content || ''}</textarea></div>`,
                ai_generate: `<div class="form-group"><label>Prompt</label><textarea rows="3" onchange="builder.updateNodeConfig('prompt', this.value)">${node.config.prompt || 'Generate a social post about...'}</textarea></div>`,
                send_email: `<div class="form-group"><label>To</label><input type="email" value="${node.config.to || ''}" onchange="builder.updateNodeConfig('to', this.value)"></div><div class="form-group"><label>Subject</label><input type="text" value="${node.config.subject || ''}" onchange="builder.updateNodeConfig('subject', this.value)"></div>`,
                webhook_call: `<div class="form-group"><label>URL</label><input type="url" value="${node.config.url || ''}" onchange="builder.updateNodeConfig('url', this.value)"></div><div class="form-group"><label>Method</label><select onchange="builder.updateNodeConfig('method', this.value)"><option value="POST">POST</option><option value="GET">GET</option><option value="PUT">PUT</option></select></div>`
            },
            condition: {
                if: `<div class="form-group"><label>Field</label><input type="text" value="${node.config.field || ''}" placeholder="platform" onchange="builder.updateNodeConfig('field', this.value)"></div><div class="form-group"><label>Operator</label><select onchange="builder.updateNodeConfig('operator', this.value)"><option value="equals">Equals</option><option value="contains">Contains</option><option value="not_empty">Not Empty</option></select></div><div class="form-group"><label>Value</label><input type="text" value="${node.config.value || ''}" onchange="builder.updateNodeConfig('value', this.value)"></div>`
            },
            util: {
                delay: `<div class="form-group"><label>Seconds</label><input type="number" value="${node.config.seconds || 5}" onchange="builder.updateNodeConfig('seconds', this.value)"></div>`,
                loop: `<div class="form-group"><label>Iterations</label><input type="number" value="${node.config.iterations || 5}" onchange="builder.updateNodeConfig('iterations', this.value)"></div>`
            }
        };
        
        return configs[node.type]?.[node.subtype] || '<p style="color:#8892b0;font-size:12px">No additional configuration</p>';
    }
    
    updateNodeConfig(key, value) {
        if (this.selectedNode) {
            this.selectedNode.config[key] = value;
            const bodyEl = document.getElementById(`node-body-${this.selectedNode.id}`);
            if (bodyEl) {
                bodyEl.innerHTML = `<div>${this.getConfigSummary(this.selectedNode)}</div>
                    <div class="node-status"><div class="node-status-dot ${this.selectedNode.status}"></div><span>${this.selectedNode.status}</span></div>`;
            }
        }
    }
    
    updateNodeLabel(label) {
        if (this.selectedNode) {
            this.selectedNode.label = label;
            const el = document.getElementById(`node-${this.selectedNode.id}`);
            el.querySelector('.node-header span').textContent = label;
        }
    }
    
    deleteNode(node) {
        this.nodes = this.nodes.filter(n => n.id !== node.id);
        this.connections = this.connections.filter(c => c.from !== node.id && c.to !== node.id);
        const el = document.getElementById(`node-${node.id}`);
        if (el) el.remove();
        this.selectedNode = null;
        this.selectedNodes.delete(node.id);
        this.saveHistory();
        this.draw();
        this.updateInfoBar();
        this.drawMinimap();
        this.updateEmptyState();
        this.showPropertiesEmpty();
    }
    
    addConnection(fromId, toId) {
        if (this.connections.find(c => c.from === fromId && c.to === toId)) return;
        if (this.wouldCreateCycle(fromId, toId)) {
            this.toast('Cannot create circular connection', 'error');
            return;
        }
        this.connections.push({ from: fromId, to: toId });
        this.saveHistory();
        this.draw();
        this.updateInfoBar();
        this.drawMinimap();
    }
    
    wouldCreateCycle(fromId, toId) {
        const visited = new Set();
        const queue = [toId];
        while (queue.length > 0) {
            const current = queue.shift();
            if (current === fromId) return true;
            if (visited.has(current)) continue;
            visited.add(current);
            this.connections.filter(c => c.from === current).forEach(c => queue.push(c.to));
        }
        return false;
    }
    
    draw() {
        this.connectionsLayer.innerHTML = '';
        this.connections.forEach(conn => {
            const fromNode = this.nodes.find(n => n.id === conn.from);
            const toNode = this.nodes.find(n => n.id === conn.to);
            if (!fromNode || !toNode) return;
            
            const fromX = fromNode.x + 220;
            const fromY = fromNode.y + 40;
            const toX = toNode.x;
            const toY = toNode.y + 40;
            
            const cp1x = fromX + (toX - fromX) / 2;
            const cp1y = fromY;
            const cp2x = fromX + (toX - fromX) / 2;
            const cp2y = toY;
            
            const d = `M ${fromX} ${fromY} C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${toX} ${toY}`;
            
            // Hit area (invisible, wider)
            const hitArea = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            hitArea.setAttribute('d', d);
            hitArea.setAttribute('class', 'connection-hit-area');
            hitArea.addEventListener('click', (e) => {
                e.stopPropagation();
                if (confirm('Delete this connection?')) {
                    this.connections = this.connections.filter(c => !(c.from === conn.from && c.to === conn.to));
                    this.saveHistory();
                    this.draw();
                    this.updateInfoBar();
                }
            });
            this.connectionsLayer.appendChild(hitArea);
            
            // Visible path
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', d);
            path.setAttribute('class', 'connection-path');
            this.connectionsLayer.appendChild(path);
        });
        
        document.querySelectorAll('.node-port').forEach(port => port.classList.remove('connected'));
        this.connections.forEach(conn => {
            const fromPort = document.querySelector(`#node-${conn.from} .node-port.output`);
            const toPort = document.querySelector(`#node-${conn.to} .node-port.input`);
            if (fromPort) fromPort.classList.add('connected');
            if (toPort) toPort.classList.add('connected');
        });
    }
    
    drawMinimap() {
        const canvas = document.getElementById('minimapCanvas');
        const ctx = canvas.getContext('2d');
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = rect.height;
        
        ctx.fillStyle = '#1a1a2e';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        if (this.nodes.length === 0) return;
        
        const minX = Math.min(...this.nodes.map(n => n.x)) - 50;
        const minY = Math.min(...this.nodes.map(n => n.y)) - 50;
        const maxX = Math.max(...this.nodes.map(n => n.x + 220)) + 50;
        const maxY = Math.max(...this.nodes.map(n => n.y + 80)) + 50;
        
        const scaleX = canvas.width / (maxX - minX);
        const scaleY = canvas.height / (maxY - minY);
        const scale = Math.min(scaleX, scaleY) * 0.85;
        
        const offsetX = (canvas.width - (maxX - minX) * scale) / 2 - minX * scale;
        const offsetY = (canvas.height - (maxY - minY) * scale) / 2 - minY * scale;
        
        // Draw connections
        ctx.strokeStyle = '#6c63ff';
        ctx.lineWidth = 1;
        this.connections.forEach(conn => {
            const fromNode = this.nodes.find(n => n.id === conn.from);
            const toNode = this.nodes.find(n => n.id === conn.to);
            if (!fromNode || !toNode) return;
            
            ctx.beginPath();
            ctx.moveTo(fromNode.x * scale + offsetX + 110 * scale, fromNode.y * scale + offsetY + 20 * scale);
            ctx.lineTo(toNode.x * scale + offsetX, toNode.y * scale + offsetY + 20 * scale);
            ctx.stroke();
        });
        
        // Draw nodes
        this.nodes.forEach(node => {
            const colors = { trigger: '#10b981', action: '#6366f1', condition: '#f59e0b', util: '#6b7280' };
            ctx.fillStyle = colors[node.type] || '#6b7280';
            ctx.fillRect(node.x * scale + offsetX, node.y * scale + offsetY, 220 * scale, 40 * scale);
        });
    }
    
    showConnectionPreview(e) {
        document.getElementById('connectionPreview').style.display = 'block';
        const fromNode = this.nodes.find(n => n.id === this.connectingFrom);
        if (fromNode) {
            this.updatePreviewPath(fromNode.x + 220, fromNode.y + 40, e.offsetX, e.offsetY);
        }
    }
    
    updateConnectionPreview(e) {
        const fromNode = this.nodes.find(n => n.id === this.connectingFrom);
        if (fromNode) {
            this.updatePreviewPath(fromNode.x + 220, fromNode.y + 40, e.offsetX, e.offsetY);
        }
    }
    
    updatePreviewPath(x1, y1, x2, y2) {
        const cpx = (x1 + x2) / 2;
        document.getElementById('previewPath').setAttribute('d', `M ${x1} ${y1} C ${cpx} ${y1}, ${cpx} ${y2}, ${x2} ${y2}`);
    }
    
    hideConnectionPreview() {
        document.getElementById('connectionPreview').style.display = 'none';
    }
    
    updateSelectionBox() {
        let box = document.getElementById('selectionBox');
        if (!box) {
            box = document.createElement('div');
            box.id = 'selectionBox';
            box.className = 'selection-box';
            this.container.appendChild(box);
        }
        
        const x = Math.min(this.selectionStart.x, this.selectionEnd.x);
        const y = Math.min(this.selectionStart.y, this.selectionEnd.y);
        const w = Math.abs(this.selectionEnd.x - this.selectionStart.x);
        const h = Math.abs(this.selectionEnd.y - this.selectionStart.y);
        
        box.style.left = `${x}px`;
        box.style.top = `${y}px`;
        box.style.width = `${w}px`;
        box.style.height = `${h}px`;
        box.style.display = 'block';
    }
    
    hideSelectionBox() {
        const box = document.getElementById('selectionBox');
        if (box) box.style.display = 'none';
    }
    
    selectNodesInBox() {
        const x1 = Math.min(this.selectionStart.x, this.selectionEnd.x);
        const y1 = Math.min(this.selectionStart.y, this.selectionEnd.y);
        const x2 = Math.max(this.selectionStart.x, this.selectionEnd.x);
        const y2 = Math.max(this.selectionStart.y, this.selectionEnd.y);
        
        this.selectedNodes.clear();
        this.nodes.forEach(node => {
            const nodeX = node.x * this.zoom + this.pan.x;
            const nodeY = node.y * this.zoom + this.pan.y;
            if (nodeX >= x1 && nodeX <= x2 && nodeY >= y1 && nodeY <= y2) {
                this.selectedNodes.add(node.id);
            }
        });
        this.updateSelection();
    }
    
    saveHistory() {
        this.history = this.history.slice(0, this.historyIndex + 1);
        this.history.push(JSON.stringify({ nodes: this.nodes, connections: this.connections }));
        this.historyIndex++;
    }
    
    undo() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            this.restoreState(JSON.parse(this.history[this.historyIndex]));
        }
    }
    
    redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            this.restoreState(JSON.parse(this.history[this.historyIndex]));
        }
    }
    
    restoreState(state) {
        this.nodes = state.nodes;
        this.connections = state.connections;
        this.nodesLayer.innerHTML = '';
        this.nodes.forEach(node => this.renderNode(node));
        this.selectedNode = null;
        this.selectedNodes.clear();
        this.updateSelection();
        this.draw();
        this.updateInfoBar();
        this.drawMinimap();
        this.updateEmptyState();
    }
    
    zoomIn() { this.zoom = Math.min(this.zoom + 0.1, 2); this.updateZoom(); }
    zoomOut() { this.zoom = Math.max(this.zoom - 0.1, 0.5); this.updateZoom(); }
    fitView() { this.zoom = 1; this.updateZoom(); }
    
    updateZoom() {
        const pct = Math.round(this.zoom * 100) + '%';
        document.getElementById('zoomLevel').textContent = pct;
        document.getElementById('zoomLevel2').textContent = pct;
        this.nodesLayer.style.transform = `scale(${this.zoom})`;
        this.nodesLayer.style.transformOrigin = '0 0';
        this.draw();
    }
    
    updateInfoBar() {
        document.getElementById('nodeCount').textContent = this.nodes.length;
        document.getElementById('connectionCount').textContent = this.connections.length;
    }
    
    updateEmptyState() {
        if (this.nodes.length === 0) {
            this.emptyState.style.display = 'block';
        } else {
            this.emptyState.style.display = 'none';
        }
    }
    
    clearCanvas() {
        if (this.nodes.length === 0) return;
        if (confirm('Clear all nodes and connections?')) {
            this.nodes = [];
            this.connections = [];
            this.nodesLayer.innerHTML = '';
            this.selectedNode = null;
            this.selectedNodes.clear();
            this.saveHistory();
            this.draw();
            this.updateInfoBar();
            this.drawMinimap();
            this.updateEmptyState();
            this.showPropertiesEmpty();
        }
    }
    
    showContextMenu(x, y) {
        const menu = document.getElementById('contextMenu');
        menu.style.left = `${x}px`;
        menu.style.top = `${y}px`;
        menu.classList.add('show');
    }
    
    hideContextMenu() {
        document.getElementById('contextMenu').classList.remove('show');
    }
    
    contextCopy() {
        if (this.contextMenuTarget) this.copyNode(this.contextMenuTarget);
        this.hideContextMenu();
    }
    
    contextPaste() {
        this.pasteNode();
        this.hideContextMenu();
    }
    
    contextDuplicate() {
        if (this.contextMenuTarget) {
            this.copyNode(this.contextMenuTarget);
            this.pasteNode();
        }
        this.hideContextMenu();
    }
    
    contextTestNode() {
        this.toast('Node testing not yet implemented', 'info');
        this.hideContextMenu();
    }
    
    contextDelete() {
        if (this.contextMenuTarget) this.deleteNode(this.contextMenuTarget);
        this.hideContextMenu();
    }
    
    showHelp() {
        document.getElementById('helpPanel').classList.toggle('show');
    }
    
    hideLog() { document.getElementById('executionLog').classList.remove('show'); }
    
    log(message, type = 'info') {
        const body = document.getElementById('logBody');
        const time = new Date().toLocaleTimeString();
        const entry = document.createElement('div');
        entry.className = `log-entry ${type}`;
        entry.innerHTML = `<span class="timestamp">[${time}]</span> <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i> ${message}`;
        body.appendChild(entry);
        body.scrollTop = body.scrollHeight;
    }
    
    showTemplates() { document.getElementById('templatesDropdown').classList.add('show'); }
    hideTemplates() { document.getElementById('templatesDropdown').classList.remove('show'); }
    
    async loadTemplates() {
        try {
            this.templates = [
                { name: 'Auto-Reply to Comments', description: 'Automatically reply to comments', category: 'social', icon: 'fa-reply', nodes: [{id:1,type:'trigger',subtype:'comment_received',x:100,y:200,config:{},label:'Comment Received'},{id:2,type:'action',subtype:'auto_reply',x:350,y:200,config:{message:'Thanks!'},label:'Auto Reply'}], connections:[{from:1,to:2}] },
                { name: 'New Post Notification', description: 'Notify when post is published', category: 'social', icon: 'fa-bell', nodes: [{id:1,type:'trigger',subtype:'post_published',x:100,y:200,config:{},label:'Post Published'},{id:2,type:'action',subtype:'send_notification',x:350,y:200,config:{message:'New post published!'},label:'Send Notification'}], connections:[{from:1,to:2}] },
                { name: 'AI Content Generator', description: 'Generate content on schedule', category: 'content', icon: 'fa-robot', nodes: [{id:1,type:'trigger',subtype:'schedule',x:100,y:200,config:{cron:'0 9 * * *'},label:'Daily at 9am'},{id:2,type:'action',subtype:'ai_generate',x:350,y:200,config:{prompt:'Generate a social post'},label:'AI Generate'},{id:3,type:'action',subtype:'create_post',x:600,y:200,config:{},label:'Create Post'}], connections:[{from:1,to:2},{from:2,to:3}] },
            ];
            
            const list = document.getElementById('templatesList');
            list.innerHTML = this.templates.map((t, i) => `
                <div class="template-card" onclick="builder.useTemplate(${i})">
                    <h6><i class="fas ${t.icon || 'fa-project-diagram'}"></i> ${t.name}</h6>
                    <p>${t.description || 'No description'}</p>
                    <span class="badge badge-${this.getCategoryColor(t.category)}">${t.category}</span>
                </div>
            `).join('');
        } catch (e) {
            console.error('Failed to load templates:', e);
        }
    }
    
    getCategoryColor(category) {
        const colors = { social: 'success', content: 'info', engagement: 'warning', analytics: 'primary', automation: 'danger' };
        return colors[category] || 'secondary';
    }
    
    useTemplate(index) {
        const template = this.templates[index];
        if (!template) return;
        
        this.nodes = [];
        this.connections = [];
        this.nodesLayer.innerHTML = '';
        this.nodeIdCounter = 0;
        
        template.nodes.forEach(node => {
            this.nodeIdCounter = Math.max(this.nodeIdCounter, node.id);
            this.nodes.push({...node});
            this.renderNode(node);
        });
        
        template.connections.forEach(conn => {
            this.connections.push({...conn});
        });
        
        this.hideTemplates();
        this.saveHistory();
        this.draw();
        this.updateInfoBar();
        this.selectedNode = null;
        this.updateSelection();
        this.drawMinimap();
        this.updateEmptyState();
    }
    
    loadExistingWorkflow() {
        const workflowData = document.getElementById('existingWorkflowData');
        if (workflowData) {
            try {
                const workflow = JSON.parse(workflowData.textContent);
                this.workflowId = workflow.id;
                this.nodeIdCounter = 0;
                
                // Load from nodes/connections (visual builder format)
                if (workflow.nodes && workflow.nodes.length > 0) {
                    workflow.nodes.forEach(node => {
                        this.nodeIdCounter = Math.max(this.nodeIdCounter, node.id);
                        this.nodes.push({
                            id: node.id,
                            type: node.type,
                            subtype: node.subtype,
                            x: node.x,
                            y: node.y,
                            config: node.config || {},
                            label: node.label || this.getNodeLabel(node.type, node.subtype),
                            status: 'ready'
                        });
                        this.renderNode(node);
                    });
                    
                    if (workflow.connections) {
                        workflow.connections.forEach(conn => {
                            this.connections.push({...conn});
                        });
                    }
                }
                // Fallback: load from actions array (legacy format)
                else if (workflow.actions && workflow.actions.length > 0) {
                    let x = 100, y = 200;
                    // Create trigger node
                    const triggerNode = {
                        id: ++this.nodeIdCounter,
                        type: 'trigger',
                        subtype: workflow.trigger_type || 'manual',
                        x: x, y: y,
                        config: workflow.trigger_config || {},
                        label: this.getNodeLabel('trigger', workflow.trigger_type || 'manual'),
                        status: 'ready'
                    };
                    this.nodes.push(triggerNode);
                    this.renderNode(triggerNode);
                    
                    // Create action nodes
                    workflow.actions.forEach((action, idx) => {
                        const actionNode = {
                            id: ++this.nodeIdCounter,
                            type: 'action',
                            subtype: action.type,
                            x: x + 250 * (idx + 1), y: y,
                            config: action.config || {},
                            label: this.getNodeLabel('action', action.type),
                            status: 'ready'
                        };
                        this.nodes.push(actionNode);
                        this.renderNode(actionNode);
                        
                        // Connect previous to this
                        this.connections.push({ from: this.nodes[this.nodes.length - 2].id, to: actionNode.id });
                    });
                }
                
                this.draw();
                this.updateInfoBar();
                this.drawMinimap();
                this.updateEmptyState();
                this.saveHistory();
                
                // Pre-fill save modal
                const nameEl = document.getElementById('workflowName');
                const descEl = document.getElementById('workflowDescription');
                if (nameEl && workflow.name) nameEl.value = workflow.name;
                if (descEl && workflow.description) descEl.value = workflow.description;
                
            } catch (e) {
                console.error('Failed to load existing workflow:', e);
            }
        }
    }
    
    testWorkflow() {
        document.getElementById('executionLog').classList.add('show');
        document.getElementById('logBody').innerHTML = '';
        this.log('Starting workflow test...', 'info');
        
        let delay = 600;
        this.nodes.forEach((node, i) => {
            setTimeout(() => {
                const el = document.getElementById(`node-${node.id}`);
                el.classList.add('executing');
                node.status = 'running';
                this.updateNodeStatus(node);
                this.log(`Executing: ${node.label}`, 'info');
                setTimeout(() => {
                    el.classList.remove('executing');
                    el.classList.add('success');
                    node.status = 'success';
                    this.updateNodeStatus(node);
                    this.log(`Completed: ${node.label}`, 'success');
                    setTimeout(() => el.classList.remove('success'), 1000);
                }, 500);
            }, delay * (i + 1));
        });
        
        setTimeout(() => this.log('Workflow test completed successfully!', 'success'), delay * (this.nodes.length + 1));
    }
    
    updateNodeStatus(node) {
        const statusEl = document.getElementById(`node-status-${node.id}`);
        const statusText = document.getElementById(`node-status-text-${node.id}`);
        const badge = document.getElementById(`node-badge-${node.id}`);
        if (statusEl) statusEl.className = `node-status-dot ${node.status}`;
        if (statusText) statusText.textContent = node.status;
        if (badge) badge.className = `node-header-badge ${node.status === 'success' ? 'connected' : ''}`;
    }
    
    openSaveModal() {
        if (this.nodes.length === 0) {
            this.toast('Add at least one node before saving.', 'error');
            return;
        }
        document.getElementById('saveModal').classList.add('show');
    }
    
    closeSaveModal() {
        document.getElementById('saveModal').classList.remove('show');
    }
    
    saveWorkflow() {
        const name = document.getElementById('workflowName').value.trim();
        if (!name) {
            this.toast('Please enter a workflow name.', 'error');
            return;
        }
        
        const description = document.getElementById('workflowDescription').value.trim();
        const saveBtn = document.getElementById('saveBtn');
        saveBtn.innerHTML = '<span class="spinner"></span> Saving...';
        saveBtn.disabled = true;
        
        const workflow = {
            name: name,
            description: description,
            nodes: this.nodes,
            connections: this.connections
        };
        
        const url = this.workflowId 
            ? `/workflows/builder/update/${this.workflowId}`
            : '/workflows/builder/save';
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(workflow)
        })
        .then(res => res.json())
        .then(data => {
            saveBtn.innerHTML = 'Save';
            saveBtn.disabled = false;
            if (data.success) {
                this.toast('Workflow saved successfully!', 'success');
                this.closeSaveModal();
                setTimeout(() => window.location.href = `/workflows/${data.workflow.id}`, 1000);
            } else {
                this.toast('Error saving workflow: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(err => {
            saveBtn.innerHTML = 'Save';
            saveBtn.disabled = false;
            this.toast('Error saving workflow. Please try again.', 'error');
            console.error(err);
        });
    }
    
    activateWorkflow() {
        if (this.nodes.length === 0) {
            this.toast('Add at least one node before activating.', 'error');
            return;
        }
        this.toast('Save the workflow first, then activate it from the workflow detail page.', 'info');
    }
    
    startAutoSave() {
        this.autoSaveInterval = setInterval(() => {
            if (this.nodes.length > 0) {
                localStorage.setItem('workflow-builder-autosave', JSON.stringify({
                    nodes: this.nodes,
                    connections: this.connections,
                    timestamp: Date.now()
                }));
            }
        }, 30000);
    }
    
    toast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        const icons = { success: 'check-circle', error: 'exclamation-circle', info: 'info-circle' };
        toast.innerHTML = `<i class="fas fa-${icons[type] || 'info-circle'}"></i> <span>${message}</span>`;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
}

const builder = new WorkflowBuilder();
</script>
@endpush
