# d-a-s-h-o/universe

🚀 **Welcome to the Universe Monorepo!**

This is the central repository for all my development projects, including chat applications, microservices, documentation, personal tools, and more. By managing everything under one roof, this monorepo ensures **better asset sharing, unified tooling, and streamlined CI/CD workflows**.

## 📂 Projects Included
- **portfolio/** – Personal website (includes submodule [d-a-s-h-o/d-a-s-h-o](https://github.com/d-a-s-h-o/d-a-s-h-o))
- **chat/** – Real-time chat application
- **droplets/** – Microservices for various tasks
- **docs/** – Documentation and guides
- **crypto/** – Crypto & Verification services
- **sshchat/** – Secure chat platform
- **dotfiles/** – Development environment configurations

## 🎯 Why Use a Monorepo?
- **Shared assets & libraries** – Centralized management of dependencies, icons, and configurations.
- **Efficient versioning** – All projects follow **epoch-based semantic versioning**.
- **Modular development** – Work on multiple projects simultaneously using **sparse checkouts** and **worktrees**.
- **Automated CI/CD** – Each project has its own GitHub Actions workflow for independent builds and deployments.
- **Containerized environments** – Uses **GitHub Container Registry (GHCR)** for project-specific container builds.

## 🚀 Getting Started
### Cloning with Submodules
This repository includes a submodule for the portfolio project. To ensure it is initialized correctly, clone with:
```sh
git clone --recurse-submodules git@github.com:d-a-s-h-o/universe.git
```
If you have already cloned the repository, initialize and update submodules manually:
```sh
git submodule update --init --recursive
```
### Clone the Monorepo
This repo is large, so use **sparse checkout** to download only the necessary projects.
```sh
git clone --filter=blob:none --no-checkout git@github.com:d-a-s-h-o/universe.git
cd universe
git sparse-checkout init --cone
git sparse-checkout set chat droplets docs

git checkout main
```

### Work on Multiple Projects Simultaneously
Use **worktrees** to keep separate working directories for different projects without switching branches.
```sh
# Create a worktree for chat
mkdir ../chat-worktree
cd ../chat-worktree
git worktree add . ../universe main:chat
```

### Commit Message Format
To maintain consistency, use the following format:
```
[project] <emoji> type(scope): subject
```
**Examples:**
```
[chat] 🚀 feat(auth): add JWT authentication
[docs] 📖 docs(readme): update installation guide
```

## 🏷️ Versioning Strategy
All projects follow an **epoch-based semantic versioning** system:
```
(EPOCH * 100 + Major).Minor.Patch
```
**Example Versions:**
- `101.2.3` → Epoch 1, Major 1, Minor 2, Patch 3
- `200.0.1` → Epoch 2, Major 0, Minor 0, Patch 1

### Tagging a Release
```sh
git tag chat-v101.2.3
git push origin chat-v101.2.3
```

## 🔀 Branching Strategy
Branches should be used sparingly:
- **Main (`main`)** – The stable, deployable version.
- **Feature branches (`feature/<project>-<name>`)** – For new features.
- **Hotfix branches (`hotfix/<project>-<name>`)** – For urgent fixes.

## 🛠 CI/CD & Containers
Each project has **independent GitHub Actions workflows** and **container builds** pushed to **GHCR**.
- CI/CD triggers only for relevant project changes.
- Containers are tagged with **version numbers** and **latest**.

## 🔍 Additional Considerations
- **Security & Access** – Use GitHub Actions Secrets for API keys.
- **Rollback Strategy** – Use `git revert` or checkout previous tags.
- **Performance Optimizations** – Use `sparse-checkout`, prune branches, and avoid large binary files.

## 📜 License
This repository follows an **open-source but restricted use** model. Check individual projects for specific licensing terms.

## 🏁 Conclusion
This monorepo is built for **scalability, efficiency, and ease of use**. Use **sparse checkouts, worktrees, and modular CI/CD workflows** to keep development streamlined. Checkout the [MONOREPO.md](MONOREPO.md) file for more information on this monorepo and a detailed guide of how to use it.

For any questions, open an issue or ping `@Dasho`. 🚀
