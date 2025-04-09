<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    'dashboard' => [
        'path' => './assets/dashboard.js',
        'entrypoint' => true,
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.13',
    ],
    'bootstrap' => [
        'version' => '5.3.3',
    ],
    '@popperjs/core' => [
        'version' => '2.10.2',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.3',
        'type' => 'css',
    ],
    'sweetalert2' => [
        'version' => '11.17.2',
    ],
    '@editorjs/warning' => [
        'version' => '1.4.1',
    ],
    '@editorjs/paragraph' => [
        'version' => '2.11.7',
    ],
    '@editorjs/quote' => [
        'version' => '2.7.6',
    ],
    '@editorjs/image' => [
        'version' => '2.10.2',
    ],
    '@editorjs/code' => [
        'version' => '2.9.3',
    ],
    '@editorjs/link' => [
        'version' => '2.6.2',
    ],
    '@editorjs/header' => [
        'version' => '2.8.8',
    ],
    '@editorjs/delimiter' => [
        'version' => '1.4.2',
    ],
    '@editorjs/table' => [
        'version' => '2.4.4',
    ],
    '@editorjs/attaches' => [
        'version' => '1.3.0',
    ],
    'editorjs-inline-image' => [
        'version' => '2.2.2',
    ],
    '@flasher/flasher' => [
        'version' => '2.1.5',
    ],
    'editorjs-alert' => [
        'version' => '1.1.4',
    ],
    '@editorjs/list' => [
        'version' => '2.0.6',
    ],
    '@editorjs/marker' => [
        'version' => '1.4.0',
    ],
    '@sotaproject/strikethrough' => [
        'version' => '1.0.1',
    ],
    'editorjs-html' => [
        'version' => '4.0.5',
    ],
    'highlight.js' => [
        'version' => '11.11.1',
    ],
    'highlight.js/styles/github.css' => [
        'version' => '11.11.1',
        'type' => 'css',
    ],
    'bigpicture' => [
        'version' => '2.6.3',
    ],
    'tippy.js' => [
        'version' => '6.3.7',
    ],
    'tippy.js/themes/material.css' => [
        'version' => '6.3.7',
        'type' => 'css',
    ],
];
