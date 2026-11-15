#!/usr/bin/env python3
"""Deterministic private-bus services. Never connects to the user's bus."""
import json
import os
from pathlib import Path
import dbus
import dbus.service
from dbus.mainloop.glib import DBusGMainLoop
from gi.repository import GLib

DBusGMainLoop(set_as_default=True)
bus = dbus.SessionBus()
loop = GLib.MainLoop()


def log(method, **args):
    with open(os.environ['FAKE_LOG'], 'a') as out:
        out.write(json.dumps(dict(method=method, **args)) + '\n')


def signal(destination, iface, member, signature, args, path='/org/myphp/Test'):
    message = dbus.lowlevel.SignalMessage(path, iface, member)
    if destination:
        message.set_destination(destination)
    message.append(*args, signature=signature)
    bus.send_message(message)


class Test(dbus.service.Object):
    @dbus.service.method('org.myphp.Test', in_signature='v', out_signature='v')
    def Echo(self, value):
        log('Echo', value=str(value))
        return value

    @dbus.service.method('org.myphp.Test', in_signature='s', out_signature='')
    def EmitTo(self, name):
        log('EmitTo', name=name)
        signal(name, 'org.myphp.Test', 'Ping', 's', ['unicast'])
        signal(None, 'org.myphp.Test', 'Ping', 's', ['broadcast'])

    @dbus.service.method('org.myphp.Test', in_signature='', out_signature='', async_callbacks=('reply', 'error'))
    def Hang(self, reply, error):
        log('Hang')


names = [dbus.service.BusName('org.myphp.Test', bus)]
objects = [Test(bus, '/org/myphp/Test')]
Path(os.environ['PHPUI_TEST_TMP'], 'ready').touch()
loop.run()
