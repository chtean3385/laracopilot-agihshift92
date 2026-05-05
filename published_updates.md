# Published Updates Log

Track every production publish: date, checkpoint commit, and what changed.
See `replit.md` → "Deployment Checklist" for pre-publish steps.

---

## Format

```
## YYYY-MM-DD — <short title>
- **Checkpoint:** <commit SHA or tag>
- **Published by:** Dreams Technology / agent
- **URL:** resort.dreamstechnology.in
- **What changed:**
  - …
- **Smoke tested:** yes / no
- **Notes:** …
```

---

## 2026-05-05 — Client Proposal PDF + PPTX (Task #122)

- **Checkpoint:** `9cce1eb8fb12fa41337424f421810dd469be2515`
- **Published by:** agent (mark_task_complete)
- **URL:** resort.dreamstechnology.in / public/proposal.html
- **What changed:**
  - Built 12-slide client proposal at `public/proposal.html`
  - 9 real CRM screenshots added to `public/proposal-assets/`
  - PDF export: browser print-to-PDF with full navy/gold design (`print-color-adjust:exact` fix)
  - PPTX export via PptxGenJS CDN
  - Yearly-only pricing (Basic ₹5,999 · Standard ₹7,999 · Premium ₹11,999 · Pro AI ₹19,999)
  - Contact: +91 97252 25519 · sales@dreamstechnology.in
  - Slide order: Cover → Challenge → Solution → Front Desk → Restaurant → WhatsApp → Reports → Getting Started → Pricing → Why Choose Us → Screenshots → CTA
- **Smoke tested:** yes (HTML renders, PDF prints with full color, PPTX downloads)
- **Notes:** Delivered as standalone HTML (slides artifact not supported). Follow-up tasks #123 (A4 flyer) and #124 (protected /proposal?token= route) queued.
