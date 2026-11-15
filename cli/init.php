<?php

declare(strict_types=1);

function cmdInit(array $args): int
{
    $name = $args[0] ?? '';
    if (count($args) !== 1 || !preg_match('/^[a-z0-9][a-z0-9._-]*$/iD', $name)) {
        return myphpError('usage: myphp init <name> (letters, numbers, dots, underscores and hyphens)', 2);
    }
    if (file_exists($name) && (!is_dir($name) || count(scandir($name)) > 2)) {
        return myphpError("\"$name\" already exists and is not empty");
    }
    if (!is_dir($name) && !mkdir($name)) return myphpError("cannot create \"$name\"");
    $version = myphpVersion();
    $constraint = preg_match('/^(\d+)\.(\d+)\./', $version, $match) ? "^$match[1].$match[2]" : 'dev-main';
    foreach (['app.php', 'composer.json', 'gitignore', 'README.md'] as $file) {
        $contents = file_get_contents(MYPHP_ROOT . "/cli/template/$file");
        // Composer names are stricter than directory names: single separators, no trailing separator.
        $package = trim(preg_replace('/[._-]+/', '-', strtolower($name)), '-');
        $contents = str_replace(['{{name}}', '{{constraint}}'], [$file === 'composer.json' ? $package : $name, $constraint], $contents);
        $target = $file === 'gitignore' ? '.gitignore' : $file;
        if (file_put_contents("$name/$target", $contents) !== strlen($contents)) {
            return myphpError("cannot write $name/$target");
        }
    }
    echo "Created $name.\n\n  cd $name\n  myphp run            start the app\n  myphp build          make one executable in build/\n  composer install     optional: editor autocomplete\n";
    return 0;
}
