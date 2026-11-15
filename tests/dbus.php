<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';
require __DIR__ . '/../src/DBus.php';
use PhpUi\DBus;

$cases = [
    ['ybnqiuxtdsog', [255, true, -123, 65535, -123456, 4294967295, -1234567890123, 1234567890123, 1.25, 'a é', '/a/b', 'a{sv}']],
    ['v', [['a{sv}', ['name' => ['s', 'hello'], 'nested' => ['v', ['u', 42]]]]]],
    ['a(sa(us))', [[['Images', [[0, '*.png'], [0, '*.jpg']]], ['All', [[0, '*']]]]]],
    ['a{sv}a(sa(us))as', [[], [], []]],
    ['(ya{sv}t)', [[1, [], 123]]],
];
foreach ($cases as [$sig, $values]) same($values, DBus::unpack($sig, DBus::pack($sig, $values)), "wire round trip $sig");
same(trim(file_get_contents(__DIR__ . '/fakes/dict.hex')), bin2hex(DBus::pack('a{sv}', [['name' => ['s', 'hello'], 'count' => ['u', 42]]])), 'dictionary bytes match independent libdbus fixture');
foreach ([['u', ''], ['b', pack('V', 2)], ['as', pack('V', 100)]] as [$sig, $bytes]) {
    try { DBus::unpack($sig, $bytes); check(false, 'malformed wire data rejected'); }
    catch (Throwable) { check(true, 'malformed wire data rejected'); }
}

$bus = DBus::session();
check($bus !== null, 'connect and authenticate to private bus');
if (!$bus) exit(1);
$wait = function (callable $ready, float $seconds = 4) use ($bus) {
    $end = microtime(true) + $seconds;
    do { $bus->poll(); if ($ready()) return true; usleep(500); } while (microtime(true) < $end);
    return false;
};
$result = null;
$bus->call('org.freedesktop.DBus', '/org/freedesktop/DBus', 'org.freedesktop.DBus', 'ListNames', done: function ($value) use (&$result) { $result = $value; });
check($wait(function () use (&$result) { return $result !== null; }), 'ListNames replies');
check(in_array($bus->name, $result[0] ?? [], true), 'ListNames includes our unique name');
if (getenv('PHPUI_TEST_MODE') === 'fake') {
    $result = null;
    $bus->call('org.myphp.Test', '/org/myphp/Test', 'org.myphp.Test', 'Echo', 'v', [['s', 'hello é']], function ($value) use (&$result) { $result = $value; });
    // Closures reading mutable scalar state must capture by reference.
    $wait(function () use (&$result) { return $result !== null; });
    same([['s', 'hello é']], $result, 'Echo interoperates with dbus-python');
    $signals = [];
    $id = $bus->on('org.myphp.Test', 'Ping', function ($args) use (&$signals) { $signals[] = $args[0]; }, '/org/myphp/Test');
    $bus->call('org.myphp.Test', '/org/myphp/Test', 'org.myphp.Test', 'EmitTo', 's', [$bus->name]);
    $wait(function () use (&$signals) { return count($signals) === 2; });
    same(['unicast', 'broadcast'], $signals, 'unicast and broadcast signals reach the same connection');
    $bus->off($id);
    $filtered = [];
    $id = $bus->on('org.myphp.Test', 'Ping', function ($args) use (&$filtered) { $filtered[] = $args[0]; }, sender: 'org.myphp.Test');
    $bus->call('org.myphp.Test', '/org/myphp/Test', 'org.myphp.Test', 'EmitTo', 's', [$bus->name]);
    $wait(function () use (&$filtered) { return count($filtered) === 2; });
    same(['unicast', 'broadcast'], $filtered, 'well-known sender filter resolves the unique bus owner');
    $bus->off($id);
    $error = null;
    $bus->call('org.myphp.Test', '/org/myphp/Test', 'org.myphp.Test', 'Hang', done: function ($value, $why) use (&$error) { $error = $why; });
    $start = microtime(true); $turns = 0; $max = 0;
    while ($error === null && microtime(true) - $start < 4) {
        $at = hrtime(true); $bus->poll(); $max = max($max, (hrtime(true) - $at) / 1e6); $turns++;
        usleep(100);
    }
    same('timeout', $error, 'unanswered method times out');
    check($turns / (microtime(true) - $start) > 1000, 'pending method does not block the loop');
    check($max < 2, "poll stays below 2ms (max $max ms)");
}
// Only this test's private bus is killed; the runner starts a fresh bus for each file.
$pid = null;
$bus->call('org.freedesktop.DBus', '/org/freedesktop/DBus', 'org.freedesktop.DBus', 'GetConnectionUnixProcessID', 's', ['org.freedesktop.DBus'], function ($value) use (&$pid) { $pid = $value[0] ?? null; });
$wait(function () use (&$pid) { return $pid !== null; });
check(is_int($pid), 'private bus PID obtained');
$error = null;
// Stop this private daemon before queueing: in real mode there is no Test service,
// so otherwise ServiceUnknown can race SIGTERM and is not a disconnect failure.
if ($pid) {
    posix_kill($pid, SIGSTOP);
    check(until(fn () => str_contains(file_get_contents("/proc/$pid/status"), "State:\tT"), poll: fn () => null), 'private daemon is stopped before disconnect test');
}
$bus->call('org.myphp.Test', '/org/myphp/Test', 'org.myphp.Test', 'Hang', done: function ($value, $why) use (&$error) { $error = $why; });
$bus->poll();
if ($pid) posix_kill($pid, SIGKILL);
$wait(fn () => !$bus->connected());
same('disconnected', $error, 'bus death fails pending calls without throwing');
putenv('DBUS_SESSION_BUS_ADDRESS=unix:path=/nonexistent');
$start = microtime(true);
same(null, DBus::session(), 'missing bus returns null');
check(microtime(true) - $start < 1, 'missing bus fails within one second');

foreach ([
    pack('CCCCVVV', 108, 4, 0, 1, 0, 1, 0), // signal missing required fields
    pack('CCCCVVV', 108, 2, 0, 1, 16777216, 1, 0), // exceeds full message cap
    pack('CCCCVVV', 108, 2, 0, 2, 0, 1, 0), // bad protocol version
] as $malformed) {
    [$client, $peer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
    stream_set_blocking($client, false);
    $testBus = (new ReflectionClass(DBus::class))->newInstanceWithoutConstructor();
    (new ReflectionProperty(DBus::class, 'socket'))->setValue($testBus, $client);
    fwrite($peer, $malformed);
    $testBus->poll();
    check(!$testBus->connected(), 'malformed or oversized header drops connection');
    fclose($peer);
}
