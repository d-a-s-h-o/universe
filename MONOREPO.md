# MONOREPO USAGE GUIDE

Welcome to the **d-a-s-h-o/universe** monorepo! This repo contains multiple projects under a single repository, allowing for shared tooling, streamlined CI/CD, and easier cross-project updates.

## 🤔 Why a Monorepo?

By consolidating all projects into a single monorepo, we ensure:
- **Easier asset sharing**: Logos, icons, and shared libraries live in one place and can be updated across all projects effortlessly.
- **Unified tooling**: All projects benefit from the same linting, formatting, and CI/CD workflows.
- **Simplified dependency management**: Shared dependencies are updated in one go, preventing fragmentation.
- **Cross-project development efficiency**: If multiple projects depend on each other, changes can be made in one commit rather than across multiple repos.
- **Consistent versioning**: Semantic versioning across projects ensures better tracking of updates.

## 📝 Commit Message Format
To ensure clarity and consistency across all commits, use the following format:

```
[project] <emoji> type(scope): subject

body (optional)

footer (optional)
```

### **Example Commits:**
```
[chat] 🚀 feat(auth): add JWT authentication

Implemented JWT authentication for user login, replacing session-based auth.

BREAKING CHANGE: Existing session-based logins will no longer work.
```
```
[droplets] 🐛 fix(api): resolve memory leak in data fetching

Updated request handling to properly close connections and prevent excessive memory usage.

Closes #42
```
```
[docs] 📖 docs(readme): update installation guide

Clarified setup instructions for new contributors.
```

### **Commit Components Explained:**
- **`[project]`** → Specifies the affected project (e.g., `chat`, `droplets`, `docs`).
- **`<emoji>`** → Provides a quick visual cue (e.g., 🚀 for features, 🐛 for fixes, 📖 for documentation updates).
- **`type(scope): subject`** → Describes the nature of the change.
- **Body (optional)** → Adds more details if needed.
- **Footer (optional)** → Notes breaking changes, issue references, etc.

## 🏷️ Epoch-Based Semantic Versioning

All projects follow an **epoch-based semantic versioning** system:
```
(EPOCH * 100 + Major).Minor.Patch
```
- **EPOCH**: A major reset of the version history (e.g., significant architecture changes).
- **Major**: Backward-incompatible changes.
- **Minor**: Backward-compatible new features.
- **Patch**: Bug fixes and small updates.

### **Example Versions:**
- `101.2.3` → Epoch 1, Major 1, Minor 2, Patch 3
- `200.0.1` → Epoch 2, Major 0, Minor 0, Patch 1

### **Tagging a Release for `chat/`**
```sh
git tag chat-v101.2.3
git push origin chat-v101.2.3
```

GitHub Actions will build and push:
- `ghcr.io/d-a-s-h-o/chat:101.2.3`
- `ghcr.io/d-a-s-h-o/chat:latest`

## 🚀 Cloning the Monorepo

This repo is large, but you don’t need to pull everything. Use **sparse checkout** to clone only the projects you need.

```sh
# Clone without downloading all files
git clone --filter=blob:none --no-checkout git@github.com:d-a-s-h-o/universe.git
cd universe

# Initialize sparse checkout
git sparse-checkout init --cone

# Checkout only specific projects
git sparse-checkout set chat droplets docs

# Checkout the branch
git checkout main
```

## 🛠 Working on Multiple Projects Simultaneously

Use **git worktrees** to keep separate working directories for different projects without switching branches.

```sh
# Create a separate worktree for chat
mkdir ../chat-worktree
cd ../chat-worktree

git worktree add . ../universe main:chat

# Create a worktree for droplets
mkdir ../droplets-worktree
cd ../droplets-worktree

git worktree add . ../universe main:droplets
```
Now, you can work on `chat/` and `droplets/` in their own directories.

## 📜 Viewing Commit History Per Project

To see only the commit history for a specific project (e.g., `chat/`):

```sh
git log -- chat/
```

To see a **short log** with one-line commits:
```sh
git log --oneline -- chat/
```

To see a **graph view**:
```sh
git log --graph --decorate -- chat/
```

## 🔄 Committing Changes Per Project

When making commits, scope them to specific projects using the defined commit format.

```sh
# Stage only files inside chat/
git add chat/

git commit -m "[chat] 🚀 feat(auth): add JWT authentication"
git push origin main
```

## 🔀 Branching Strategy

To keep things simple, **branches should be used sparingly**. The preferred approach is:
- **Main branch (`main`)**: The stable, deployable version of all projects.
- **Feature branches (`feature/<project>-<name>`)**: Used for developing major changes before merging into `main`.
- **Hotfix branches (`hotfix/<project>-<name>`)**: Used for urgent fixes that need to be merged quickly.

### **Creating a Project-Specific Branch**
```sh
git checkout -b feature/chat-new-ui
```
### **Committing and Pushing to a Feature Branch**
```sh
git add chat/
git commit -m "[chat] 🚀 feat(ui): redesign chat interface"
git push origin feature/chat-new-ui
```

### **Merging Feature Branch Back into `main`**
```sh
git checkout main
git merge --squash feature/chat-new-ui
git commit -m "[chat] 🚀 Merge chat UI update"
git push origin main
```

## 🔄 CI/CD Per Project
Each project has its own **GitHub Actions workflow** to ensure that CI/CD only runs when necessary.

Example `.github/workflows/chat.yml`:
```yaml
on:
  push:
    paths:
      - "chat/**"
```
This ensures only changes inside `chat/` trigger the workflow.

## 🔎 Listing Project-Specific Tags
To see all tags related to `chat/`:
```sh
git tag --list "chat-*"
```

## ❌ Deleting a Tag (If Needed)
If you need to delete a project-specific tag:
```sh
git tag -d chat-v101.2.3
git push origin --delete chat-v101.2.3
```

## 🔍 Additional Considerations

### Handling Dependencies Between Projects
If projects depend on each other, use:
- **Symbolic links (`ln -s`)** to reference shared assets.
- **Git submodules** if external dependencies need version tracking.
- **Shared libraries** in a central `libs/` directory.

### Rollback Strategy
If a breaking change is introduced, roll back using:
```sh
git revert <commit-hash>
```
Or reset to a previous tag:
```sh
git checkout <previous-tag>
```

### Security & Access Control
- Use **protected branches** for `main` to require reviews before merging.
- Consider **personal forks** for external contributors.
- Store API keys & secrets in **GitHub Actions Secrets**, never in the repo.

### Monorepo Tooling
If scaling up, consider tools like:
- **Nx / Turborepo** for caching & task execution.
- **Lerna** for managing package-based workflows.
- **Bazel** for dependency tracking & builds.

### Performance Considerations
- Use **sparse checkout** to avoid downloading the whole repo.
- Regularly **prune old branches**:
```sh
git branch --merged | grep -v "main" | xargs git branch -d
```
- Prevent large binary files by adding them to `.gitignore`.

## 🏁 Conclusion
This monorepo is designed to make managing multiple projects efficient while keeping dependencies and tooling centralized. Use **sparse checkout**, **worktrees**, and **scoped CI/CD** to keep your workflow smooth!

---

For any questions, open an issue or ping `@Dasho`. 🚀
