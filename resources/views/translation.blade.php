<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Translation POC</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 0 16px;
            line-height: 1.5;
        }

        h1 {
            margin-bottom: 8px;
        }

        .description {
            margin-bottom: 24px;
            color: #555;
        }

        textarea {
            width: 100%;
            min-height: 140px;
            padding: 12px;
            font-size: 15px;
            box-sizing: border-box;
        }

        .field {
            margin-bottom: 16px;
        }

        .btn {
            padding: 10px 18px;
            font-size: 15px;
            cursor: pointer;
        }

        .error {
            color: #b00020;
            margin-top: 8px;
        }

        .server-error {
            margin-top: 16px;
            padding: 12px;
            background: #ffe9e9;
            border: 1px solid #ffb3b3;
            color: #8a0000;
        }

        .results {
            margin-top: 32px;
        }

        .card {
            border: 1px solid #ddd;
            padding: 16px;
            margin-bottom: 16px;
            border-radius: 8px;
            background: #fafafa;
        }

        .label {
            font-weight: bold;
            margin-bottom: 8px;
        }

        .meta {
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <h1>AI Translation POC</h1>
    <p class="description">
        Enter English text and generate simplified English, Hindi, Gujarati, French, and Spanish.
    </p>

    <form method="POST" action="{{ route('translation.generate') }}">
        @csrf

        <div class="field">
            <label for="text"><strong>English Text</strong></label>
            <textarea name="text" id="text" placeholder="Enter English text here...">{{ old('text', $inputText ?? '') }}</textarea>
            @error('text')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="model"><strong>Model (optional)</strong></label>
            <input
                type="text"
                name="model"
                id="model"
                value="{{ old('model') }}"
                placeholder="Leave blank to use provider default"
                style="width: 100%; padding: 10px; box-sizing: border-box;"
            >
            @error('model')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn">Generate Translations</button>
    </form>

    @if (session('errorMessage'))
        <div class="server-error">
            {{ session('errorMessage') }}
        </div>
    @endif

    @if (!empty($result))
        <div class="results">
            <h2>Results</h2>

            <div class="card">
                <div class="label">Original English</div>
                <div>{{ $result['original_english'] }}</div>
            </div>

            <div class="card">
                <div class="label">English (Simplified)</div>
                <div>{{ $result['simplified_english'] }}</div>
            </div>

            <div class="card">
                <div class="label">Hindi</div>
                <div>{{ $result['hindi'] }}</div>
            </div>

            <div class="card">
                <div class="label">Gujarati</div>
                <div>{{ $result['gujarati'] }}</div>
            </div>

            <div class="card">
                <div class="label">French</div>
                <div>{{ $result['french'] }}</div>
            </div>

            <div class="card">
                <div class="label">Spanish</div>
                <div>{{ $result['spanish'] }}</div>
            </div>

            <div class="meta">
                Provider used: {{ $result['provider'] }}<br>
                Model used: {{ $result['model'] }}
            </div>
        </div>
    @endif
</body>
</html>
