# Versioned Object AI JSON APIs — Documentation

## Overview

A **file-based versioning system** for Object AI JSON configs. Each platform (Android, iOS, Debug) can have multiple JSON files tagged to app versions (e.g. `2.12.2`). The app requests its version, and the API returns the matching (or nearest lower) config.

**No database required** — the folder structure *is* the source of truth.

---

## Architecture

### File Storage Structure

```
storage/app/versioned/
├── android/
│   ├── 2.12.2.json
│   ├── 2.13.0.json
│   └── 3.0.0.json
├── ios/
│   ├── 2.12.2.json
│   └── 2.13.0.json
└── debug/
    └── 2.12.2.json
```

- Each `.json` file is named after the app version it targets
- The folder name is the platform (`android`, `ios`, `debug`)
- Files are automatically synced to GitHub on create/update

### Files Added

| File | Purpose |
|------|---------|
| `app/Http/Controllers/ObjectAi/VersionedJsonController.php` | Controller with all 7 APIs |
| `routes/api.php` | 7 new routes added |
| `storage/app/versioned/{android,ios,debug}/` | Storage directories |
| `ObjectAi_Versioned_APIs.postman_collection.json` | Postman collection for testing |

---

## Version Matching Logic

When the app requests a version, the API uses **fallback matching**:

```
App requests version "2.14.0"
├── Exact match exists (2.14.0.json)?  → Return it
└── No exact match?
    └── Find highest version ≤ 2.14.0
        ├── Found 2.13.0.json → Return it (fallback)
        └── Nothing found → Return 404 error
```

**Example:** If versions `2.12.2` and `2.13.0` exist:

| App sends version | Returns | Why |
|-------------------|---------|-----|
| `2.12.2` | `2.12.2` | Exact match |
| `2.12.5` | `2.12.2` | Nearest lower |
| `2.13.0` | `2.13.0` | Exact match |
| `2.15.0` | `2.13.0` | Nearest lower |
| `1.0.0` | 404 error | No version ≤ 1.0.0 |

This means you only need to upload a new config **when the JSON content actually changes**, not for every app release.

---

## API Reference

### Authentication

All APIs require the `key` field in the request body, validated against the `MODEL_API_KEY` environment variable.

---

### Client APIs (3) — App calls these

#### 1. Get Android Config

```
POST /api/object-ai/android
```

**Request Body (JSON):**
```json
{
    "key": "your_api_key",
    "version": "2.12.2"
}
```

**Success Response (200):**
```json
{
    "status": true,
    "message": "Success",
    "data": {
        "matched_version": "2.12.2",
        "requested_version": "2.12.2",
        "config": {
            // ... contents of the JSON file
        }
    }
}
```

**Error Response (404):**
```json
{
    "status": false,
    "message": "No JSON config found for android version 2.12.2"
}
```

---

#### 2. Get iOS Config

```
POST /api/object-ai/ios
```

**Request Body (JSON):**
```json
{
    "key": "your_api_key",
    "version": "2.12.2"
}
```

Same response format as Android.

---

#### 3. Get Debug Config

```
POST /api/object-ai/debug
```

**Request Body (JSON):**
```json
{
    "key": "your_api_key",
    "version": "2.12.2"
}
```

Same response format as Android.

---

### Admin APIs (4) — Manage versioned files

#### 4. List All Versions

```
POST /api/object-ai/versions/list
```

**Request Body (JSON):**
```json
{
    "key": "your_api_key"
}
```

**Filter by platform (optional):**
```json
{
    "key": "your_api_key",
    "platform": "android"
}
```

**Success Response (200):**
```json
{
    "status": true,
    "message": "Success",
    "data": [
        {
            "platform": "android",
            "version": "2.12.2",
            "file_size": 1024,
            "updated_at": "2026-02-28 10:30:00"
        },
        {
            "platform": "android",
            "version": "2.13.0",
            "file_size": 2048,
            "updated_at": "2026-02-28 11:00:00"
        },
        {
            "platform": "ios",
            "version": "2.12.2",
            "file_size": 1024,
            "updated_at": "2026-02-28 10:30:00"
        }
    ]
}
```

---

#### 5. Create Version

```
POST /api/object-ai/versions/create
```

**Request Body (form-data):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `key` | text | Yes | API key |
| `platform` | text | Yes | `android`, `ios`, or `debug` |
| `version` | text | Yes | Semantic version (e.g. `2.12.2`) |
| `file` | file | Yes | A `.json` file |

**Validations:**
- Version must match format `X.Y.Z` (digits only)
- File must be valid JSON
- Combination of platform + version must not already exist

**Success Response (200):**
```json
{
    "status": true,
    "message": "Version created successfully",
    "data": {
        "platform": "android",
        "version": "2.12.2"
    }
}
```

**Conflict Response (409):**
```json
{
    "status": false,
    "message": "Version 2.12.2 for android already exists. Use update instead."
}
```

---

#### 6. Update Version

```
POST /api/object-ai/versions/update
```

**Request Body (form-data):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `key` | text | Yes | API key |
| `platform` | text | Yes | `android`, `ios`, or `debug` |
| `version` | text | Yes | Version to update (e.g. `2.12.2`) |
| `file` | file | Yes | Replacement `.json` file |

**Validations:**
- Version must already exist (use Create first)
- File must be valid JSON

**Success Response (200):**
```json
{
    "status": true,
    "message": "Version updated successfully",
    "data": {
        "platform": "android",
        "version": "2.12.2"
    }
}
```

**Not Found Response (404):**
```json
{
    "status": false,
    "message": "Version 2.12.2 for android not found. Use create first."
}
```

---

#### 7. Delete Version

```
POST /api/object-ai/versions/delete
```

**Request Body (JSON):**
```json
{
    "key": "your_api_key",
    "platform": "android",
    "version": "2.12.2"
}
```

**Success Response (200):**
```json
{
    "status": true,
    "message": "Version 2.12.2 for android deleted successfully",
    "data": null
}
```

**Not Found Response (404):**
```json
{
    "status": false,
    "message": "Version 2.12.2 for android not found"
}
```

---

## GitHub Integration

On **Create** and **Update**, files are automatically pushed to your GitHub repository:
- Path: `storage/app/versioned/{platform}/{version}.json`
- Commit message: `Add {platform} v{version} config` or `Update {platform} v{version} config`
- Uses existing env variables: `GITHUB_REPO_OWNER`, `GITHUB_REPO_NAME`, `GITHUB_TOKEN`, `GITHUB_BRANCH`

If GitHub env variables are not configured, the upload is silently skipped (file is still saved locally).

---

## Postman Collection

Import `ObjectAi_Versioned_APIs.postman_collection.json` into Postman.

**After importing:**
1. Go to collection **Variables** tab
2. Set `base_url` → your server URL (default: `http://127.0.0.1:8000/api`)
3. Set `api_key` → your `MODEL_API_KEY` value

**Testing order:**
1. Create a version (e.g. android 2.12.2)
2. Create another version (e.g. android 2.13.0)
3. List versions — verify both appear
4. Get Android with version `2.12.2` — exact match
5. Get Android with version `2.14.0` — should fallback to `2.13.0`
6. Get Android with version `1.0.0` — should return 404
7. Update version `2.12.2` with a new file
8. Delete version `2.12.2`
9. List versions — verify only `2.13.0` remains

---

## Route Summary

```
POST /api/object-ai/android              → VersionedJsonController@getAndroidJson
POST /api/object-ai/ios                  → VersionedJsonController@getIosJson
POST /api/object-ai/debug               → VersionedJsonController@getDebugJson
POST /api/object-ai/versions/list        → VersionedJsonController@listVersions
POST /api/object-ai/versions/create      → VersionedJsonController@createVersion
POST /api/object-ai/versions/update      → VersionedJsonController@updateVersion
POST /api/object-ai/versions/delete      → VersionedJsonController@deleteVersion
```
