---
paths:
  - app/Services/GenerateAssetCardQrCode.php
---

# Services

## Asset QR encodes the incident report URL, not the asset code
execute() renders reportUrl() = route('tickets.create', ['asset' => code]) so scanning opens the report form with the asset pre-resolved. QR SVGs generated before REQ-12 still encode the raw code; regenerate them if existing printed codes must open the report form.
