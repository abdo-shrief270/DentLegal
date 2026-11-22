# DentLegal — Project 1

Legal-protection platform for dentists in Saudi Arabia. This repository holds **Project 1**: the AI core (RAG over Saudi regulations) and the simplified release (Flutter dentist app, Vue web dashboard for lawyers and admins, Nuxt landing page).

- **Plan:** [`PROJECT1_PLAN.md`](PROJECT1_PLAN.md) (approved, 9 weeks).

## Reference material (approved by the client)

| Path | What it is |
|---|---|
| `site/prototype/` | Interactive HTML prototype: the approved UI for the dentist app, lawyer portal and admin. Open `site/prototype/index.html`. |
| `site/screenshots/` | Screenshots of every prototype screen (`m*` mobile, `w*` web) |
| `site/docs/prd.html` | Product requirements |
| `site/docs/user-flows.html` | User flows and screen map |
| `site/docs/architecture.html` | Technical direction |
| `site/assets/tokens.css` | Design tokens (colours, font) used by the prototype |
| `*.pdf` (repo root) | Regulatory sources to index: نظام مزاولة المهن الصحية ولائحته التنفيذية (current + previous edition), اللائحة التنظيمية لخدمات طب الأسنان, الوصف الوظيفي |
| `1.jpeg`, `2.jpeg` | The client's original concept images |

The prototype uses fictional data only. No real patient data belongs in this repository.

## Previewing the prototype

```bash
cd tools && npm install && npm run serve
```

Then open http://localhost:5173/prototype/. `npm run shots` regenerates `site/screenshots/` (needs Chrome; set `CHROME_PATH`).
