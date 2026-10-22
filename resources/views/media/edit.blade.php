@extends('layouts.unified')
@section('title', 'Edit: '.$asset->name)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
<style>
    .editor-container {
        display: flex;
        height: calc(100vh - 140px);
        position: relative;
    }
    .editor-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .editor-toolbar {
        background: #1f2937;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
        border-bottom: 1px solid #374151;
    }
    .toolbar-group {
        display: flex;
        align-items: center;
        gap: 2px;
        padding-right: 8px;
        border-right: 1px solid #374151;
        margin-right: 4px;
    }
    .toolbar-group:last-child {
        border-right: none;
    }
    .toolbar-btn {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 6px 10px;
        background: transparent;
        border: 1px solid transparent;
        border-radius: 4px;
        color: #d1d5db;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.15s;
        white-space: nowrap;
    }
    .toolbar-btn:hover {
        background: #374151;
        color: #ffffff;
    }
    .toolbar-btn.active {
        background: #2563eb;
        color: #ffffff;
    }
    .toolbar-btn svg {
        width: 16px;
        height: 16px;
    }
    .canvas-area {
        flex: 1;
        background: #111827;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: auto;
        padding: 20px;
    }
    .canvas-area canvas {
        max-width: 100%;
        max-height: 100%;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
        cursor: crosshair;
    }
    .editor-sidebar {
        width: 260px;
        background: #1f2937;
        border-left: 1px solid #374151;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }
    .sidebar-section {
        padding: 12px 16px;
        border-bottom: 1px solid #374151;
    }
    .sidebar-section h4 {
        color: #9ca3af;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }
    .sidebar-row {
        display: flex;
        justify-content: space-between;
        color: #d1d5db;
        font-size: 12px;
        margin-bottom: 4px;
    }
    .sidebar-row .label {
        color: #9ca3af;
    }
    .filter-slider {
        width: 100%;
        margin-bottom: 8px;
    }
    .filter-slider label {
        display: flex;
        justify-content: space-between;
        color: #d1d5db;
        font-size: 11px;
        margin-bottom: 2px;
    }
    .filter-slider input[type="range"] {
        width: 100%;
        height: 4px;
        accent-color: #2563eb;
    }
    .resize-inputs {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .resize-inputs input {
        width: 70px;
        padding: 4px 6px;
        background: #374151;
        border: 1px solid #4b5563;
        border-radius: 3px;
        color: #d1d5db;
        font-size: 11px;
        text-align: center;
    }
    .resize-inputs span {
        color: #6b7280;
        font-size: 11px;
    }
    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #d1d5db;
        font-size: 11px;
        cursor: pointer;
    }
    .editor-footer {
        background: #1f2937;
        padding: 8px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #374151;
    }
    .footer-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .footer-left span {
        color: #9ca3af;
        font-size: 11px;
    }
    .quota-bar {
        width: 150px;
        height: 4px;
        background: #374151;
        border-radius: 2px;
        overflow: hidden;
    }
    .quota-fill {
        height: 100%;
        background: #10b981;
        border-radius: 2px;
        transition: width 0.3s;
    }
    .quota-fill.warning {
        background: #f59e0b;
    }
    .quota-fill.danger {
        background: #ef4444;
    }
    .footer-right {
        display: flex;
        gap: 8px;
    }
    .btn-save {
        padding: 6px 16px;
        background: #2563eb;
        color: #ffffff;
        border: none;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.15s;
    }
    .btn-save:hover {
        background: #1d4ed8;
    }
    .btn-save:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .btn-cancel {
        padding: 6px 16px;
        background: transparent;
        color: #d1d5db;
        border: 1px solid #4b5563;
        border-radius: 4px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .btn-cancel:hover {
        background: #374151;
    }
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 50;
    }
    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid #374151;
        border-top-color: #2563eb;
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
@endpush

@section('content')
<x-flash-messages />
<div x-data="imageEditor" class="editor-container" @mouseup.window="onMouseUp()">
    <!-- Loading Overlay -->
    <div x-show="isLoading" class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Main Editor Area -->
    <div class="editor-main">
        <!-- Toolbar -->
        <div class="editor-toolbar">
            <!-- History -->
            <div class="toolbar-group">
                <button @click="undo()" class="toolbar-btn" title="Undo (Ctrl+Z)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M3 13a9 9 0 0 1 15.36-6.36L21 9"/></svg>
                    Undo
                </button>
                <button @click="redo()" class="toolbar-btn" title="Redo (Ctrl+Y)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M21 13a9 9 0 0 0-15.36-6.36L3 9"/></svg>
                    Redo
                </button>
            </div>

            <!-- Crop -->
            <div class="toolbar-group">
                <button @click="startCrop()" class="toolbar-btn" :class="{ active: activeTool === 'crop' }" title="Crop">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2v14a2 2 0 0 0 2 2h14"/><path d="M18 22V8a2 2 0 0 0-2-2H2"/></svg>
                    Crop
                </button>
                <button x-show="activeTool === 'crop'" @click="applyCrop()" class="toolbar-btn active">Apply</button>
                <button x-show="activeTool === 'crop'" @click="cancelCrop()" class="toolbar-btn">Cancel</button>
            </div>

            <!-- Rotate -->
            <div class="toolbar-group">
                <button @click="rotate(-90)" class="toolbar-btn" title="Rotate Left (R)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.5 2v6h6"/><path d="M2.5 8a9 9 0 1 1-1 12.66L2.5 16"/></svg>
                    -90°
                </button>
                <button @click="rotate(90)" class="toolbar-btn" title="Rotate Right (R)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6"/><path d="M21.5 8a9 9 0 1 0 1 12.66L21.5 16"/></svg>
                    +90°
                </button>
            </div>

            <!-- Flip -->
            <div class="toolbar-group">
                <button @click="flipHorizontal()" class="toolbar-btn" :class="{ active: flipH }" title="Flip Horizontal (H)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v18"/><path d="M8 7l-4 5 4 5"/><path d="M16 7l4 5-4 5"/></svg>
                    Flip H
                </button>
                <button @click="flipVertical()" class="toolbar-btn" :class="{ active: flipV }" title="Flip Vertical (V)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18"/><path d="M7 8l5-4 5 4"/><path d="M7 16l5 4 5-4"/></svg>
                    Flip V
                </button>
            </div>

            <!-- Reset -->
            <div class="toolbar-group">
                <button @click="resetAll()" class="toolbar-btn" title="Reset All">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                    Reset
                </button>
            </div>

            <!-- Download -->
            <div class="toolbar-group">
                <button @click="downloadImage()" class="toolbar-btn" title="Download (Ctrl+S to save)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                    Download
                </button>
            </div>
        </div>

        <!-- Canvas Area -->
        <div class="canvas-area">
            <canvas
                x-ref="editorCanvas"
                :data-image-url="'{{ $asset->thumbnail_url }}'"
                :data-image-name="'{{ $asset->name }}'"
                @mousedown="onMouseDown($event)"
                @mousemove="onMouseMove($event)"
            ></canvas>
        </div>

        <!-- Footer -->
        <div class="editor-footer">
            <div class="footer-left">
                <span x-text="resizeWidth + ' x ' + resizeHeight + ' px'"></span>
                <span>|</span>
                <span>Storage:</span>
                <div class="quota-bar">
                    <div class="quota-fill"
                         :class="{ warning: {{ $quota['used_percentage'] }} >= 70 && {{ $quota['used_percentage'] }} < 90, danger: {{ $quota['used_percentage'] }} >= 90 }"
                         :style="'width: {{ min($quota['used_percentage'], 100) }}%'"></div>
                </div>
                <span>{{ round($quota['used_percentage'], 1) }}%</span>
                @if($quota['plan_limit_bytes'])
                    <span>({{ round($quota['used_bytes'] / 1048576, 1) }} / {{ round($quota['plan_limit_bytes'] / 1048576) }} MB)</span>
                @else
                    <span>(Unlimited)</span>
                @endif
            </div>
            <div class="footer-right">
                <button @click="window.location.href='{{ route('media.show', $asset) }}'" class="btn-cancel">Cancel</button>
                <button @click="saveImage()" class="btn-save" :disabled="isSaving">
                    <span x-show="!isSaving">Save Changes</span>
                    <span x-show="isSaving">Saving...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="editor-sidebar">
        <!-- Asset Info -->
        <div class="sidebar-section">
            <h4>Asset Info</h4>
            <div class="sidebar-row">
                <span class="label">Name</span>
                <span>{{ Str::limit($asset->name, 20) }}</span>
            </div>
            <div class="sidebar-row">
                <span class="label">Type</span>
                <span>{{ $asset->mime_type }}</span>
            </div>
            <div class="sidebar-row">
                <span class="label">Size</span>
                <span>{{ $asset->human_size }}</span>
            </div>
            <div class="sidebar-row">
                <span class="label">Uploaded</span>
                <span>{{ $asset->created_at->format('M d, Y') }}</span>
            </div>
        </div>

        <!-- Resize -->
        <div class="sidebar-section">
            <h4>Resize</h4>
            <div class="resize-inputs">
                <input type="number" x-model="resizeWidth" @change="onResizeWidthChange()" min="1" max="10000">
                <span>x</span>
                <input type="number" x-model="resizeHeight" @change="onResizeHeightChange()" min="1" max="10000">
            </div>
            <label class="checkbox-label" style="margin-top: 6px;">
                <input type="checkbox" x-model="maintainAspectRatio">
                Lock aspect ratio
            </label>
            <button @click="applyResize()" class="toolbar-btn" style="margin-top: 6px; width: 100%; justify-content: center; border-color: #4b5563;">Apply Resize</button>
        </div>

        <!-- Filters -->
        <div class="sidebar-section">
            <h4>Filters</h4>
            <div class="filter-slider">
                <label>
                    <span>Brightness</span>
                    <span x-text="adjustments.brightness + '%'"></span>
                </label>
                <input type="range" min="0" max="200" x-model.number="adjustments.brightness" @input="applyAdjustments()" @change="commitAdjustments()">
            </div>
            <div class="filter-slider">
                <label>
                    <span>Contrast</span>
                    <span x-text="adjustments.contrast + '%'"></span>
                </label>
                <input type="range" min="0" max="200" x-model.number="adjustments.contrast" @input="applyAdjustments()" @change="commitAdjustments()">
            </div>
            <div class="filter-slider">
                <label>
                    <span>Saturation</span>
                    <span x-text="adjustments.saturation + '%'"></span>
                </label>
                <input type="range" min="0" max="200" x-model.number="adjustments.saturation" @input="applyAdjustments()" @change="commitAdjustments()">
            </div>
            <div class="filter-slider">
                <label>
                    <span>Grayscale</span>
                    <span x-text="adjustments.grayscale + '%'"></span>
                </label>
                <input type="range" min="0" max="100" x-model.number="adjustments.grayscale" @input="applyAdjustments()" @change="commitAdjustments()">
            </div>
            <div class="filter-slider">
                <label>
                    <span>Sepia</span>
                    <span x-text="adjustments.sepia + '%'"></span>
                </label>
                <input type="range" min="0" max="100" x-model.number="adjustments.sepia" @input="applyAdjustments()" @change="commitAdjustments()">
            </div>
            <div class="filter-slider">
                <label>
                    <span>Blur</span>
                    <span x-text="adjustments.blur + 'px'"></span>
                </label>
                <input type="range" min="0" max="20" step="0.5" x-model.number="adjustments.blur" @input="applyAdjustments()" @change="commitAdjustments()">
            </div>
            <button @click="resetAdjustments()" class="toolbar-btn" style="width: 100%; justify-content: center; border-color: #4b5563;">Reset Filters</button>
        </div>

        <!-- Keyboard Shortcuts -->
        <div class="sidebar-section">
            <h4>Shortcuts</h4>
            <div class="sidebar-row"><span class="label">Undo</span><span>Ctrl+Z</span></div>
            <div class="sidebar-row"><span class="label">Redo</span><span>Ctrl+Y</span></div>
            <div class="sidebar-row"><span class="label">Save</span><span>Ctrl+S</span></div>
            <div class="sidebar-row"><span class="label">Rotate</span><span>R</span></div>
            <div class="sidebar-row"><span class="label">Flip H</span><span>H</span></div>
            <div class="sidebar-row"><span class="label">Flip V</span><span>V</span></div>
            <div class="sidebar-row"><span class="label">Cancel</span><span>Esc</span></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/image-editor.js') }}" defer></script>
@endpush
