<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Generator</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: system-ui; max-width: 900px; margin: 40px auto; padding: 0 16px; }
        textarea, input, select { width: 100%; padding: 12px; font-size: 16px; }
        button { padding: 10px 16px; font-size: 16px; cursor: pointer; }
        .row { margin-top: 12px; }
        .box { margin-top: 16px; padding: 12px; background: #f6f6f6; white-space: pre-wrap; border-radius: 8px; }
        .err { color: crimson; margin-top: 12px; }
        .meta { margin-top: 10px; color: #444; }
    </style>
</head>
<body>
    <h2>Laravel Blade → Gemini Text Generation</h2>

    <div class="row">
        <label><b>Model (optional)</b></label>
        <input id="model" placeholder="gemini-3-flash-preview (default)" />
    </div>

    <div class="row">
        <label><b>Prompt</b></label>
        <textarea id="prompt" rows="6" placeholder="Type your prompt..."></textarea>
    </div>

    <div class="row" style="display:flex; gap:12px;">
        <button id="generateBtn">Generate</button>
        <button id="clearBtn" type="button">Clear</button>
    </div>

    <div id="error" class="err" style="display:none;"></div>

    <div id="meta" class="meta" style="display:none;"></div>
    <div id="output" class="box" style="display:none;"></div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;

const promptEl = document.getElementById('prompt');
const modelEl  = document.getElementById('model');
const btn      = document.getElementById('generateBtn');
const clearBtn = document.getElementById('clearBtn');
const errEl    = document.getElementById('error');
const metaEl   = document.getElementById('meta');
const outEl    = document.getElementById('output');

function setError(msg) {
  errEl.style.display = msg ? 'block' : 'none';
  errEl.textContent = msg || '';
}

function setResult(meta, text) {
  if (!text) {
    metaEl.style.display = 'none';
    outEl.style.display = 'none';
    return;
  }
  metaEl.style.display = 'block';
  outEl.style.display = 'block';
  metaEl.textContent = `provider: ${meta.provider} | model: ${meta.model}`;
  outEl.textContent = text;
}

btn.addEventListener('click', async () => {
  setError('');
  setResult({}, '');

  const prompt = promptEl.value.trim();
  const model  = modelEl.value.trim() || null;

  if (!prompt) {
    setError('Please enter a prompt.');
    return;
  }

  btn.disabled = true;
  btn.textContent = 'Generating...';

  try {
    const res = await fetch(`{{ route('ai.generate') }}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ prompt, model }),
    });

    const data = await res.json();

    if (!res.ok) {
      // Laravel validation errors show here
      const msg = data?.message || 'Request failed';
      setError(msg);
      return;
    }

    setResult({ provider: data.provider, model: data.model }, data.text);
  } catch (e) {
    setError(e?.message || 'Network error');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Generate';
  }
});

clearBtn.addEventListener('click', () => {
  promptEl.value = '';
  modelEl.value = '';
  setError('');
  setResult({}, '');
});
</script>

</body>
</html>