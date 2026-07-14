import re, sys

path = sys.argv[1]
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

sm = re.search(r'<script>(.*?)</script>', content, re.DOTALL)
script = sm.group(1)

# Find export default object
m = re.search(r'export\s+default\s*(\{)', script)
start = m.start(1)
d = 1
pos = start + 1
in_str = None
while pos < len(script) and d > 0:
    if in_str:
        if script[pos] == '\\': pos += 2; continue
        if script[pos] == in_str: in_str = None
        pos += 1; continue
    if script[pos] in ("'", '"', '`'): in_str = script[pos]; pos += 1; continue
    if script[pos] == '{': d += 1
    elif script[pos] == '}': d -= 1
    pos += 1
obj = script[start:pos]

# Find computed
cm = re.search(r'computed\s*:', obj)
if cm:
    p = cm.end()
    while p < len(obj) and obj[p] in ' \t\n\r': p += 1
    print(f"char at computed end: '{obj[p]}' (ord {ord(obj[p])})")
    if obj[p] == '{':
        d = 1
        pp = p + 1
        in_s = None
        while pp < len(obj) and d > 0:
            if in_s:
                if obj[pp] == '\\': pp += 1
                elif obj[pp] == in_s: in_s = None
                pp += 1; continue
            if obj[pp] in ("'", '"', '`'): in_s = obj[pp]; pp += 1; continue
            if obj[pp] == '{': d += 1
            elif obj[pp] == '}': d -= 1
            pp += 1
        comp_val = obj[p:pp]
        print(f"comp_val[:150]: {comp_val[:150]}")
        print(f"comp_val starts with: {repr(comp_val[:5])}")
        
        # Now try the regex on inner
        inner = comp_val[1:-1] if comp_val.startswith('{') and comp_val.endswith('}') else comp_val
        offset = len(comp_val) - len(inner)
        print(f"offset: {offset}")
        
        matches = list(re.finditer(r'(\w+)\s*\(\s*\)\s*\{', inner))
        print(f"matches: {len(matches)}")
        for m in matches:
            name = m.group(1)
            brace_start = m.end() - 1 + offset
            print(f"  computed '{name}': brace_start={brace_start}, comp_val[{brace_start}]='{comp_val[brace_start]}'")
            # Try find_balanced
            close = '{'
            dd = 1
            ppp = brace_start + 1
            in_ss = None
            while ppp < len(comp_val) and dd > 0:
                if in_ss:
                    if comp_val[ppp] == '\\': ppp += 2; continue
                    if comp_val[ppp] == in_ss: in_ss = None
                    ppp += 1; continue
                if comp_val[ppp] in ("'", '"', '`'): in_ss = comp_val[ppp]; ppp += 1; continue
                if comp_val[ppp] == '{': dd += 1
                elif comp_val[ppp] == '}': dd -= 1
                ppp += 1
            prop_text = comp_val[m.start() + offset:ppp].rstrip(',').strip()
            print(f"  extracted: {prop_text[:100]}")
    else:
        print(f"Expected '{{' but found '{obj[p]}'")
else:
    print("No computed section found")
