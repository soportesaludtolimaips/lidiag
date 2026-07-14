import re, sys, os

def skip_ws_and_comments(s, pos):
    while pos < len(s):
        if s[pos] in ' \t\n\r':
            pos += 1
        elif s[pos:pos+2] == '//':
            eol = s.find('\n', pos)
            pos = eol + 1 if eol >= 0 else len(s)
        elif s[pos:pos+2] == '/*':
            end = s.find('*/', pos + 2)
            pos = end + 2 if end >= 0 else len(s)
        else:
            break
    return pos

def find_balanced(s, start):
    assert s[start] in '{['
    close = {'{': '}', '[': ']'}[s[start]]
    d = 1; pos = start + 1; in_str = None
    while pos < len(s) and d > 0:
        if in_str:
            if s[pos] == '\\': pos += 2; continue
            if s[pos] == in_str: in_str = None
            pos += 1; continue
        if s[pos] in ("'", '"', '`'): in_str = s[pos]
        elif s[pos] == s[start]: d += 1
        elif s[pos] == close: d -= 1
        pos += 1
    return pos

def get_value_at(obj, pos):
    pos = skip_ws_and_comments(obj, pos)
    if pos >= len(obj): return None, pos
    c = obj[pos]
    if c == '{':
        end = find_balanced(obj, pos)
        return obj[pos:end], end
    if c == '[':
        end = find_balanced(obj, pos)
        return obj[pos:end], end
    if c == '(':
        pdepth = 1; p = pos + 1; in_str = None
        while p < len(obj) and pdepth > 0:
            if in_str:
                if obj[p] == '\\': p += 2; continue
                if obj[p] == in_str: in_str = None
                p += 1; continue
            if obj[p] in ("'", '"', '`'): in_str = obj[p]
            elif obj[p] == '(': pdepth += 1
            elif obj[p] == ')': pdepth -= 1
            p += 1
        p = skip_ws_and_comments(obj, p)
        if p < len(obj) and obj[p] == '{':
            end = find_balanced(obj, p)
            return obj[pos:end], end
        return None, p
    if c == 'f' and obj[pos:pos + 8] == 'function':
        p = skip_ws_and_comments(obj, pos + 8)
        if p < len(obj) and obj[p] == '(':
            return get_value_at(obj, p)
    if c == ']':
        return None, pos + 1
    in_str = None; start = pos
    while pos < len(obj):
        if in_str:
            if obj[pos] == '\\': pos += 2; continue
            if obj[pos] == in_str: in_str = None
            pos += 1; continue
        if obj[pos] in ("'", '"', '`'): in_str = obj[pos]; pos += 1; continue
        if obj[pos] in ',}': return obj[start:pos], pos
        pos += 1
    return obj[start:pos], pos

with open('resources/js/components/modulos/ecografias/admisionarComponent.vue.bak', 'r', encoding='utf-8') as f:
    data = f.read()

sm = re.search(r'<script>(.*?)</script>', data, re.DOTALL)
script = sm.group(1)
ex = re.search(r'export\s+default\s*', script)
obj = script[ex.end():]

# Get data_val
m = re.search(r'(?:^|[,\s])\s*data\s*(?::|\()', obj)
sep = obj[m.end() - 1]
start_pos = m.end() if sep != '(' else m.end() - 1
val, _ = get_value_at(obj, start_pos)
data_val = val.strip()

# Find return object
rm = re.search(r'return\s*(\{)', data_val)
ds = rm.start(1)
de = find_balanced(data_val, ds)
inner = data_val[ds+1:de-1]

# Find ALL occurrences of sedeActual in inner
for i, m in enumerate(re.finditer(r'(\w+)\s*:', inner)):
    if m.group(1) in ('sedeActual', 'medicoActual', 'registros'):
        print(f'  [{i}] key={m.group(1)} at pos {m.start()}, after colon: {repr(inner[m.end():m.end()+50])}')
        v, np = get_value_at(inner, m.end())
        print(f'      val={repr(v)}')

print()

# Find the FIRST top-level sedeActual in inner (not inside nested braces)
# Count depth at each position
in_str = None
depth = 0
for i, ch in enumerate(inner):
    if in_str:
        if ch == '\\': continue
        if ch == in_str: in_str = None
        continue
    if ch in ("'", '"', '`'):
        in_str = ch
    elif ch == '{': depth += 1
    elif ch == '}': depth -= 1
    
    # Check for top-level keys
    m2 = re.match(r'(\w+)\s*:', inner[i:])
    if m2 and depth == 0 and i == 0 or (depth == 0 and inner[i-1] in ' \t\n\r,'):
        key = m2.group(1)
        if key in ('sedeActual', 'medicoActual', 'registros', 'errores'):
            print(f'TOP-LEVEL key={key} at pos {i}: before: {repr(inner[i-5:i])}')
            v, np = get_value_at(inner, i + m2.end() - i)
            print(f'  val={repr(v)}')
print(f'sedeActual found at inner pos {idx}')
print(f'Context: {repr(inner[idx:idx+80])}')

# Find the ':' after sedeActual
km = re.compile(r'(\w+)\s*:').search(inner, idx)
print(f'key match: start={km.start()}, end={km.end()}, group={km.group()}')

vpos = km.end()
print(f'km.end() char: {repr(inner[vpos])}')

vpos2 = skip_ws_and_comments(inner, km.end())
print(f'After ws-skip: pos={vpos2}, char={repr(inner[vpos2])}')
print(f'Context after colon: {repr(inner[km.end():km.end()+100])}')

# Manually track what happens in get_value_at
val, next_pos = get_value_at(inner, km.end())
print(f'\nget_value_at result: val={repr(val)}')
print(f'next_pos={next_pos}')
print(f'char at next_pos: {repr(inner[next_pos] if next_pos < len(inner) else "EOF")}')

# Debug the simple value scan
start = vpos2
in_str = None
pos = start
steps = []
while pos < len(inner):
    if in_str:
        if inner[pos] == '\\': pos += 2; continue
        if inner[pos] == in_str: in_str = None
        pos += 1; continue
    if inner[pos] in ("'", '"', '`'):
        in_str = inner[pos]
        steps.append((pos, f'STR({repr(inner[pos])})'))
        pos += 1
        continue
    if inner[pos] in ',}':
        steps.append((pos, f'STOP({repr(inner[pos])})'))
        break
    if len(steps) < 15 and inner[pos] not in ' \t\n\r':
        steps.append((pos, inner[pos]))
    pos += 1

final_val = inner[start:pos]
print(f'\nDebug scan from {start} to {pos}')
for s in steps[:20]:
    print(f'  pos {s[0]} ({s[0]-start}): {repr(s[1])}')

print(f'\nFinal value: {repr(final_val)}')
print(f'Value ends with ) : {final_val.endswith(")")}')
print(f'Position of comma after value: {pos}')
print(f'context at pos: {repr(inner[pos:pos+10])}')
