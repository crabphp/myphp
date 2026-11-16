# Press keys on the X display with XTest: python3 keys.py "vieet " (letters, digits, space).
import ctypes, sys, time
x11 = ctypes.CDLL('libX11.so.6'); xt = ctypes.CDLL('libXtst.so.6')
x11.XOpenDisplay.restype = ctypes.c_void_p
x11.XKeysymToKeycode.argtypes = [ctypes.c_void_p, ctypes.c_ulong]
x11.XStringToKeysym.restype = ctypes.c_ulong
xt.XTestFakeKeyEvent.argtypes = [ctypes.c_void_p, ctypes.c_uint, ctypes.c_int, ctypes.c_ulong]
x11.XFlush.argtypes = [ctypes.c_void_p]
d = x11.XOpenDisplay(None)
# No window manager: keyboard focus follows the pointer, so put it over the window.
xt.XTestFakeMotionEvent.argtypes = [ctypes.c_void_p, ctypes.c_int, ctypes.c_int, ctypes.c_int, ctypes.c_ulong]
xt.XTestFakeMotionEvent(d, -1, 512, 384, 0); x11.XFlush(d); time.sleep(0.3)
names = {' ': 'space', '\n': 'Return'}
for ch in sys.argv[1]:
    code = x11.XKeysymToKeycode(d, x11.XStringToKeysym(names.get(ch, ch).encode()))
    xt.XTestFakeKeyEvent(d, code, 1, 0); x11.XFlush(d); time.sleep(0.05)
    xt.XTestFakeKeyEvent(d, code, 0, 0); x11.XFlush(d); time.sleep(0.12)
