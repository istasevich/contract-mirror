# ContractMirror MVP update

Included in this archive:

- PDF text extraction with `pdftotext` support and fallback parsing
- DOCX text extraction via `ZipArchive`
- TXT extraction cleanup
- file validation (PDF/DOCX/TXT, max 10 MB)
- safer OpenAI client error handling
- live landing-page report renderer
- simple/detailed report views
- loading and error states in UI
- nginx config included from docker archive

Notes:

- `pdftotext` is installed in Dockerfile for better PDF extraction quality.
- This source archive does not add billing/auth/history yet; it focuses on demo-ready MVP polish.
- If the original repository has a `composer.json`, keep using it from your repo root.
