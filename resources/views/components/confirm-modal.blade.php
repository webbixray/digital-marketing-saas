@props([
    'id' => 'confirm-modal',
    'title' => 'Confirm Action',
    'message' => 'Are you sure?',
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel',
])

<div x-data="{
    show: false,
    modalTitle: {{ Js::from($title) }},
    modalMessage: {{ Js::from($message) }},
    modalConfirmText: {{ Js::from($confirmText) }},
    modalCancelText: {{ Js::from($cancelText) }},
    onConfirm: null,
    init() {
        this.\$watch('show', (value) => {
            if (value) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });
        
        window.addEventListener('open-confirm-modal', (e) => {
            if (e.detail.id === '{{ \$id }}') {
                this.modalTitle = e.detail.title || this.modalTitle;
                this.modalMessage = e.detail.message || this.modalMessage;
                this.modalConfirmText = e.detail.confirmText || this.modalConfirmText;
                this.modalCancelText = e.detail.cancelText || this.modalCancelText;
                this.onConfirm = e.detail.onConfirm;
                this.show = true;
            }
        });
    },
    handleKeydown(e) {
        if (e.key === 'Escape') this.show = false;
    }
}"
x-cloak
x-show="show"
x-transition.opacity
@keydown="handleKeydown(\$event)"
class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
style="display: none;"
role="dialog"
aria-modal="true"
aria-labelledby="{{ \$id }}-title">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-md w-full" @click.outside="show = false">
        <h3 id="{{ \$id }}-title" class="text-lg font-semibold text-gray-900 dark:text-white mb-2" x-text="modalTitle"></h3>
        <p class="text-gray-600 dark:text-gray-300 mb-6" x-text="modalMessage"></p>
        <div class="flex gap-3 justify-end">
            <button type="button" @click="show = false" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700" x-text="modalCancelText"></button>
            <button type="button" @click="if (onConfirm) onConfirm(); show = false" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700" x-text="modalConfirmText"></button>
        </div>
    </div>
</div>
