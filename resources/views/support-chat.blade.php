<!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Support Chat</title>
  <style>
    body { font-family: system-ui; max-width: 900px; margin: 30px auto; padding: 0 16px; }
    #chat { border: 1px solid #ddd; border-radius: 10px; padding: 12px; height: 60vh; overflow:auto; background:#fafafa;}
    .msg { margin: 10px 0; display:flex; }
    .user { justify-content: flex-end; }
    .assistant { justify-content: flex-start; }
    .bubble { max-width: 70%; padding: 10px 12px; border-radius: 12px; white-space: pre-wrap; }
    .user .bubble { background: #d9fdd3; }
    .assistant .bubble { background: #fff; border: 1px solid #eee; }
    .row { margin-top: 12px; display:flex; gap: 10px; }
    textarea { width: 100%; padding: 10px; font-size: 16px; }
    button { padding: 10px 16px; font-size: 16px; cursor:pointer;}
    .small { color:#666; font-size: 13px; margin-top: 8px; }
    .err { color:crimson; margin-top: 10px; }
  </style>
</head>
<body>
  <h2>Customer Support Chat</h2>

  <div id="chat"></div>

  <div class="row">
    <textarea id="input" rows="2" placeholder="Type your message..."></textarea>
    <button id="send">Send</button>
  </div>

  <div class="small">Enter = send, Shift+Enter = new line</div>
  <div id="err" class="err" style="display:none;"></div>

<script>
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const chatEl = document.getElementById('chat');
  const inputEl = document.getElementById('input');
  const sendBtn = document.getElementById('send');
  const errEl = document.getElementById('err');

  let conversationId = null;

  function showError(msg) {
    errEl.style.display = msg ? 'block' : 'none';
    errEl.textContent = msg || '';
  }

  function addMessage(role, text) {
    const wrap = document.createElement('div');
    wrap.className = `msg ${role}`;
    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    bubble.textContent = text;
    wrap.appendChild(bubble);
    chatEl.appendChild(wrap);
    chatEl.scrollTop = chatEl.scrollHeight;
  }

  async function startConversation() {
    const res = await fetch("{{ route('support.chat.start') }}", {
      method: "POST",
      headers: { "X-CSRF-TOKEN": csrf, "Accept": "application/json" }
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data?.message || "Failed to start conversation");
    conversationId = Number.parseInt(data.conversation_id, 10);
    if (!Number.isInteger(conversationId) || conversationId < 1) {
      throw new Error("Invalid integer conversation_id");
    }
  }

  async function sendMessage() {
    showError('');
    const text = inputEl.value.trim();
    if (!text) return;

    if (!conversationId) {
      await startConversation();
      addMessage('assistant', "Hi! How can I help you today?");
    }

    addMessage('user', text);
    inputEl.value = '';
    sendBtn.disabled = true;

    try {
      const res = await fetch("{{ route('support.chat.send') }}", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrf,
          "Accept": "application/json"
        },
        body: JSON.stringify({
          conversation_id: Number.parseInt(conversationId, 10),
          message: text,
          model: "gemini-3-flash-preview"
        })
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data?.message || "Request failed");

      addMessage('assistant', data.text || '(no response)');
    } catch (e) {
      showError(e.message || 'Error');
    } finally {
      sendBtn.disabled = false;
      inputEl.focus();
    }
  }

  sendBtn.addEventListener('click', sendMessage);
  inputEl.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  addMessage('assistant', "Hi! Ask me about our products, pricing, or troubleshooting.");
</script>

</body>
</html>
