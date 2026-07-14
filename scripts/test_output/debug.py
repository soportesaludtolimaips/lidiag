import re, sys, os
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..'))

# Inline the needed functions
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

def find_balanced(s, pos):
    depth = 1; in_str = None; p = pos + 1
    while p < len(s):
        if in_str:
            if s[p] == '\\': p += 2; continue
            if s[p] == in_str: in_str = None
            p += 1; continue
        if s[p] in ("'", '"', '`'): in_str = s[p]
        elif s[p] == '{': depth += 1
        elif s[p] == '}': depth -= 1
        if depth == 0: return p + 1
        p += 1
    return p

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

def get_key_section(obj, key):
    m = re.search(r'(?:^|[,\s])\s*' + re.escape(key) + r'\s*(?::|\()', obj)
    if not m:
        return None
    sep = obj[m.end() - 1]
    start_pos = m.end()
    if sep == '(':
        start_pos = m.end() - 1
    val, _ = get_value_at(obj, start_pos)
    return val.strip() if val else None

def get_data_keys(data_val):
    result = {}
    if not data_val:
        return result
    m = re.search(r'return\s*(\{)', data_val)
    if not m:
        return result
    start = m.start(1)
    end = find_balanced(data_val, start)
    inner = data_val[start + 1:end - 1]
    pos = 0
    while pos < len(inner):
        km = re.compile(r'(\w+)\s*:').search(inner, pos)
        if not km:
            break
        key = km.group(1)
        val, next_pos = get_value_at(inner, km.end())
        if val is None:
            pos = next_pos
            continue
        result[key] = val
        pos = next_pos
        pos = skip_ws_and_comments(inner, pos)
        if pos < len(inner) and inner[pos] == ',':
            pos += 1
    return result

with open('resources/js/components/modulos/ecografias/admisionarComponent.vue.bak', 'r', encoding='utf-8') as f:
    data = f.read()

sm = re.search(r'<script>(.*?)</script>', data, re.DOTALL)
script = sm.group(1)
ex = re.search(r'export\s+default\s*', script)
obj = script[ex.end():]

data_val = get_key_section(obj, 'data')
print('data_val exists:', data_val is not None)
if data_val:
    print('data_val[:100]:', repr(data_val[:100]))
    print('data_val[-50:]:', repr(data_val[-50:]))

keys = get_data_keys(data_val)
print('\nKeys:', list(keys.keys()))
for k, v in keys.items():
    if 'sede' in k or 'medico' in k:
        print(f'  {k}: {repr(v)} len={len(v)}')
