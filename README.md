Installation Instructions:

- Download the repository
- You will need a webhosting service such as an apache webserver, included in XAMMP
- You will need an sql database as well such as Maria DB, included in XAMMP

Notes:

Setup the database, save the database connection details in the following location: database/db_config.php.

```php
<?php

return [
    "host" => "",
    "dbname" => "",
    "username" => "",
    "password" => ""
];
```

Then use the database dump export from the Database_Structure file to populate the database.

Photos will need to be sourced then imported to the images/product images folder.
- Refer to database import file, Photo UrL tables for expected names. Alternatively you may create new products and upload your own files.

Account Setup:

For access to the admin and management pages use these credentials

User: myadmin@hotmail.com
Password: pass123

For the regular user flow, please create an account using the register button on the top right of the main home screen.
