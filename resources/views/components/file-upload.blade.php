@props(['id' => null, 'name', 'accept' => '*', 'multiple' => false])

<div x-data="{
    dragging: false,
    files: [],
    handleDrop(e) {
        this.dragging = false;
        this.files = Array.from(e.dataTransfer.files);
        this.$refs.input.files = e.dataTransfer.files;
    },
    handleSelect(e) {
        this.files = Array.from(e.target.files);
    }
}"
@dragover.prevent="dragging = true"
@dragleave.prevent="dragging = false"
@drop.prevent="handleDrop($event)"
class="relative">
    <div class="border-2 border-dashed rounded-lg p-8 text-center transition-colors"
         :class="dragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 dark:border-gray-600'">
        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4"></i>
        <p class="text-gray-600 dark:text-gray-400 mb-2">
            <span x-show="files.length === 0">Drag & drop files here, or</span>
            <span x-show="files.length > 0" x-text="`${files.length} file(s) selected`"></span>
        </p>
        <label class="inline-block">
            <span class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 cursor-pointer">
                Browse Files
            </span>
            <input type="file"
                   id="{{ $id }}"
                   name="{{ $name }}"
                   accept="{{ $accept }}"
                   {{ $multiple ? 'multiple' : '' }}
                   class="hidden"
                   x-ref="input"
                   @change="handleSelect($event)">
        </label>
    </div>
</div>
