<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Object AI — Version Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0f0f11;
            --surface: #18181b;
            --surface-2: #1e1e22;
            --surface-3: #27272a;
            --border: #2e2e33;
            --border-hover: #3f3f46;
            --text: #fafafa;
            --text-secondary: #a1a1aa;
            --text-muted: #71717a;
            --accent: #FF4A4A;
            --accent-hover: #e53e3e;
            --accent-bg: rgba(255, 74, 74, 0.1);
            --green: #22c55e;
            --green-bg: rgba(34, 197, 94, 0.1);
            --red: #ef4444;
            --red-bg: rgba(239, 68, 68, 0.1);
            --yellow: #eab308;
            --yellow-bg: rgba(234, 179, 8, 0.1);
            --blue: #3b82f6;
            --blue-bg: rgba(59, 130, 246, 0.1);
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 4px 24px rgba(0, 0, 0, 0.3);
        }

        [data-theme="light"] {
            --bg: #f4f4f5;
            --surface: #ffffff;
            --surface-2: #f1f1f3;
            --surface-3: #e4e4e7;
            --border: #e4e4e7;
            --border-hover: #d4d4d8;
            --text: #18181b;
            --text-secondary: #52525b;
            --text-muted: #71717a;
            --accent: #FF4A4A;
            --accent-hover: #e53e3e;
            --accent-bg: rgba(255, 74, 74, 0.1);
            --green: #16a34a;
            --green-bg: rgba(22, 163, 74, 0.1);
            --red: #dc2626;
            --red-bg: rgba(220, 38, 38, 0.1);
            --yellow: #ca8a04;
            --yellow-bg: rgba(202, 138, 4, 0.1);
            --blue: #2563eb;
            --blue-bg: rgba(37, 99, 235, 0.1);
            --shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            line-height: 1.5;
            transition: background 0.25s, color 0.25s;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--border-hover); }

        /* Header */
        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(12px);
        }
        .header-left { display: flex; align-items: center; gap: 12px; }
        .header-logo {
            width: 36px; height: 36px;
            border-radius: 10px;
            overflow: hidden;
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .header-logo img {
            width: 100%; height: 100%;
            object-fit: cover;
            display: block;
        }
        .header-title { font-size: 18px; font-weight: 600; }
        .header-subtitle { font-size: 13px; color: var(--text-muted); }

        .api-key-group {
            display: flex; align-items: center; gap: 8px;
        }
        .api-key-group label { font-size: 13px; color: var(--text-secondary); white-space: nowrap; }
        .api-key-input {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 8px 12px;
            color: var(--text);
            font-size: 13px;
            font-family: 'SF Mono', 'Fira Code', monospace;
            width: 260px;
            outline: none;
            transition: border-color 0.2s;
        }
        .api-key-input:focus { border-color: var(--accent); }

        /* Layout */
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 24px 32px;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 4px;
            background: var(--surface);
            border-radius: var(--radius);
            padding: 4px;
            margin-bottom: 24px;
            border: 1px solid var(--border);
        }
        .tab {
            flex: 1;
            padding: 10px 16px;
            text-align: center;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            transition: all 0.2s;
            border: none;
            background: none;
            user-select: none;
        }
        .tab:hover { color: var(--text); background: var(--surface-2); }
        .tab.active {
            color: white;
            background: var(--accent);
            box-shadow: 0 2px 8px rgba(255, 74, 74, 0.35);
        }
        [data-theme="light"] .tab.active {
            box-shadow: 0 2px 8px rgba(255, 74, 74, 0.35);
        }

        /* Tab Panels */
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* Cards */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-header h3 { font-size: 15px; font-weight: 600; }
        .card-body { padding: 20px; }

        /* Badge */
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-android { background: var(--green-bg); color: var(--green); }
        .badge-ios { background: var(--blue-bg); color: var(--blue); }
        .badge-debug { background: var(--yellow-bg); color: var(--yellow); }
        .badge-method {
            background: var(--accent-bg);
            color: var(--accent);
            font-family: 'SF Mono', monospace;
        }

        /* Form elements */
        .form-group { margin-bottom: 16px; }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }

        input[type="text"], select {
            width: 100%;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            color: var(--text);
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }
        input[type="text"]:focus, select:focus { border-color: var(--accent); }
        select { cursor: pointer; appearance: none; }

        .file-input-wrapper {
            position: relative;
            background: var(--surface-2);
            border: 2px dashed var(--border);
            border-radius: var(--radius-sm);
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .file-input-wrapper:hover { border-color: var(--accent); background: var(--accent-bg); }
        .file-input-wrapper.has-file { border-color: var(--green); background: var(--green-bg); }
        .file-input-wrapper input[type="file"] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer;
        }
        .file-input-wrapper .file-label {
            font-size: 14px; color: var(--text-secondary);
        }
        .file-input-wrapper .file-icon {
            font-size: 28px; margin-bottom: 8px; display: block;
        }
        .file-input-wrapper .file-name {
            color: var(--green); font-weight: 500; font-size: 14px;
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }
        .btn-primary {
            background: var(--accent);
            color: white;
        }
        .btn-primary:hover { background: var(--accent-hover); transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }
        .btn-danger { background: var(--red-bg); color: var(--red); }
        .btn-danger:hover { background: var(--red); color: white; }
        .btn-ghost {
            background: var(--surface-2);
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        .btn-ghost:hover { border-color: var(--accent); color: var(--text); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
        .btn-sm { padding: 6px 14px; font-size: 13px; }
        .btn-full { width: 100%; justify-content: center; }

        .btn-group { display: flex; gap: 8px; margin-top: 8px; }

        /* Table */
        .table-wrapper { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            text-align: left;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            background: var(--surface-2);
            border-bottom: 1px solid var(--border);
        }
        td {
            padding: 14px 16px;
            font-size: 14px;
            border-bottom: 1px solid var(--border);
            color: var(--text-secondary);
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--surface-2); }
        td.version-cell { font-family: 'SF Mono', monospace; color: var(--text); font-weight: 500; }

        /* Response Panel */
        .response-panel {
            margin-top: 20px;
        }
        .response-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm) var(--radius-sm) 0 0;
        }
        .response-status {
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .response-status .dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .response-status .dot.success { background: var(--green); }
        .response-status .dot.error { background: var(--red); }
        .response-time { font-size: 12px; color: var(--text-muted); }
        .response-body {
            background: var(--bg);
            border: 1px solid var(--border);
            border-top: none;
            border-radius: 0 0 var(--radius-sm) var(--radius-sm);
            padding: 16px;
            max-height: 400px;
            overflow-y: auto;
        }
        .response-body pre {
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: 13px;
            line-height: 1.6;
            color: var(--text-secondary);
            white-space: pre-wrap;
            word-break: break-word;
        }

        /* Loading spinner */
        .spinner {
            width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            display: none;
        }
        .btn.loading .spinner { display: inline-block; }
        .btn.loading .btn-text { display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Toast */
        .toast-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .toast {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 12px 20px;
            font-size: 14px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
            min-width: 280px;
        }
        .toast.success { border-left: 3px solid var(--green); }
        .toast.error { border-left: 3px solid var(--red); }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(60px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .empty-state .icon { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
        .empty-state p { font-size: 15px; margin-bottom: 8px; }
        .empty-state small { font-size: 13px; }

        /* Quick test section */
        .quick-test {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 24px;
        }
        .quick-test h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .endpoint-display {
            font-family: 'SF Mono', monospace;
            font-size: 13px;
            color: var(--text-muted);
            background: var(--surface-2);
            padding: 8px 14px;
            border-radius: var(--radius-sm);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .endpoint-display .method { color: var(--accent); font-weight: 600; }

        /* Confirm modal */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
            z-index: 200;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            max-width: 420px;
            width: 90%;
            box-shadow: var(--shadow);
        }
        .modal h3 { font-size: 16px; margin-bottom: 8px; }
        .modal p { font-size: 14px; color: var(--text-secondary); margin-bottom: 20px; }
        .modal .btn-group { justify-content: flex-end; }

        /* Filter bar */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .filter-bar select { width: auto; min-width: 140px; }

        /* Theme toggle */
        .header-right { display: flex; align-items: center; gap: 12px; }
        .theme-toggle {
            width: 38px; height: 38px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--surface-2);
            color: var(--text-secondary);
            font-size: 18px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .theme-toggle:hover {
            border-color: var(--accent);
            color: var(--text);
            background: var(--accent-bg);
        }

        @media (max-width: 768px) {
            .header { padding: 12px 16px; flex-direction: column; gap: 12px; }
            .container { padding: 16px; }
            .form-row, .form-row-3 { grid-template-columns: 1fr; }
            .api-key-input { width: 100%; }
            .tabs { flex-wrap: wrap; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <div class="header-logo">
                <img src="https://objectsai.app/wp-content/uploads/2023/04/Object-AI-site-icon.png" alt="Object AI" />
            </div>
            <div>
                <div class="header-title">Object AI — Version Manager</div>
                <div class="header-subtitle">Manage versioned JSON configs for Android, iOS & Debug</div>
            </div>
        </div>
        <div class="header-right">
            <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Toggle light / dark mode">
                <span id="themeIcon"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg></span>
            </button>
            <div class="api-key-group">
                <label>API Key</label>
                <input type="text" class="api-key-input" id="apiKey" placeholder="Enter your MODEL_API_KEY" oninput="onApiKeyInput()" />
            </div>
        </div>
    </div>

    <!-- Toasts -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <h3>⚠️ Delete Version</h3>
            <p id="deleteModalText">Are you sure you want to delete this version? This cannot be undone.</p>
            <div class="btn-group">
                <button class="btn btn-ghost" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" data-tab="versions" onclick="switchTab('versions')">📋 All Versions</button>
            <button class="tab" data-tab="create" onclick="switchTab('create')">➕ Create</button>
            <button class="tab" data-tab="update" onclick="switchTab('update')">✏️ Update</button>
            <button class="tab" data-tab="client" onclick="switchTab('client')">📱 Get</button>
        </div>

        <!-- ==================== TAB 1: LIST VERSIONS ==================== -->
        <div class="tab-panel active" id="tab-versions">
            <div class="card">
                <div class="card-header">
                    <h3>All Versioned Configs</h3>
                    <div class="filter-bar">
                        <select id="filterPlatform" onchange="loadVersions()">
                            <option value="">All Platforms</option>
                            <option value="android">Android</option>
                            <option value="ios">iOS</option>
                            <option value="debug">Debug</option>
                        </select>
                        <button class="btn btn-ghost btn-sm" onclick="loadVersions()">↻ Refresh</button>
                    </div>
                </div>
                <div class="card-body" style="padding: 0;">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Platform</th>
                                    <th>Version</th>
                                    <th>File Size</th>
                                    <th>Last Updated</th>
                                    <th style="text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="versionsTableBody">
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <div class="icon">📂</div>
                                            <p>No versions loaded</p>
                                            <small>Enter your API key and click Refresh</small>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== TAB 2: CREATE VERSION ==================== -->
        <div class="tab-panel" id="tab-create">
            <div class="card">
                <div class="card-header">
                    <h3>Create New Version</h3>
                    <span class="badge badge-method">POST</span>
                </div>
                <div class="card-body">
                    <div class="endpoint-display">
                        <span class="method">POST</span>
                        <span>/object-ai/versions/create</span>
                    </div>
                    <form id="createForm" onsubmit="handleCreate(event)">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Platform</label>
                                <select id="createPlatform" required>
                                    <option value="">Select platform...</option>
                                    <option value="android">Android</option>
                                    <option value="ios">iOS</option>
                                    <option value="debug">Debug</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Version</label>
                                <input type="text" id="createVersion" placeholder="e.g. 2.12.2" pattern="^\d+\.\d+\.\d+$" required />
                            </div>
                        </div>
                        <div class="form-group">
                            <label>JSON File</label>
                            <div class="file-input-wrapper" id="createFileWrapper">
                                <input type="file" accept=".json" id="createFile" onchange="handleFileSelect(this, 'createFileWrapper')" required />
                                <span class="file-icon">📄</span>
                                <span class="file-label">Click or drag to upload a <strong>.json</strong> file</span>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-full" id="createBtn">
                            <span class="spinner"></span>
                            <span class="btn-text">Create Version</span>
                        </button>
                    </form>
                    <div class="response-panel" id="createResponse" style="display:none;"></div>
                </div>
            </div>
        </div>

        <!-- ==================== TAB 3: UPDATE VERSION ==================== -->
        <div class="tab-panel" id="tab-update">
            <div class="card">
                <div class="card-header">
                    <h3>Update Existing Version</h3>
                    <span class="badge badge-method">POST</span>
                </div>
                <div class="card-body">
                    <div class="endpoint-display">
                        <span class="method">POST</span>
                        <span>/object-ai/versions/update</span>
                    </div>
                    <form id="updateForm" onsubmit="handleUpdate(event)">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Platform</label>
                                <select id="updatePlatform" required onchange="loadUpdateVersions()">
                                    <option value="">Select platform...</option>
                                    <option value="android">Android</option>
                                    <option value="ios">iOS</option>
                                    <option value="debug">Debug</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Version</label>
                                <select id="updateVersion" required>
                                    <option value="">Select platform first...</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Replacement JSON File</label>
                            <div class="file-input-wrapper" id="updateFileWrapper">
                                <input type="file" accept=".json" id="updateFile" onchange="handleFileSelect(this, 'updateFileWrapper')" required />
                                <span class="file-icon">📄</span>
                                <span class="file-label">Click or drag to upload a <strong>.json</strong> file</span>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-full" id="updateBtn">
                            <span class="spinner"></span>
                            <span class="btn-text">Update Version</span>
                        </button>
                    </form>
                    <div class="response-panel" id="updateResponse" style="display:none;"></div>
                </div>
            </div>
        </div>

        <!-- ==================== TAB 4: CLIENT TEST ==================== -->
        <div class="tab-panel" id="tab-client">
            <div class="card">
                <div class="card-header">
                    <h3>Client API Test</h3>
                    <span class="badge badge-method">POST</span>
                </div>
                <div class="card-body">
                    <div class="form-row-3">
                        <div class="form-group">
                            <label>Platform</label>
                            <select id="clientPlatform" onchange="updateClientEndpoint(); loadClientVersions();">
                                <option value="android">Android</option>
                                <option value="ios">iOS</option>
                                <option value="debug">Debug</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>App Version</label>
                            <select id="clientVersion">
                                <option value="">Enter API key first...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary btn-full" id="clientBtn" onclick="handleClientTest()">
                                <span class="spinner"></span>
                                <span class="btn-text">Send Request</span>
                            </button>
                        </div>
                    </div>
                    <div class="endpoint-display" id="clientEndpoint">
                        <span class="method">POST</span>
                        <span>/api/object-ai/android</span>
                    </div>
                    <div class="response-panel" id="clientResponse" style="display:none;"></div>
                </div>
            </div>

            <!-- Quick reference -->
            <div class="quick-test" style="margin-top: 24px;">
                <h4>💡 How Version Matching Works</h4>
                <table>
                    <thead>
                        <tr>
                            <th>App Sends</th>
                            <th>Available Versions</th>
                            <th>Returns</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="version-cell">2.12.2</td>
                            <td>2.12.2, 2.13.0</td>
                            <td class="version-cell" style="color: var(--green);">2.12.2</td>
                            <td>Exact match</td>
                        </tr>
                        <tr>
                            <td class="version-cell">2.12.5</td>
                            <td>2.12.2, 2.13.0</td>
                            <td class="version-cell" style="color: var(--yellow);">2.12.2</td>
                            <td>Nearest lower version</td>
                        </tr>
                        <tr>
                            <td class="version-cell">2.15.0</td>
                            <td>2.12.2, 2.13.0</td>
                            <td class="version-cell" style="color: var(--yellow);">2.13.0</td>
                            <td>Nearest lower version</td>
                        </tr>
                        <tr>
                            <td class="version-cell">1.0.0</td>
                            <td>2.12.2, 2.13.0</td>
                            <td class="version-cell" style="color: var(--red);">404</td>
                            <td>No version ≤ 1.0.0</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const BASE_URL = window.location.origin ;

        // ───────────── Tab switching ─────────────
        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
            document.getElementById(`tab-${tab}`).classList.add('active');

            if (tab === 'versions') loadVersions();
            if (tab === 'client') loadClientVersions();
        }

        // ───────────── Utils ─────────────
        function getApiKey() {
            return document.getElementById('apiKey').value.trim();
        }

        function onApiKeyInput() {
            // Auto-refresh version dropdowns if a platform is already selected
            const updatePlatform = document.getElementById('updatePlatform')?.value;
            if (updatePlatform) loadUpdateVersions();
            loadClientVersions();
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `<span>${type === 'success' ? '✅' : '❌'}</span> ${message}`;
            container.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        function setLoading(btnId, loading) {
            const btn = document.getElementById(btnId);
            if (loading) btn.classList.add('loading');
            else btn.classList.remove('loading');
            btn.disabled = loading;
        }

        function renderResponse(containerId, data, status, time) {
            const container = document.getElementById(containerId);
            const isSuccess = status >= 200 && status < 300;
            container.style.display = 'block';
            container.innerHTML = `
                <div class="response-header">
                    <div class="response-status">
                        <span class="dot ${isSuccess ? 'success' : 'error'}"></span>
                        Status: ${status}
                    </div>
                    <span class="response-time">${time}ms</span>
                </div>
                <div class="response-body">
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                </div>
            `;
        }

        function handleFileSelect(input, wrapperId) {
            const wrapper = document.getElementById(wrapperId);
            if (input.files.length > 0) {
                wrapper.classList.add('has-file');
                wrapper.querySelector('.file-label').style.display = 'none';
                wrapper.querySelector('.file-icon').textContent = '✅';
                let nameEl = wrapper.querySelector('.file-name');
                if (!nameEl) {
                    nameEl = document.createElement('div');
                    nameEl.className = 'file-name';
                    wrapper.appendChild(nameEl);
                }
                nameEl.textContent = input.files[0].name;
            }
        }

        function formatBytes(bytes) {
            if (!bytes || bytes === 0) return '0 B';
            const sizes = ['B', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(1024));
            return parseFloat((bytes / Math.pow(1024, i)).toFixed(1)) + ' ' + sizes[i];
        }

        function platformBadge(platform) {
            return `<span class="badge badge-${platform}">${platform}</span>`;
        }

        // ───────────── LIST VERSIONS ─────────────
        async function loadVersions() {
            const key = getApiKey();
            if (!key) {
                showToast('Please enter your API key first', 'error');
                return;
            }

            const platform = document.getElementById('filterPlatform').value;
            const body = { key };
            if (platform) body.platform = platform;

            try {
                const start = Date.now();
                const res = await fetch(`${BASE_URL}/object-ai/versions/list`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(body)
                });
                const data = await res.json();
                const tbody = document.getElementById('versionsTableBody');

                if (data.status && data.data && data.data.length > 0) {
                    tbody.innerHTML = data.data.map(v => `
                        <tr>
                            <td>${platformBadge(v.platform)}</td>
                            <td class="version-cell">${v.version}</td>
                            <td>${formatBytes(v.file_size)}</td>
                            <td>${v.updated_at || '—'}</td>
                            <td style="text-align: right;">
                                <button class="btn btn-danger btn-sm" onclick="openDeleteModal('${v.platform}', '${v.version}')">Delete</button>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = `
                        <tr><td colspan="5">
                            <div class="empty-state">
                                <div class="icon">📭</div>
                                <p>No versioned configs found</p>
                                <small>Create one using the "Create" tab</small>
                            </div>
                        </td></tr>`;
                }
            } catch (e) {
                showToast('Failed to load versions: ' + e.message, 'error');
            }
        }

        // ───────────── CREATE VERSION ─────────────
        async function handleCreate(e) {
            e.preventDefault();
            const key = getApiKey();
            if (!key) { showToast('Enter API key first', 'error'); return; }

            const formData = new FormData();
            formData.append('key', key);
            formData.append('platform', document.getElementById('createPlatform').value);
            formData.append('version', document.getElementById('createVersion').value);
            formData.append('file', document.getElementById('createFile').files[0]);

            setLoading('createBtn', true);
            const start = Date.now();
            try {
                const res = await fetch(`${BASE_URL}/object-ai/versions/create`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();
                renderResponse('createResponse', data, res.status, Date.now() - start);
                if (data.status) {
                    showToast('Version created successfully!');
                    document.getElementById('createForm').reset();
                    const wrapper = document.getElementById('createFileWrapper');
                    wrapper.classList.remove('has-file');
                    wrapper.querySelector('.file-label').style.display = '';
                    wrapper.querySelector('.file-icon').textContent = '📄';
                    const nameEl = wrapper.querySelector('.file-name');
                    if (nameEl) nameEl.remove();
                } else {
                    showToast(data.message || 'Create failed', 'error');
                }
            } catch (e) {
                showToast('Request failed: ' + e.message, 'error');
            }
            setLoading('createBtn', false);
        }

        // ───────────── UPDATE VERSION ─────────────
        async function loadUpdateVersions() {
            const key = getApiKey();
            const platform = document.getElementById('updatePlatform').value;
            const select = document.getElementById('updateVersion');

            select.innerHTML = '<option value="">Loading...</option>';
            select.disabled = true;

            if (!platform) {
                select.innerHTML = '<option value="">Select platform first...</option>';
                select.disabled = false;
                return;
            }

            if (!key) {
                select.innerHTML = '<option value="">Enter API key first...</option>';
                select.disabled = false;
                return;
            }

            try {
                const res = await fetch(`${BASE_URL}/object-ai/versions/list`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ key, platform })
                });
                const data = await res.json();

                if (data.status && data.data && data.data.length > 0) {
                    select.innerHTML = '<option value="">Select version...</option>' +
                        data.data.map(v => `<option value="${v.version}">${v.version}</option>`).join('');
                } else {
                    select.innerHTML = '<option value="">No versions found for ' + platform + '</option>';
                }
            } catch (e) {
                select.innerHTML = '<option value="">Failed to load versions</option>';
            }

            select.disabled = false;
        }

        async function handleUpdate(e) {
            e.preventDefault();
            const key = getApiKey();
            if (!key) { showToast('Enter API key first', 'error'); return; }

            const formData = new FormData();
            formData.append('key', key);
            formData.append('platform', document.getElementById('updatePlatform').value);
            formData.append('version', document.getElementById('updateVersion').value);
            formData.append('file', document.getElementById('updateFile').files[0]);

            setLoading('updateBtn', true);
            const start = Date.now();
            try {
                const res = await fetch(`${BASE_URL}/object-ai/versions/update`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                });
                const data = await res.json();
                renderResponse('updateResponse', data, res.status, Date.now() - start);
                if (data.status) showToast('Version updated!');
                else showToast(data.message || 'Update failed', 'error');
            } catch (e) {
                showToast('Request failed: ' + e.message, 'error');
            }
            setLoading('updateBtn', false);
        }

        // ───────────── DELETE VERSION ─────────────
        let pendingDelete = { platform: '', version: '' };

        function openDeleteModal(platform, version) {
            pendingDelete = { platform, version };
            document.getElementById('deleteModalText').textContent =
                `Are you sure you want to delete ${platform} v${version}? This cannot be undone.`;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        async function confirmDelete() {
            const key = getApiKey();
            if (!key) { showToast('Enter API key first', 'error'); return; }

            closeDeleteModal();
            try {
                const res = await fetch(`${BASE_URL}/object-ai/versions/delete`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        key,
                        platform: pendingDelete.platform,
                        version: pendingDelete.version
                    })
                });
                const data = await res.json();
                if (data.status) {
                    showToast(`Deleted ${pendingDelete.platform} v${pendingDelete.version}`);
                    loadVersions();
                } else {
                    showToast(data.message || 'Delete failed', 'error');
                }
            } catch (e) {
                showToast('Delete failed: ' + e.message, 'error');
            }
        }

        const ICON_SUN  = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>`;
        const ICON_MOON = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`;

        function setThemeIcon(theme) {
            document.getElementById('themeIcon').innerHTML = theme === 'dark' ? ICON_SUN : ICON_MOON;
        }
        function toggleTheme() {
            const html = document.documentElement;
            const isDark = html.getAttribute('data-theme') === 'dark';
            const next = isDark ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            document.getElementById('themeIcon').innerHTML = next === 'dark' ? ICON_SUN : ICON_MOON;
            localStorage.setItem('vm-theme', next);
        }

        function initTheme() {
            const saved = localStorage.getItem('vm-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', saved);
            setThemeIcon(saved);
        }

        initTheme();

        // ───────────── CLIENT TEST ─────────────
        async function loadClientVersions() {
            const key = getApiKey();
            const platform = document.getElementById('clientPlatform').value;
            const select = document.getElementById('clientVersion');

            if (!key) {
                select.innerHTML = '<option value="">Enter API key first...</option>';
                return;
            }

            select.innerHTML = '<option value="">Loading...</option>';
            select.disabled = true;

            try {
                const res = await fetch(`${BASE_URL}/object-ai/versions/list`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ key, platform })
                });
                const data = await res.json();

                if (data.status && data.data && data.data.length > 0) {
                    select.innerHTML = '<option value="">Select version...</option>' +
                        data.data.map(v => `<option value="${v.version}">${v.version}</option>`).join('');
                } else {
                    select.innerHTML = `<option value="">No versions found for ${platform}</option>`;
                }
            } catch (e) {
                select.innerHTML = '<option value="">Failed to load versions</option>';
            }

            select.disabled = false;
        }
        function updateClientEndpoint() {
            const p = document.getElementById('clientPlatform').value;
            document.querySelector('#clientEndpoint span:last-child').textContent = `/api/object-ai/${p}`;
        }

        async function handleClientTest() {
            const key = getApiKey();
            if (!key) { showToast('Enter API key first', 'error'); return; }

            const platform = document.getElementById('clientPlatform').value;
            const version = document.getElementById('clientVersion').value.trim();
            if (!version) { showToast('Enter a version to test', 'error'); return; }

            setLoading('clientBtn', true);
            const start = Date.now();
            try {
                const res = await fetch(`${BASE_URL}/object-ai/${platform}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ key, version })
                });
                const data = await res.json();
                renderResponse('clientResponse', data, res.status, Date.now() - start);
            } catch (e) {
                showToast('Request failed: ' + e.message, 'error');
            }
            setLoading('clientBtn', false);
        }
    </script>
</body>
</html>
