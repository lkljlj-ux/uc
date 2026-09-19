---
name: GitHub-to-VPS deployment access
description: Access boundary for bootstrapping and operating automatic GitHub Actions deployments to the VPS.
---

Use a least-privilege repository credential only to bootstrap the repository and Actions secrets. Runtime deployments must authenticate to the VPS with a dedicated SSH deploy key; never place the VPS root password in GitHub.

**Why:** A dedicated Actions SSH key limits password exposure and supports unattended deployments without sharing an administrative password with the CI provider.

**How to apply:** For future automation changes, preserve the dedicated SSH-key model and atomic release workflow. Verify effective repository permissions without printing credentials.