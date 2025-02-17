<?php

require_once('inc/inc.functions.php');

Kirby::plugin('mountbatt/kirby-autoalt', [
    'hooks' => [
        // Hook für file.create:after, der nur ein File übergibt
        'file.create:after' => function (Kirby\Cms\File $file) use ($generateAltText) {
            $generateAltText($file);
        },
        // Hook für file.replace:after, der zwei Files übergibt (neues und altes File)
        'file.replace:after' => function (Kirby\Cms\File $newFile, Kirby\Cms\File $oldFile) use ($generateAltText) {
            // Hier interessiert uns nur die neue Datei, da sie ersetzt wurde
            $generateAltText($newFile);
        },
    ]
]);
