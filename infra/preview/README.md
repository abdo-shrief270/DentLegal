# UI preview on the staging VPS

Until the API exists, the staging Hostinger VPS serves the two UIs with **fake data only** (PROJECT1_PLAN.md §8.2):

| URL | What |
|---|---|
| `https://<PREVIEW_HOST>/` | Web dashboard, lawyer and admin (sign-in: `sara@dentlegal.test` / `noura@dentlegal.test`, any 8+ character password, code `123456`) |
| `https://<PREVIEW_HOST>/app/` | Dentist app, Flutter web build (phone `551234417`, code `123456`) |

`.github/workflows/preview.yml` builds both on every push to `main` that touches `web/`, `mobile/` or this folder, then copies them to the VPS with `rsync` and (re)starts Caddy, which gets the TLS certificate by itself. The deploy job is skipped until `PREVIEW_HOST` is set, so builds still run before the server is ready.

No real patient data goes on the preview. It is replaced by the real staging stack in W4 (S0.4).

## 1. Prepare the VPS (once)

Hostinger **KVM 4**, Ubuntu 24.04 (Docker template or plain). As root:

```bash
apt-get update && apt-get install -y docker.io docker-compose-v2 rsync ufw fail2ban
```

```bash
adduser --disabled-password --gecos "" deploy && usermod -aG docker deploy
```

```bash
install -d -o deploy -g deploy /srv/dentlegal-preview
```

```bash
ufw allow 22/tcp && ufw allow 80/tcp && ufw allow 443 && ufw --force enable
```

Also open 22, 80 and 443 in the VPS firewall in hPanel if you use it.

## 2. Deploy key (once)

On your computer, create a key used only by GitHub Actions:

```bash
ssh-keygen -t ed25519 -N "" -C "github-preview-deploy" -f dentlegal_preview
```

Put the public key on the VPS for `deploy`:

```bash
ssh-copy-id -i dentlegal_preview.pub deploy@<vps-ip>
```

Then turn off password login for SSH (`PasswordAuthentication no` in `/etc/ssh/sshd_config`, then `systemctl restart ssh`).

## 3. Domain (once)

Use either:
- the hostname Hostinger gives the VPS (`srvXXXXXX.hstgr.cloud`, shown in hPanel), which already points to the server; or
- your own name, e.g. `preview.<domain>`, with an **A record** to the VPS IP.

Ports 80 and 443 must be reachable so Caddy can get the Let's Encrypt certificate.

## 4. GitHub settings (once)

Repository → Settings → Secrets and variables → Actions.

| Kind | Name | Value |
|---|---|---|
| Secret | `PREVIEW_SSH_KEY` | Contents of the private key `dentlegal_preview` |
| Secret | `PREVIEW_KNOWN_HOSTS` | Output of `ssh-keyscan -t ed25519 <vps-ip-or-host>` (check the fingerprint against hPanel's console) |
| Variable | `PREVIEW_HOST` | The preview hostname from step 3 |
| Variable (optional) | `PREVIEW_SSH_HOST` | SSH address if different from `PREVIEW_HOST` (e.g. the IP) |
| Variable (optional) | `PREVIEW_SSH_USER` / `PREVIEW_SSH_PORT` / `PREVIEW_DIR` | Defaults: `deploy` / `22` / `/srv/dentlegal-preview` |

Then run the workflow once: Actions → **UI preview** → Run workflow. The last step checks both URLs.

## Files

- `Caddyfile`: serves the dashboard at `/` (SPA fallback to `200.html`) and the app at `/app/`, adds security headers and `noindex`.
- `compose.yaml`: one `caddy` container with the two build folders mounted read-only.
