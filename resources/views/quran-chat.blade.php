<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quran AI Chat - Tanya Al-Quran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1d4ed8;
            --accent-color: #10b981;
            --system-color: #6b7280;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --user-bubble: #2563eb;
            --assistant-bubble: #f1f5f9;
            --system-bubble: #fef3c7;
        }

        [data-theme="dark"] {
            --primary-color: #3b82f6;
            --secondary-color: #1e40af;
            --accent-color: #10b981;
            --system-color: #9ca3af;
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border-color: #334155;
            --user-bubble: #3b82f6;
            --assistant-bubble: #334155;
            --system-bubble: #451a03;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-primary);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            transition: all 0.3s ease;
            margin: 0;
            padding: 0;
        }

        .chat-container {
            max-width: 800px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0 auto;
            background: var(--card-bg);
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .chat-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.25rem 1.5rem;
            border-radius: 0 0 20px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .app-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .app-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .app-text h1 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            line-height: 1.2;
        }

        .app-text p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.85rem;
        }

        .header-actions {
            display: flex;
            gap: 0.5rem;
        }

        .header-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .header-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            background: var(--bg-color);
        }

        /* Message Bubbles */
        .message {
            max-width: 85%;
            padding: 0.875rem 1rem;
            border-radius: 1.125rem;
            line-height: 1.4;
            animation: messageSlideIn 0.3s ease;
            position: relative;
            word-wrap: break-word;
        }

        .message-user {
            align-self: flex-end;
            background: var(--user-bubble);
            color: white;
            border-bottom-right-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15);
        }

        .message-assistant {
            align-self: flex-start;
            background: var(--assistant-bubble);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-bottom-left-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .message-system {
            align-self: center;
            background: var(--system-bubble);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 1rem;
            max-width: 70%;
            font-size: 0.875rem;
            text-align: center;
            padding: 0.75rem 1rem;
            margin: 0.5rem 0;
        }

        /* Typing Indicators */
        .typing-indicator {
            align-self: flex-start;
            background: var(--assistant-bubble);
            border: 1px solid var(--border-color);
            padding: 1rem 1.25rem;
            border-radius: 1.125rem;
            border-bottom-left-radius: 0.5rem;
            display: none;
            animation: messageSlideIn 0.3s ease;
        }

        .typing-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .typing-avatar {
            width: 28px;
            height: 28px;
            background: var(--primary-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
            flex-shrink: 0;
        }

        .typing-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .typing-dots {
            display: flex;
            gap: 0.2rem;
            margin-left: 0.5rem;
        }

        .typing-dot {
            width: 6px;
            height: 6px;
            background: var(--text-secondary);
            border-radius: 50%;
            animation: typingBounce 1.4s infinite ease-in-out;
        }

        .typing-dot:nth-child(1) {
            animation-delay: -0.32s;
        }

        .typing-dot:nth-child(2) {
            animation-delay: -0.16s;
        }

        /* System Processing States */
        .system-processing {
            align-self: center;
            background: var(--system-bubble);
            border: 1px solid var(--border-color);
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            display: none;
            animation: messageSlideIn 0.3s ease;
            max-width: 70%;
        }

        .processing-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .processing-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid var(--border-color);
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Verse Display */
        .message-verse {
            background: rgba(16, 185, 129, 0.1);
            border-left: 3px solid var(--accent-color);
            padding: 0.75rem;
            margin-top: 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }

        .message-verse .surah {
            font-weight: 600;
            color: var(--primary-color);
            display: block;
            margin-bottom: 0.25rem;
        }

        .message-verse .arabic {
            font-family: 'Traditional Arabic', 'Scheherazade', serif;
            font-size: 1.1em;
            line-height: 1.6;
            text-align: right;
            margin: 0.5rem 0;
            direction: rtl;
        }

        .message-verse .translation {
            font-style: italic;
            color: var(--text-secondary);
            margin: 0.5rem 0;
            font-size: 0.9rem;
            line-height: 1.4;
            border-top: 1px solid var(--border-color);
            padding-top: 0.5rem;
        }

        .message-verse .similarity {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
            text-align: right;
        }

        /* Input Area */
        .input-container {
            padding: 1.25rem 1.5rem;
            border-top: 1px solid var(--border-color);
            background: var(--card-bg);
            position: sticky;
            bottom: 0;
        }

        .input-group {
            background: var(--bg-color);
            border-radius: 1.5rem;
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .input-group:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-control {
            border: none;
            background: transparent;
            color: var(--text-primary);
            resize: none;
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .form-control::placeholder {
            color: var(--text-secondary);
        }

        .form-control:focus {
            box-shadow: none;
            background: transparent;
        }

        .btn-send {
            background: var(--primary-color);
            border: none;
            color: white;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .btn-send:hover:not(:disabled) {
            background: var(--secondary-color);
            transform: scale(1.05);
        }

        .btn-send:disabled {
            background: var(--text-secondary);
            transform: none;
            cursor: not-allowed;
        }

        .input-hint {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        /* Animations */
        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(10px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes typingBounce {

            0%,
            80%,
            100% {
                transform: scale(0.8);
                opacity: 0.5;
            }

            40% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        /* Scrollbar */
        .messages-container::-webkit-scrollbar {
            width: 6px;
        }

        .messages-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .messages-container::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        .messages-container::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .chat-container {
                height: 100vh;
                border-radius: 0;
            }

            .chat-header {
                border-radius: 0;
                padding: 1rem 1.25rem;
            }

            .message {
                max-width: 90%;
            }

            .message-system,
            .system-processing {
                max-width: 85%;
            }

            .input-container {
                padding: 1rem 1.25rem;
            }
        }

        /* Loading States */
        .message-loading {
            opacity: 0.7;
            animation: pulse 2s infinite;
        }

        .timestamp {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
            text-align: right;
        }

        .message-user .timestamp {
            color: rgba(255, 255, 255, 0.7);
        }
    </style>
</head>

<body>
    <!-- Main Chat Container -->
    <div class="chat-container">
        <!-- Header -->
        <div class="chat-header">
            <div class="header-content">
                <div class="app-info">
                    <div class="app-icon">
                        <i class="fas fa-quran"></i>
                    </div>
                    <div class="app-text">
                        <h1>Quran AI Chat</h1>
                        <p>Tanya Al-Quran dengan AI</p>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="header-btn" id="themeToggle" title="Ubah Tema">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="header-btn" id="clearChat" title="Hapus Riwayat">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Messages Container -->
        <div class="messages-container" id="messagesContainer">
            <!-- Welcome System Message -->
            <div class="message-system">
                <i class="fas fa-robot"></i> Sistem siap membantu Anda bertanya tentang Al-Quran
            </div>

            <!-- Initial Assistant Message -->
            <div class="message message-assistant">
                <strong>Assalamu'alaikum! 👋</strong><br>
                Saya adalah asisten AI Al-Quran. Silakan tanyakan pertanyaan apa pun tentang Al-Quran,
                tafsir, atau makna kehidupan menurut Islam. Saya akan membantu dengan merujuk kepada
                ayat-ayat yang relevan.
                <div class="timestamp" id="welcomeTime"></div>
            </div>
        </div>

        <!-- Typing Indicator -->
        <div class="typing-indicator" id="typingIndicator">
            <div class="typing-content">
                <div class="typing-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="typing-text">
                    AI sedang mengetik
                    <div class="typing-dots">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Processing Indicator -->
        <div class="system-processing" id="systemProcessing">
            <div class="processing-content">
                <div class="processing-spinner"></div>
                <span id="processingText">Mencari ayat yang relevan...</span>
            </div>
        </div>

        <!-- Input Area -->
        <div class="input-container">
            <div class="input-group">
                <textarea class="form-control" id="messageInput" placeholder="Ketik pertanyaan tentang Al-Quran..." rows="1"
                    maxlength="1000"></textarea>
                <button class="btn-send" id="sendButton" disabled>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="input-hint">
                Tekan <kbd>Enter</kbd> untuk mengirim • <kbd>Shift</kbd>+<kbd>Enter</kbd> untuk baris baru
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        class QuranChat {
            constructor() {
                this.sessionId = localStorage.getItem('quran_chat_session') || this.generateSessionId();
                this.messagesContainer = document.getElementById('messagesContainer');
                this.messageInput = document.getElementById('messageInput');
                this.sendButton = document.getElementById('sendButton');
                this.typingIndicator = document.getElementById('typingIndicator');
                this.systemProcessing = document.getElementById('systemProcessing');
                this.processingText = document.getElementById('processingText');
                this.themeToggle = document.getElementById('themeToggle');
                this.clearChatBtn = document.getElementById('clearChat');

                this.processingSteps = [
                    "Menganalisis pertanyaan...",
                    "Mencari ayat yang relevan...",
                    "Memproses konteks...",
                    "Menyusun jawaban...",
                    "Hampir selesai..."
                ];
                this.currentStep = 0;

                this.init();
            }

            init() {
                this.setWelcomeTime();
                this.loadChatHistory();
                this.setupEventListeners();
                this.setupTheme();
                this.autoResizeTextarea();
            }

            setWelcomeTime() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                document.getElementById('welcomeTime').textContent = timeString;
            }

            generateSessionId() {
                const sessionId = 'session_' + Math.random().toString(36).substr(2, 9);
                localStorage.setItem('quran_chat_session', sessionId);
                return sessionId;
            }

            setupEventListeners() {
                this.sendButton.addEventListener('click', () => this.sendMessage());

                this.messageInput.addEventListener('input', () => {
                    this.sendButton.disabled = !this.messageInput.value.trim();
                    this.autoResizeTextarea();
                });

                this.messageInput.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.sendMessage();
                    }
                });

                this.themeToggle.addEventListener('click', () => this.toggleTheme());
                this.clearChatBtn.addEventListener('click', () => this.clearChatHistory());
            }

            setupTheme() {
                const savedTheme = localStorage.getItem('theme') || 'light';
                document.documentElement.setAttribute('data-theme', savedTheme);
                this.updateThemeIcon(savedTheme);
            }

            toggleTheme() {
                const currentTheme = document.documentElement.getAttribute('data-theme');
                const newTheme = currentTheme === 'light' ? 'dark' : 'light';

                document.documentElement.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                this.updateThemeIcon(newTheme);
            }

            updateThemeIcon(theme) {
                const icon = this.themeToggle.querySelector('i');
                icon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
            }

            autoResizeTextarea() {
                this.messageInput.style.height = 'auto';
                this.messageInput.style.height = Math.min(this.messageInput.scrollHeight, 120) + 'px';
            }

            async sendMessage() {
                const message = this.messageInput.value.trim();
                if (!message) return;

                // Add user message with timestamp
                this.addMessage('user', message);
                this.messageInput.value = '';
                this.sendButton.disabled = true;
                this.autoResizeTextarea();

                // Show system processing
                this.showSystemProcessing();

                try {
                    // Simulate processing steps
                    const processingInterval = this.simulateProcessing();

                    const response = await fetch('/api/quran-chat/message', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            message: message,
                            session_id: this.sessionId
                        })
                    });

                    const data = await response.json();

                    // Clear processing simulation
                    clearInterval(processingInterval);
                    this.hideSystemProcessing();

                    if (data.success) {
                        // Show typing indicator before response
                        this.showTypingIndicator();

                        // Simulate typing delay for better UX
                        setTimeout(() => {
                            this.hideTypingIndicator();
                            this.typeWriterEffect(data.answer, data.similar_verses);
                        }, 1000 + Math.random() * 1000);

                    } else {
                        this.addSystemMessage('Maaf, terjadi kesalahan sistem. Silakan coba lagi.');
                    }

                } catch (error) {
                    this.hideSystemProcessing();
                    this.addSystemMessage('Koneksi terputus. Silakan periksa internet dan coba lagi.');
                    console.error('Error:', error);
                }
            }

            simulateProcessing() {
                this.currentStep = 0;
                return setInterval(() => {
                    this.processingText.textContent = this.processingSteps[this.currentStep];
                    this.currentStep = (this.currentStep + 1) % this.processingSteps.length;
                }, 2000);
            }

            showSystemProcessing() {
                this.systemProcessing.style.display = 'block';
                this.scrollToBottom();
            }

            hideSystemProcessing() {
                this.systemProcessing.style.display = 'none';
            }

            showTypingIndicator() {
                this.typingIndicator.style.display = 'block';
                this.scrollToBottom();
            }

            hideTypingIndicator() {
                this.typingIndicator.style.display = 'none';
            }

            typeWriterEffect(text, verses = null) {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message message-assistant';
                this.messagesContainer.appendChild(messageDiv);

                let index = 0;
                const speed = 25; // kecepatan ketikan (ms per karakter)

                const typeChar = () => {
                    if (index < text.length) {
                        messageDiv.innerHTML = text.substring(0, index + 1);
                        index++;
                        this.scrollToBottom();
                        setTimeout(typeChar, speed);
                    } else {
                        // setelah selesai mengetik, tambahkan ayat referensi bila ada
                        if (verses && verses.length > 0) {
                            let verseHtml = '<div class="mt-2"><small><strong>📖 Ayat referensi:</strong></small>';
                            verses.forEach(verse => {
                                verseHtml += `
                        <div class="message-verse">
                            <span class="surah">${verse.surah_name} Ayat ${verse.ayah_in_surah}</span>
                            <div class="arabic">${verse.arabic_text}</div>
                            <div class="translation">${verse.verse_number}</div>
                            <div class="similarity">Relevansi: ${verse.similarity}</div>
                        </div>
                    `;
                            });
                            verseHtml += '</div>';
                            messageDiv.innerHTML += verseHtml;
                        }
                        this.scrollToBottom();
                    }
                };

                typeChar();
            }


            addMessage(role, content, verses = null) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message message-${role}`;

                const timestamp = this.getCurrentTime();

                let messageContent = content;

                // Add verses if available
                if (verses && verses.length > 0) {
                    messageContent += '<div class="mt-2"><small><strong>📖 Ayat referensi:</strong></small>';
                    verses.forEach(verse => {
                        messageContent += `
                            <div class="message-verse">
                            <span class="surah">${verse.surah_name} Ayat ${verse.ayah_in_surah}</span>
                            <div class="arabic">${verse.arabic_text}</div>
                            <div class="translation">${verse.verse_number}</div>
                            <div class="similarity">Relevansi: ${verse.similarity}</div>
                            </div>
                        `;
                    });
                    messageContent += '</div>';
                }

                // Add timestamp
                messageContent += `<div class="timestamp">${timestamp}</div>`;

                messageDiv.innerHTML = messageContent;
                this.messagesContainer.appendChild(messageDiv);
                this.scrollToBottom();
            }

            addSystemMessage(content) {
                const systemDiv = document.createElement('div');
                systemDiv.className = 'message-system';
                systemDiv.innerHTML = `<i class="fas fa-info-circle"></i> ${content}`;
                this.messagesContainer.appendChild(systemDiv);
                this.scrollToBottom();
            }

            getCurrentTime() {
                const now = new Date();
                return now.toLocaleTimeString('id-ID', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            scrollToBottom() {
                setTimeout(() => {
                    this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
                }, 100);
            }

            async loadChatHistory() {
                try {
                    const response = await fetch(`/api/quran-chat/history/${this.sessionId}`);
                    const data = await response.json();

                    // Clear welcome messages if there's history
                    if (data.history && data.history.length > 0) {
                        this.messagesContainer.innerHTML = '';

                        // Add system welcome back
                        this.addSystemMessage('Memuat riwayat percakapan...');
                    }

                    // Load history
                    if (data.history) {
                        data.history.forEach(item => {
                            this.addMessage(item.role, item.message);
                        });

                        this.addSystemMessage('Riwayat percakapan telah dimuat');
                    }
                } catch (error) {
                    console.error('Error loading chat history:', error);
                    this.addSystemMessage('Gagal memuat riwayat percakapan');
                }
            }

            async clearChatHistory() {
                if (!confirm('Apakah Anda yakin ingin menghapus semua riwayat percakapan?')) {
                    return;
                }

                try {
                    // Show processing
                    this.addSystemMessage('Menghapus riwayat percakapan...');

                    await fetch(`/api/quran-chat/history/${this.sessionId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });

                    // Clear UI and show fresh start
                    this.messagesContainer.innerHTML = '';

                    // Add system messages
                    this.addSystemMessage('Riwayat percakapan telah dihapus');

                    // Add welcome message back
                    this.addMessage('assistant', `
                        <strong>Assalamu'alaikum! 👋</strong><br>
                        Percakapan telah dimulai baru. Silakan tanyakan pertanyaan apa pun tentang Al-Quran, 
                        tafsir, atau makna kehidupan menurut Islam.
                    `);

                    // Generate new session ID
                    this.sessionId = this.generateSessionId();

                } catch (error) {
                    console.error('Error clearing chat history:', error);
                    this.addSystemMessage('Gagal menghapus riwayat percakapan');
                }
            }
        }

        // Initialize the chat when page loads
        document.addEventListener('DOMContentLoaded', () => {
            new QuranChat();
        });
    </script>
</body>

</html>
