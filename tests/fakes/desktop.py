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
settings = {'chooser': 'ok:/tmp/one', 'scheme': '1', 'settings': 'ok'}


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
    @dbus.service.method('org.myphp.Test', in_signature='ss', out_signature='')
    def Set(self, key, value):
        settings[str(key)] = str(value)
        log('Set', key=key, value=value)
        if key == 'scheme':
            signal(None, 'org.freedesktop.portal.Settings', 'SettingChanged', 'ssv', ['org.freedesktop.appearance', 'color-scheme', dbus.UInt32(int(value), variant_level=1)], '/org/freedesktop/portal/desktop')

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


class Request(dbus.service.Object):
    @dbus.service.method('org.freedesktop.portal.Request', in_signature='', out_signature='')
    def Close(self):
        self.remove_from_connection()


class Portal(dbus.service.Object):
    @dbus.service.method('org.freedesktop.portal.Settings', in_signature='ss', out_signature='v')
    def ReadOne(self, namespace, key):
        log('ReadOne', namespace=namespace, key=key)
        if settings['settings'] == 'legacy':
            raise dbus.exceptions.DBusException('Old portal', name='org.freedesktop.DBus.Error.UnknownMethod')
        if settings['settings'] == 'error':
            raise dbus.exceptions.DBusException('No settings', name='org.freedesktop.portal.Error.Failed')
        return dbus.UInt32(int(settings['scheme']), variant_level=1)

    @dbus.service.method('org.freedesktop.portal.Settings', in_signature='ss', out_signature='v')
    def Read(self, namespace, key):
        log('Read', namespace=namespace, key=key)
        return dbus.UInt32(int(settings['scheme']), variant_level=2)

    def choose(self, method, parent, title, options, sender):
        token = str(options['handle_token'])
        path = '/org/freedesktop/portal/desktop/request/' + sender[1:].replace('.', '_') + '/' + token
        log(method, parent=parent, title=title, options=options)
        mode = settings['chooser']
        if mode == 'error':
            raise dbus.exceptions.DBusException('No backend', name='org.freedesktop.portal.Error.Failed')
        request = Request(bus, path)
        requests.append(request)
        if mode != 'hold':
            from urllib.parse import quote
            code = 1 if mode == 'cancel' else 0
            uris = ['file://' + quote(p) for p in mode[3:].split(',')] if code == 0 else []
            def respond():
                signal(sender, 'org.freedesktop.portal.Request', 'Response', 'ua{sv}', [code, {'uris': dbus.Array(uris, signature='s', variant_level=1)}], path)
                return False
            GLib.timeout_add(100, respond)
        return dbus.ObjectPath(path)

    @dbus.service.method('org.freedesktop.portal.FileChooser', in_signature='ssa{sv}', out_signature='o', sender_keyword='sender')
    def OpenFile(self, parent, title, options, sender):
        return self.choose('OpenFile', parent, title, options, sender)

    @dbus.service.method('org.freedesktop.portal.FileChooser', in_signature='ssa{sv}', out_signature='o', sender_keyword='sender')
    def SaveFile(self, parent, title, options, sender):
        return self.choose('SaveFile', parent, title, options, sender)

    @dbus.service.method('org.freedesktop.DBus.Properties', in_signature='ss', out_signature='v')
    def Get(self, iface, name):
        return dbus.UInt32(2 if iface == 'org.freedesktop.portal.Settings' else 4)


requests = []
names = [dbus.service.BusName('org.myphp.Test', bus), dbus.service.BusName('org.freedesktop.portal.Desktop', bus)]
objects = [Test(bus, '/org/myphp/Test'), Portal(bus, '/org/freedesktop/portal/desktop')]
Path(os.environ['PHPUI_TEST_TMP'], 'ready').touch()
loop.run()
