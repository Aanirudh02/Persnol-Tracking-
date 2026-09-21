# Odometer & Mileage Module - CSS Architecture & Styling Logic

This document details the complete CSS styling logic, color palette, layout structure, and responsive design patterns used in the **Odometer Reading & Mileage Cycles** module.

---

## 1. Color Palette & Hierarchy

The module follows a premium, dark-indigo aesthetic for active cycle tracking paired with a crisp slate background that seamlessly supports both Light and Dark modes.

| Concept / State | Tailwind Utility Classes | Hex / Visual Tone | Purpose |
|---|---|---|---|
| **Active Cycle Card** | `bg-gradient-to-br from-indigo-900 via-slate-900 to-indigo-950 text-white` | Deep Midnight Indigo (`#1e1b4b` &rarr; `#0f172a`) | Premium focal element highlighting the ongoing fuel cycle |
| **Card Borders** | `border border-indigo-800/60` and `border border-white/10` | Translucent Indigo / White | Glassmorphism contrast against dark surfaces |
| **Source Reading (Start)** | `bg-emerald-500/20 text-emerald-300 border border-emerald-500/40` | Emerald Green (`#10b981`) | Visual anchor for initial refuel / start of cycle |
| **Intermediate Leg** | `bg-sky-500/20 text-sky-300 border border-sky-500/40` | Sky Blue (`#0284c7`) | Distinguishes daily transit legs |
| **Ending Reading (Refuel)** | `bg-amber-500/20 text-amber-300 border border-amber-500/40` | Warm Amber (`#f59e0b`) | Signals tank refill and cycle completion |
| **Action Buttons (Edit)** | `hover:bg-white/20 text-indigo-200 hover:text-white` | Translucent white hover | Smooth, non-destructive editing interaction |
| **Action Buttons (Delete)** | `hover:bg-rose-500/20 text-rose-400 hover:text-rose-200` | Rose / Coral Red (`#f43f5e`) | Immediate warning cue for destructive removal |

---

## 2. Layout Structure & Responsive Grids

### A. Summary Metrics Row
```html
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
    <!-- Metric cards: Latest Odometer, Total Tracked, Avg Mileage, Best Mileage -->
</div>
```
- **Mobile (`<640px`)**: Compact 2-column grid (`grid-cols-2`).
- **Desktop (`>=640px`)**: 4-column balanced dashboard layout (`sm:grid-cols-4`).

### B. Cycle Timeline Item Layout
```html
<div class="p-3 rounded-xl bg-white/10 hover:bg-white/15 border border-white/10 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs transition">
    <!-- Left: Reading Badge (S/L/E) + Route + Date/Time + Speed -->
    <!-- Right: Odometer KM + Leg Delta (+X.X km) + Photo + Edit Button + Delete Form -->
</div>
```
- **Hover micro-interaction**: `hover:bg-white/15` provides instant interactive feedback on each leg.
- **Badge**: 32x32px squircle (`w-8 h-8 rounded-lg flex items-center justify-center font-bold`).
- **Action Group**: Inline flex alignment (`flex items-center gap-3 self-end sm:self-center`) ensuring the photo, edit, and delete icons stay properly spaced without wrapping.

---

## 3. Dialog Modal Styling Logic

The edit dialog modal uses the standard application modal pattern:

```html
<div id="edit-reading-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto" onclick="if(event.target === this) closeEditReadingModal()">
    <div class="relative w-full max-w-lg bg-white dark:bg-slate-900 rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden my-8" onclick="event.stopPropagation()">
        <!-- Header, Form Inputs, Actions -->
    </div>
</div>
```

1. **Backdrop Blur & Dimming**:
   - `fixed inset-0 z-50`: Positions modal above all layout elements.
   - `bg-black/60 backdrop-blur-sm`: 60% black mask with CSS blur filter.
   - Click-outside-to-close behavior via `if(event.target === this) closeEditReadingModal()`.

2. **Modal Card**:
   - `rounded-3xl shadow-2xl`: Deep elevation shadow with soft 24px corner radius.
   - `border border-slate-100 dark:border-slate-800`: Subtle outline defining the boundary in dark mode.

3. **Form Inputs**:
   - `rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800`: Cohesive input styling matching the app's design language.
   - `focus:ring-2 focus:ring-indigo-500 focus:outline-none`: Accessible high-contrast focus indicator.
   - Native number spinner suppression via global CSS (`appearance: textfield`).

4. **Action Buttons**:
   - Cancel button: `rounded-xl border border-slate-200 dark:border-slate-700 hover:bg-slate-100`.
   - Submit button: `rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-md shadow-indigo-600/20`.

---

## 4. Mileage Efficiency Pill Logic

Calculated mileage (`km/L`) displays colored status pills based on automotive fuel-efficiency thresholds:
- **$\ge 45\text{ km/L}$**: `bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300` (Excellent economy)
- **$35 - 44.9\text{ km/L}$**: `bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300` (Average commuter range)
- **$< 35\text{ km/L}$**: `bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300` (High consumption / heavy traffic)
