---
name: UC mapping migrations
description: Safely moving UC operator mappings between the Replit development database and the VPS production database.
---

Treat the Replit development database and VPS production database as separate data stores. When moving a UC operator mapping, decrypt sensitive operator fields with the source environment's session secret and re-encrypt them with the destination environment's session secret before inserting the mapping.

**Why:** UC operator fields use AES-GCM encryption keyed from the environment-specific session secret. Copying ciphertext directly makes values unreadable on the other environment.

**How to apply:** Prefer creating records through the production admin UI. If a one-record migration is explicitly approved, use an encrypted transport, temporary restricted files, database transactions, and securely delete temporary plaintext material afterward.