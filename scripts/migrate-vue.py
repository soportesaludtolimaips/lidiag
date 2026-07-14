#!/usr/bin/env python3
"""
migrate-vue.py: Transform Vue Options API to Composition API (<script setup>).

Usage:
    python scripts/migrate-vue.py <file.vue> [--dry-run]
"""

import sys
import os
import re
import shutil


def find_balanced(s, start):
    """Return end index (exclusive) of balanced delimiters.
    Handles {}, [], and strings within."""
    assert s[start] in '{['
    close = {'{': '}', '[': ']'}[s[start]]
    d = 1
    pos = start + 1
    in_str = None
    while pos < len(s) and d > 0:
        if in_str:
            if s[pos] == '\\':
                pos += 2
                continue
            if s[pos] == in_str:
                in_str = None
            pos += 1
            continue
        if s[pos] in ("'", '"', '`'):
            in_str = s[pos]
            pos += 1
            continue
        if s[pos] == s[start]:
            d += 1
        elif s[pos] == close:
            d -= 1
        pos += 1
    return pos


def skip_ws_and_comments(s, pos):
    """Skip whitespace, // and /* */ comments."""
    while pos < len(s):
        c = s[pos]
        if c in ' \t\n\r':
            pos += 1
        elif c == '/' and pos + 1 < len(s) and s[pos + 1] == '/':
            eol = s.find('\n', pos)
            pos = eol + 1 if eol >= 0 else len(s)
        elif c == '/' and pos + 1 < len(s) and s[pos + 1] == '*':
            end = s.find('*/', pos + 2)
            pos = end + 2 if end >= 0 else len(s)
        else:
            break
    return pos


def parse_export_default(text):
    """Return (content, start, end) of export default {...} or None."""
    m = re.search(r'export\s+default\s*(\{)', text)
    if not m:
        return None
    start = m.start(1)
    end = find_balanced(text, start)
    return text[start:end], start, end


def get_value_at(obj, pos):
    """Starting from pos (past ':' and ws), extract the complete value expression.
    Returns (value_string, end_pos) or None."""
    pos = skip_ws_and_comments(obj, pos)
    if pos >= len(obj):
        return None, pos
    c = obj[pos]
    if c == '{':
        end = find_balanced(obj, pos)
        return obj[pos:end], end
    if c == '[':
        end = find_balanced(obj, pos)
        return obj[pos:end], end
    if c == '(':
        # Method shorthand: data() { ... } or data: function() { ... }
        # Skip past (...) to find {
        pdepth = 1
        p = pos + 1
        in_str = None
        while p < len(obj) and pdepth > 0:
            if in_str:
                if obj[p] == '\\':
                    p += 2
                    continue
                if obj[p] == in_str:
                    in_str = None
                p += 1
                continue
            if obj[p] in ("'", '"', '`'):
                in_str = obj[p]
            elif obj[p] == '(':
                pdepth += 1
            elif obj[p] == ')':
                pdepth -= 1
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
    # Simple value (string, number, boolean, identifier)
    in_str = None
    start = pos
    while pos < len(obj):
        if in_str:
            if obj[pos] == '\\':
                pos += 2
                continue
            if obj[pos] == in_str:
                in_str = None
            pos += 1
            continue
        if obj[pos] in ("'", '"', '`'):
            in_str = obj[pos]
            pos += 1
            continue
        if obj[pos] in ',}':
            return obj[start:pos], pos
        pos += 1
    return obj[start:pos], pos


def get_key_section(obj, key):
    """Extract the value for a top-level key in an object literal."""
    m = re.search(r'(?:^|[\s,])\s*' + re.escape(key) + r'\s*(?::|\()', obj)
    if not m:
        return None
    sep = obj[m.end() - 1]
    start_pos = m.end()
    if sep == '(':
        start_pos = m.end() - 1
    val, _ = get_value_at(obj, start_pos)
    return val.strip() if val else None


def get_data_keys(data_val):
    """Parse data() return object. Returns dict: key -> val string."""
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


def get_top_level_hooks(obj):
    """Get lifecycle hooks at top level of export default. Returns dict name->body."""
    hooks = {}
    hook_names = [
        'mounted', 'beforeUnmount', 'created', 'beforeMount',
        'beforeCreate', 'unmounted', 'activated', 'deactivated',
        'updated', 'beforeUpdate'
    ]
    for name in hook_names:
        pattern = r'(?:^|[\s,]+)((?:async\s+)?' + re.escape(name) + r'\s*(?::\s*(?:async\s+)?function\s+)?\(\s*\)\s*\{)'
        m = re.search(pattern, obj)
        if m:
            hook_start = m.start(1)
            brace_pos = m.end() - 1
            end = find_balanced(obj, brace_pos)
            hooks[name] = obj[hook_start:end]
    return hooks


def get_methods(obj):
    """Extract methods: {...} block and parse each method."""
    m = re.search(r'(?:^|[\s,]+)methods\s*:\s*(\{)', obj)
    if not m:
        return {}
    start = m.start(1)
    end = find_balanced(obj, start)
    inner = obj[start + 1: end - 1]
    if not inner.strip():
        return {}

    entries = {}
    # Find method definitions at depth 0
    # A method looks like: [async] name(params) { ... }
    # We must only match at depth 0 (not inside another method's body)
    pattern = re.compile(r'(?:async\s+)?(\w+)\s*\(')
    pos = 0
    methods_found = []

    while pos < len(inner):
        # Compute brace depth at pos
        depth = 0
        in_str = None
        for i in range(0, pos):
            if in_str:
                if inner[i] == '\\':
                    continue
                if inner[i] == in_str:
                    in_str = None
                continue
            if inner[i] in ("'", '"', '`'):
                in_str = inner[i]
                continue
            if inner[i] == '{':
                depth += 1
            elif inner[i] == '}':
                depth -= 1

        m = pattern.search(inner, pos)
        if not m:
            break

        if depth == 0:
            raw_name = m.group(1)
            base_start = m.start()

            # Handle "name: function(params)" old-style syntax
            actual_name = raw_name
            is_old_style = False
            if raw_name == 'function':
                pre = inner[:base_start]
                pre_m = re.search(r'(\w+)\s*:\s*$', pre)
                if pre_m:
                    actual_name = pre_m.group(1)
                    is_old_style = True
                    base_start = pre_m.start(1)

            # Skip past (...)
            ppos = m.end()
            pdepth = 1
            in_str = None
            while ppos < len(inner) and pdepth > 0:
                if in_str:
                    if inner[ppos] == '\\':
                        ppos += 2
                        continue
                    if inner[ppos] == in_str:
                        in_str = None
                    ppos += 1
                    continue
                if inner[ppos] in ("'", '"', '`'):
                    in_str = inner[ppos]
                elif inner[ppos] == '(':
                    pdepth += 1
                elif inner[ppos] == ')':
                    pdepth -= 1
                ppos += 1
            ppos = skip_ws_and_comments(inner, ppos)
            if ppos < len(inner) and inner[ppos] == '{':
                mend = find_balanced(inner, ppos)
                if is_old_style:
                    params = inner[m.end():ppos].strip().strip('()').strip()
                    body = f"{actual_name}({params})" + inner[ppos:mend]
                else:
                    body = inner[base_start:mend]
                methods_found.append((actual_name, base_start, body))
                pos = mend
            else:
                pos = ppos
        else:
            pos = m.start() + 1

    for name, _, body in methods_found:
        body = body.strip().rstrip(',').strip()
        # Extract params and async flag
        params = ''
        is_async = False
        pm = re.match(r'((?:async\s+)?)' + re.escape(name) + r'\s*\(([^)]*)\)', body)
        if pm:
            is_async = bool(pm.group(1).strip())
            params = pm.group(2).strip()
        entries[name] = {'body': body, 'params': params, 'async': is_async}

    return entries


def get_prop_names(props_val):
    """Extract prop names from array ['a','b'] or object {a:{}, b:{}} style."""
    if not props_val:
        return []
    if props_val.startswith('['):
        return re.findall(r"'([^']+)'", props_val)
    if props_val.startswith('{'):
        names = []
        pos = 1
        inner = props_val
        while pos < len(inner) - 1:
            m = re.compile(r'(\w+)\s*:').search(inner, pos)
            if not m:
                break
            names.append(m.group(1))
            pos = m.end()
            if pos < len(inner) and inner[pos] == '{':
                pos = find_balanced(inner, pos)
            else:
                pos = skip_ws_and_comments(inner, pos)
            if pos < len(inner) and inner[pos] == ',':
                pos += 1
                pos = skip_ws_and_comments(inner, pos)
            else:
                break
        return names
    return []


def replace_this(text, data_keys, computed_keys, method_names, hook_names, prop_names):
    """Convert this.xxx references to appropriate forms."""
    for k in data_keys:
        text = re.sub(r'(?<!\w)this\.' + re.escape(k) + r'(?!\w)', k + '.value', text)
    for k in computed_keys:
        text = re.sub(r'(?<!\w)this\.' + re.escape(k) + r'(?!\w)', k + '.value', text)
    for pn in prop_names:
        text = re.sub(r'(?<!\w)this\.' + re.escape(pn) + r'(?!\w)', 'props.' + pn, text)
    text = re.sub(r'\$emit\(', 'emit(', text)
    text = re.sub(r'\$nextTick\(', 'nextTick(', text)
    text = re.sub(r'(?<!\w)\$refs\.(\w+)', r'\1.value', text)
    text = re.sub(r'\$forceUpdate\(\)', '', text)
    for n in method_names:
        text = re.sub(r'(?<!\w)this\.' + re.escape(n) + r'\(', n + '(', text)
    for n in hook_names:
        text = re.sub(r'(?<!\w)this\.' + re.escape(n) + r'\(', n + '(', text)
    text = re.sub(r'(?<!\w)this\.', '', text)
    return text


def get_hook_body(func_str, func_name):
    """Extract inner body content from a lifecycle hook."""
    paren_start = func_str.find('(')
    if paren_start < 0:
        return func_str.strip()
    depth = 1
    pos = paren_start + 1
    in_str = None
    while pos < len(func_str) and depth > 0:
        if in_str:
            if func_str[pos] == '\\': pos += 2; continue
            if func_str[pos] == in_str: in_str = None
            pos += 1; continue
        if func_str[pos] in ("'", '"', '`'):
            in_str = func_str[pos]
        elif func_str[pos] == '(': depth += 1
        elif func_str[pos] == ')': depth -= 1
        pos += 1
    if depth > 0:
        return func_str.strip()
    while pos < len(func_str) and func_str[pos] in ' \t\n\r':
        pos += 1
    if pos < len(func_str) and func_str[pos] == '{':
        body_end = find_balanced(func_str, pos)
        return func_str[pos + 1:body_end - 1].strip()
    return func_str.strip()


def get_function_body(func_str, func_name):
    """Strip function signature, return just the inner body."""
    paren_start = func_str.find('(')
    if paren_start < 0:
        return func_str
    depth = 1
    pos = paren_start + 1
    in_str = None
    while pos < len(func_str) and depth > 0:
        if in_str:
            if func_str[pos] == '\\': pos += 2; continue
            if func_str[pos] == in_str: in_str = None
            pos += 1; continue
        if func_str[pos] in ("'", '"', '`'):
            in_str = func_str[pos]
        elif func_str[pos] == '(':
            depth += 1
        elif func_str[pos] == ')':
            depth -= 1
        pos += 1
    if depth > 0:
        return func_str
    while pos < len(func_str) and func_str[pos] in ' \t\n\r':
        pos += 1
    if pos < len(func_str) and func_str[pos] == '{':
        body_end = find_balanced(func_str, pos)
        return func_str[pos + 1:body_end - 1].strip()
    return func_str


def convert_vue(input_path, dry_run=False):
    with open(input_path, 'r', encoding='utf-8') as f:
        content = f.read()

    if re.search(r'<\s*script\s+setup', content):
        print(f"Already <script setup>: {input_path}")
        return

    tm = re.search(r'<template>.*</template>', content, re.DOTALL)
    if not tm:
        raise ValueError("No <template> found")
    template_block = tm.group()

    sm = re.search(r'<script>(.*?)</script>', content, re.DOTALL)
    if not sm:
        raise ValueError("No <script> block found")
    script_inner = sm.group(1)

    styles = re.findall(r'(<style[^>]*>.*?</style>)', content, re.DOTALL)

    imports = [line.strip() for line in script_inner.split('\n')
               if line.strip().startswith('import ')]

    parsed = parse_export_default(script_inner)
    if not parsed:
        raise ValueError("No export default found")
    obj, _, _ = parsed

    props_val = get_key_section(obj, 'props')
    prop_names = get_prop_names(props_val)
    if props_val and props_val.startswith('['):
        if prop_names:
            entry_lines = [f"    {pn}: {{}}" for pn in prop_names]
            props_val = "  {\n" + ",\n".join(entry_lines) + "\n  }"
        else:
            props_val = "{}"

    emits_val = get_key_section(obj, 'emits')
    data_val = get_key_section(obj, 'data')
    data_keys = get_data_keys(data_val) if data_val else {}

    comp_val = get_key_section(obj, 'computed')
    computed = {}
    if comp_val and comp_val != '':
        inner_comp = comp_val
        inner_offset = 0
        if inner_comp.startswith('{') and inner_comp.endswith('}'):
            inner_comp = comp_val[1:-1]
            inner_offset = 1
        pos = 0
        while pos < len(inner_comp):
            m = re.search(r'(\w+)\s*(?::\s*(?:function\s+)?)?(?:[({])', inner_comp[pos:])
            if not m:
                break
            word = m.group(1)
            after_word = inner_comp[pos + m.start():]
            sep = after_word[len(word):]
            if word == 'function' and not sep.startswith(':'):
                pos = pos + m.start() + 1
                continue
            if m.group().endswith('('):
                brace_type = '('
            else:
                brace_type = '{'
            seg_start = pos + m.start()
            inner_haystack = inner_comp[seg_start:]
            pdepth = 0
            br_start = None
            for k, ch in enumerate(inner_haystack):
                if ch in ("'", '"', '`'):
                    ks = k
                    k += 1
                    while k < len(inner_haystack):
                        if inner_haystack[k] == ch and inner_haystack[k-1] != '\\':
                            break
                        k += 1
                    continue
                if ch == '(':
                    pdepth += 1
                elif ch == ')':
                    pdepth -= 1
                    if pdepth == 0 and brace_type == '(':
                        br_start = k + 1
                        break
                elif ch == '{' and brace_type == '(' and pdepth == 0:
                    break
            if br_start is not None:
                rest = inner_haystack[br_start:]
                m2 = re.match(r'\s*\{', rest)
                if m2:
                    brace_pos = seg_start + br_start + m2.end() - 1 + inner_offset
                    end = find_balanced(comp_val, brace_pos)
                    start_in_comp = seg_start + inner_offset
                    computed[word] = comp_val[start_in_comp:end].rstrip(',').strip()
                    pos = end - inner_offset
                    continue
            pos = seg_start + 1

    hooks = get_top_level_hooks(obj)
    methods = get_methods(obj)

    # --- Generate <script setup> ---
    lines = []

    vue_imports = set()
    if hooks.get('mounted'):
        vue_imports.add('onMounted')
    if hooks.get('beforeUnmount'):
        vue_imports.add('onBeforeUnmount')
    if computed:
        vue_imports.add('computed')
    if re.search(r'\$nextTick', script_inner):
        vue_imports.add('nextTick')
    if data_keys:
        vue_imports.add('ref')

    vue_imports = sorted(vue_imports)
    if vue_imports:
        lines.append(f"import {{ {', '.join(vue_imports)} }} from 'vue';")

    non_vue = []
    for imp in imports:
        m = re.search(r"from\s+['\"]([^'\"]+)['\"]", imp)
        if m and m.group(1) != 'vue':
            non_vue.append(imp)
    if non_vue:
        lines.append('')
        lines.extend(non_vue)

    if re.search(r'\baxios\b', script_inner) and not any('axios' in imp for imp in imports):
        lines.append("import axios from 'axios';")

    if props_val:
        lines.append('')
        lines.append(f"const props = defineProps({props_val});")

    if emits_val:
        lines.append(f"const emit = defineEmits({emits_val});")

    if data_keys:
        lines.append('')
    for key, val in data_keys.items():
        lines.append(f"const {key} = ref({val});")

    if computed:
        lines.append('')
    for name, body in computed.items():
        b = get_function_body(body, name)
        b = replace_this(b, data_keys, list(computed.keys()),
                         list(methods.keys()), list(hooks.keys()), prop_names)
        lines.append(f"const {name} = computed(() => {{{b}\n}});")

    import_names = set()
    for imp in imports:
        nm = re.search(r'\{([^}]+)\}', imp)
        if nm:
            for part in nm.group(1).split(','):
                part = part.strip()
                p = re.match(r'(\w+)(?:\s+as\s+(\w+))?', part)
                if p:
                    import_names.add(p.group(2) or p.group(1))
    if methods:
        lines.append('')
    for name, info in methods.items():
        if name in ('mounted', 'beforeUnmount'):
            continue
        if name in import_names:
            continue
        b = get_function_body(info['body'], name)
        b = replace_this(b, data_keys, list(computed.keys()),
                         list(methods.keys()), list(hooks.keys()), prop_names)
        async_prefix = 'async ' if info['async'] else ''
        lines.append(f"{async_prefix}function {name}({info['params']}) {{{b}\n}}")

    if hooks.get('mounted'):
        mbody = get_hook_body(hooks['mounted'], 'mounted')
        mbody = replace_this(mbody, data_keys, list(computed.keys()),
                             list(methods.keys()), list(hooks.keys()), prop_names)
        ma = 'async ' if 'await ' in mbody else ''
        lines.append(f"\nonMounted({ma}() => {{{mbody}\n}});")

    if hooks.get('beforeUnmount'):
        ubody = get_hook_body(hooks['beforeUnmount'], 'beforeUnmount')
        ubody = replace_this(ubody, data_keys, list(computed.keys()),
                             list(methods.keys()), list(hooks.keys()), prop_names)
        ua = 'async ' if 'await ' in ubody else ''
        lines.append(f"\nonBeforeUnmount({ua}() => {{{ubody}\n}});")

    output = template_block + '\n'
    output += '<script setup>\n'
    output += '\n'.join(lines)
    output += '\n</script>\n'
    output += '\n'.join(styles)

    if dry_run:
        print("=== Generated <script setup> ===")
        print(output)
        print("=== File not written (DryRun) ===")
        return

    shutil.copy(input_path, input_path + '.bak')
    print(f"Backup saved: {input_path}.bak")

    with open(input_path, 'w', encoding='utf-8') as f:
        f.write(output)
    print(f"Migrated: {input_path}")


if __name__ == '__main__':
    dry_run = '--dry-run' in sys.argv
    if dry_run:
        sys.argv.remove('--dry-run')

    if len(sys.argv) < 2:
        print(f"Usage: python {sys.argv[0]} <file.vue> [--dry-run]")
        sys.exit(1)

    path = sys.argv[1]
    if not os.path.exists(path):
        print(f"File not found: {path}")
        sys.exit(1)

    convert_vue(path, dry_run)
