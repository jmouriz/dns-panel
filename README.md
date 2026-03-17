# dns-panel

Minimal web administration panel for PowerDNS, built with PHP, Lighttpd and SQLite.

The panel is designed to manage a PowerDNS authoritative server through its HTTP API, with a simple MVC structure and a lightweight deployment model based on Docker.

---

## Features

- Web-based PowerDNS administration
- Login and role-based access
- Zone management
- Record management
- SOA editing
- DNSSEC toggle from zone editing
- Native and Slave zone support
- Read-only behavior for slave zones
- Per-zone diagnostics using `dig`
- Responsive interface
- Light and dark theme support
- Installer / bootstrap workflow
- SQLite for panel configuration and users

---

## Project goals

This panel aims to be:

- simple to deploy
- easy to understand
- lightweight
- easy to adapt
- suitable for small and medium PowerDNS installations

It does not try to replace large control panels. It focuses on clarity and practical DNS operations.

---

## Stack

- Alpine Linux
- Lighttpd
- PHP
- SQLite
- PowerDNS HTTP API

---

## Project structure

```text
.
├── Dockerfile
├── docker-compose.yml
├── bootstrap.sh
├── www/
│   ├── index.php
│   ├── install.php
│   ├── assets/
│   ├── app/
│   │   ├── bootstrap.php
│   │   ├── controllers/
│   │   ├── core/
│   │   ├── repositories/
│   │   ├── services/
│   │   └── views/
│   └── storage/
```

---

## Main features

## Authentication

The panel includes:

- login
- logout
- role-based access control

Typical roles include:

- administrator
- hostmaster

The structure is intentionally simple and can be extended if needed.

---

## Zones

The panel supports:

- listing zones
- creating zones
- editing zones
- deleting zones
- SOA editing
- diagnostics

Supported zone types:

- `Native`
- `Slave`

### Native zones
Native zones can be edited normally.

### Slave zones
Slave zones are treated as read-only for records and SOA, which matches PowerDNS behavior more naturally.

---

## Records

Supported record types currently include:

- A
- AAAA
- NS
- MX
- CNAME
- TXT
- PTR
- SRV
- TLSA
- RAW

The UI uses type-specific partials for record forms.

Long record contents such as DKIM and TLSA values are truncated visually with ellipsis in the list view while preserving the full content in tooltips.

---

## Diagnostics

Each zone includes a diagnostics view that can run DNS queries using `dig`.

Current uses include:

- querying SOA
- querying NS
- querying A / AAAA / MX / TXT / TLSA / etc.
- comparing SOA serial between master and slave
- checking whether master and slave are in sync

This feature is useful both for troubleshooting and for verifying real DNS behavior from the panel.

---

## Configuration

The panel stores its own configuration in SQLite.

Typical settings include:

- panel title
- subtitle
- logo
- PowerDNS URL
- PowerDNS API key
- PowerDNS server ID
- default theme
- whether users may override theme
- master DNS IP
- slave DNS IP

---

## Installer

The project includes an installation flow through:

```text
install.php
```

This bootstraps the panel configuration and initial admin setup.

---

## Docker

The panel is intended to be deployed in a container.

Typical container contents:

- Lighttpd
- PHP CGI / FastCGI
- SQLite
- `dig` for diagnostics

---

## Example deployment

Typical usage:

```bash
docker compose up -d --build
```

If your project includes a helper script:

```bash
./bootstrap.sh
```

---

## PowerDNS integration

The panel talks to PowerDNS through the HTTP API.

Typical PowerDNS settings used by the panel:

- API enabled on the PowerDNS side
- API key configured
- reachable API URL
- server ID, usually `localhost`

---

## Notes on slave zones

Slave zones are not meant to be edited manually from the panel.

The panel therefore avoids:

- direct record editing on slave zones
- direct SOA editing on slave zones
- direct DNSSEC toggling through the old standalone action

This keeps the UI aligned with real DNS replication behavior.

---

## Notes on autoprimary / autosecondary

The panel itself does not need to run on the slave.

A common architecture is:

- panel + PowerDNS API on the master
- plain PowerDNS on the slave
- autoprimary / autosecondary handled directly by PowerDNS

This keeps the operational model simpler and safer.

---

## Development notes

This project follows a lightweight MVC structure:

- controllers handle flow
- repositories handle storage access
- services handle business logic and PowerDNS integration
- views render HTML
- assets handle CSS and JavaScript

The goal is to keep the code modular without making it unnecessarily complex.

---

## Suggested `.gitignore`

```gitignore
www/storage/panel.sqlite
www/storage/uploads/*
!www/storage/uploads/.gitkeep
*.log
.DS_Store
```

If you prefer to keep an empty uploads directory in the repo, add a `.gitkeep` file.

---

## Useful notes

- The panel is intended for authoritative DNS management
- Diagnostics use `dig`, so the container should include `bind-tools` on Alpine
- For slave replication, the panel is not required on the slave server
- For GitHub publishing, do not commit real API keys or live SQLite databases

---

## Status

The panel currently supports a full practical workflow including:

- authentication
- zone creation and edition
- record management
- TLSA support
- diagnostics
- master/slave friendly behavior

---

## License

MIT
