<h1 align="center"> Laravel Firestore Eloquent</h1> <br>
<h1 align="center">

![Logo](img/logo.png)
</h1> <br>


This package is a customized version of Laravel Eloquent designed for seamless integration with Google Firestore within Laravel applications. Firestore boasts exceptional scalability and speed, but it provides a more limited feature set compared to conventional SQL databases.

### Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Documentation](#documentation)
- [Limitations](#limitations)
- [Contributing](#contributing)
- [Changelog](#changelog)
- [License](#license)

### Requirements
- [PHP >= 8.1](https://php.net)
- [gRPC extension](https://cloud.google.com/php/grpc)
- [Any requirements found on Cloud Firestore for PHP](https://cloud.google.com/php/docs/reference/cloud-firestore/latest)
- [Laravel >= 9](https://laravel.com/docs/9.x) (Recommeded [Latest Laravel](https://laravel.com))
- [Composer](https://getcomposer.org/)


### Installation
1. Install this package using composer
   ```bash
    composer require roddy/firestore-eloquent
   ```
2. Add the following line to the service **providers** array within your **config/app.php** file:
    ```php
    Roddy\FirestoreEloquent\Providers\FModelProvider::class,
    ```

3. Copy and paste this to your **.env** file and replace ``path/to/firebase-credentials.json`` with the path to your credentials json file.
    ```bash
   GOOGLE_APPLICATION_CREDENTIALS=path/to/firebase-credentials.json
    ```

4. Copy and paste this to your **.env** file and replace ``https://<your-project>.firebaseio.com`` with the database URL for your project. You can find the database URL for your project at [https://console.firebase.google.com/u/project/_/settings/general](https://console.firebase.google.com/u/project/_/settings/general)
   ```bash
    FIREBASE_DATABASE_URL=https://<your-project>.firebaseio.com
   ```
5. Copy and paste this to your **.env** file and replace ``your-project-id`` with your project id.
   ```php
   FIREBASE_PROJECT_ID=your-project-id
   ```
6. Publish the package configuration using Artisan
   ```bash
   php artisan vendor:publish --provider="Roddy\FirestoreEloquent\Providers\FModelProvider" --force
   ```
You can locate the configuration file in **`config/firebase.php`**.


### Documentation
Visit [Laravel Firestore Eloquent](https://firestore-eloquent.netlify.app/) for documentation.

OR

Use this link [https://firestore-eloquent.netlify.app/](https://firestore-eloquent.netlify.app/)

### Limitations
[Limitations](https://firestore-eloquent.netlify.app/docs/limitations) for documentation.

### TODO
[Todo](https://firestore-eloquent.netlify.app/docs/todo) for documentation.

### License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Acknowledgments

- [Laravel](https://laravel.com/)
- [Google Cloud Firestore](https://cloud.google.com/firestore/)
- [Google Cloud Firestore PHP Client Documentation](https://googleapis.github.io/google-cloud-php/#/docs/cloud-firestore/v1.1.0/firestore/readme)
- [Google gRpc](https://cloud.google.com/php/grpc)
- [Google Cloud Firestore Storage](https://cloud.google.com/)
- [PHP](https://php.net)

## Contributors

### Code Contributors

This project exists thanks to all the people who contribute. [[Contribute](https://github.com/FreddyWhest/firestore-eloquent/graphs/contributors)].

<a href = "https://github.com/FreddyWhest/firestore-eloquent/graphs/contributors">
  <img src = "https://contrib.rocks/image?repo=FreddyWhest/firestore-eloquent"/>
</a>

### Financial Contributors
Become a financial contributor and help us sustain our community.

<h1>
  <a href="https://www.buymeacoffee.com/alfrednti" target="_blank">
  <img src="https://cdn.buymeacoffee.com/buttons/v2/default-red.png" alt="Buy Me A Coffee" width="150" />
  </a>
</h1>
