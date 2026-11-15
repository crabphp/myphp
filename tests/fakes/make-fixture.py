#!/usr/bin/env python3
"""Generate an independent a{sv} body fixture using libdbus (dbus-python's wire library)."""
import ctypes as c
import struct
from pathlib import Path

lib = c.CDLL('libdbus-1.so.3')

def function(name, result, *args):
    fn = getattr(lib, name)
    fn.restype, fn.argtypes = result, args
    return fn

ptr = c.c_void_p
new = function('dbus_message_new_signal', ptr, c.c_char_p, c.c_char_p, c.c_char_p)
init = function('dbus_message_iter_init_append', None, ptr, ptr)
open_ = function('dbus_message_iter_open_container', c.c_int, ptr, c.c_int, c.c_char_p, ptr)
close = function('dbus_message_iter_close_container', c.c_int, ptr, ptr)
append = function('dbus_message_iter_append_basic', c.c_int, ptr, c.c_int, ptr)
marshal = function('dbus_message_marshal', c.c_int, ptr, c.POINTER(ptr), c.POINTER(c.c_int))
message = new(b'/org/myphp/Test', b'org.myphp.Test', b'Fixture')
root, array, entry, variant = [c.create_string_buffer(128) for _ in range(4)]
init(message, root)
assert open_(root, ord('a'), b'{sv}', array)
for key, signature, value in [(b'name', b's', b'hello'), (b'count', b'u', 42)]:
    assert open_(array, ord('e'), None, entry)
    assert append(entry, ord('s'), c.byref(c.c_char_p(key)))
    assert open_(entry, ord('v'), signature, variant)
    v = c.c_char_p(value) if signature == b's' else c.c_uint(value)
    assert append(variant, signature[0], c.byref(v))
    assert close(entry, variant)
    assert close(array, entry)
assert close(root, array)
data, size = ptr(), c.c_int()
assert marshal(message, c.byref(data), c.byref(size))
raw = c.string_at(data, size.value)
header_size = struct.unpack_from('<I', raw, 12)[0]
body = raw[(16 + header_size + 7) & ~7:]
Path(__file__).with_name('dict.hex').write_text(body.hex() + '\n')
