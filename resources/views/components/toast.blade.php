<div x-data="{
    show: false,
    message: '',
    type: 'success',
    init() {
        this.$watch('show', (value) => {
            if (value) {
                setTimeout(() => this.show = false, 3000);
            }
        });
    },
    notify(message, type = 'success') {
        this.message = message;
        this.type = type;
        this.show = true;
    }
}"
x-cloak
x-show="show"
x-transition:enter="transition ease-out duration-300"
x-transition:enter-start="opacity-0 translate-y-2"
x-transition:enter-end="opacity-100 translate-y-0"
x-transition:leave="transition ease-in duration-200"
x-transition:leave-start="opacity-100 translate-y-0"
x-transition:leave-end="opacity-0 translate-y-2"
class="fixed top-4 right-4 z-[100] px-6 py-3 rounded-lg shadow-lg text-white flex items-center gap-2"
:class="{
    'bg-green-500': type === 'success',
    'bg-red-500': type === 'error',
    'bg-yellow-500': type === 'warning',
    'bg-blue-500': type === 'info'
}"
style="display: none;"
role="alert"
aria-live="polite">
    <i x-show="type === 'success'" class="fas fa-check-circle"></i>
    <i x-show="type === 'error'" class="fas fa-exclamation-circle"></i>
    <i x-show="type === 'warning'" class="fas fa-exclamation-triangle"></i>
    <i x-show="type === 'info'" class="fas fa-info-circle"></i>
    <span x-text="message"></span>
</div>
