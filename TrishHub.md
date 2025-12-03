Title

TrishHub v1 – Personal Git + AI Platform

Status

Draft concept – ready for architecture + implementation planning.

⸻

1. Purpose & Vision

I want to build my own code hosting and AI assistant platform, instead of relying only on GitHub + third-party tools.

Conceptually:

“My own mini GitHub + AI engineer, running on infrastructure I control.”

Core goals
	•	Host my own Git repositories for projects like Out.
	•	Log in with a username/password and browse code in a web UI.
	•	Expose a clean API so an AI assistant (like Cody) can:
	•	Read the codebase holistically, not just one file at a time.
	•	Propose changes as diffs, not full rewrites.
	•	Create branches and (eventually) pull-request-like reviews.
	•	Keep human control: AI suggests, I review and approve.

I don’t want to re-implement Git itself. I do want to build:
	•	A web + API layer that uses real Git under the hood.
	•	A future AI layer that can act like “my engineer in a branch.”

⸻

2. Current Context
	•	I’m actively developing the Out app (HTML/CSS/JS) and hosting the code on GitHub right now.
	•	I have:
	•	A custom GPT called Cody that helps with front-end work using diff-only updates.
	•	Basic Git knowledge (commit, push, branches), but I’m still a novice programmer.
	•	Long-term, I want something more self-owned and extensible than just GitHub + one-off tools.

For now, GitHub is still the “source of truth” for Out, but TrishHub is the future home for my code + AI workflows.

⸻

3. High-Level Requirements (v1)

TrishHub v1 should support:
	1.	User Accounts
	•	At minimum: a single admin user (me).
	•	Login via email/username + password.
	•	Session management (cookies or tokens).
	2.	Repositories
	•	Backed by real Git repos on disk (bare repos).
	•	Ability to:
	•	Create a new repo.
	•	List existing repos.
	•	View files and folders.
	•	View recent commits.
	3.	Web UI
	•	Simple but usable:
	•	Login page
	•	Repo list
	•	Basic repo view:
	•	File tree
	•	Code viewer (read-only for now)
	•	Commit history list
	4.	API Layer
	•	REST API endpoints for:
	•	Authentication (login, maybe token generation).
	•	Listing repos.
	•	Fetching files.
	•	Listing commits.
	•	A starting point for future AI integration: e.g., /api/repos/:name/ai-task.
	5.	AI Integration (Design, not fully built yet)
	•	v1: define how the AI will:
	•	Fetch code via the TrishHub API.
	•	Propose changes as diffs.
	•	Push changes into branches.
	•	Implementation can be deferred to v1.1+, but the architecture should anticipate it.
	6.	Security & Ownership
	•	Code stored on a server I control (e.g. VPS).
	•	HTTPS planned (even if not implemented in very first prototype).
	•	Reasonable protections around auth (no plain-text passwords, etc.).

⸻

4. Non-Goals (for now)

To stay focused, v1 is not trying to:
	•	Rebuild everything GitHub offers (issues, wiki, release assets, etc.).
	•	Support multiple organizations, advanced permissions, or complex ACLs.
	•	Implement a full PR system with comments and reviews.
	•	Replace GitHub immediately for all my projects.

Instead, v1 is about:

A minimal, working “TrishHub” that can host repos, show code, and be a foundation for an AI assistant.

⸻

5. Primary User Stories

As Trish (admin user), I want to:
	1.	Log into TrishHub
	•	So I can see and manage my own code.
	2.	Create a repository
	•	For example: out-app.
	3.	Push existing code into TrishHub
	•	From my iPad or laptop using Git remotes.
	4.	Browse the repo in the browser
	•	See file tree, open index.html, see its contents.
	•	Tip: nice but optional for v1: simple syntax highlighting.
	5.	View commit history
	•	So I can sanity check what has changed.

Future user story (AI):
	6.	Ask the AI to perform a repo-wide task
	•	e.g. “Fix spacing inconsistencies in intro.css and relevant HTML, create a new branch, and show me the diff.”
	•	AI should:
	•	Get the relevant files via TrishHub API.
	•	Propose changes as diffs.
	•	Commit these changes on a dedicated branch.

⸻

6. Architecture Overview (Target v1)

This is the intended architecture Cody should design towards.

Components
	1.	Backend API (“TrishHub API”)
	•	Language: Node.js + TypeScript (preferred) or another modern backend stack.
	•	Responsibilities:
	•	Handle auth (login, sessions/tokens).
	•	Expose REST endpoints for repos, files, commits.
	•	Interact with Git on disk via git CLI (or a Git library).
	•	Read/write repo metadata in a database.
	2.	Frontend (“TrishHub Web”)
	•	Minimal single-page app or server-rendered HTML.
	•	Responsibilities:
	•	Login UI.
	•	List of repos.
	•	Repo detail view (file tree + file viewer + commit list).
	•	Should call TrishHub API, not talk directly to Git.
	3.	Git Storage
	•	Folder on disk, e.g. /srv/trishhub/repos.
	•	Each repo stored as a bare Git repo, e.g. /srv/trishhub/repos/out-app.git.
	•	Backend uses Git commands to read/write.
	•	No need to re-implement Git protocol.
	4.	Database
	•	Stores:
	•	Users (credentials, roles).
	•	Repo metadata.
	•	Later: AI tasks, logs, etc.
	•	Do this in SQLite for v0. 
	5.	AI Service (Future)
	•	For v1: We mostly define how it should work.
	•	Later: a standalone service or module that:
	•	Calls TrishHub API to fetch code.
	•	Calls OpenAI (or other models) to generate diffs.
	•	Pushes changes back via TrishHub API (branches/commits).

⸻

7. Git Handling (Implementation Detail Direction)

Cody should plan to:
	•	Use real Git under the hood, via:
	•	git init --bare to create repos.
	•	git log, git show, git ls-tree to read history/file trees.
	•	git commit, git update-ref, etc. to write history.
	•	Provide a thin abstraction layer so the rest of the code base doesn’t care that it’s talking to CLI vs a library.

Important constraints:
	•	Do not rewrite Git functionality; use existing Git tooling.
	•	Avoid dangerous operations that could corrupt repos without careful design (force pushes, reflog manipulation, etc.).
	•	Keep everything scoped to the TrishHub repos directory.

⸻

8. AI Integration – Design and Guardrails

This is the behavior I want from the AI when it’s integrated:
	1.	Diff-only mindset
	•	The AI should propose minimal changes in unified diff/patch format.
	•	No mass rewrites unless explicitly requested.
	2.	Explicit file scope
	•	AI should never silently modify files outside the requested scope.
	•	If it thinks other files need updates, it must:
	•	Explain why.
	•	Ask for confirmation.
	3.	Branch-based changes
	•	All AI changes should:
	•	Happen on a new branch (e.g. ai/intro-spacing-2025-12-02).
	•	Leave the main branch untouched until I approve.
	4.	Tokens / design system safety
	•	Shared design tokens (colors, spacing, typography, etc.) should be treated as protected.
	•	AI should not modify token files unless I explicitly allow it.
	5.	Explain-first behavior
	•	Before applying changes, AI should:
	•	Summarize what it intends to change.
	•	Describe the impact (especially on layout/UX).
	•	If ambiguous, AI should ask questions instead of guessing.
	6.	Human-in-the-loop
	•	I must always be the one to:
	•	Approve or reject branches.
	•	Merge changes into main.

For v1, TrishHub mainly needs to:
	•	Be AI-friendly in its API design.
	•	Make it easy to build this behavior into a separate AI service later.

⸻

9. API Sketch (v1)

This is a rough sketch, not a final contract, but Cody should use it to design endpoints and data models.

Auth
	•	POST /api/auth/login
	•	Input: { username, password }
	•	Output: session token or cookie.

Repos
	•	GET /api/repos
	•	Returns list of repos (name, description, last commit).
	•	POST /api/repos
	•	Create a new repo (for now: name only).
	•	GET /api/repos/:name
	•	Repo metadata.

Files & Tree
	•	GET /api/repos/:name/tree?ref=main&path=
	•	Returns list of files/folders at a given ref + path.
	•	GET /api/repos/:name/file?ref=main&path=styles/screens/intro.css
	•	Returns file content as text.

Commits
	•	GET /api/repos/:name/commits?ref=main
	•	Returns recent commits (hash, message, author, date).

AI Tasks (design only for v1)
	•	POST /api/repos/:name/ai-task
	•	Input: JSON object (branch name, files/paths, instruction).
	•	Behavior: For v1, just log or stub this out; we design it now, implement later.

Cody can refine the exact shapes as we get into implementation.

⸻

10. Security & Privacy Considerations
	•	Use HTTPS in front of TrishHub (reverse proxy or managed load balancer).
	•	Store passwords hashed (e.g. bcrypt/argon2), never in plain text.
	•	Use API tokens for AI services – never share my main login.
	•	Make sure any logs that include code are stored safely (or minimized).

Because this is a personal system, we’re balancing:
	•	Simplicity (not enterprise-grade IAM)
	•	with
	•	Not doing anything obviously unsafe.

⸻

11. Phased Implementation Plan

Cody should think in phases, not try to build everything at once.

Phase 0 – Architecture & repo layout
	•	Decide stack (Node + TS + Express, DB, frontend approach).
	•	Scaffold backend and frontend projects.
	•	Set up a local dev environment.

Phase 1 – Core Git hosting
	•	Implement user login.
	•	Implement basic repo management (create/list).
	•	Implement minimal Git wrapping to:
	•	list files (tree)
	•	read file contents
	•	list recent commits
	•	Build a basic web UI for:
	•	login
	•	repo list
	•	repo file browser + viewer.

Phase 2 – AI-ready API
	•	Add a first version of /api/repos/:name/ai-task endpoint.
	•	Implement internal models for “AI tasks” (even if we don’t call OpenAI yet).
	•	Make sure all code-fetching APIs are clean and composable for AI usage.

Phase 3 – AI Assistant Integration (later)
	•	Stand up a separate service or module that:
	•	Calls TrishHub APIs for code.
	•	Uses OpenAI APIs to generate diffs.
	•	Writes those diffs back into new branches.
	•	Add UI to:
	•	Show AI branches.
	•	View diffs in the browser.
	•	Merge or discard AI suggestions.

⸻

12. How Cody Should Use This Spec
	•	Treat this document as the source of truth for:
	•	The vision of TrishHub.
	•	Guardrails around Git and AI behavior.
	•	Phased priorities.
	•	When I ask for help designing or implementing TrishHub:
	•	Refer back to this spec.
	•	Call out when something I ask for conflicts with it or expands scope.
	•	Always:
	•	Prefer small, incremental steps.
	•	Provide clear explanations and diffs.
	•	Ask for clarification when my intent is ambiguous.
