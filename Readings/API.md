
# TrishHub API

This document describes the HTTP API for TrishHub, including routes used by the web UI and by AI clients.

Base URL examples:

- UI: `https://trishhub.example.com/`
- API: `https://trishhub.example.com/api`

All responses are JSON unless otherwise noted.

---

## 1. Auth & Sessions

### 1.1 Login (API)

**Endpoint**

- `POST /api/auth/login`

**Body**

```json
{
  "username": "trish@example.com",
  "password": "secret"
}
```

**Response (session-cookie mode)**

```json
{ "ok": true }
```

- Sets HTTP-only, Secure cookie for session ID.

**Response (token mode, optional)**

```json
{ "token": "JWT_OR_OPAQUE_TOKEN" }
```

Use whichever mode you choose to implement first; cookie sessions are simplest.

### 1.2 AI Token Auth

AI clients use a static token header:

- Header: `Authorization: Bearer <AI_TOKEN>`

The token is matched by hashing and looking up in `ai_tokens.token_hash`.

AI tokens are only valid for `/api/*` endpoints, not HTML UI routes.

---

## 2. Repositories API

### 2.1 List Repositories

**Endpoint**

- `GET /api/repos`

**Auth**

- Requires logged-in user or AI token.

**Response**

```json
[
  {
    "name": "out-app",
    "slug": "out-app",
    "description": "Out web app",
    "default_branch": "main",
    "last_commit": {
      "hash": "abc123",
      "message": "Initial commit",
      "author": "Trish",
      "date": "2025-12-02T10:00:00Z"
    }
  }
]
```

### 2.2 Create Repository

**Endpoint**

- `POST /api/repos`

**Auth**

- Admin-only.

**Body**

```json
{
  "name": "out-app",
  "description": "Out web app"
}
```

**Behavior**

1. Insert row into `repositories` table.
2. Create bare Git repo at configured path:
   - `git init --bare /srv/trishhub/repos/out-app.git`

**Response**

```json
{
  "name": "out-app",
  "slug": "out-app",
  "description": "Out web app",
  "default_branch": "main"
}
```

### 2.3 Get Repository Metadata

**Endpoint**

- `GET /api/repos/:name`

`name` corresponds to `repositories.slug`.

**Response**

```json
{
  "name": "out-app",
  "slug": "out-app",
  "description": "Out web app",
  "default_branch": "main"
}
```

---

## 3. File & Tree API

### 3.1 List Tree

**Endpoint**

- `GET /api/repos/:name/tree`

**Query Parameters**

- `ref` (optional, default: repo default branch)
- `path` (optional, default: root of repo)

**Example**

- `/api/repos/out-app/tree?ref=main&path=src/`

**Response**

```json
{
  "ref": "main",
  "path": "src/",
  "entries": [
    { "type": "dir",  "name": "components", "path": "src/components" },
    { "type": "file", "name": "index.html", "path": "src/index.html", "size": 2048 }
  ]
}
```

Implementation uses:

- `git ls-tree {ref} {path}`

### 3.2 Get File Content

**Endpoint**

- `GET /api/repos/:name/file`

**Query Parameters**

- `ref` (required) – branch name or commit hash.
- `path` (required) – path inside repo.

**Example**

- `/api/repos/out-app/file?ref=main&path=styles/screens/intro.css`

**Response**

```json
{
  "ref": "main",
  "path": "styles/screens/intro.css",
  "encoding": "utf-8",
  "content": "body { ... }"
}
```

If the file is binary or too large:

- Return an error or a truncated version with a flag, e.g.:

```json
{
  "ref": "main",
  "path": "images/logo.png",
  "binary": true,
  "size": 12345
}
```

Implementation uses:

- `git show {ref}:{path}`

---

## 4. Commits API

### 4.1 List Commits

**Endpoint**

- `GET /api/repos/:name/commits`

**Query Parameters**

- `ref` (optional, default: repo default branch)
- `limit` (optional, default: 50)

**Example**

- `/api/repos/out-app/commits?ref=main&limit=50`

**Response**

```json
[
  {
    "hash": "abc123",
    "message": "Fix spacing on intro",
    "author_name": "Trish",
    "author_email": "trish@example.com",
    "date": "2025-12-02T10:00:00Z"
  }
]
```

Implementation uses:

- `git log --max-count=<limit> --format=... <ref>`

### 4.2 (Future) Get Commit Detail

You can optionally add:

- `GET /api/repos/:name/commits/:hash`

Response could include:

- Commit metadata.
- List of changed files.
- Optionally, per-file diffs.

Implementation uses:

- `git show --name-status <hash>`

---

## 5. AI Task API

The AI task endpoint is designed now and can be fully implemented later.

### 5.1 Create AI Task

**Endpoint**

- `POST /api/repos/:name/ai-task`

**Auth**

- Admin or AI token.

**Body (v1 design)**

```json
{
  "branch": "ai/intro-spacing-2025-12-02",
  "paths": [
    "styles/screens/intro.css",
    "index.html"
  ],
  "instruction": "Fix spacing inconsistencies related to intro layout."
}
```

**Behavior (v1)**

1. Validate that `:name` corresponds to an existing repo.
2. Insert a new row into `ai_tasks`:
   - `branch_name` = `branch`
   - `paths` = JSON-encoded array
   - `instruction` = text
   - `status` = `"logged_only"`
3. Return the created task.

**Response**

```json
{
  "id": 1,
  "status": "logged_only"
}
```

**Behavior (future)**

- An AI service polls for `ai_tasks` with `status = "pending"`.
- Uses other endpoints (`/tree`, `/file`, `/commits`) to gather context.
- Produces diffs and pushes to a dedicated AI branch.
- Updates `ai_tasks.status` to `"completed"` or `"failed"` and logs details.

---

## 6. Future Write API (Design Only)

These endpoints are **not required for v1**, but are sketched for later AI integration.

### 6.1 Create Branch (AI Branches)

**Endpoint**

- `POST /api/repos/:name/branches`

**Body**

```json
{
  "branch": "ai/intro-spacing-2025-12-02",
  "from_ref": "main"
}
```

**Behavior**

- Creates a new branch `branch` from `from_ref` using Git:
  - `git branch <branch> <from_ref>`

### 6.2 Commit Changes on AI Branch

**Endpoint**

- `POST /api/repos/:name/commits`

**Body**

```json
{
  "branch": "ai/intro-spacing-2025-12-02",
  "base_commit": "abc123",
  "message": "AI: Fix intro spacing (task #12)",
  "diff": "diff --git a/... b/...\n..."
}
```

Or a structured format:

```json
{
  "branch": "ai/intro-spacing-2025-12-02",
  "base_commit": "abc123",
  "message": "AI: Fix intro spacing (task #12)",
  "changes": [
    {
      "path": "styles/screens/intro.css",
      "action": "modify",
      "content": "body { ... }"
    }
  ]
}
```

**Behavior**

1. Validate that `branch` is an AI branch (e.g. starts with `ai/`).
2. Ensure `base_commit` matches the current HEAD of `branch` to prevent overwriting newer work.
3. Check out a temp working tree.
4. Apply changes (either from diff or structured changes).
5. Run `git add`/`git rm`.
6. Commit with:
   - Author: `Trish AI <ai@trishhub.local>`.
   - Message from request.
7. Update branch in the repo.
8. Log the operation.

### 6.3 Show Diff (AI vs Main)

**Endpoint**

- `GET /api/repos/:name/diff`

**Query Parameters**

- `from` – source ref (e.g. `ai/intro-spacing-2025-12-02`)
- `to` – target ref (e.g. `main`)

**Response**

```json
{
  "from": "ai/intro-spacing-2025-12-02",
  "to": "main",
  "patch": "diff --git a/... b/...\n..."
}
```

Implementation uses:

- `git diff <to>...<from>`

---

## 7. Error Handling & Conventions

### 7.1 Error Format

Recommended error shape:

```json
{
  "error": {
    "code": "NOT_FOUND",
    "message": "Repository not found"
  }
}
```

Common codes:

- `UNAUTHORIZED`
- `FORBIDDEN`
- `NOT_FOUND`
- `VALIDATION_ERROR`
- `INTERNAL_ERROR`

### 7.2 Status Codes

- `200` – Successful GET/POST.
- `201` – Resource created (`POST /api/repos`, future write endpoints).
- `400` – Invalid request (missing/invalid parameters).
- `401` – Not authenticated.
- `403` – Authenticated but not allowed.
- `404` – Repo/file/commit not found.
- `409` – Conflict (e.g. AI write with stale `base_commit`).
- `500` – Unexpected error.

---

## 8. Security Notes (API Layer)

- Always check auth before accessing repo info.
- For AI tokens:
  - Restrict to API routes.
  - Rate-limit calls to `/file` and write endpoints.
- Validate all `name`, `ref`, and `path` parameters:
  - `name` must match a known `repositories.slug`.
  - `ref` should be a safe string (branch or commit hash).
  - `path` should not contain `..` or be absolute.

This API surface provides everything an AI agent (or a minimal frontend app) needs to:

- Discover repositories.
- Inspect branches and history.
- Read file trees and contents.
- Submit scoped AI tasks.
- Eventually propose and apply safe code changes on dedicated AI branches.
