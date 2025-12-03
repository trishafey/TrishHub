
# TrishHub Architecture

This document describes the system-level architecture of TrishHub: a self-hosted, GitHub-like code host with an AI-friendly API.

---

## 1. Overview & Tech Stack

**Goal:**  
Host private Git repositories for Trish, with a web UI for browsing and an API that an AI assistant can use to read and (later) propose code changes.

**Stack:**

- **Backend:** PHP 8.x
- **Database:** MySQL
- **Frontend:** Server-rendered HTML + progressive enhancement with vanilla JS
- **Git Storage:** Bare Git repos on disk (via `git` CLI)
- **Deployment:** Single VPS (nginx or Apache + PHP-FPM)

**Non-goals (initially):**

- Multiple organizations
- Complex ACLs and teams
- Full PR system
- Issue tracker

---

## 2. User & Client Roles

### 2.1 System-Level Roles

- **Admin**
  - Full access to UI and API.
  - Can create repositories.
  - Can manage users and AI tokens.
- **User (future)**
  - Basic repo access (own repos + any explicitly shared).
  - No system settings.

For v1, there is effectively a single **Admin** user (Trish).

### 2.2 Repo-Level Roles (Future-Friendly)

- **Owner**
  - User who created the repo.
  - Full control over repo metadata.
- **Collaborator (future)**
  - Read + write access to repo over HTTP/SSH/Git (if added).

For v1, assume the Admin owns all repos.

### 2.3 AI Client Role

- **AI Client**
  - Not a human user; represented by an API token.
  - Initially supports:
    - `GET /api/repos`
    - `GET /api/repos/:name/tree`
    - `GET /api/repos/:name/file`
    - `GET /api/repos/:name/commits`
    - `POST /api/repos/:name/ai-task` (logged only)
  - Future: branch-creation and commit-write endpoints on dedicated AI branches.

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
   - Git remote support so Trish can push from laptop/iPad.

3. **Web UI**
   - Login page.
   - Repo list.
   - Repo detail:
     - File tree at a ref (branch/commit).
     - File viewer (read-only).
     - Commit history list.

4. **API Layer (v1)**
   - REST endpoints for:
     - Auth (login).
     - Repos (list, create, metadata).
     - Files (tree, file contents).
     - Commits (list).
   - Separate doc: see `API.md`.

5. **AI Integration (Design for Future)**
   - Define `/api/repos/:name/ai-task` for AI workflows.
   - For v1, store/log requests only (no automated writes).

6. **Security & Ownership**
   - Password hashing.
   - HTTPS via reverse proxy.
   - No plaintext credentials or tokens.

---

## 4. Data Model (MySQL)

Tables are designed for v1 and future AI features, without complex ACLs.

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

### 4.2 `sessions` (optional, if not using stock PHP sessions)

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

For multiple users and per-repo roles.

| Field           | Type             | Notes                                          |
|-----------------|------------------|------------------------------------------------|
| `id`            | BIGINT PK        |                                                |
| `repository_id` | BIGINT FK        |                                                |
| `user_id`       | BIGINT FK        |                                                |
| `role`          | ENUM('owner','collaborator','read_only') | Repo-level role |

In v1, this can be omitted; the Admin has full access.

### 4.5 `ai_tokens`

For AI client authentication.

| Field           | Type              | Notes                        |
|-----------------|-------------------|------------------------------|
| `id`            | BIGINT PK         |                              |
| `name`          | VARCHAR(255)      | “Cody-TrishHub”              |
| `token_hash`    | CHAR(64)          | Hash of API token            |
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

## 5. File & Git Storage

### 5.1 Directory Layout

On the server:

- App code: `/var/www/trishhub/`
- Git repos root: `/srv/trishhub/repos/`
  - One bare repo per TrishHub repo:
    - `/srv/trishhub/repos/{slug}.git`
- Logs: `/var/log/trishhub/` (app + AI logs)

`repositories.git_path` stores the full path to each `.git` directory.

### 5.2 Git as Source of Truth

Use real Git via CLI:

- **Create repo**
  - `git init --bare /srv/trishhub/repos/{slug}.git`
- **List branches/commits**
  - `git for-each-ref`, `git branch --list`, `git log`
- **File tree**
  - `git ls-tree {ref} {path}`
- **File content**
  - `git show {ref}:{path}`

Commits and file contents live in Git, not in the database.

---

## 6. Backend Architecture (High-Level)

### 6.1 Layers

- **Routing layer**
  - Map HTTP paths to controllers (simple router or micro-framework).
- **Controllers**
  - Orchestrate requests, sessions, DB calls, Git operations.
- **Services**
  - `GitService` to wrap all `git` CLI calls.
  - `AuthService` for login/session/token handling.
- **Persistence**
  - PDO-based MySQL access (prepared statements only).
- **Views**
  - PHP templates rendering HTML (login, repo list, tree/file views).

### 6.2 Core UI Flows

- **Login**
  1. `GET /login` → show form.
  2. `POST /login` → validate credentials, create session cookie, redirect `/repos`.

- **Repo Creation**
  1. `GET /repos/new` → show form.
  2. `POST /repos`:
     - Insert repo row into DB.
     - Run `git init --bare` at configured path.
     - Redirect to `/repos/{name}`.

- **Repo Browsing**
  - `GET /repos` → list repos from DB.
  - `GET /repos/{name}` → overview (default branch, recent commits).
  - `GET /repos/{name}/tree?ref=...&path=...` → show file tree.
  - `GET /repos/{name}/file?ref=...&path=...` → show file content.

---

## 7. Security Considerations

### 7.1 Authentication & Passwords

- Enforce HTTPS (nginx + Let’s Encrypt).
- Hash passwords (bcrypt or argon2id).
- Use HTTP-only, Secure cookies for session IDs.
- Set sensible session expiry and idle timeouts.
- Don’t log sensitive data (passwords, tokens, raw code unnecessarily).

### 7.2 Authorization

- v1: single admin → any authenticated user is effectively Admin.
- Future:
  - `user.role = 'admin'` required for sensitive operations:
    - Repo creation/deletion.
    - AI token management.
  - `repo_access` governs non-admin repo access.

### 7.3 AI Token Auth

- Header: `Authorization: Bearer <AI_TOKEN>`.
- Tokens looked up via `ai_tokens.token_hash`.
- AI tokens scoped to `/api/*` routes.
- Rate-limit AI endpoints (especially `/file`).

### 7.4 Git & Filesystem Safety

- Never pass unsanitized user input to shell.
- Sanitize repo `slug` and `path`:
  - Allow only known repo slugs from DB.
  - Validate `path` to avoid `../`, `~`, and other traversal tricks.
- Do not expose `/srv/trishhub/repos/` via web server.

### 7.5 Web Security

- CSRF protection for HTML forms (hidden token).
- Output escaping in templates to avoid XSS.
- Use prepared statements for all DB queries.
- Consider a basic Content Security Policy (CSP).

---

## 8. Branches & Commits (Model & Strategy)

### 8.1 Storage

- Git handles:
  - Branches in `refs/heads/*`
  - Commits and blobs in `objects/`
- Database is metadata only.

### 8.2 Human vs AI Branches

- **Human work**
  - Primary branch: `main` (or `master`).
- **AI work (future)**
  - Branches like: `ai/intro-spacing-2025-12-02`.
  - Created when an AI task is executed.
  - AI commits only on AI branches.

Main branch is never modified directly by AI; merges are manual.

### 8.3 Commit Authoring (Future AI Writes)

- Human author:
  - Real user name/email.
- AI author:
  - Dedicated identity, e.g. `Trish AI <ai@trishhub.local>`.
- AI commit messages:
  - Include task ID, e.g. `AI: Fix intro spacing (task #12)`.

---

## 9. MVP Milestones

### MVP0 – Setup & Skeleton

**Goal:** Basic project skeleton.

- Add PHP project structure:
  - `/src`
    - `/Controllers`
    - `/Services`
    - `/Models`
    - `/Views`
  - `/config` (env settings)
  - `/public` (web root)
  - `/database/migrations` (database schema and seeders)
- Configure MySQL and migrations.
- Implement a simple router.

### MVP1 – Core Git Hosting

**Goal:** TrishHub as a private Git viewer.

- Admin login.
- Repo management:
  - Create repo.
  - List repos.
- Implement `GitService`:
  - `listRefs(ref)`
  - `listTree(ref, path)`
  - `getFile(ref, path)`
  - `listCommits(ref)`
- Web UI:
  - Login screen.
  - Repo list.
  - Repo details (tree, file viewer, commit list).

### MVP2 – AI-Ready API

**Goal:** Stable API for AI clients.

- Implement JSON API:
  - `POST /api/auth/login`
  - `GET /api/repos`, `POST /api/repos`, `GET /api/repos/:name`
  - `GET /api/repos/:name/tree`
  - `GET /api/repos/:name/file`
  - `GET /api/repos/:name/commits`
- Add `ai_tokens` + minimal management.
- Implement `POST /api/repos/:name/ai-task`:
  - Store tasks in `ai_tasks` with `status = "logged_only"`.

### MVP3 – AI Assistant Integration (Beyond v1)

**Goal:** AI can propose code changes via branches.

- Separate AI service or workflow:
  - Reads code via API.
  - Produces unified diffs.
- Extend backend API for writes:
  - `POST /api/repos/:name/branches` (create AI branch).
  - `POST /api/repos/:name/commits` (apply diff to AI branch).
  - `GET /api/repos/:name/diff` (main vs AI branch).
- UI:
  - Show AI branches.
  - Show diffs.
  - Buttons for “Merge AI changes” and “Discard AI branch”.

AI guardrails throughout:

- Branch-based, diff-only.
- Explicit scope.
- Human review before merge.
