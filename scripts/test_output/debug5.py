"""Debug: run migrate-vue.py's actual get_value_at against the backup."""
import sys
sys.path.insert(0, 'C:\\laragon\\www\\lidiag\\scripts')
import importlib.util
spec = importlib.util.spec_from_file_location("mv", "C:\\laragon\\www\\lidiag\\scripts\\migrate-vue.py")
mv = importlib.util.module_from_spec(spec)
spec.loader.exec_module(mv)

import re

with open('resources/js/components/modulos/ecografias/admisionarComponent.vue.bak', 'r', encoding='utf-8') as f:
    data = f.read()

sm = re.search(r'<script>(.*?)</script>', data, re.DOTALL)
script = sm.group(1)
parsed = mv.parse_export_default(script)
if not parsed:
    print("No export default")
    sys.exit(1)
obj = parsed[0]

data_val = mv.get_key_section(obj, 'data')
print('data_val[:80]:', repr(data_val[:80]))
print('data_val[-80:]:', repr(data_val[-80:]))

keys = mv.get_data_keys(data_val)
print(f'\nKeys ({len(keys)}): {list(keys.keys())}')

for k in ('sedeActual', 'medicoActual', 'registros'):
    if k in keys:
        print(f'  {k}: {repr(keys[k][:60])}')
