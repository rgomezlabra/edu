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
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    'bootstrap' => [
        'version' => '5.3.3',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.3',
        'type' => 'css',
    ],
    '@kurkle/color' => [
        'version' => '0.3.4',
    ],
    'jquery' => [
        'version' => '3.7.1',
    ],
    'datatables.net-bs5' => [
        'version' => '2.1.8',
    ],
    'datatables.net' => [
        'version' => '2.1.8',
    ],
    'datatables.net-bs5/css/dataTables.bootstrap5.min.css' => [
        'version' => '2.1.8',
        'type' => 'css',
    ],
    'datatables.net-buttons-bs5' => [
        'version' => '3.2.0',
    ],
    'datatables.net-buttons' => [
        'version' => '3.2.0',
    ],
    'datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css' => [
        'version' => '3.2.0',
        'type' => 'css',
    ],
    'datatables.net-responsive-bs5' => [
        'version' => '3.0.3',
    ],
    'datatables.net-select-bs5' => [
        'version' => '2.1.0',
    ],
    'datatables.net-responsive' => [
        'version' => '3.0.3',
    ],
    'datatables.net-select' => [
        'version' => '2.1.0',
    ],
    'datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css' => [
        'version' => '3.0.3',
        'type' => 'css',
    ],
    'datatables.net-select-bs5/css/select.bootstrap5.min.css' => [
        'version' => '2.1.0',
        'type' => 'css',
    ],
    'datatables.net-buttons/js/buttons.html5.js' => [
        'version' => '3.2.0',
    ],
    'datatables.net-buttons/js/buttons.print.js' => [
        'version' => '3.2.0',
    ],
    'datatables.net-rowreorder' => [
        'version' => '1.5.0',
    ],
    'datatables.net-rowreorder-bs5' => [
        'version' => '1.5.0',
    ],
    'datatables.net-rowreorder-bs5/css/rowReorder.bootstrap5.min.css' => [
        'version' => '1.5.0',
        'type' => 'css',
    ],
    'jszip' => [
        'version' => '3.10.1',
    ],
    'bootstrap-slider' => [
        'version' => '11.0.2',
    ],
    'bootstrap-slider/dist/css/bootstrap-slider.min.css' => [
        'version' => '11.0.2',
        'type' => 'css',
    ],
    '@fortawesome/fontawesome-free' => [
        'version' => '6.7.1',
    ],
    '@fortawesome/fontawesome-free/css/fontawesome.min.css' => [
        'version' => '6.7.1',
        'type' => 'css',
    ],
    '@fortawesome/fontawesome-svg-core' => [
        'version' => '6.7.1',
    ],
    '@fortawesome/fontawesome-svg-core/styles.min.css' => [
        'version' => '6.7.1',
        'type' => 'css',
    ],
    '@fortawesome/free-regular-svg-icons' => [
        'version' => '6.7.1',
    ],
    '@fortawesome/free-solid-svg-icons' => [
        'version' => '6.7.1',
    ],
    '@fortawesome/free-brands-svg-icons' => [
        'version' => '6.7.1',
    ],
    '@ckeditor/ckeditor5-build-classic' => [
        'version' => '44.1.0',
    ],
    'ckeditor5/translations/es.js' => [
        'version' => '44.1.0',
    ],
];
