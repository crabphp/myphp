# {{name}}

```sh
myphp run --watch   # hot reload: changes show as you save
myphp build         # one executable in build/ (on a Mac also an .app; --dmg for a disk image)
composer install   # optional: packages, editor autocomplete
```

Put a 1024×1024 `icon.png` beside `app.php` to give the app its icon, and name it in
`composer.json`: `"extra": {"myphp": {"name": "{{name}}", "id": "com.example.{{name}}", "version": "1.0.0"}}`.

[MyPHP getting started](https://github.com/crabphp/myphp/blob/main/docs/getting-started.md)
