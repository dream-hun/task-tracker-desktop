---
paths:
  - 'resources/views/documents/**'
---

# Documents

## The document PDF is dompdf, so its Blade cannot use Tailwind
`documents/pdf.blade.php` is rendered by dompdf (`RenderDocumentPdf`), which only understands CSS 2.1. It carries its own inline stylesheet on purpose: no Tailwind, no flex/grid, no `oklch()` colours — tables, borders and margins only. `@page` sets the margins, `thead` repeats across pages, and `page-break-inside: avoid` keeps rows and the totals block whole.

Keep `defaultFont` on "DejaVu Sans": it is what makes accented client names and currency glyphs render instead of turning into boxes. Amounts print as `EUR 1,234.56` (code + `number_format`) rather than locale symbols, so nothing depends on ext-intl or on a glyph dompdf's font may lack.

The wording that differs per type (`Invoice`/`Quotation`, `Due date`/`Valid until`, `Amount due`/`Quoted total`) comes from `DocumentType::label()`, `dueDateLabel()` and `totalLabel()`, mirroring `documentWording` in `resources/js/lib/documents.ts`. Change one, change the other.
