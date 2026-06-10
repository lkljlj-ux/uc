---
name: Running a PHP/MySQL (LAMP) app on Replit
description: How to host a legacy PHP + MySQL app on Replit, including the MariaDB bootstrap quirk and socket wiring.
---

# Running a PHP/MySQL (LAMP) app on Replit

Replit has no native MySQL; use the `php-8.2` module + `mariadb` system dependency, run MariaDB locally, and serve PHP with the built-in server.

## MariaDB bootstrap quirk (the important one)
`mariadb-install-db` run from the **bash tool** gets killed mid-bootstrap by the sandbox syscall tracer (error: `handle_syscall ... openat: get fd path ffffffff /proc/<pid>/fd/-1`). It leaves system tables half-created (only `global_priv`), so `mariadbd` later fails with "Table 'mysql.plugin'/'mysql.servers' doesn't exist".

**Why:** the multi-threaded bootstrap process trips the bash tool's tracer.

**How to apply:** run `mariadb-install-db` as a **workflow** (outputType console) instead of the bash tool — the workflow process manager completes it cleanly (~88 entries in `data/mysql`). Then swap that one-shot workflow for the long-running `mariadbd` workflow. Same applies to backgrounding `mariadbd &` from bash — it gets killed; run it as a workflow.

## Socket wiring
PHP's mysqli default socket is `/run/mysqld/mysqld.sock` (not writable). Point PHP at the local MariaDB socket via a project `php.ini` (`mysqli.default_socket` + `pdo_mysql.default_socket` = absolute path under the repl, e.g. `/home/runner/workspace/.mysql/mysql.sock`) and start the server with `php -c php.ini -S 0.0.0.0:5000 -t .`. This makes app code that connects to `'localhost'` work without editing every PHP file.

## Other flags
Start `mariadbd` with `--innodb-use-native-aio=0` (io_uring is blocked: `kernel.io_uring_disabled=2`). Gitignore the data dir (`.mysql/`).
