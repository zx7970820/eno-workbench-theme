# Design QA

**Source visual truth**

- Source: `design-reference.png`
- Source pixels: 1568 × 1000
- Selected direction: Product Design option 2, dark engineering workbench
- Intentional refinement: broadened from a frontend-only identity to systems and programming, including backend, Rust, Shell, Linux, infrastructure, and frontend engineering.

**Implementation evidence**

- Desktop screenshot: `implementation-v2.png`
- Mobile navigation screenshot: `mobile-menu-v2.png`
- Side-by-side evidence: `design-comparison-v2.png`
- Desktop viewport: 1568 × 1000 CSS px, device density 1
- Source and implementation were compared at identical pixel dimensions; no density normalization was required.
- State: dark homepage, default topic “全部文章”, no search query.

**Comparison history**

1. First implementation (`implementation.png`)
   - P1: the feature panel spanned the research rail, moving “热门文章” and “正在研究” below the hero instead of keeping them top-aligned.
   - P2: above-the-fold density was looser than the source, with an oversized hero and fewer article rows visible.
2. Fixes
   - Rebuilt the desktop composition as a persistent reading column plus a 340 px research rail.
   - Moved search, feature, and article feed into the reading column; aligned the right rail to the top.
   - Reduced hero type and illustration height while preserving the selected hierarchy.
3. Post-fix evidence (`implementation-v2.png`, `design-comparison-v2.png`)
   - The left identity rail, center reading column, top-aligned research rail, compact cards, border rhythm, mint/violet accents, and overall density now match the selected direction.
   - The changed taxonomy and copy are intentional user-requested product changes, not fidelity drift.

**Required fidelity surfaces**

- Fonts and typography: IBM Plex Mono and Noto Sans SC reproduce the source’s code/editorial contrast. Heading scale, compact metadata, line height, and wrapping are consistent with the selected option.
- Spacing and layout rhythm: 250 px identity rail, central reading column, 340 px research rail, compact 12–18 px gaps, sharp 3 px radii, and 1 px borders match the source structure.
- Colors and tokens: graphite background, charcoal panels, muted slate copy, mint success/accent, and restrained violet secondary accent are consistent and pass visual contrast checks.
- Image quality and assets: generated author portrait and systems-topology artwork are real raster assets with correct crop, sharpness, palette, and card placement. Standard interface icons use Tabler Icons.
- Copy and content: content was deliberately broadened to system design, backend, Rust, Shell/Linux, tooling, and frontend engineering while preserving realistic article metadata.

**Interaction and responsive checks**

- Topic filter: passed (`Rust` returns one matching card).
- Search and empty state: passed.
- Keyboard search shortcut: implemented (`Ctrl/Cmd + K`).
- Mobile navigation: passed at 390 × 844; open and close controls work and the overlay prevents background interaction.
- Browser console: no warnings or errors after the final desktop render.
- Production build: passed.
- Sites packaging tests: 4/4 passed.

**Focused region comparison**

- Hero + research rail: checked in the full-size side-by-side image; hierarchy, crop, borders, and baseline alignment match.
- Mobile navigation: checked separately because it is not represented in the desktop source; no overflow or clipped controls were found.

**Follow-up polish**

- P3: external webfonts may fall back to system fonts on restricted networks; the fallback stack preserves layout.

final result: passed
