@php
$cspNonce = base64_encode(random_bytes(16));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="script-src 'nonce-{{ $cspNonce }}' 'strict-dynamic' https:; style-src 'self' 'unsafe-inline' https:; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' https://ui-avatars.com data:;">
    <title>{{ $channel->name }} | Chat 2.0</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Tailwind CSS -->
    @vite(['resources/css/unified.css', 'resources/js/unified.js'])
    
    <style nonce="{{ $cspNonce }}">
        [x-cloak] { display: none !important; }
        .chat-container { height: calc(100vh - 64px); }
        .message-bubble { max-width: 70%; }
        .typing-indicator span {
            animation: typing 1.4s infinite ease-in-out;
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #9ca3af;
            margin: 0 1px;
        }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-6px); }
        }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
        .dark .scrollbar-thin::-webkit-scrollbar-thumb { background: #374151; }
        .emoji-picker { max-height: 200px; overflow-y: auto; }
        .reaction-pill:hover { transform: scale(1.1); }
        .message-actions { opacity: 0; transition: opacity 0.15s; }
        .message-row:hover .message-actions { opacity: 1; }
        .read-receipt {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-slide-in { animation: slideIn 0.2s ease-out; }
    </style>
</head>
<body class="h-full bg-gray-100 text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100 font-inter overflow-hidden"
      x-data="chat2ChannelApp()"
      x-init="init()">

    <!-- Header -->
    <header class="h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center px-4 gap-4 flex-shrink-0">
        <a href="{{ route('chat.v2.index') }}" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="flex items-center gap-3 flex-1">
            <span class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-lg font-semibold">
                {{ strtoupper(substr($channel->name, 0, 1)) }}
            </span>
            <div>
                <div class="text-base font-semibold text-gray-900 dark:text-white">{{ $channel->name }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $channel->users_count }} members</div>
            </div>
        </div>
        <span class="text-xs px-2 py-1 rounded-full {{ $channel->type === 'public' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' }}">
            {{ ucfirst($channel->type) }}
        </span>
        <span class="text-sm text-gray-500 dark:text-gray-400" x-text="connectionStatus"></span>
        <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode); document.documentElement.classList.toggle('dark', darkMode)" 
                class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <i class="fas" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
        </button>
    </header>

    <div class="chat-container flex">
        <!-- Main Chat Area -->
        <main class="flex-1 flex flex-col min-w-0">
            <!-- Messages -->
            <div class="flex-1 overflow-y-auto scrollbar-thin p-4 space-y-4 bg-gray-50 dark:bg-gray-900" x-ref="messagesContainer">
                @forelse($messages as $msg)
                    <div class="flex gap-3 message-row animate-slide-in {{ $msg->user_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                        @if($msg->user_id !== auth()->id())
                            <div class="flex-shrink-0">
                                <img src="https://ui-avatars.com/api/?name={{ urlencode($msg->user->name) }}&background=6366f1&color=fff&size=32" 
                                     class="w-8 h-8 rounded-full" alt="{{ $msg->user->name }}">
                            </div>
                        @endif

                        <div class="message-bubble relative">
                            @if($msg->user_id !== auth()->id())
                                <div class="text-xs font-medium text-indigo-600 dark:text-indigo-400 mb-1">{{ $msg->user->name }}</div>
                            @endif
                            
                            <div class="rounded-2xl px-4 py-2.5 {{ $msg->user_id === auth()->id() ? 'bg-indigo-600 text-white rounded-br-md' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-bl-md border border-gray-200 dark:border-gray-700' }}"
                                 :data-message-id="{{ $msg->id }}">
                                
                                @if($msg->is_deleted)
                                    <div class="italic opacity-60 text-sm">
                                        <i class="fas fa-ban mr-1"></i>This message was deleted
                                    </div>
                                @else
                                    @if($msg->reply_to)
                                        <div class="text-xs opacity-70 mb-1.5 border-l-2 pl-2 border-current/40 cursor-pointer hover:opacity-100"
                                             onclick="document.getElementById('msg-{{ $msg->reply_to_id }}')?.scrollIntoView({behavior:'smooth', block:'center'})">
                                            <i class="fas fa-reply mr-1"></i>{{ Str::limit($msg->reply_to->content, 50) }}
                                        </div>
                                    @endif
                                    
                                    @if($msg->file_url)
                                        <div class="mb-2">
                                            <a href="{{ $msg->file_url }}" target="_blank" 
                                               class="flex items-center gap-2 text-sm underline opacity-90 hover:opacity-100">
                                                <i class="fas fa-file"></i>
                                                <span>{{ $msg->file_name }}</span>
                                                @if($msg->file_size)
                                                    <span class="text-xs opacity-70">{{ round($msg->file_size / 1024, 1) }} KB</span>
                                                @endif
                                            </a>
                                        </div>
                                    @endif
                                    
                                    <div class="text-sm whitespace-pre-wrap break-words">{{ $msg->content }}</div>
                                @endif
                            </div>
                            
                            <!-- Reactions -->
                            @php
                                $msgReactions = $msg->reactions_summary ?? [];
                            @endphp
                            @if(!empty($msgReactions))
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($msgReactions as $emoji => $count)
                                        <button onclick="window.chatApp.toggleReaction({{ $msg->id }}, '{{ $emoji }}')"
                                                class="reaction-pill inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs border transition-transform
                                                {{ in_array(auth()->id(), $msg->reactions->where('emoji', $emoji)->pluck('user_id')->toArray()) ? 'bg-indigo-100 border-indigo-300 dark:bg-indigo-900/30 dark:border-indigo-700' : 'bg-gray-100 border-gray-200 dark:bg-gray-700 dark:border-gray-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20' }}">
                                            <span>{{ $emoji }}</span>
                                            <span>{{ $count }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            
                            <!-- Timestamp & Status -->
                            <div class="text-xs mt-1 flex items-center gap-2 {{ $msg->user_id === auth()->id() ? 'justify-end' : '' }}">
                                <span class="{{ $msg->user_id === auth()->id() ? 'text-indigo-200' : 'text-gray-400' }}">
                                    {{ $msg->created_at->format('g:i A') }}
                                </span>
                                @if($msg->is_edited)
                                    <span class="italic {{ $msg->user_id === auth()->id() ? 'text-indigo-200' : 'text-gray-400' }}">(edited)</span>
                                @endif
                                @if($msg->user_id === auth()->id())
                                    <span class="read-receipt {{ $msg->created_at < now()->subMinute() ? 'bg-green-500 text-white' : 'bg-gray-300 dark:bg-gray-600' }}" title="{{ $msg->created_at < now()->subMinute() ? 'Read' : 'Sent' }}">
                                        <i class="fas {{ $msg->created_at < now()->subMinute() ? 'fa-check-double' : 'fa-check' }}"></i>
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($msg->user_id === auth()->id())
                            <div class="flex-shrink-0">
                                <img src="https://ui-avatars.com/api/?name=You&background=6366f1&color=fff&size=32" 
                                     class="w-8 h-8 rounded-full" alt="You">
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-16">
                        <i class="fas fa-comments text-5xl text-gray-300 dark:text-gray-600 mb-4"></i>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">No messages yet</p>
                        <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Be the first to say something!</p>
                    </div>
                @endforelse

                <!-- Typing Indicator -->
                <div x-show="typingUsers.length > 0" x-cloak class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <div class="typing-indicator flex">
                        <span></span><span></span><span></span>
                    </div>
                    <span x-text="typingText"></span>
                </div>
            </div>

            <!-- Message Input -->
            <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4 flex-shrink-0">
                <!-- Reply Preview -->
                <div x-show="replyingTo" class="flex items-center gap-2 mb-2 px-3 py-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg text-sm border border-indigo-200 dark:border-indigo-800" x-cloak>
                    <i class="fas fa-reply text-indigo-600 dark:text-indigo-400"></i>
                    <span class="text-indigo-700 dark:text-indigo-300">Replying to <strong x-text="replyingTo?.user?.name"></strong></span>
                    <span class="text-gray-500 dark:text-gray-400 truncate flex-1" x-text="replyingTo?.content"></span>
                    <button @click="replyingTo = null" class="text-gray-400 hover:text-gray-600 ml-2">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- File Preview -->
                <div x-show="pendingFile" class="flex items-center gap-2 mb-2 px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm" x-cloak>
                    <i class="fas fa-paperclip text-gray-500"></i>
                    <span x-text="pendingFile?.name"></span>
                    <span class="text-gray-400" x-text="formatFileSize(pendingFile?.size)"></span>
                    <button @click="pendingFile = null" class="text-gray-400 hover:text-gray-600 ml-auto">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Input Row -->
                <div class="flex items-end gap-2">
                    <!-- Emoji Picker -->
                    <div class="relative">
                        <button @click="emojiPickerOpen = !emojiPickerOpen" 
                                class="p-2.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                title="Add emoji">
                            <i class="fas fa-smile text-lg"></i>
                        </button>
                        
                        <template x-if="emojiPickerOpen">
                            <div @click.outside="emojiPickerOpen = false"
                                 class="absolute bottom-full mb-2 left-0 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-3 w-72 emoji-picker z-50">
                                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2">Emoji</div>
                                <div class="grid grid-cols-8 gap-1">
                                    <template x-for="emoji in emojis" :key="emoji">
                                        <button @click="insertEmoji(emoji)" 
                                                class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded text-xl transition-transform hover:scale-125"
                                                x-text="emoji"></button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Attach File -->
                    <button @click="$refs.fileInput.click()" 
                            class="p-2.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            title="Attach file (max 10MB)">
                        <i class="fas fa-paperclip text-lg"></i>
                    </button>
                    <input type="file" x-ref="fileInput" @change="handleFileSelect" class="hidden">

                    <!-- Text Input -->
                    <div class="flex-1 relative">
                        <textarea x-model="messageInput"
                                  @keydown.enter.prevent="sendMessage()"
                                  @input="handleTyping()"
                                  placeholder="Type a message... (Enter to send, Shift+Enter for new line)"
                                  rows="1"
                                  class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent resize-none overflow-hidden"
                                  x-ref="messageTextarea"></textarea>
                    </div>

                    <!-- Send Button -->
                    <button @click="sendMessage()" 
                            :disabled="!canSend"
                            class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl transition-colors font-medium"
                            title="Send message">
                        <i class="fas fa-paper-plane mr-1"></i> Send
                    </button>
                </div>
            </div>
        </main>

        <!-- Right Sidebar - Members -->
        <aside class="w-64 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col flex-shrink-0">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Members ({{ $channel->users_count }})</h3>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin p-2">
                @foreach($channel->users as $member)
                    <div class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($member->name) }}&background=6366f1&color=fff&size=28" 
                             class="w-7 h-7 rounded-full" alt="{{ $member->name }}">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $member->name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $member->pivot->is_moderator ? 'Moderator' : 'Member' }}</div>
                        </div>
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    </div>
                @endforeach
            </div>
        </aside>
    </div>

    <script nonce="{{ $cspNonce }}">
        function chat2ChannelApp() {
            return {
                channelId: {{ $channel->id }},
                channel: @json($channel),
                messages: @json($messages->items()),
                currentUserId: {{ auth()->id() }},
                messageInput: '',
                replyingTo: null,
                pendingFile: null,
                emojiPickerOpen: false,
                typingUsers: [],
                typingTimeout: null,
                isTyping: false,
                connectionStatus: 'Connected',
                darkMode: localStorage.getItem('darkMode') === 'true',
                
                emojis: ['😀', '😃', '😄', '😁', '😅', '😂', '🤣', '😊', '😇', '🙂', '😍', '🥰', '😘', '😎', '🤩', '🤔', '🤨', '😐', '😑', '🙄', '😏', '😴', '🤮', '👍', '👎', '👏', '🙌', '🤝', '💪', '❤️', '💔', '🔥', '⭐', '✨', '🎉', '🎊', '🎁', '💯', '✅', '❌', '⚠️', '🚀', '👀', '💡', '📌', '🔗', '📎'],

                echo: null,

                init() {
                    this.setupEcho();
                    this.$nextTick(() => this.scrollToBottom());
                    this.markAsRead();
                    
                    if (this.darkMode) {
                        document.documentElement.classList.add('dark');
                    }
                    
                    // Expose toggleReaction globally for inline onclick
                    window.chatApp = this;
                },

                setupEcho() {
                    if (typeof window.Echo === 'undefined') {
                        this.connectionStatus = 'Echo not configured';
                        return;
                    }
                    
                    this.echo = window.Echo;
                    
                    this.echo.private('chat.' + this.channelId)
                        .listen('.message.sent', (e) => this.handleNewMessage(e))
                        .listen('.typing', (e) => this.handleTypingEvent(e))
                        .listen('.read', (e) => this.handleReadEvent(e));
                },

                handleNewMessage(event) {
                    if (event.user_id === this.currentUserId) return;
                    if (this.messages.find(m => m.id === event.id)) return;
                    
                    this.messages.push(event);
                    this.$nextTick(() => this.scrollToBottom());
                    this.markAsRead();
                },

                handleTypingEvent(event) {
                    if (event.user.id === this.currentUserId) return;
                    
                    if (event.is_typing) {
                        if (!this.typingUsers.find(u => u.id === event.user.id)) {
                            this.typingUsers.push(event.user);
                        }
                    } else {
                        this.typingUsers = this.typingUsers.filter(u => u.id !== event.user.id);
                    }
                },

                handleReadEvent(event) {
                    // Update read receipts if needed
                },

                async sendMessage() {
                    if (!this.canSend) return;
                    
                    const content = this.messageInput.trim();
                    if (!content && !this.pendingFile) return;
                    
                    const messageData = {
                        content: content,
                        reply_to_id: this.replyingTo?.id || null,
                    };
                    
                    if (this.pendingFile) {
                        const formData = new FormData();
                        formData.append('file', this.pendingFile);
                        try {
                            const uploadRes = await fetch('/api/v1/chat/upload', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: formData
                            });
                            const uploadData = await uploadRes.json();
                            messageData.file_url = uploadData.url;
                            messageData.file_name = this.pendingFile.name;
                            messageData.file_type = this.pendingFile.type;
                            messageData.file_size = this.pendingFile.size;
                        } catch (err) {
                            console.error('File upload failed:', err);
                            window.showToast && window.showToast('File upload failed', 'error');
                            return;
                        }
                    }
                    
                    const optimisticMessage = {
                        id: 'temp-' + Date.now(),
                        channel_id: this.channelId,
                        user_id: this.currentUserId,
                        content: content,
                        user: { id: this.currentUserId, name: 'You' },
                        reply_to: this.replyingTo,
                        reactions: [],
                        is_edited: false,
                        is_deleted: false,
                        created_at: new Date().toISOString(),
                    };
                    this.messages.push(optimisticMessage);
                    this.messageInput = '';
                    this.replyingTo = null;
                    this.pendingFile = null;
                    this.$nextTick(() => this.scrollToBottom());
                    this.sendTyping(false);
                    
                    try {
                        const response = await fetch(`/chat/v2/${this.channelId}/send`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify(messageData)
                        });
                        
                        if (!response.ok) throw new Error('Failed to send');
                        
                        const data = await response.json();
                        const idx = this.messages.findIndex(m => m.id === optimisticMessage.id);
                        if (idx !== -1) {
                            this.messages[idx] = data.message;
                        }
                    } catch (error) {
                        console.error('Failed to send message:', error);
                        this.messages = this.messages.filter(m => m.id !== optimisticMessage.id);
                        window.showToast && window.showToast('Failed to send message', 'error');
                    }
                },

                handleTyping() {
                    if (this.isTyping) return;
                    this.isTyping = true;
                    this.sendTyping(true);
                    
                    clearTimeout(this.typingTimeout);
                    this.typingTimeout = setTimeout(() => {
                        this.isTyping = false;
                        this.sendTyping(false);
                    }, 3000);
                },

                async sendTyping(isTyping) {
                    try {
                        await fetch(`/chat/v2/${this.channelId}/typing`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ typing: isTyping })
                        });
                    } catch (error) {
                        console.error('Typing indicator failed:', error);
                    }
                },

                async markAsRead() {
                    try {
                        await fetch(`/chat/v2/${this.channelId}/read`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            }
                        });
                    } catch (error) {
                        console.error('Mark read failed:', error);
                    }
                },

                async toggleReaction(messageId, emoji) {
                    const message = this.messages.find(m => m.id === messageId);
                    if (!message) return;
                    
                    const existingReaction = message.reactions?.find(r => r.emoji === emoji && r.user_id === this.currentUserId);
                    
                    try {
                        if (existingReaction) {
                            await fetch(`/chat/v2/messages/${messageId}/reactions/${encodeURIComponent(emoji)}`, {
                                method: 'DELETE',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                }
                            });
                            message.reactions = message.reactions.filter(r => !(r.emoji === emoji && r.user_id === this.currentUserId));
                        } else {
                            const response = await fetch(`/chat/v2/messages/${messageId}/reactions`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({ emoji })
                            });
                            if (response.ok) {
                                if (!message.reactions) message.reactions = [];
                                message.reactions.push({ emoji, user_id: this.currentUserId });
                            }
                        }
                    } catch (error) {
                        console.error('Reaction toggle failed:', error);
                    }
                },

                handleFileSelect(event) {
                    const file = event.target.files[0];
                    if (file) {
                        if (file.size > 10 * 1024 * 1024) {
                            window.showToast && window.showToast('File size must be less than 10MB', 'error');
                            return;
                        }
                        this.pendingFile = file;
                    }
                },

                insertEmoji(emoji) {
                    const textarea = this.$refs.messageTextarea;
                    const start = textarea.selectionStart;
                    const end = textarea.selectionEnd;
                    this.messageInput = this.messageInput.substring(0, start) + emoji + this.messageInput.substring(end);
                    this.emojiPickerOpen = false;
                    this.$nextTick(() => {
                        textarea.focus();
                        textarea.setSelectionRange(start + emoji.length, start + emoji.length);
                    });
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const container = this.$refs.messagesContainer;
                        if (container) {
                            container.scrollTop = container.scrollHeight;
                        }
                    });
                },

                get canSend() {
                    return this.messageInput.trim().length > 0 || this.pendingFile !== null;
                },

                get typingText() {
                    if (this.typingUsers.length === 0) return '';
                    if (this.typingUsers.length === 1) return this.typingUsers[0].name + ' is typing...';
                    if (this.typingUsers.length === 2) return this.typingUsers[0].name + ' and ' + this.typingUsers[1].name + ' are typing...';
                    return this.typingUsers.length + ' people are typing...';
                },

                formatFileSize(bytes) {
                    if (!bytes) return '';
                    if (bytes < 1024) return bytes + ' B';
                    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                    return (bytes / 1048576).toFixed(1) + ' MB';
                }
            };
        }
    </script>
</body>
</html>
