<div x-data="{
    show: false,
    title: '',
    message: '',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    onConfirm: null,
    confirm(title, message, onConfirm, confirmText = 'Confirm', cancelText = 'Cancel') {
        this.title = title;
        this.message = message;
        this.onConfirm = onConfirm;
        this.confirmText = confirmText;
        this.cancelText = cancelText;
        this.show = true;
    }
}"
x-cloak
x-show="show"
x-transition.opacity
class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
style="display: none;"
role="dialog"
aria-modal="true">
    <div class="bg-white rounded-xl shadow-2xl p-6 max-w-md w-full" @click.outside="show = false">
        <h3 class="text-lg font-semibold text-gray-900 mb-2" x-text="title"></h3>
        <p class="text-gray-600 mb-6" x-text="message"></p>
        <div class="flex gap-3 justify-end">
            <button @click="show = false" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50" x-text="cancelText"></button>
            <button @click="onConfirm(); show = false" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700" x-text="confirmText"></button>
        </div>
    </div>
</div>
