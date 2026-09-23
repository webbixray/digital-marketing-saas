@extends('layouts.unified')
@section('title', 'Folder Management')

@push('styles')
<style>
    .folders-container {
        display: flex;
        gap: 24px;
        min-height: 500px;
    }
    .folder-tree-panel {
        width: 320px;
        flex-shrink: 0;
    }
    .folder-detail-panel {
        flex: 1;
    }
    .tree-view {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .tree-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 8px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        color: #374151;
        transition: all 0.15s;
    }
    .tree-item:hover {
        background: #f3f4f6;
    }
    .tree-item.active {
        background: #eff6ff;
        color: #1d4ed8;
    }
    .tree-item .folder-icon {
        width: 16px;
        color: #f59e0b;
    }
    .tree-item .toggle-icon {
        width: 16px;
        height: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .tree-item .toggle-icon.expanded {
        transform: rotate(90deg);
    }
    .tree-item .toggle-icon:hover {
        color: #4b5563;
    }
    .tree-item .actions {
        display: none;
        margin-left: auto;
        gap: 2px;
    }
    .tree-item:hover .actions {
        display: flex;
    }
    .tree-item .actions button {
        padding: 2px 4px;
        border: none;
        background: transparent;
        color: #6b7280;
        cursor: pointer;
        border-radius: 3px;
    }
    .tree-item .actions button:hover {
        background: #e5e7eb;
        color: #374151;
    }
    .tree-children {
        list-style: none;
        padding-left: 20px;
        margin: 0;
        border-left: 1px solid #e5e7eb;
        margin-left: 14px;
    }
    .drop-zone {
        border: 2px dashed #d1d5db;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        margin-bottom: 16px;
        transition: all 0.15s;
    }
    .drop-zone.drag-over {
        border-color: #2563eb;
        background: #eff6ff;
    }
    .asset-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 12px;
    }
    .asset-item {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
        cursor: grab;
        transition: all 0.15s;
    }
    .asset-item:active {
        cursor: grabbing;
    }
    .asset-item.dragging {
        opacity: 0.5;
    }
    .asset-item img {
        width: 100%;
        height: 80px;
        object-fit: cover;
        display: block;
    }
    .asset-item .asset-name {
        padding: 6px 8px;
        font-size: 11px;
        color: #374151;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .breadcrumb {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 16px;
        font-size: 13px;
    }
    .breadcrumb a {
        color: #2563eb;
        text-decoration: none;
    }
    .breadcrumb span {
        color: #6b7280;
    }
</style>
@endpush

@section('content')
<x-flash-messages />

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Folder Management</h1>
        <button onclick="showCreateFolderModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm font-medium">
            <i class="fas fa-folder-plus"></i> New Folder
        </button>
    </div>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="{{ route('media.index') }}">All Files</a>
        <i class="fas fa-chevron-right text-xs text-gray-400"></i>
        <span>Folder Management</span>
    </div>

    <div class="folders-container">
        <!-- Tree View -->
        <div class="folder-tree-panel bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Folder Tree</h3>
            <ul class="tree-view" id="folderTree">
                <li class="tree-item {{ !request('folder') ? 'active' : '' }}"
                    onclick="window.location='{{ route('media.index') }}'">
                    <i class="fas fa-folder folder-icon"></i>
                    <span>All Files</span>
                </li>
                <template x-data x-for="folder in folders" :key="folder.id">
                    <li class="tree-item" @click="selectFolder(folder)">
                        <span class="toggle-icon" @click.stop="toggleFolder(folder)">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                        <i class="fas fa-folder folder-icon"></i>
                        <span x-text="folder.name"></span>
                        <span class="actions">
                            <button @click.stop="renameFolder(folder)" title="Rename">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button @click.stop="deleteFolder(folder)" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </span>
                    </li>
                </template>
            </ul>
        </div>

        <!-- Detail Panel -->
        <div class="folder-detail-panel bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6">
            <div x-data="folderManager">
                <!-- Drop Zone -->
                <div class="drop-zone"
                     @dragover.prevent="$el.classList.add('drag-over')"
                     @dragleave="$el.classList.remove('drag-over')"
                     @drop.prevent="handleDrop($event); $el.classList.remove('drag-over')">
                    <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-600">Drag and drop assets here to move them to the selected folder</p>
                </div>

                <!-- Assets Grid -->
                <h4 class="text-sm font-semibold text-gray-700 mb-3" x-show="selectedFolder">
                    Assets in <span x-text="selectedFolder?.name"></span>
                </h4>
                <div class="asset-list" id="assetList">
                    <template x-for="asset in assets" :key="asset.id">
                        <div class="asset-item"
                             draggable="true"
                             @dragstart="handleDragStart($event, asset)"
                             @dragend="handleDragEnd($event)">
                            <img :src="asset.thumbnail_url" :alt="asset.name">
                            <div class="asset-name" x-text="asset.name"></div>
                        </div>
                    </template>
                </div>

                <div x-show="assets.length === 0" class="text-center py-8 text-gray-500">
                    <i class="fas fa-folder-open fa-2x mb-2"></i>
                    <p>No assets in this folder</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Folder Modal -->
<div id="folderModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50" onclick="closeModal(event)">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md" onclick="event.stopPropagation()">
        <h3 id="modalTitle" class="text-lg font-semibold mb-4">Create Folder</h3>
        <form id="folderForm">
            <input type="hidden" id="folderId">
            <input type="hidden" id="folderParentId">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Folder Name</label>
                <input type="text" id="folderName" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" required>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeFolderModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-sm">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let folders = [];
    let assets = [];
    let selectedFolder = null;

    // Load folders from API
    async function loadFolders() {
        try {
            const response = await fetch('{{ route("media.folders.index") }}', {
                headers: { 'Accept': 'application/json' }
            });
            folders = await response.json();
            renderTree();
        } catch (e) {
            console.error('Failed to load folders:', e);
        }
    }

    function renderTree() {
        const tree = document.getElementById('folderTree');
        // Clear existing items except "All Files"
        const allFilesItem = tree.firstElementChild;
        tree.innerHTML = '';
        tree.appendChild(allFilesItem);

        // Render root folders
        const rootFolders = folders.filter(f => !f.parent_id);
        rootFolders.forEach(folder => {
            tree.appendChild(createTreeItem(folder));
        });
    }

    function createTreeItem(folder) {
        const li = document.createElement('li');
        li.className = 'tree-item';
        li.dataset.id = folder.id;
        li.innerHTML = `
            <span class="toggle-icon">
                <i class="fas fa-chevron-right"></i>
            </span>
            <i class="fas fa-folder folder-icon"></i>
            <span>${folder.name}</span>
            <span class="actions">
                <button onclick="renameFolder(${folder.id}, '${folder.name}')" title="Rename"><i class="fas fa-pen"></i></button>
                <button onclick="deleteFolderById(${folder.id})" title="Delete"><i class="fas fa-trash"></i></button>
            </span>
        `;
        li.addEventListener('click', (e) => {
            if (e.target.closest('.actions') || e.target.closest('.toggle-icon')) return;
            selectFolder(folder);
        });
        return li;
    }

    async function selectFolder(folder) {
        selectedFolder = folder;
        document.querySelectorAll('.tree-item').forEach(item => item.classList.remove('active'));
        const item = document.querySelector(`.tree-item[data-id="${folder.id}"]`);
        if (item) item.classList.add('active');

        // Load assets for this folder
        try {
            const response = await fetch(`{{ url("media") }}?folder=${encodeURIComponent(folder.name)}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await response.json();
            assets = data.assets || [];
            renderAssets();
        } catch (e) {
            console.error('Failed to load assets:', e);
        }
    }

    function renderAssets() {
        const list = document.getElementById('assetList');
        list.innerHTML = '';
        assets.forEach(asset => {
            const div = document.createElement('div');
            div.className = 'asset-item';
            div.draggable = true;
            div.innerHTML = `
                <img src="${asset.thumbnail_url}" alt="${asset.name}">
                <div class="asset-name">${asset.name}</div>
            `;
            div.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('assetId', asset.id);
                div.classList.add('dragging');
            });
            div.addEventListener('dragend', () => div.classList.remove('dragging'));
            list.appendChild(div);
        });
    }

    async function handleDrop(event) {
        if (!selectedFolder) {
            alert('Please select a folder first');
            return;
        }
        const assetId = event.dataTransfer.getData('assetId');
        if (!assetId) return;

        // Update asset folder
        try {
            await fetch(`/api/assets/${assetId}/move`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ folder_id: selectedFolder.id })
            });
            selectFolder(selectedFolder); // Reload
        } catch (e) {
            console.error('Failed to move asset:', e);
        }
    }

    function showCreateFolderModal() {
        document.getElementById('modalTitle').textContent = 'Create Folder';
        document.getElementById('folderId').value = '';
        document.getElementById('folderParentId').value = '';
        document.getElementById('folderName').value = '';
        document.getElementById('folderModal').classList.remove('hidden');
    }

    function closeModal(event) {
        if (event.target === document.getElementById('folderModal')) {
            closeFolderModal();
        }
    }

    function closeFolderModal() {
        document.getElementById('folderModal').classList.add('hidden');
    }

    function renameFolder(id, name) {
        document.getElementById('modalTitle').textContent = 'Rename Folder';
        document.getElementById('folderId').value = id;
        document.getElementById('folderName').value = name || '';
        document.getElementById('folderModal').classList.remove('hidden');
    }

    async function deleteFolderById(id) {
        if (!confirm('Delete this folder and all its contents?')) return;
        try {
            await fetch(`/media/folders/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                }
            });
            loadFolders();
        } catch (e) {
            console.error('Failed to delete folder:', e);
        }
    }

    document.getElementById('folderForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('folderId').value;
        const name = document.getElementById('folderName').value;

        try {
            if (id) {
                await fetch(`/media/folders/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ name })
                });
            } else {
                await fetch('{{ route("media.folders.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ name })
                });
            }
            closeFolderModal();
            loadFolders();
        } catch (err) {
            console.error('Failed to save folder:', err);
        }
    });

    // Initial load
    loadFolders();
</script>
@endpush
