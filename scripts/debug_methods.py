import re, sys

def find_balanced(s, start):
    assert s[start] in '{['
    close = {'{': '}', '[': ']'}[s[start]]
    d = 1
    pos = start + 1
    in_str = None
    while pos < len(s) and d > 0:
        if in_str:
            if s[pos] == '\\': pos += 2; continue
            if s[pos] == in_str: in_str = None
            pos += 1; continue
        if s[pos] in ("'", '"', '`'): in_str = s[pos]; pos += 1; continue
        if s[pos] == s[start]: d += 1
        elif s[pos] == close: d -= 1
        pos += 1
    return pos

def skip_ws_and_comments(s, pos):
    while pos < len(s):
        c = s[pos]
        if c in ' \t\n\r': pos += 1
        elif c == '/' and pos + 1 < len(s) and s[pos + 1] == '/':
            eol = s.find('\n', pos); pos = eol + 1 if eol >= 0 else len(s)
        elif c == '/' and pos + 1 < len(s) and s[pos + 1] == '*':
            end = s.find('*/', pos + 2); pos = end + 2 if end >= 0 else len(s)
        else: break
    return pos

with open('resources/js/components/modulos/configuracion/configConsentimientoInformadoComponent.vue') as f:
    c = f.read()
sm = re.search(r'<script>(.*?)</script>', c, re.DOTALL)
s = sm.group(1)
m = re.search(r'export\s+default\s*(\{)', s)
start = m.start(1)
end = find_balanced(s, start)
obj = s[start:end]

# find methods
mm = re.search(r'(?:^|[\s,]+)methods\s*:\s*(\{)', obj)
if not mm:
    print("NO METHODS")
    sys.exit(1)
ms = mm.start(1)
me = find_balanced(obj, ms)
inner = obj[ms + 1: me - 1]

print(f"Methods inner length: {len(inner)}")
print(f"First 200: {inner[:200]}")

# Scan for mostrarRegistro
idx = inner.find('mostrarRegistro')
if idx < 0:
    print("mostrarRegistro NOT FOUND in methods")
    sys.exit(1)
print(f"\nmostrarRegistro at inner[{idx}]")
print(f"Context: {inner[idx:idx+80]}")

# Trace depth at idx
depth = 0
in_str = None
for i in range(0, idx):
    if in_str:
        if inner[i] == '\\': continue
        if inner[i] == in_str: in_str = None
        continue
    if inner[i] in ("'", '"', '`'): in_str = inner[i]; continue
    if inner[i] == '{': depth += 1
    elif inner[i] == '}': depth -= 1
print(f"Depth at mostrarRegistro: {depth}")

# Now extract it
pattern = re.compile(r'(?:async\s+)?(\w+)\s*\(')
pos = idx
m = pattern.search(inner, pos)
if m:
    name = m.group(1)
    print(f"Matched name: {name}")
    print(f"m.start(): {m.start()}, m.end(): {m.end()}")
    
    # Skip past params
    ppos = m.end()
    pdepth = 1
    in_str = None  
    while ppos < len(inner) and pdepth > 0:
        if in_str:
            if inner[ppos] == '\\': ppos += 2; continue
            if inner[ppos] == in_str: in_str = None
            ppos += 1; continue
        if inner[ppos] in ("'", '"', '`'): in_str = inner[ppos]
        elif inner[ppos] == '(': pdepth += 1
        elif inner[ppos] == ')': pdepth -= 1
        ppos += 1
    
    print(f"After params, ppos={ppos}, char='{inner[ppos]}'")
    ppos = skip_ws_and_comments(inner, ppos)
    print(f"After ws, ppos={ppos}, char='{inner[ppos]}'")
    
    if ppos < len(inner) and inner[ppos] == '{':
        mend = find_balanced(inner, ppos)
        body = inner[m.start():mend]
        print(f"Body len: {len(body)}")
        print(f"Body: {body[:200]}")
    else:
        print(f"Expected '{{' at ppos but found '{inner[ppos]}'")
