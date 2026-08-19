---
paths:
  - 'tests/**'
---

# Tests

## Pass '8bit' to mb_* when parsing binary in tests
pint.json enables `mb_str_functions`, so Pint rewrites `substr`/`strlen` into `mb_substr`/`mb_strlen` everywhere — including code that walks binary formats. The multibyte versions count UTF-8 characters, not bytes, so offsets into binary silently shift (on public/icon.ico, mb_strlen reports 6346 vs the real 6615 bytes, and the extracted frame hashes come out wrong).

Always pass the `'8bit'` encoding argument: `mb_substr($binary, $offset, $length, '8bit')`, `mb_strlen($binary, '8bit')`. That is byte-exact and survives Pint. See tests/Unit/DesktopIconTest.php.
