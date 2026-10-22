<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$lockPath = $argv[1] ?? $projectRoot . '/composer.lock';
$installedPath = $argv[2] ?? $projectRoot . '/vendor/composer/installed.json';

foreach ([$lockPath, $installedPath] as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "Missing Composer metadata: {$path}\n");
        exit(1);
    }
}

$lock = json_decode((string) file_get_contents($lockPath), true, 512, JSON_THROW_ON_ERROR);
$installed = json_decode((string) file_get_contents($installedPath), true, 512, JSON_THROW_ON_ERROR);

$lockedVersions = [];
foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
    $lockedVersions[$package['name']] = $package['version'];
}

$installedPackages = $installed['packages'] ?? $installed;
foreach ($installedPackages as $package) {
    $name = $package['name'] ?? null;
    $installedVersion = $package['version'] ?? null;

    if (
        is_string($name)
        && is_string($installedVersion)
        && ($package['installation-source'] ?? null) === 'source'
        && isset($lockedVersions[$name])
        && $lockedVersions[$name] !== $installedVersion
    ) {
        echo $name, PHP_EOL;
    }
}
