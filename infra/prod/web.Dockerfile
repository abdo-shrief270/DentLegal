# Edge image: Caddy with the production Caddyfile and the static builds of the dashboard and the marketing site.
# Built by .github/workflows/deploy.yml per environment (the dashboard's API host is set at build time), with
# infra/prod as the context after the workflow copies the builds into portal/ and site/.
FROM caddy:2.10.2-alpine
COPY Caddyfile /etc/caddy/Caddyfile
COPY portal /srv/portal
COPY site /srv/site
# Fail the build on a Caddyfile error (placeholder hosts; the real ones come from the environment at run time).
RUN API_HOST=api.example.test PORTAL_HOST=portal.example.test SITE_HOST=example.test ACME_EMAIL=ops@example.test \
    caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile
