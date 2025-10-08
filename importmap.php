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
    '@symfony/ux-live-component' => [
        'path' => './vendor/symfony/ux-live-component/assets/dist/live_controller.js',
    ],
    '@symfony/ux-autocomplete' => [
        'version' => '2.27.0',
    ],
    'sortablejs' => [
        'path' => './assets/vendor/sortable.esm.js',
    ],
    './styles/app.scss' => [
        'path' => './assets/styles/app.scss',
        'type' => 'css',
    ],
    './controllers/integrations/folder_browser_controller.js' => [
        'path' => './assets/controllers/integrations/folder_browser_controller.js',
    ],
    './controllers/integrations/google_file_picker_controller.js' => [
        'path' => './assets/controllers/integrations/google_file_picker_controller.js',
    ],
    './controllers/custom-autocomplete_controller.js' => [
        'path' => './assets/controllers/custom-autocomplete_controller.js',
    ],
    './controllers/story_graph_controller.js' => [
        'path' => './assets/controllers/story_graph_controller.js',
    ],
    './controllers/decision_tree_controller.js' => [
        'path' => './assets/controllers/decision_tree_controller.js',
    ],
    './controllers/wysiwyg_controller.js' => [
        'path' => './assets/controllers/wysiwyg_controller.js',
    ],
    './controllers/kanban_controller.js' => [
        'path' => './assets/controllers/kanban_controller.js',
    ],
    './controllers/csrf_protection_controller.js' => [
        'path' => './assets/controllers/csrf_protection_controller.js',
    ],
    './controllers/sortable_character_choices_controller.js' => [
        'path' => './assets/controllers/sortable_character_choices_controller.js',
    ],
    './utils/factionGroupLayout.js' => [
        'path' => './assets/utils/factionGroupLayout.js',
    ],
    './utils/googleApiLoader.js' => [
        'path' => './assets/utils/googleApiLoader.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    'tom-select/dist/css/tom-select.bootstrap5.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
    'tom-select' => [
        'version' => '2.4.3',
    ],
    '@orchidjs/sifter' => [
        'version' => '1.1.0',
    ],
    '@orchidjs/unicode-variants' => [
        'version' => '1.1.2',
    ],
    'tom-select/dist/css/tom-select.default.min.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
    'jquery' => [
        'version' => '3.7.1',
    ],
    'cytoscape' => [
        'version' => '3.32.1',
    ],
    'bootstrap' => [
        'version' => '5.3.7',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    'bootstrap/dist/css/bootstrap.min.css' => [
        'version' => '5.3.7',
        'type' => 'css',
    ],
    '@fortawesome/fontawesome-free/css/all.css' => [
        'version' => '6.7.2',
        'type' => 'css',
    ],
    'select2' => [
        'version' => '4.1.0-rc.0',
    ],
    'select2/dist/css/select2.min.css' => [
        'version' => '4.1.0-rc.0',
        'type' => 'css',
    ],
    'jquery-ui' => [
        'version' => '1.14.1',
    ],

    'quill' => [
        'version' => '2.0.3',
    ],
    'quill-mention' => [
        'version' => '6.1.1',
    ],
    'lodash-es' => [
        'version' => '4.17.21',
    ],
    'parchment' => [
        'version' => '3.0.0',
    ],
    'quill-delta' => [
        'version' => '5.1.0',
    ],
    'eventemitter3' => [
        'version' => '5.0.1',
    ],
    'fast-diff' => [
        'version' => '1.3.0',
    ],
    'lodash.clonedeep' => [
        'version' => '4.5.0',
    ],
    'lodash.isequal' => [
        'version' => '4.5.0',
    ],
];
