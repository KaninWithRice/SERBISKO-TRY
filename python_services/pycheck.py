import traceback, py_compile

try:
    py_compile.compile('enrollment_form_filler.py', doraise=True)
    print('ok')
except Exception:
    traceback.print_exc()
