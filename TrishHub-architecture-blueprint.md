
# TrishHub System Architecture Blueprint

## 1. Overview & Tech Stack

**Goal:**  
A self-hosted mini GitHub + AI-friendly API so Trish can host code, browse it in a web UI, and later plug in an AI “engineer” that works in branches and diffs.

**Stack:**

- **Backend:** PHP 8.x
- **Database:** MySQL
- **Frontend:** Server-rendered HTML + progressive enhancement with vanilla JS
- **Git Storage:** Bare Git repos on disk (via `git` CLI)
- **Deployment target:** Single VPS under your control

Non-goals (initially): no multi-org, no complex ACLs, no full PR system, no issue tracker.

---

## 2. User Roles

For v1, there will effectively be a single admin user (you). The architecture supports more users later.

### 2.1 System-Level Roles

- **Admin**
  - Full access to UI and API.
  - Can create repositories.
  - Can manage future users and AI tokens.

- **User (future)**
  - Basic repo access (own repos + any explicitly shared).
  - No system settings.

### 2.2 Repo-Level Roles (Future-Friendly, Minimal)

- **Owner**
  - User who created the repo.
  - Full control over repo metadata.

- **Collaborator (future)**
  - Read + write access to repo over HTTP/SSH/Git (if added).

For v1, assume the admin owns all repos.

### 2.3 AI Role

- **AI Client**
  - Not a human user; represented by an API token.
  - Initially can:
    - `GET /api/repos`
    - `GET /api/repos/:name/tree`
    - `GET /api/repos/:name/file`
    - `GET /api/repos/:name/commits`
    - `POST /api/repos/:name/ai-task` (stub in v1)
  - Future: branch-creation and commit-write endpoints on a dedicated AI branch.

---

## 3. Feature List

### 3.1 Core v1 Features

1. **User Accounts**
   - Single admin user.
   - Login via username/email + password.
   - Persistent session via cookie.

2. **Repositories**
   - Bare Git repos on disk (`git init --bare`).
   - Create repo (name, optional description).
   - List repos.
   - Git remote support (push from laptop/iPad).

3. **Web UI**
   - Login page.
   - Repo list.
   - Repo detail view:
     - File tree at a `ref` (branch/commit).
     - File viewer (read-only).
     - Commit history list.

4. **API Layer**
   - REST endpoints:
     - Auth: login (token or cookie).
     - Repos: list, create, get metadata.
     - Files: list tree, fetch file contents.
     - Commits: list.

5. **AI Integration (Design for Future)**
   - Define `/api/repos/:name/ai-task` for future AI workflows.
   - For v1, store and log requests without executing changes.

6. **Security & Ownership**
   - Password hashing.
   - HTTPS via reverse proxy.
   - No plaintext credentials or tokens.

---

## 4. Data Models (MySQL)

Tables are designed to:

- Support v1 features now.
- Anticipate AI tasks, logs, and multiple repos later, without heavy ACL complexity.

### 4.1 `users`

System and (future) repo users.

| Field           | Type                | Notes                    |
|----------------|---------------------|--------------------------|
| `id`           | BIGINT PK AUTO_INC  | User ID                  |
| `email`        | VARCHAR(255) UNIQUE | Login/email              |
| `username`     | VARCHAR(64) UNIQUE  | Optional, nice-to-have   |
| `password_hash`| VARCHAR(255)        | bcrypt/argon2id          |
| `display_name` | VARCHAR(255)        | “Trish”                  |
| `role`         | ENUM('admin','user')| System role              |
| `is_active`    | TINYINT(1)          | Soft disable             |
| `created_at`   | DATETIME            |                          |
| `updated_at`   | DATETIME            |                          |

### 4.2 `sessions` (if not using built-in PHP sessions)

| Field        | Type              | Notes                |
|--------------|-------------------|----------------------|
| `id`         | CHAR(64) PK       | Random session token |
| `user_id`    | BIGINT FK         | `users.id`           |
| `ip_address` | VARCHAR(45)       | IPv4/IPv6            |
| `user_agent` | VARCHAR(255)      |                      |
| `created_at` | DATETIME          |                      |
| `expires_at` | DATETIME          |                      |

### 4.3 `repositories`

Metadata for Git repos on disk.

| Field            | Type                | Notes                                      |
|------------------|---------------------|--------------------------------------------|
| `id`             | BIGINT PK AUTO_INC  | Repo ID                                    |
| `owner_user_id`  | BIGINT FK           | `users.id` (Trish for v1)                  |
| `name`           | VARCHAR(255)        | Display name e.g. “Out app”                |
| `slug`           | VARCHAR(255) UNIQUE | URL name, repo name in Git (`out-app`)     |
| `description`    | TEXT NULL           | Optional                                   |
| `default_branch` | VARCHAR(255)        | `main` by default                          |
| `git_path`       | VARCHAR(512)        | e.g. `/srv/trishhub/repos/out-app.git`     |
| `created_at`     | DATETIME            |                                            |
| `updated_at`     | DATETIME            |                                            |

### 4.4 `repo_access` (future)

For when you want more users than just you.

| Field           | Type             | Notes                                          |
|-----------------|------------------|------------------------------------------------|
| `id`            | BIGINT PK        |                                                |
| `repository_id` | BIGINT FK        |                                                |
| `user_id`       | BIGINT FK        |                                                |
| `role`          | ENUM('owner','collaborator','read_only') | Repo-level role |

In v1, you can skip this and assume the admin has full access.

### 4.5 `ai_tokens`

For AI client authentication.

| Field           | Type              | Notes                        |
|-----------------|-------------------|------------------------------|
| `id`            | BIGINT PK         |                              |
| `name`          | VARCHAR(255)      | “Cody-TrishHub”              |
| `token_hash`    | CHAR(64)          | Hash of the API token        |
| `is_active`     | TINYINT(1)        |                              |
| `created_at`    | DATETIME          |                              |
| `last_used_at`  | DATETIME NULL     |                              |

### 4.6 `ai_tasks`

Supports `/api/repos/:name/ai-task` workflow.

| Field           | Type              | Notes                                        |
|-----------------|-------------------|----------------------------------------------|
| `id`            | BIGINT PK         |                                              |
| `repository_id` | BIGINT FK         |                                              |
| `created_by`    | BIGINT FK NULL    | If initiated by Trish via UI/API             |
| `token_id`      | BIGINT FK NULL    | If initiated by AI token                     |
| `branch_name`   | VARCHAR(255)      | Desired AI branch name                       |
| `paths`         | TEXT              | JSON array of paths to consider              |
| `instruction`   | TEXT              | What AI should do                            |
| `status`        | ENUM('pending','in_progress','completed','failed','logged_only') | v1: `logged_only` |
| `result_summary`| TEXT NULL         | Description or error                         |
| `created_at`    | DATETIME          |                                              |
| `updated_at`    | DATETIME          |                                              |

### 4.7 `ai_task_logs` (optional, later)

Detailed logs for AI runs (diffs, patch text, etc.) once AI writes are implemented.

---

## 5. File & Git Storage Structure

### 5.1 Directory Layout

On the server:

- **App code:** `/var/www/trishhub/`
- **Git repos root:** `/srv/trishhub/repos/`
  - One **bare** repo per TrishHub repo:
    - `/srv/trishhub/repos/{slug}.git` (e.g. `/srv/trishhub/repos/out-app.git`)
- **Logs:** `/var/log/trishhub/` (app and AI task logs)

The `repositories.git_path` field stores the full path to each `.git` directory.

### 5.2 Git Operations

Use real Git via CLI, not reimplementation.

- **Create repo:**
  - `git init --bare /srv/trishhub/repos/{slug}.git`

- **List branches/commits:**
  - `git for-each-ref` / `git branch --list`
  - `git log --max-count=50 --format=...`

- **File tree at ref:**
  - `git ls-tree {ref} {path}` (path optional)

- **File content:**
  - `git show {ref}:{path}`

---

## 6. Backend Routes / Endpoints (PHP)

Two layers:

- **UI routes** (HTML pages).
- **API routes** (JSON for both UI and AI).

### 6.1 UI Routes (HTML)

- `GET /login`
  - Show login form.

- `POST /login`
  - Validate credentials, create session.

- `POST /logout`
  - Destroy session.

- `GET /`
  - If logged in: redirect to `/repos`.
  - If not: redirect to `/login`.

- `GET /repos`
  - List all repos from `repositories` table.

- `GET /repos/new`
  - Form to create a repo.

- `POST /repos`
  - Create repo row in DB.
  - Run `git init --bare` at the expected path.

- `GET /repos/{name}`
  - Repo overview:
    - Default branch.
    - Recent commits in default branch.
    - Links to tree and history.

- `GET /repos/{name}/tree`
  - Query: `ref` (defaults to default branch), `path` (optional).
  - Render file/folder list using `git ls-tree`.

- `GET /repos/{name}/file`
  - Query: `ref`, `path`.
  - Render code viewer (read-only).

- `GET /repos/{name}/commits`
  - Query: `ref` (branch).
  - List recent commits via `git log`.

### 6.2 API Routes (JSON)

Base prefix: `/api`.

#### Auth

- `POST /api/auth/login`
  - Body: `{ "username": "...", "password": "..." }`
  - Response:
    - Either sets HTTP-only cookie and returns `{ "ok": true }`, or
    - Returns `{ "token": "..." }` for token-based API usage.

AI tokens will use a separate static token header (see below).

#### Repos

- `GET /api/repos`
  - Returns list of repos:
  ```json
  [
    {
      "name": "out-app",
      "slug": "out-app",
      "description": "...",
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

- `POST /api/repos`
  - Body: `{ "name": "out-app", "description": "Out web app" }`
  - Creates DB row + bare Git repo.

- `GET /api/repos/:name`
  - Return repo metadata:
  ```json
  {
    "name": "out-app",
    "slug": "out-app",
    "description": "Out web app",
    "default_branch": "main"
  }
  ```

#### Files & Tree

- `GET /api/repos/:name/tree`
  - Query: `ref=main&path=src/`
  - Output:
  ```json
  {
    "ref": "main",
    "path": "src/",
    "entries": [
      { "type": "dir", "name": "components", "path": "src/components" },
      { "type": "file", "name": "index.html", "path": "src/index.html", "size": 2048 }
    ]
  }
  ```

- `GET /api/repos/:name/file`
  - Query: `ref=main&path=styles/screens/intro.css`
  - Output:
  ```json
  {
    "ref": "main",
    "path": "styles/screens/intro.css",
    "encoding": "utf-8",
    "content": "body { ... }"
  }
  ```

#### Commits

- `GET /api/repos/:name/commits`
  - Query: `ref=main&limit=50`
  - Output:
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

#### AI Task (Design-First Endpoint)

- `POST /api/repos/:name/ai-task`
  - Input (v1 design):
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

  - v1 Behavior:
    - Validate `name` points to an existing repo.
    - Insert `ai_tasks` row with `status = "logged_only"`.
    - Return created task:
    ```json
    {
      "id": 1,
      "status": "logged_only"
    }
    ```

  - Future Behavior:
    - AI service picks up pending tasks.
    - Fetches files via other API endpoints.
    - Proposes changes as diffs on a new branch.
    - Writes results & diffs back to `ai_tasks` / `ai_task_logs`.

---

## 7. API Endpoints for AI Access

When you hook up the AI assistant, it will use:

1. **Code reading:**
   - `GET /api/repos`
   - `GET /api/repos/:name`
   - `GET /api/repos/:name/tree`
   - `GET /api/repos/:name/file`
   - `GET /api/repos/:name/commits`

2. **Task submission (design for future):**
   - `POST /api/repos/:name/ai-task`

3. **Future write endpoints (not v1, but design direction):**
   - `POST /api/repos/:name/branches` (create an AI branch).
   - `POST /api/repos/:name/commits` (apply diff to AI branch).
   - `GET /api/repos/:name/diff?from=ai/branch&to=main` (show diff for review).

Future write endpoints will enforce AI guardrails:

- AI changes only on dedicated branches (e.g. `ai/...`).
- Diff-only approach, never mass rewrites without explicit instruction.
- No touching sensitive files unless explicitly allowed.

---

## 8. Security Considerations

### 8.1 Authentication & Passwords

- Use HTTPS via nginx + Let’s Encrypt.
- Hash passwords with bcrypt/argon2id.
- HTTP-only, Secure cookies for session IDs.
- Reasonable session timeout and idle expiry.
- Avoid logging sensitive data (passwords, tokens, full code contents).

### 8.2 Authorization

- v1: single admin; simplest rule is “if logged in, full access”.
- As you add users:
  - Check that `user.role = "admin"` for repo creation and AI token management.
  - Check `repo_access` for non-admin operations (if you enable that table).

### 8.3 AI Token Auth

- API header: `Authorization: Bearer <AI_TOKEN>`.
- Match token by hashing the provided token and looking up in `ai_tokens`.
- Restrict AI tokens to the `/api` namespace, not HTML UI.
- Rate-limit AI endpoints (especially `/file` and future write endpoints).

### 8.4 Git & Filesystem Safety

- All `git` commands:
  - Use whitelisted arguments.
  - Sanitize repo names and paths (only allow known slug + safe characters).
  - No user-controlled shell fragments.
- Never expose `/srv/trishhub/repos/` directly via the web server.
- Validate `path` parameters to prevent directory traversal (`..`, `~`, etc.).

### 8.5 Web Security

- CSRF protection for HTML forms (login, repo creation).
- Escape all user inputs in templates to avoid XSS.
- Prepared statements for all DB access.
- Consider a basic Content Security Policy (CSP).

---

## 9. Branches & Commits Storage

### 9.1 Git as the Source of Truth

- Branches: `refs/heads/*` inside each bare repo.
- Commits: Git objects inside the repo’s `objects/` directory.

The database is **metadata only**; commits and file contents live in Git.

### 9.2 Branching Strategy for AI (Future)

- Human work:
  - `main` (or `master`) as the primary branch.

- AI work:
  - Always on branches like `ai/intro-spacing-2025-12-02`.
  - Controlled by `ai-task` spec:
    - Branch name passed in request.
    - AI uses that branch for commits.

Main branch isn’t touched by AI; merges happen manually after reviewing diffs.

### 9.3 Commit Authoring (Future AI Writes)

- Human author:
  - Actual user name/email.

- AI author:
  - Dedicated identity, e.g. `Trish AI <ai@trishhub.local>`.

- Commits created by AI include:
  - Task ID in commit message, e.g. `AI: Fix intro spacing (task #12)`.

---

## 10. MVP Milestone Breakdown

Mapping phases to MVP slices for TrishHub.

### MVP0 – Setup & Skeleton

**Goal:** Have the basic project scaffolding ready.

- Decide stack (PHP + MySQL + Git CLI).
- Create repo structure:
  - `/src` for PHP.
  - `/public` for web root.
  - `/config` for environment settings.
- Configure DB connection and migrations.
- Implement basic router in PHP (or use a micro-framework).

### MVP1 – Core Git Hosting

**Goal:** “TrishHub as a private Git viewer” for your own repos.

- User login (admin).
- Repo management:
  - Create repo.
  - List repos.
- Git abstraction service in PHP:
  - `listRefs(ref)`, `listTree(ref, path)`, `getFile(ref, path)`, `listCommits(ref)`.
- Web UI:
  - Login page.
  - Repo list page.
  - Repo details:
    - File tree.
    - File viewer (read-only).
    - Commit list.

No AI integration yet beyond planning.

### MVP2 – AI-Ready API

**Goal:** Make the API clean and ready for AI clients.

- Implement full JSON API:
  - `POST /api/auth/login`
  - `GET /api/repos`, `POST /api/repos`, `GET /api/repos/:name`
  - `GET /api/repos/:name/tree`
  - `GET /api/repos/:name/file`
  - `GET /api/repos/:name/commits`
- Add `ai_tokens` table + minimal token management (CLI or simple admin page).
- Implement `POST /api/repos/:name/ai-task`:
  - Store tasks in `ai_tasks` with `status = "logged_only"`.
- Ensure endpoints are stable and documented so your AI agent can rely on them.

### MVP3 – AI Assistant Integration

**Goal:** AI can propose real code changes via branches, with you in control.

- Build a small, separate AI service or workflow that:
  - Uses `/api/repos/:name/...` endpoints to read code.
  - Uses an LLM to generate unified diffs.
- Extend backend API (for write phase):
  - `POST /api/repos/:name/branches` (create AI branch).
  - `POST /api/repos/:name/commits` (apply diff to AI branch).
  - `GET /api/repos/:name/diff` (main vs AI branch).
- Web UI:
  - Show AI branches.
  - Show diffs.
  - Provide “Merge AI changes” and “Discard AI branch” actions.

Throughout all phases, keep AI guardrails in place:

- Branch-based, diff-only.
- Explicit scope of work.
- Explain-first, human-in-the-loop for merges.

---
