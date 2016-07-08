
**silex-rest-api-multi-lazyload**

This is a SILEX based Rest API with module lazy load, it uses micro services and has authentication.
It uses token authentication and is useful for Angularjs Applications.

This api can be used for exposing different web services, that means that every service can have its own workspace and database configuration,
it is built in a modular fashion, where removing any part won't affect the whole functionality.

For multiple API interfaces, just create a new dir:

public2/
    .htaccess
    index.php
    
**.htaccess file contents:**
    #Options -MultiViews
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]

**index.php contents:**
    require_once __DIR__ . '/../vendor/autoload.php';
   
    $app = App\Rest::run('prod2');

Create a prod2.php config file under ./config/

_About the Lazy Load._

This rest API uses lazy loading for services, it has a Factory pattern implemented so all naming conventions must be kept for controllers and services.


**## Do the following to run:**
    composer install 
    php -S 0:8001 -t public1/


**## Run tests:  **
    vendor/bin/phpunit 


**## Point to: **
	http://localhost:8001/api/v1/whatever

**## Headers:**
 'Content-Type: application/json'
 'x-token: your-auth-token'

**## License ##**
see LICENSE file.


**##Authentication method:**
   _ curl -i -H "x-requested-with: CLFYf7yz1it9x16FX1b5rlDNp3qkXJWB" -H "Content-Type: application/json" -X-POST http://localhost:8001/api/v1/login -d '{
             "login": {
                 "email": "test@example.com",
                 "pass": "04fbd445b467cf8679232accbcedf6192070d068"
             }
         }'_
    
    `$appKey = 'CLFYf7yz1it9x16FX1b5rlDNp3qkXJWB';
    $pass   = sha1('User123!*?'); //43e1eeda52d762652c2846badea1dd6a2a761d81
    
    echo sha1($appKey.$pass); //04fbd445b467cf8679232accbcedf6192070d068`

## Login Headers, company token:
  'x-requested-with: CLFYf7yz1it9x16FX1b5rlDNp3qkXJWB'
  'Content-Type: application/json'


I owe you the docs and unit tests, sorry, test writers are welcome!!, any questions just contact me.

