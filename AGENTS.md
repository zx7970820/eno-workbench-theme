# Prototype Instructions

Run the local server yourself and open the preview in the browser available to this environment. Do not give the user server-start instructions when you can run it.

Before making substantial visual changes, use the Product Design plugin's `get-context` skill when the visual source is unclear or no longer matches the current goal. When the user gives durable prototype-specific design feedback, preferences, or decisions, record them in `AGENTS.md`.

## Durable design direction

- Selected visual target: Product Design ideation option 1, the dark editorial console in `C:\Users\Administrator\Documents\Codex\2026-08-24\w-x20\work\selected-design-option-1.png`.
- Product positioning: a broad personal systems and programming notebook, not a frontend-only publication.
- Content taxonomy should comfortably include frontend, backend, Shell, Rust, Linux, infrastructure, and engineering practice.
- Preserve the dense dark workbench layout, restrained mint/violet accents, sharp borders, and code-oriented typography while avoiding obvious frontend-only branding.
- Every visible navigation item, article link, search control, archive entry, category, and call to action must resolve to real WordPress content or a real WordPress route. Remove any control that cannot be implemented truthfully; do not use fictional series, progress, counts, social profiles, or articles as decoration.
- Support light and dark modes. Use the native View Transition API for a smooth theme reveal when available, respect `prefers-reduced-motion`, provide a no-animation fallback, follow the system preference on first visit, and persist an explicit visitor choice locally.
- Keep the project as a complete local repository: WordPress theme, content/import plugin, reproducible Docker-based WordPress + database environment, deployment notes, and content sources belong together.
- Editorial voice should be conversational and experience-led, avoiding formulaic AI-style sectioning and filler. Maintain a broad topic mix across frontend, backend, Shell/Linux, Rust, systems, and engineering practice. Seeded publication dates should span 2020 through the present instead of clustering on one import date.

When implementing from a selected generated mock, treat that image as the source of truth for layout, component anatomy, density, spacing, color, typography, visible content, and hierarchy.

Build app UI in `src/`. Keep `.openai/hosting.json`, `worker/index.js`, `scripts/prepare-sites-build.mjs`, and `tests/sites-worker.test.mjs` intact so the same local prototype can be handed to Sites. Before a Sites handoff, run `npm run build` and `npm run test:sites`; the build must leave `dist/client/index.html`, `dist/server/index.js`, and `dist/.openai/hosting.json`.
