"""
Repair corrupt build plan JSON where section_guidance was written as
\"section_guidance\":\"[{...}]\" with unescaped inner quotes (invalid JSON).

Usage:
  python repair_plan_section_guidance_json.py broken.json fixed.json

Apply fixed.json to WordPress (after backup): prefer Build_Plan_Repository::save_plan_definition
from a decoded array. If update_post_meta truncates the blob in your environment, use a direct
$wpdb INSERT of wp_slash( json ) after DELETE for that post_id and meta key.

Reads input path, writes output path. Exits non-zero if output is not valid JSON.
"""

from __future__ import annotations

import json
import sys


def extract_top_json_array(s: str, start: int) -> str:
    if start >= len(s) or s[start] != "[":
        raise ValueError("expected [")
    depth = 0
    in_string = False
    escape = False
    for i in range(start, len(s)):
        c = s[i]
        if in_string:
            if escape:
                escape = False
            elif c == "\\":
                escape = True
            elif c == '"':
                in_string = False
        else:
            if c == '"':
                in_string = True
            elif c == "[":
                depth += 1
            elif c == "]":
                depth -= 1
                if depth == 0:
                    return s[start : i + 1]
    raise ValueError("unclosed array")


def repair_section_guidance(s: str) -> str:
    key = '"section_guidance":"'
    i = 0
    while True:
        pos = s.find(key, i)
        if pos < 0:
            break
        val_start = pos + len(key)
        if val_start >= len(s) or s[val_start] != "[":
            i = pos + 1
            continue
        inner = extract_top_json_array(s, val_start)
        close_quote = val_start + len(inner)
        if close_quote >= len(s) or s[close_quote] != '"':
            raise ValueError("expected closing quote after section_guidance array")
        new_frag = '"section_guidance":' + json.dumps(inner)
        s = s[:pos] + new_frag + s[close_quote + 1 :]
        i = pos + len(new_frag)
    return s


def main() -> int:
    if len(sys.argv) != 3:
        print("usage: repair_plan_section_guidance_json.py in.json out.json", file=sys.stderr)
        return 2
    inp, outp = sys.argv[1], sys.argv[2]
    raw = open(inp, encoding="utf-8").read()
    fixed = repair_section_guidance(raw)
    json.loads(fixed)
    open(outp, "w", encoding="utf-8").write(fixed)
    print("ok", outp, "len", len(fixed))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
