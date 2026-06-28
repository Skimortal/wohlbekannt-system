# wohlbekannt-system — Design System

A lean, implementable design system for the internal ERP/CRM (Kunden, Angebote,
Rechnungen) of an Austrian mobile-bar / event-catering company. German UI,
data-dense, calm and high-end.

---

## 1. Aesthetic direction

Quiet, confident and paper-like — the screen should feel like an extension of the
elegant printed Angebot, not a dashboard. We lean on generous whitespace, a warm
near-white canvas and hairline borders instead of heavy boxes and shadows; the
sand/champagne accent (`#E6C7A9`) is used sparingly for selection, emphasis and the
brand moment, while near-black ink (`#0E1214`) carries primary actions and text.
Restraint à la Stripe/Linear, warmed by a Tyrolean champagne tint. The thin script
of the logo stays in the logo only — the working UI is a clean, highly legible sans.

---

## 2. Color system

All colors are defined as Tailwind 4 `@theme` tokens (see `frontend/src/style.css`).
The existing tokens are kept; this section **extends** them. Names map 1:1 to
Tailwind utilities, e.g. `--color-canvas` → `bg-canvas`, `text-canvas`,
`border-canvas`.

### 2.1 Surfaces / backgrounds

| Token | Hex | Use |
|---|---|---|
| `--color-paper` | `#FFFFFF` | Cards, panels, table surface, modals |
| `--color-canvas` | `#FBFAF8` | App background (warm off-white) |
| `--color-sunken` | `#F6F4F1` | Table header, inset wells, code/preview areas |
| `--color-hover` | `#F4F1EC` | Row / list hover |
| `--color-overlay` | `rgba(14,18,20,.45)` | Modal / drawer scrim |

### 2.2 Ink / text levels

| Token | Hex | Use | Contrast on paper |
|---|---|---|---|
| `--color-ink` | `#0E1214` | Headings, primary text, primary button bg | 18.5:1 ✓ AAA |
| `--color-ink-soft` | `#3A3F42` | Body / secondary text | 9.7:1 ✓ AAA |
| `--color-ink-muted` | `#6B7176` | Labels, captions, meta | 4.8:1 ✓ AA |
| `--color-ink-faint` | `#9AA0A4` | Placeholders, disabled, "Opt." cells | 2.6:1 (non-text only) |
| `--color-ink-invert` | `#FFFFFF` | Text on ink / colored buttons | — |

### 2.3 Sand accent + tints

| Token | Hex | Use |
|---|---|---|
| `--color-sand` | `#E6C7A9` | Brand accent, selected-row left border, focus ring tint |
| `--color-sand-50` | `#FAF3EA` | Selected-row background, subtle highlight |
| `--color-sand-100` | `#F3E6D6` | Hover on sand surfaces, badge bg (brand) |
| `--color-sand-200` | `#ECD8C1` | Borders on sand surfaces |
| `--color-sand-300` | `#DDB892` | Deeper accent, sand button hover/border |
| `--color-sand-700` | `#8A5A2E` | **Accessible** sand-toned text/icon (5.0:1 on paper) |

> Sand is **never** used as text color on white (fails AA). For "sand-flavoured"
> text use `--color-sand-700`. Sand's job is fills, selection and the brand touch;
> **ink** is the color of primary action.

### 2.4 Lines / borders

| Token | Hex | Use |
|---|---|---|
| `--color-line` | `#E8E2DA` | Default hairline borders, table rules |
| `--color-line-strong` | `#D9D2C8` | Input borders, dividers needing presence |
| `--color-focus` | `#0E1214` | Focus outline (ink), paired with sand ring |

### 2.5 Semantic colors

Each role ships a **fg** (text/icon, AA on its tint), **bg** (tint), **border**.

| Role | fg | bg | border |
|---|---|---|---|
| Success | `#1F7A4D` | `#EAF4EE` | `#C9E3D4` |
| Warning | `#8A5A00` | `#FBF1DD` | `#EFD9A8` |
| Danger | `#B23129` | `#FBEBEA` | `#F0CFCC` |
| Info | `#2C5A82` | `#EAF1F7` | `#CBDDEC` |
| Neutral | `#4B5156` | `#F2F1EE` | `#E0DCD4` |

Tokens: `--color-success`, `--color-success-bg`, `--color-success-border`, … same
pattern for `warning`, `danger`, `info`, `neutral`. All `fg` values clear ≥ 4.5:1 on
their `bg`. Danger fg `#B23129` is also the **danger button** base (white text → AA).

### 2.6 Status colors — Angebot & Rechnung

Status is always shown as a **badge** (§4.6): `bg` fill, `fg` text, `border` hairline.
We deliberately reuse the semantic palette so the system stays small and learnable.

**Angebot (quote)**

| Status (DE) | Role | fg | bg | border |
|---|---|---|---|---|
| Entwurf (draft) | Neutral | `#4B5156` | `#F2F1EE` | `#E0DCD4` |
| Versendet (sent) | Info | `#2C5A82` | `#EAF1F7` | `#CBDDEC` |
| Angenommen (accepted) | Success | `#1F7A4D` | `#EAF4EE` | `#C9E3D4` |
| Abgelehnt (declined) | Danger | `#B23129` | `#FBEBEA` | `#F0CFCC` |
| Abgelaufen (expired) | Warning | `#8A5A00` | `#FBF1DD` | `#EFD9A8` |

**Rechnung (invoice)**

| Status (DE) | Role | fg | bg | border |
|---|---|---|---|---|
| Entwurf (draft) | Neutral | `#4B5156` | `#F2F1EE` | `#E0DCD4` |
| Versendet (sent) | Info | `#2C5A82` | `#EAF1F7` | `#CBDDEC` |
| Bezahlt (paid) | Success | `#1F7A4D` | `#EAF4EE` | `#C9E3D4` |
| Überfällig (overdue) | Danger | `#B23129` | `#FBEBEA` | `#F0CFCC` |
| Storniert (cancelled) | Neutral-muted | `#9AA0A4` | `#F6F4F1` | `#E8E2DA` |

> *Storniert* uses the faint ink + a strikethrough on the document number to read as
> "voided" without shouting. *Überfällig* is the only red an invoice list should ever
> show — keep red rare so it stays meaningful.

---

## 3. Typography

**UI font: Inter** (Google Fonts, free, already in tokens). Rationale: Inter was
designed for screen UI at small sizes, has excellent legibility in dense tables, real
**tabular figures** (`font-feature-settings: "tnum"`) for money columns, a wide weight
range, and a neutral-but-warm character that sits well next to the script logo without
competing with it. Fallback: `system-ui, "Segoe UI", Roboto, sans-serif`.

The logo's script is **not** a UI font — it appears only as the logo asset.

Load: `<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">`

### 3.1 Type scale

| Token | Size / line-height | Weight | Use |
|---|---|---|---|
| Display | 28px / 36px | 600 | Page title (rare, e.g. login) |
| h1 | 22px / 30px | 600 | View title ("Angebote") |
| h2 | 18px / 26px | 600 | Section / panel title |
| h3 | 15px / 22px | 600 | Card title, group label |
| Body | 14px / 22px | 400 | Default text, form values |
| Body-strong | 14px / 22px | 600 | Emphasised inline, line-item name |
| Small | 13px / 18px | 400 | Secondary, helper text |
| Caption / Label | 12px / 16px | 500, +0.02em tracking, often UPPERCASE | Field labels, table headers, badges |
| Table cell | 14px / 20px | 400 | Data rows |
| Mono-num | 14px tabular | 400/600 | Money & quantity columns |

- Base body is **14px** (dense B2B norm), not 16px — keeps tables readable without
  scrolling.
- **Money / quantity columns: always `font-variant-numeric: tabular-nums;`** and
  right-aligned. Tailwind: `tabular-nums text-right`.
- Headings use `tracking-tight` (`-0.01em`); labels use slight positive tracking.

---

## 4. Spacing, layout & components

### 4.1 Spacing & radius

- **Base unit: 4px.** Use the 4px scale (4/8/12/16/24/32/48). Tailwind defaults match.
- **Radius scale:** `--radius-sm 4px` (inputs, badges), `--radius-md 8px`
  (buttons, cards), `--radius-lg 12px` (modals, large panels), `--radius-full`
  (avatars, pills only when intentional — status badges use `sm`, not full pills).
- **Shadows (subtle, rare):** elevation comes from borders first.
  - `--shadow-sm: 0 1px 2px rgba(14,18,20,.05)` — cards on canvas (optional).
  - `--shadow-md: 0 8px 24px -8px rgba(14,18,20,.12)` — dropdowns, popovers.
  - `--shadow-lg: 0 24px 48px -16px rgba(14,18,20,.18)` — modals/drawers only.
  - No glows, no colored shadows.

### 4.2 App shell — **left sidebar (recommended)**

For an ERP with several top-level domains (Dashboard, Kunden, Angebote, Rechnungen,
Einstellungen) a **fixed left sidebar + slim topbar** beats a pure topbar: it scales to
more nav items, keeps context visible, and leaves vertical space for dense tables.

- **Sidebar:** 240px, `bg-canvas`, `border-r border-line`. Logo at top (28px high),
  nav groups below. Active item: `bg-sand-50 text-ink` with a 2px `sand` left border;
  inactive: `text-ink-muted hover:bg-hover hover:text-ink`. Collapsible to 64px (icons).
- **Topbar:** 56px, `bg-paper border-b border-line`, holds breadcrumb/page title (left)
  and global search + user menu (right).
- **Content:** `bg-canvas`, padding `px-8 py-6`.
- **Content max-width:** lists/tables `max-w-screen-2xl` (use the room); detail forms
  & document editors `max-w-4xl` (≈ 896px) centered for readability; reading prose
  `max-w-prose`.

### 4.3 Tables (data density)

- Surface `bg-paper`, outer `border border-line rounded-md overflow-hidden`.
- Header: `bg-sunken text-ink-muted text-xs font-medium uppercase tracking-wide`,
  `border-b border-line`, cells `px-4 py-2.5`.
- Rows: `border-b border-line last:border-0`, cells `px-4 py-3` (comfortable) or
  `py-2` (compact toggle). Hover `hover:bg-hover`. Selected `bg-sand-50` + 2px sand
  left border.
- Money & numbers: right-aligned, `tabular-nums`, `text-ink`. Currency suffix
  `text-ink-muted` (" EUR"). Totals row: `bg-sunken font-semibold`.
- Zebra striping is **off** by default (hairlines are enough); offer it only on
  very wide tables.

### 4.4 Cards / panels

```html
<section class="bg-paper border border-line rounded-md p-6">
  <h2 class="text-[18px] leading-7 font-semibold tracking-tight text-ink">Titel</h2>
  <p class="mt-1 text-sm text-ink-muted">Beschreibung</p>
  <!-- … -->
</section>
```
No drop shadow on canvas by default; add `shadow-sm` only when a card floats over
busy content. Panel header divider: `border-b border-line pb-4 mb-4`.

### 4.5 Buttons

Height 36px (`h-9`), `rounded-md`, `text-sm font-medium`, `px-3.5`, focus
`outline-none ring-2 ring-sand ring-offset-2 ring-offset-paper`, `transition-colors`.

| Variant | Classes |
|---|---|
| **Primary** | `bg-ink text-ink-invert hover:bg-ink-soft active:bg-ink disabled:opacity-40` |
| **Secondary** | `bg-paper text-ink border border-line-strong hover:bg-hover` |
| **Ghost** | `bg-transparent text-ink-soft hover:bg-hover` |
| **Danger** | `bg-danger text-white hover:brightness-95` (use for destructive only) |
| **Accent/CTA** | `bg-sand-300 text-ink hover:bg-sand` (rare — e.g. "Angebot annehmen") |

Sizes: sm `h-8 text-xs px-3`, md `h-9` (default), lg `h-10 px-4`. Icon-only: square,
same height. Primary action per view = **one** ink button; everything else secondary/ghost.

### 4.6 Inputs / selects

```html
<label class="block text-xs font-medium uppercase tracking-wide text-ink-muted">Firma</label>
<input class="mt-1.5 h-9 w-full rounded-sm border border-line-strong bg-paper px-3
  text-sm text-ink placeholder:text-ink-faint
  focus:outline-none focus:border-ink focus:ring-2 focus:ring-sand
  disabled:bg-sunken disabled:text-ink-muted" />
```
- Selects same metrics; native chevron or a 16px `text-ink-muted` icon.
- Error: `border-danger` + helper `text-danger text-xs mt-1`.
- Money input: right-aligned, `tabular-nums`, " EUR" suffix in an adornment.
- Always pair an input with a 12px uppercase label; group with `space-y-4`.

### 4.7 Status badges

```html
<span class="inline-flex items-center gap-1.5 rounded-sm border px-2 py-0.5
  text-xs font-medium
  text-success bg-success-bg border-success-border">Angenommen</span>
```
- Optional 6px leading dot (`bg-{role}` rounded-full) for quicker scanning.
- *Storniert*: `text-ink-faint bg-sunken border-line`; render the doc-number with
  `line-through`.
- One badge per status; never combine colors.

### 4.8 Tabs

Underline style, not pills: container `border-b border-line`; tab
`px-1 pb-3 text-sm text-ink-muted hover:text-ink`; active
`text-ink border-b-2 border-ink -mb-px font-medium`. Sand is *not* used here (keep it
for selection/brand).

### 4.9 Modals / drawers

- **Modal** (confirmations, short forms): centered, `max-w-lg`, `bg-paper rounded-lg
  shadow-lg`, `p-6`; scrim `bg-overlay`; header h2 + close ghost-icon; footer
  right-aligned `gap-2` (secondary + primary).
- **Drawer** (record detail, line-item editing): right-side, `w-[480px]` (or
  `w-[640px]` for editors), `bg-paper border-l border-line shadow-lg`, slides in.
- Trap focus, `Esc` closes, restore focus on close.

### 4.10 Toasts

Bottom-right stack, `bg-ink text-ink-invert` (default), `rounded-md shadow-md px-4 py-3
text-sm`, max-w 360px, auto-dismiss 4s. Success/Danger variants tint a 3px left bar in
`success`/`danger` rather than recoloring the whole toast. No icons-as-emoji.

### 4.11 Line-item editor row (Angebot / Rechnung)

Grid columns mirror the PDF: **Pos. · Beschreibung · Menge · Einzelpreis ·
Gesamtpreis · (Aktion)**.

```html
<!-- header -->
<div class="grid grid-cols-[48px_1fr_96px_120px_140px_40px] gap-3 px-3 py-2
  text-xs font-medium uppercase tracking-wide text-ink-muted bg-sunken border-b border-line">
  <span>Pos.</span><span>Beschreibung</span>
  <span class="text-right">Menge</span><span class="text-right">Einzelpreis</span>
  <span class="text-right">Gesamtpreis</span><span></span>
</div>

<!-- standard row -->
<div class="grid grid-cols-[48px_1fr_96px_120px_140px_40px] gap-3 px-3 py-3
  items-start border-b border-line hover:bg-hover">
  <span class="text-sm text-ink-muted tabular-nums">1.</span>
  <div>
    <input class="w-full bg-transparent text-sm font-semibold text-ink
      focus:outline-none" value="Sektempfang 2h" />
    <textarea class="mt-1 w-full bg-transparent text-sm text-ink-soft
      focus:outline-none" rows="2">Wir bereiten euch den Sektempfang …</textarea>
  </div>
  <input class="text-right tabular-nums text-sm" value="42,00" />
  <input class="text-right tabular-nums text-sm" value="24,00" />
  <span class="text-right tabular-nums text-sm font-medium text-ink">1.008,00 EUR</span>
  <button class="text-ink-faint hover:text-danger">✕-icon</button>
</div>
```
- Editing happens inline; cells are borderless inputs that only show a hairline on
  `:focus`. Drag-handle (`text-ink-faint`) at row start for reordering.
- Per-row controls: a quiet "Optional" toggle, Steuersatz select, delete.

### 4.12 Optional positions ("Opt.") — the signature treatment

The printed Angebot marks optional items with **"Opt."** in the Pos. column and puts
their prices **in parentheses** so they read as *not included in the total*. The UI
mirrors this exactly — clearly secondary, still elegant, never a different/garish color:

- **Pos. marker:** instead of a running number, show a small uppercase chip
  `Opt.` — `text-[11px] font-medium uppercase tracking-wide text-sand-700
  bg-sand-50 border border-sand-200 rounded-sm px-1.5 py-0.5`. The single sand-toned
  accent that signals "optional" at a glance.
- **Row background:** subtly inset — `bg-sunken/60` (or a thin left rule
  `border-l-2 border-sand-200`) to separate from binding positions.
- **Text:** name `text-ink-soft` (not full ink), description `text-ink-muted`.
- **Prices:** rendered **in parentheses** and de-emphasised —
  `text-ink-muted tabular-nums`, e.g. `(95,00 EUR)`. This is the literal echo of the PDF.
- **Excluded from the binding total:** optional sums roll up into a separate line
  **"Summe optionaler Positionen"** under the totals block (matching the PDF),
  `text-ink-muted`, never added to *Gesamtbetrag brutto*.
- A faint dotted divider (`border-dashed border-line`) may group the optional block.

Result: optional items look like a quiet aside — lighter ink, parenthesised price, a
single sand chip — exactly the refined, "you could add this" tone of the paper quote.

---

## 5. Motion

Minimal and quick; motion confirms, never decorates.

- **Durations:** 120ms (hover/color/background), 160ms (small enter/exit),
  200ms (drawer/modal slide & fade). Nothing over 240ms.
- **Easing:** `cubic-bezier(.2,.0,.2,1)` (standard) for most; `ease-out` for enters,
  `ease-in` for exits.
- **What moves:** color/bg on hover, opacity+slight translate on drawer/modal,
  toast slide-in. Use `transition-colors` on interactive elements.
- Respect `prefers-reduced-motion: reduce` → drop translate/scale, keep instant
  opacity. No spinners where a skeleton or inline state fits.

---

## 6. AI-slop to avoid

- ❌ Purple/blue or any multi-color **gradients** (and gradient text/buttons).
- ❌ **Glassmorphism** / frosted blur panels.
- ❌ **Emoji in the UI** (labels, buttons, statuses, toasts) — use restrained icons.
- ❌ **Heavy / large / colored drop shadows** and glows; borders carry hierarchy.
- ❌ Oversized rounded "pill everything", huge radii, neon accent colors.
- ❌ Full-width hero gradients, marketing-y illustrations, stock 3D blobs.
- ❌ Centered body text, decorative fonts in UI, ALL-CAPS paragraphs.
- ❌ Sand used as body text on white (fails contrast) — sand fills, ink reads.
- ❌ Animations longer than ~240ms, bouncy spring easing, parallax.

Stay calm, paper-like, and let the data and the champagne accent do the talking.
