@props(['id' => null, 'name', 'value' => '', 'placeholder' => ''])

<div x-data="{
    value: '{{ old($name, $value) }}',
    maxLength: null,
    init() {
        const counter = this.querySelector('.char-counter');
        if (counter) this.maxLength = counter.dataset.max;
    },
    updateCounter() {
        if (this.maxLength) {
            const counter = this.querySelector('.char-counter');
            if (counter) counter.textContent = `${this.value.length}/${this.maxLength}`;
        }
    }
}">
    <textarea 
        id="{{ $id }}"
        name="{{ $name }}"
        placeholder="{{ $placeholder }}"
        x-model="value"
        @input="updateCounter()"
        {{ $attributes->merge(['class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white resize-y min-h-[100px]']) }}>{{ old($name, $value) }}</textarea>
</div>
