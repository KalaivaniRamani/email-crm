<!DOCTYPE html>
<html>
<head>
    <title>Real-time Email</title>
    <style>
        body { font-family: Arial; background: #f4f5f7; margin: 0; padding: 20px; }
        .container { max-width: 1300px; margin: 0 auto; display: flex; gap: 20px; }
        .sidebar { flex: 1; max-width: 400px; }
        .content { flex: 2; }
        #status { padding: 10px; margin-bottom: 20px; text-align:center; border-radius:5px; }
        .connected { background: #d4edda; color: #155724; }
        .disconnected { background: #f8d7da; color: #721c24; }
        .conversation { border:1px solid #ddd; margin:10px 0; border-radius:8px; background:#fff; cursor:pointer; padding:15px; }
        .conversation:hover { background: #f8f9fa; }
        .conversation.active { border-color: #1a73e8; background: #f0f7ff; }
        .conversation-subject { font-weight:bold; color:#1a73e8; }
        .conversation-preview { color:#666; margin-top:5px; font-size: 14px; }
        .conversation-date { color: #999; font-size: 12px; margin-top: 5px; }
        .email-thread { background: #fff; border-radius: 8px; padding: 0; }
        .email-message { padding: 20px; border-bottom: 1px solid #eee; }
        .email-message:last-child { border-bottom: none; }
        .email-message.outgoing { background: #f0f7ff; border-left: 4px solid #1a73e8; }
        .email-message.incoming { background: #f8f9fa; border-left: 4px solid #34a853; }
        .email-header { display: flex; justify-content: space-between; margin-bottom: 10px; align-items: center; }
        .email-from { font-weight: bold; }
        .email-date { color: #666; font-size: 14px; }
        .email-body { line-height: 1.6; white-space: pre-wrap; color: #444; }
        .reply-section { padding: 20px; background: #f8f9fa; border-radius: 8px; margin-top: 20px; }
        .reply-textarea { width: 100%; height: 120px; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial; font-size: 14px; resize: vertical; }
        .reply-btn { background: #1a73e8; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .reply-btn:disabled { background: #ccc; cursor: not-allowed; }
        .no-conversation { text-align: center; padding: 40px; color: #666; background: #fff; border-radius: 8px; }
        .conversation.unread .conversation-subject { font-weight: bold; color:#1a73e8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <h1>📧 Email CRM</h1>
            <div id="status" class="disconnected">Connecting to WebSocket...</div>
            <div id="conversations">Loading conversations...</div>
        </div>
        <div class="content">
            <div id="conversation-messages">
                <div class="no-conversation">
                    <h3>Select a conversation</h3>
                    <p>Choose an email from the sidebar to view the conversation thread and reply.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        class EmailCRM {
            constructor() {
                this.ws = null;
                this.currentConversationId = null;
                this.connectWebSocket();
                this.loadConversations();
            }

            async loadConversations() {
                try {
                    console.log("Loading conversations...");
                    const res = await fetch('api/get_conversations.php');
                    const data = await res.json();
                    console.log("Conversations loaded:", data);
                    
                    if (data.success) {
                        this.displayConversations(data.conversations);
                    } else {
                        document.getElementById('conversations').innerHTML = 
                            '<div style="color: red;">Error loading conversations</div>';
                    }
                } catch (error) {
                    console.error('Error loading conversations:', error);
                    document.getElementById('conversations').innerHTML = 
                        '<div style="color: red;">Failed to load conversations</div>';
                }
            }

            displayConversations(conversations) {
                const container = document.getElementById('conversations');
                if (!conversations || conversations.length === 0) {
                    container.innerHTML = '<div style="text-align:center;padding:40px;color:#666;">No emails yet.</div>';
                    return;
                }
                
                container.innerHTML = conversations.map(conv => `
                    <div class="conversation" onclick="crm.openConversation('${conv.conversation_id}')" 
                         id="conv-${conv.conversation_id}">
                        <div class="conversation-subject">${this.escapeHtml(conv.subject)}</div>
                        <div class="conversation-preview">
                            <strong>From:</strong> ${this.escapeHtml(conv.from_email)}<br>
                            ${this.escapeHtml(conv.body.substring(0, 120))}...
                        </div>
                        <div class="conversation-date">
                            ${new Date(conv.created_at).toLocaleString()}
                        </div>
                    </div>
                `).join('');
            }

            async openConversation(conversationId) {
                document.querySelectorAll('.conversation').forEach(conv => {
                    conv.classList.remove('active');
                });
                document.getElementById(`conv-${conversationId}`).classList.add('active');
                
                this.currentConversationId = conversationId;
                
                try {
                    const res = await fetch(`api/get_conversation_messages.php?conversation_id=${conversationId}`);
                    const data = await res.json();
                    
                    if (!data.success) {
                        document.getElementById('conversation-messages').innerHTML = 
                            '<div style="color: red;">Failed to load conversation</div>';
                        return;
                    }

                    this.displayConversationThread(data.emails, conversationId);
                } catch (error) {
                    console.error('Error:', error);
                    document.getElementById('conversation-messages').innerHTML = 
                        '<div style="color: red;">Error loading conversation</div>';
                }
            }

            displayConversationThread(emails, conversationId) {
                if (!emails || emails.length === 0) {
                    document.getElementById('conversation-messages').innerHTML = 
                        '<div class="no-conversation">No messages in this conversation</div>';
                    return;
                }

                const firstEmail = emails[0];
                const threadHTML = emails.map(email => {
                    const isOutgoing = email.from_email === 'rkalai1001@gmail.com';
                    return `
                        <div class="email-message ${isOutgoing ? 'outgoing' : 'incoming'}">
                            <div class="email-header">
                                <div class="email-from">${this.escapeHtml(email.from_email)}</div>
                                <div class="email-date">${new Date(email.received_at).toLocaleString()}</div>
                            </div>
                            <div class="email-body">${this.escapeHtml(email.body)}</div>
                        </div>
                    `;
                }).join('');

                const replySection = `
                    <div class="email-thread">
                        ${threadHTML}
                    </div>
                    <div class="reply-section">
                        <h4>Reply to this conversation:</h4>
                        <textarea id="reply-text" class="reply-textarea" placeholder="Type your reply here..."></textarea>
                        <button class="reply-btn" onclick="crm.sendReply('${conversationId}')">Send Reply</button>
                    </div>
                `;

                document.getElementById('conversation-messages').innerHTML = replySection;
            }

            async sendReply(conversationId) {
                const replyText = document.querySelector('#reply-text').value.trim();
                const sendButton = document.querySelector('.reply-btn');
                
                if (!replyText) {
                    alert('Please enter a reply message');
                    return;
                }

                sendButton.disabled = true;
                sendButton.textContent = 'Sending...';

                try {
                    const res = await fetch('api/send_reply.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            conversationId: conversationId,
                            replyText: replyText
                        })
                    });
                    
                    const data = await res.json();
                    
                    if (data.success) {
                        document.querySelector('#reply-text').value = '';
                        await this.openConversation(conversationId);
                        alert('✅ Reply sent successfully!');
                    } else {
                        alert('❌ Error: ' + data.error);
                    }
                } catch (error) {
                    console.error('Error sending reply:', error);
                    alert('❌ Failed to send reply');
                } finally {
                    sendButton.disabled = false;
                    sendButton.textContent = 'Send Reply';
                }
            }

            connectWebSocket() {
                console.log("Connecting to WebSocket...");
                
                try {
                    // Try different WebSocket URLs
                    const wsUrl = 'ws://localhost:2346';
                    this.ws = new WebSocket(wsUrl);
                    
                    this.ws.onopen = () => {
                        console.log('✅ WebSocket Connected to:', wsUrl);
                        this.updateStatus('✅ Connected - Real-time active', 'connected');
                    };
                    
                    this.ws.onmessage = (e) => {
                        const data = JSON.parse(e.data);
                        if (data.type === 'new_email') {
                            this.loadConversations(); // reload email list automatically
                            this.showNotification('📧 New email received from ' + data.from_email, 'success');
                        }
                    };

                    this.ws.onerror = (error) => {
                        console.log('❌ WebSocket Error:', error);
                        this.updateStatus('❌ WebSocket Error', 'disconnected');
                    };
                    
                    this.ws.onclose = () => {
                        console.log('🔌 WebSocket Closed');
                        this.updateStatus('❌ Disconnected - Reconnecting...', 'disconnected');
                        setTimeout(() => this.connectWebSocket(), 3000);
                    };
                    
                } catch (error) {
                    console.error('WebSocket connection error:', error);
                    this.updateStatus('❌ WebSocket Server Not Running', 'disconnected');
                }
            }

            showNotification(message, type) {
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 12px 20px;
                    background: ${type === 'success' ? '#d4edda' : '#f8d7da'};
                    color: ${type === 'success' ? '#155724' : '#721c24'};
                    border-radius: 4px;
                    z-index: 1000;
                    border: 1px solid ${type === 'success' ? '#c3e6cb' : '#f5c6cb'};
                `;
                notification.textContent = message;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 3000);
            }

            updateStatus(msg, type) {
                const status = document.getElementById('status');
                status.textContent = msg;
                status.className = type;
            }

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        }

        const crm = new EmailCRM();
    </script>
</body>
</html>