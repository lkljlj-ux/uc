#!/usr/bin/env python3
"""Independent GitHub runner probes; no request requires an API credential."""
import json
import os
import sys
import time
import urllib.error
import urllib.request


def check(name, url, status_endpoint=False):
    if not url or not url.startswith(("http://", "https://")):
        return f"{name}: missing or invalid monitor URL"
    try:
        with urllib.request.urlopen(
            urllib.request.Request(url, headers={"User-Agent": "myjoin-uptime/1"}),
            timeout=12,
        ) as response:
            if response.status != 200:
                return f"{name}: HTTP {response.status}"
            body = response.read(4096)
        data = json.loads(body)
        if status_endpoint:
            timestamp = data.get("checked_at")
            if data.get("ok") is not True or type(timestamp) is not int or not 0 <= time.time() - timestamp <= 900:
                return f"{name}: unhealthy or stale host monitor status"
        elif data.get("status") != "ok":
            return f"{name}: unexpected health response"
    except (OSError, ValueError, json.JSONDecodeError) as exc:
        return f"{name}: probe failed ({type(exc).__name__})"
    return None


def main():
    checks = [
        ("web health", os.environ.get("WEB_URL", "") + "/health.php"),
        ("API health", os.environ.get("API_URL", "") + "/health.php"),
        ("host monitor", os.environ.get("API_URL", "") + "/monitor-status.json"),
    ]
    errors = [result for name, url in checks if (result := check(name, url, name == "host monitor"))]
    if errors:
        print("\n".join(errors))
        return 1
    print("Web, API and host integrity status healthy")
    return 0


if __name__ == "__main__":
    sys.exit(main())