# Live preview — manual QA checklist

## GeneratePress / GenerateBlocks

- Open a page template detail and a section template detail with live preview enabled.
- Confirm the iframe loads themed output (fonts, spacing) consistent with the active theme.
- Toggle desktop / tablet / mobile; confirm the preview width changes without breaking scroll.
- Enable “Reduced motion” and confirm the URL adds `reduced_motion=1` and preview respects the flag where applicable.

## iframe render

- Confirm loading text clears after the iframe `load` event.
- Confirm “Open in new tab” opens the same preview URL in a new tab.
- Confirm “Regenerate preview” reloads the admin page and issues a new opaque ticket.

## Expired ticket

- Wait past ticket TTL (default 10 minutes) or consume both allowed loads; confirm the iframe shows the branded error document with a reason code (not a blank screen).

## Open in new tab

- From the detail screen, open in new tab; confirm the preview still requires an authenticated session and valid ticket behavior.

## Caching plugins

- With a page-cache plugin active, confirm preview responses are not cached (no-store headers) and the preview still updates after regeneration.
- If a CDN is in front of the origin, confirm `Surrogate-Control` / `CDN-Cache-Control` bypass behavior matches expectations for the preview URL only.

## Multisite (if applicable)

- Issue preview on site A; confirm the ticket is rejected on site B (wrong blog).
