<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat 2.0 | Team Chat</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons: Font Awesome self-hosted via Vite (unified.css import) -->
    
    <!-- Tailwind CSS -->
    @vite(['resources/css/unified.css', 'resources/js/unified.js'])
    
    <style nonce="{{ $cspNonce ?? '' }}">
        [x-cloak] { display: none !important; }
        .chat-container { height: calc(100vh - 64px); }
        .message-bubble { max-width: 75%; }
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
        .reaction-pill {
            transition: all 0.2s ease;
        }
        .reaction-pill:hover {
            transform: scale(1.1);
        }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
        .dark .scrollbar-thin::-webkit-scrollbar-thumb { background: #374151; }
        .emoji-picker {
            max-height: 200px;
            overflow-y: auto;
        }
        @keyframes messageSlideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message-animate {
            animation: messageSlideIn 0.2s ease-out;
        }
    </style>
</head>
<body class="h-full bg-gray-100 text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100 font-inter overflow-hidden" 
      x-data="chat2App()" 
      x-init="init()"
      @keydown.escape.window="emojiPickerOpen = false">

    <!-- CSRF Token -->
    <input type="hidden" name="_token" value="{{ csrf_token() }}">

    <!-- Header -->
    <header class="h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center px-4 gap-4 flex-shrink-0">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
            <i class="fas fa-comments text-indigo-600 mr-2"></i>Chat 2.0
        </h1>
        <div class="flex-1"></div>
        <span class="text-sm text-gray-500 dark:text-gray-400" x-text="connectionStatus"></span>
        <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" 
                class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <i class="fas" :class="darkMode ? 'fa-sun' : 'fa-moon'"></i>
        </button>
    </header>

    <div class="chat-container flex">
        <!-- Sidebar -->
        <aside class="w-72 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col flex-shrink-0">
            <!-- Sidebar Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Channels</h2>
            </div>

            <!-- Channels List -->
            <nav class="flex-1 overflow-y-auto scrollbar-thin p-2">
                @forelse($channels as $ch)
                    <button @click="switchChannel({{ $ch->id }})"
                            class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-left transition-colors mb-1"
                            :class="activeChannelId === {{ $ch->id }} ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300' : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300'">
                        <span class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-xs font-semibold flex-shrink-0">
                            {{ strtoupper(substr($ch->name, 0, 1)) }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium truncate">{{ $ch->name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                @if($ch->last_message)
                                    {{ Str::limit($ch->last_message->content, 30) }}
                                @else
                                    No messages
                                @endif
                            </div>
                        </div>
                        @if($ch->unread_count > 0)
                            <span class="bg-indigo-600 text-white text-xs font-semibold rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">
                                {{ $ch->unread_count }}
                            </span>
                        @endif
                    </button>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">No channels available</p>
                @endforelse
            </nav>
        </aside>

        <!-- Main Chat Area -->
        <main class="flex-1 flex flex-col min-w-0">
            <!-- Channel Header -->
            <div class="h-14 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center px-4 flex-shrink-0">
                @if($activeChannel)
                    <div class="flex items-center gap-3">
                        <span class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-sm font-semibold">
                            {{ strtoupper(substr($activeChannel->name, 0, 1)) }}
                        </span>
                        <div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $activeChannel->name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $activeChannel->users_count }} members</div>
                        </div>
                    </div>
                @else
                    <div class="text-sm text-gray-500 dark:text-gray-400">Select a channel to start chatting</div>
                @endif
            </div>

            <!-- Messages Area -->
            <div class="flex-1 overflow-y-auto scrollbar-thin p-4 space-y-4 bg-gray-50 dark:bg-gray-900" 
                 x-ref="messagesContainer">
                @forelse($messages as $msg)
                    <div class="flex gap-3 message-animate {{ $msg->user_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                        @if($msg->user_id !== auth()->id())
                            <div class="flex-shrink-0">
                                <img src="/avatar/{{ urlencode($msg->user->name) }}?background=6366f1&color=fff&size=32" 
                                     class="w-8 h-8 rounded-full" alt="{{ $msg->user->name }}">
                            </div>
                        @endif

                        <div class="message-bubble">
                            @if($msg->user_id !== auth()->id())
                                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $msg->user->name }}</div>
                            @endif
                            
                            <div class="rounded-2xl px-4 py-2 {{ $msg->user_id === auth()->id() ? 'bg-indigo-600 text-white rounded-br-md' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-bl-md border border-gray-200 dark:border-gray-700' }}">
                                @if($msg->is_deleted)
                                    <div class="italic opacity-60 text-sm">
                                        <i class="fas fa-ban mr-1"></i>This message was deleted
                                    </div>
                                @else
                                    @if($msg->reply_to)
                                        <div class="text-xs opacity-70 mb-1 border-l-2 pl-2 border-indigo-400">
                                            Replying to: {{ Str::limit($msg->reply_to->content, 50) }}
                                        </div>
                                    @endif
                                    
                                    @if($msg->file_url)
                                        <div class="mb-2">
                                            <a href="{{ $msg->file_url }}" target="_blank" 
                                               class="flex items-center gap-2 text-sm underline opacity-90 hover:opacity-100">
                                                <i class="fas fa-file"></i>
                                                <span>{{ $msg->file_name }}</span>
                                            </a>
                                        </div>
                                    @endif
                                    
                                    <div class="text-sm whitespace-pre-wrap break-words">{{ $msg->content }}</div>
                                @endif
                            </div>
                            
                            <!-- Reactions -->
                            @php
                                $reactions = $msg->reactions_summary ?? [];
                            @endphp
                            @if(!empty($reactions))
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($reactions as $emoji => $count)
                                        <button @click="toggleReaction({{ $msg->id }}, '{{ $emoji }}')"
                                                class="reaction-pill inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs border bg-gray-100 border-gray-200 dark:bg-gray-700 dark:border-gray-600">
                                            <span>{{ $emoji }}</span>
                                            <span>{{ $count }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                            
                            <div class="text-xs mt-1 flex items-center gap-2 {{ $msg->user_id === auth()->id() ? 'justify-end' : '' }}">
                                <span class="{{ $msg->user_id === auth()->id() ? 'text-indigo-200' : 'text-gray-400' }}">
                                    {{ $msg->created_at->format('g:i A') }}
                                </span>
                                @if($msg->is_edited)
                                    <span class="italic {{ $msg->user_id === auth()->id() ? 'text-indigo-200' : 'text-gray-400' }}">(edited)</span>
                                @endif
                            </div>
                        </div>

                        @if($msg->user_id === auth()->id())
                            <div class="flex-shrink-0">
                                <img src="/avatar/You?background=6366f1&color=fff&size=32" 
                                     class="w-8 h-8 rounded-full" alt="You">
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-12">
                        <i class="fas fa-comments text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                        <p class="text-gray-500 dark:text-gray-400">No messages yet. Start the conversation!</p>
                    </div>
                @endforelse

                <!-- Typing Indicator -->
                <div class="typing-indicator-container flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400" style="display: none;" x-show="typingUsers.length > 0">
                    <div class="typing-indicator flex">
                        <span></span><span></span><span></span>
                    </div>
                    <span x-text="typingText"></span>
                </div>
            </div>

            <!-- Message Input -->
            <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4 flex-shrink-0">
                <!-- Reply Preview -->
                <div x-show="replyingTo" class="flex items-center gap-2 mb-2 px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg text-sm" x-cloak>
                    <span class="text-indigo-600 dark:text-indigo-400">
                        <i class="fas fa-reply mr-1"></i>Replying to <strong x-text="replyingTo?.user?.name"></strong>
                    </span>
                    <span class="text-gray-500 dark:text-gray-400 truncate flex-1" x-text="replyingTo?.content"></span>
                    <button @click="replyingTo = null" class="text-gray-400 hover:text-gray-600">
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
                    <!-- Emoji Button -->
                    <div class="relative">
                        <button @click="emojiPickerOpen = !emojiPickerOpen" 
                                class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                                title="Add emoji">
                            <i class="fas fa-smile"></i>
                        </button>
                        
                        <!-- Emoji Picker -->
                        <template x-if="emojiPickerOpen">
                            <div @click.outside="emojiPickerOpen = false"
                                 class="absolute bottom-full mb-2 left-0 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg p-2 w-64 emoji-picker z-50">
                                <div class="grid grid-cols-8 gap-1">
                                    <template x-for="emoji in emojis" :key="emoji">
                                        <button @click="insertEmoji(emoji)" 
                                                class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded text-lg"
                                                x-text="emoji"></button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Attach File -->
                    <button @click="$refs.fileInput.click()" 
                            class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                            title="Attach file">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <input type="file" x-ref="fileInput" @change="handleFileSelect" class="hidden">

                    <!-- Text Input -->
                    <div class="flex-1 relative">
                        <textarea x-model="messageInput"
                                  @keydown.enter.prevent="sendMessage()"
                                  @input="handleTyping()"
                                  placeholder="Type a message..."
                                  rows="1"
                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none overflow-hidden"
                                  x-ref="messageTextarea"></textarea>
                    </div>

                    <!-- Send Button -->
                    <button @click="sendMessage()" 
                            :disabled="!canSend"
                            class="p-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg transition-colors"
                            title="Send message">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </main>
    </div>

    <script nonce="{{ $cspNonce ?? '' }}">
        function chat2App() {
            return {
                activeChannelId: {{ $activeChannel?->id ?? 'null' }},
                activeChannel: @json($activeChannel),
                channels: @json($channels),
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
                channel: null,

                init() {
                    this.setupEcho();
                    this.scrollToBottom();
                    this.markAsRead();
                    
                    if (this.darkMode) {
                        document.documentElement.classList.add('dark');
                    }
                },

                setupEcho() {
                    if (typeof window.Echo === 'undefined') {
                        this.connectionStatus = 'Echo not configured';
                        return;
                    }
                    
                    this.echo = window.Echo;
                    this.subscribeToChannel(this.activeChannelId);
                    this.connectionStatus = 'Connected';
                },

                subscribeToChannel(channelId) {
                    if (!channelId || !this.echo) return;
                    
                    if (this.channel) {
                        this.echo.leave('chat.' + this.channel);
                    }
                    
                    this.channel = channelId;
                    
                    this.echo.private('chat.' + channelId)
                        .listen('.message.sent', (e) => {
                            this.handleNewMessage(e);
                        })
                        .listen('.typing', (e) => {
                            this.handleTypingEvent(e);
                        })
                        .listen('.read', (e) => {
                            this.handleReadEvent(e);
                        });
                },

                switchChannel(channelId) {
                    if (this.activeChannelId === channelId) return;
                    
                    this.activeChannelId = channelId;
                    this.messages = [];
                    this.typingUsers = [];
                    
                    const ch = this.channels.find(c => c.id === channelId);
                    if (ch) this.activeChannel = ch;
                    
                    history.pushState({}, '', '/chat/v2/' + channelId);
                    this.fetchMessages(channelId);
                    this.subscribeToChannel(channelId);
                    this.markAsRead();
                },

                async fetchMessages(channelId) {
                    try {
                        const response = await fetch(`/api/v1/chat/channels/${channelId}/messages`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            }
                        });
                        const data = await response.json();
                        this.messages = data.data || [];
                        this.$nextTick(() => this.scrollToBottom());
                    } catch (error) {
                        console.error('Failed to fetch messages:', error);
                    }
                },

                handleNewMessage(event) {
                    if (event.user_id === this.currentUserId) return;
                    if (this.messages.find(m => m.id === event.id)) return;
                    
                    this.messages.push(event);
                    this.$nextTick(() => this.scrollToBottom());
                    this.markAsRead();
                    
                    const channel = this.channels.find(c => c.id === this.activeChannelId);
                    if (channel) {
                        channel.last_message = {
                            content: event.content.substring(0, 50),
                            user: event.user.name,
                            created_at: event.created_at
                        };
                    }
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
                    if (event.user.id === this.currentUserId) return;
                },

                async sendMessage() {
                    if (!this.canSend || !this.activeChannelId) return;
                    
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
                            return;
                        }
                    }
                    
                    const optimisticMessage = {
                        id: 'temp-' + Date.now(),
                        channel_id: this.activeChannelId,
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
                        const response = await fetch(`/chat/v2/${this.activeChannelId}/send`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify(messageData)
                        });
                        
                        if (!response.ok) {
                            throw new Error('Failed to send');
                        }
                        
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
                    if (!this.activeChannelId) return;
                    
                    try {
                        await fetch(`/chat/v2/${this.activeChannelId}/typing`, {
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
                    if (!this.activeChannelId) return;
                    
                    try {
                        await fetch(`/chat/v2/${this.activeChannelId}/read`, {
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

                getReactionsSummary(reactions) {
                    const summary = {};
                    reactions.forEach(r => {
                        const key = r.emoji;
                        if (!summary[key]) {
                            summary[key] = { emoji: key, count: 0, user_reacted: false };
                        }
                        summary[key].count++;
                        if (r.user_id === this.currentUserId) {
                            summary[key].user_reacted = true;
                        }
                    });
                    return Object.values(summary);
                },

                formatTime(dateStr) {
                    const date = new Date(dateStr);
                    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                },

                formatFileSize(bytes) {
                    if (!bytes) return '';
                    if (bytes < 1024) return bytes + ' B';
                    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                    return (bytes / 1048576).toFixed(1) + ' MB';
                },

                getFileIcon(mimeType) {
                    if (!mimeType) return 'fa-file';
                    if (mimeType.includes('image')) return 'fa-file-image';
                    if (mimeType.includes('pdf')) return 'fa-file-pdf';
                    if (mimeType.includes('word') || mimeType.includes('document')) return 'fa-file-word';
                    return 'fa-file';
                }
            };
        }
    </script>
</body>
</html>
