# Versioned Object AI APIs - Current Documentation

## Overview

This project uses a file-based versioning system for Object AI JSON configs.

- No database table is required for versioned files.
- Files are grouped by user-selected folder name.
- Each folder contains platform subfolders: android, ios, debug.
- Version matching supports exact and nearest-lower fallback.

## Storage Layout

Current storage root:

```text
storage/app/{folder_name}/{platform}/{file_version}.json
```

Example:

```text
storage/app/release-may/android/2.12.2.json
storage/app/release-may/ios/2.12.2.json
storage/app/release-may/debug/2.12.2.json
```

Legacy folder support:

- The folder named versioned is still listed and selectable in APIs/UI.

## Version Matching Logic

For a given folder + platform + requested version:

1. Return exact file_version if present.
2. Otherwise return the highest available version less than or equal to requested version.
3. If none exists, return 404.

Example with available versions 2.12.2 and 2.13.0:

- Request 2.12.2 -> returns 2.12.2
- Request 2.12.5 -> returns 2.12.2
- Request 2.15.0 -> returns 2.13.0
- Request 1.0.0 -> 404

## Route Summary

All routes are under api.php.

```text
GET    /api/object-ai/getFile
GET    /api/object-ai/versions/list
POST   /api/object-ai/versions/create
POST   /api/object-ai/versions/update
DELETE /api/object-ai/versions/delete

GET    /api/object-ai/folders/list
GET    /api/object-ai/folders/items
POST   /api/object-ai/folders/create
POST   /api/object-ai/folders/rename
DELETE /api/object-ai/folders/delete
```

## API Details

### 1) Get Config (client-facing)

Endpoint:

```text
GET /api/object-ai/getFile
```

Query params:

- organization_key (string, required by UI/client flow)
- folder_name (string, **optional**; defaults to **'versioned'** when omitted)
- platform (android|ios|debug, required)
- file_version (string, required)

Examples:

```text
# With specific folder
/api/object-ai/getFile?organization_key=abc&folder_name=release-may&platform=android&file_version=2.14.0

# Without folder (uses default 'versioned')
/api/object-ai/getFile?organization_key=abc&platform=android&file_version=2.14.0
```

Folder resolution behavior:

- If `folder_name` is provided: API searches only in that folder.
- If `folder_name` is omitted: API automatically uses the **'versioned'** folder as default.
- This allows clients to request configs without explicitly specifying a folder name.

Success response:

```json
{
  "status": true,
  "message": "Requested 2.14.0 not available; returning Android config for nearest version 2.13.0.",
  "data": {
    "platform": "android",
    "folder_name": "versioned",
    "matched_version": "2.13.0",
    "requested_version": "2.14.0",
    "config": {}
  }
}
```

**Note:** The `folder_name` in the response shows which folder was actually used. If you omitted it in the request, the response will show `"folder_name": "versioned"` (the default).

### 2) List Versions

Endpoint:

```text
GET /api/object-ai/versions/list
```

Query params:

- organization_key (string, required by UI/client flow)
- folder_name (string, optional)
- platform (android|ios|debug, optional)

Response item shape:

```json
{
  "folder_name": "release-may",
  "platform": "android",
  "file_version": "2.12.2",
  "file_size": 1024,
  "updated_at": "2026-04-22 12:00:00"
}
```

### 3) Create Version

Endpoint:

```text
POST /api/object-ai/versions/create
```

Body (form-data):

- organization_key (text)
- folder_name (text, required)
- platform (text, required)
- file_version (text, required, regex X.Y.Z)
- file (.json file, required)

Behavior:

- Creates directories if missing:
  - storage/app/{folder_name}/android
  - storage/app/{folder_name}/ios
  - storage/app/{folder_name}/debug
- Saves JSON at storage/app/{folder_name}/{platform}/{file_version}.json
- Rejects duplicate version in same folder/platform.

### 4) Update Version

Endpoint:

```text
POST /api/object-ai/versions/update
```

Body (form-data):

- organization_key (text)
- folder_name (text, required)
- platform (text, required)
- file_version (text, required)
- file (.json file, required)

Behavior:

- Replaces existing JSON file.
- Returns 404 if version file does not exist.

### 5) Delete Version

Endpoint:

```text
DELETE /api/object-ai/versions/delete
```

Body (JSON):

```json
{
  "organization_key": "abc",
  "folder_name": "release-may",
  "platform": "android",
  "file_version": "2.12.2"
}
```

### 6) List Folders

Endpoint:

```text
GET /api/object-ai/folders/list
```

Returns all managed folders detected under storage/app that contain android/ios/debug subfolders, plus the legacy versioned folder if present.

Response item:

```json
{
  "folder_name": "release-may",
  "platform_counts": {
    "android": 2,
    "ios": 1,
    "debug": 1
  },
  "total_files": 4,
  "created_at": "2026-04-22 11:00:00"
}
```

### 7) List Folder Items (easy browse)

Endpoint:

```text
GET /api/object-ai/folders/items
```

Query params:

- organization_key (string)
- folder_name (string, required)
- platform (android|ios|debug, optional)

Returns item list with relative_path.

Example relative_path:

```text
release-may/android/2.12.2.json
```

### 8) Create Folder

Endpoint:

```text
POST /api/object-ai/folders/create
```

Body (JSON):

```json
{
  "organization_key": "abc",
  "folder_name": "release-may"
}
```

Creates folder and all platform subfolders under storage/app.

### 9) Rename Folder

Endpoint:

```text
POST /api/object-ai/folders/rename
```

Body (JSON):

```json
{
  "organization_key": "abc",
  "old_folder_name": "release-may",
  "new_folder_name": "release-june"
}
```

### 10) Delete Folder

Endpoint:

```text
DELETE /api/object-ai/folders/delete
```

Body (JSON):

```json
{
  "organization_key": "abc",
  "folder_name": "release-june",
  "force": true
}
```

If force is false and files exist, API returns 409.

## Validation Rules

- folder_name regex: ^[a-z0-9][a-z0-9_-]{0,49}$
- platform: android | ios | debug
- file_version regex: ^\d+\.\d+\.\d+$
- uploaded file must be valid JSON

## GitHub Sync Behavior

On create/update, API attempts GitHub sync if env variables exist:

- GITHUB_REPO_OWNER
- GITHUB_REPO_NAME
- GITHUB_TOKEN
- GITHUB_BRANCH (optional, defaults to main)

Remote path used:

```text
storage/app/{folder_name}/{platform}/{file_version}.json
```

If GitHub env config is missing, file is still saved locally and sync is skipped.

## UI Notes (Version Manager)

### All Versions Tab
- Filter by folder and/or platform
- Displays all versions across selected folders
- Can browse files in selected folder via Folder Items panel

### Create & Update Tabs
- **Folder selection is required** for creating/updating versions
- Folder management features available (create/rename/delete folders)
- Cannot proceed without selecting a folder

### Client Test Tab (Get Config)
- **Folder selection is optional**
- Leave folder empty to use the default **'versioned'** folder
- If a specific folder is selected, API uses that folder
- This matches the optional folder_name parameter in the getFile API
- Version dropdown loads versions based on selected platform and folder

### General Features
- Users can create/rename/delete folders via folder management controls
- All operations respect the selected folder context
- Folder Items panel shows all files within the currently selected folder
