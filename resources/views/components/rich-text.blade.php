@props(['id' => null, 'name', 'value' => ''])

<div x-data="{
    value: {{ json_encode(old($name, $value)) }},
    editor: null,
    init() {
        this.$nextTick(() => {
            if (typeof ClassicEditor !== 'undefined') {
                ClassicEditor.create(this.$refs.editor)
                    .then(editor => {
                        this.editor = editor;
                        editor.model.document.on('change:data', () => {
                            this.value = editor.getData();
                        });
                    })
                    .catch(error => console.error(error));
            }
        });
    }
}">
    <textarea id="{{ $id }}" name="{{ $name }}" x-ref="editor" {{ $attributes->merge(['class' => 'hidden']) }}>{{ old($name, $value) }}</textarea>
    <input type="hidden" name="{{ $name }}" x-model="value">
</div>

@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js"></script>
@endpush
