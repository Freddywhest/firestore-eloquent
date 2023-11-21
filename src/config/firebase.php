<?php

declare(strict_types=1);

return [
    /*
     * ------------------------------------------------------------------------
     * Default Firebase project
     * ------------------------------------------------------------------------
     */

    'default' => env('FIREBASE_PROJECT', 'app'),

    /*
     * ------------------------------------------------------------------------
     * Route to redirect to when authenticated
     * ------------------------------------------------------------------------
     */

    'guest_url' => 'home',

    /*
     * ------------------------------------------------------------------------
     * Route to redirect to when not authenticated
     * ------------------------------------------------------------------------
     */

    'auth_url' => 'login',

    /*
     * ------------------------------------------------------------------------
     * Model to use for authentication.
     * Default is "App\FModels\User" model
     * ------------------------------------------------------------------------
     */

    'auth_model' => 'App\\FModels\\User',

    /*
    |---------------------------------------------------------------------------
    | Class Namespace
    |---------------------------------------------------------------------------
    |
    | This value sets the root class namespace for FModels classes in
    | your application. This value will change where component auto-discovery
    | finds components. It's also referenced by the file creation commands.
    |
    */

    'class_namespace' => 'App\\FModels',

    /*
     * ------------------------------------------------------------------------
     * Firebase project configurations
     * ------------------------------------------------------------------------
     */

    'projects' => [
        'app' => [

            /*
             * ------------------------------------------------------------------------
             * Credentials / Service Account
             * ------------------------------------------------------------------------
             *
             * In order to access a Firebase project and its related services using a
             * server SDK, requests must be authenticated. For server-to-server
             * communication this is done with a Service Account.
             *
             * If you don't already have generated a Service Account, you can do so by
             * following the instructions from the official documentation pages at
             *
             * https://firebase.google.com/docs/admin/setup#initialize_the_sdk
             *
             * Once you have downloaded the Service Account JSON file, you can use it
             * to configure the package.
             *
             * If you don't provide credentials, the Firebase Admin SDK will try to
             * auto-discover them
             *
             * - by checking the environment variable FIREBASE_CREDENTIALS
             * - by checking the environment variable GOOGLE_APPLICATION_CREDENTIALS
             * - by trying to find Google's well known file
             * - by checking if the application is running on GCE/GCP
             *
             * If no credentials file can be found, an exception will be thrown the
             * first time you try to access a component of the Firebase Admin SDK.
             *
             */

            'credentials' => env('FIREBASE_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS')),

            /*
             * ------------------------------------------------------------------------
             * Firebase Realtime Database
             * ------------------------------------------------------------------------
             */

            'database' => [

                /*
                 * In most of the cases the project ID defined in the credentials file
                 * determines the URL of your project's Firestore Database. If the
                 * connection to the Firestore Database fails, you can override
                 * its Project id with the value you see at
                 *
                 * https://console.firebase.google.com/u/1/project/_/database/settings/general
                 *
                 */

                'project_id' => env('FIREBASE_PROJECT_ID'),

            ],
        ],
    ],
];
