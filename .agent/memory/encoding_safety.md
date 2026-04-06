# Encoding Safety Note

## Problem
Several admin UI strings in `src/Controllers/AdminController.php` were corrupted because UTF-8 source text was round-tripped through non-UTF-8 handling in the shell/editor pipeline.

## Root Cause
- PHP source files must stay UTF-8.
- Avoid reading/writing them through ANSI/CP1252 or any implicit terminal encoding conversion.
- Mixed edits across multiple passes can leave the file syntactically valid but textually broken.

## Prevention Rules
1. Edit PHP source with `apply_patch` or another UTF-8-safe editor only.
2. Never normalize source text through PowerShell byte conversions unless the encoding is explicitly controlled.
3. After large text edits, verify the file content by searching for mojibake markers such as `Ã`, `Ä`, `Æ`, `ï¿½`.
4. Run `php -l` after every edit batch.
5. If UI text still appears stale after a source fix, invalidate opcache or restart PHP/Laragon before assuming the source is still broken.

## Recovery Checklist
- Restore the affected file to pure UTF-8.
- Replace the corrupted strings directly, not through a full-file encoding roundtrip.
- Recheck the file content, not just syntax.
