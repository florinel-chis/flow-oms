<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #{{ $sync->increment_id }} - Raw JSON Data</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Menlo', 'Monaco', 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: #2d2d2d;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            border-bottom: 2px solid #007acc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 20px;
            color: #fff;
            font-weight: 500;
        }

        .header .meta {
            color: #858585;
            font-size: 14px;
        }

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            background: #007acc;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-family: inherit;
            transition: background 0.2s;
        }

        .btn:hover {
            background: #005a9e;
        }

        .btn-secondary {
            background: #3c3c3c;
        }

        .btn-secondary:hover {
            background: #4c4c4c;
        }

        .json-container {
            background: #252526;
            padding: 24px;
            border-radius: 0 0 8px 8px;
            overflow-x: auto;
        }

        pre {
            margin: 0;
            font-size: 13px;
            line-height: 1.7;
            color: #d4d4d4;
        }

        /* JSON Syntax Highlighting */
        .json-key {
            color: #9cdcfe;
        }

        .json-string {
            color: #ce9178;
        }

        .json-number {
            color: #b5cea8;
        }

        .json-boolean {
            color: #569cd6;
        }

        .json-null {
            color: #569cd6;
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4ec9b0;
            color: #1e1e1e;
            padding: 12px 20px;
            border-radius: 4px;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 1000;
        }

        .notification.show {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Magento Order Sync - Raw JSON Data</h1>
                <div class="meta">
                    Order #{{ $sync->increment_id }} (Entity ID: {{ $sync->entity_id }}) |
                    Synced: {{ $sync->synced_at?->format('Y-m-d H:i:s') ?? 'N/A' }}
                </div>
            </div>
            <div class="actions">
                <button class="btn btn-secondary" onclick="downloadJSON()">
                    Download JSON
                </button>
                <button class="btn" onclick="copyToClipboard()">
                    Copy to Clipboard
                </button>
            </div>
        </div>

        <div class="json-container">
            <pre id="json-content">{{ $json }}</pre>
        </div>
    </div>

    <div id="notification" class="notification">
        JSON copied to clipboard!
    </div>

    <script>
        // Copy to clipboard
        function copyToClipboard() {
            const jsonContent = document.getElementById('json-content').textContent;
            navigator.clipboard.writeText(jsonContent).then(() => {
                showNotification('JSON copied to clipboard!');
            });
        }

        // Download JSON
        function downloadJSON() {
            const jsonContent = document.getElementById('json-content').textContent;
            const blob = new Blob([jsonContent], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'order-{{ $sync->increment_id }}-{{ $sync->entity_id }}.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showNotification('JSON file downloaded!');
        }

        // Show notification
        function showNotification(message) {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.classList.add('show');
            setTimeout(() => {
                notification.classList.remove('show');
            }, 2000);
        }

        // Apply syntax highlighting
        function highlightJSON() {
            const pre = document.getElementById('json-content');
            let json = pre.textContent;

            // Highlight different JSON elements
            json = json.replace(/"([^"]+)":/g, '<span class="json-key">"$1"</span>:');
            json = json.replace(/: "([^"]*)"/g, ': <span class="json-string">"$1"</span>');
            json = json.replace(/: (-?\d+\.?\d*)/g, ': <span class="json-number">$1</span>');
            json = json.replace(/: (true|false)/g, ': <span class="json-boolean">$1</span>');
            json = json.replace(/: null/g, ': <span class="json-null">null</span>');

            pre.innerHTML = json;
        }

        // Apply highlighting on load
        window.addEventListener('DOMContentLoaded', highlightJSON);
    </script>
</body>
</html>
