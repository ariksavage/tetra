# TETRA Project
## Installation

1. Clone the repository
1. Initialize git submodules: `git submodule update --init --recursive`
1. Configure *.ddev/config/yaml*
  - Set 
    - name: **you-project** (eg knowledge-direct)
    - host_https_port: **unique_port** (eg 20250)
    - host_db_port: **another_unique_port** (eg 20251)
1. Start DDEV: `ddev start`
1. Configure **proxy.conf.json**
```
{
  "/core/**/*": {
    "target": "https://127.0.0.1:{{host_https_port}}",
    "secure": false,
    "logLevel": "debug",
    "changeOrigin": true
  }
}
```

### First time setup
1. Run tetra installer to configure the database: `ddev tetra install`
### Migration from another system:
1. Run tetra db-migrate to configure the database: `ddev tetra db-migrate`

## Tetra Core
The core layer defines back end logic, and handles database operations.

Generally speaking, the Core is made up of **APIs** that handle requests, **Models**, that represent individual database entities, and **interfaces** that communicate with the databse and other services.

Requests are set to the Core index.php.
The URL of the request, eg `https://url.com/core/{apitype}/{action}/{id}/{id2}` is mapped to
`index.php?apitype={apitype}&action={action}&id={id}&id2={id2}`. 


These parameters, along with the request's method (GET, PUT, POST, DELETE) determine:

A) the API to be used: \\Core\\API\\{$apitype}

B) the API's method to be called: {$action}{$method}

Examples:

Request: POST https://url.com/core/users/list

```
<?php
$method="POST";
$apitype="users";
$action="list";
$id = null;
$id2 = null;
$core = new \Core\API\Users();

$core->listPOST();
?>
```

Request: GET https://url.com/core/users/id/42

```
<?php
$method="GET";
$apitype="users";
$action="id";
$id = 42;
$id2 = null;
$core = new \Core\API\Users();

$core->idGET($id);
?>
```

### APIs
An API is a class which contains methods to handle HTTP requests. All responses, success and error, will be returned as raw JSON to be consumed by the Angular front end.

A successful response will look something like the following:
```
{
  "data": {
    "property": "value",
    "property2": "value2"
  },
  "message": "Potentially user facing text".
}
```
#### Overriding
When the core/index.php instantiates the API class, it will first look to the application's **Plugins** folder for a corresponding $type folder, and use the .api there.

Example: 

REQUEST: `https://url.com/core/users/id/2`

If **Plugins/users/myUsers.api** exists, the API class will be \Core\API\myUsers.

An override API class will look something like:

```
<?php
namespace Core\API;

require_once(CORE_ROOT . '/api/users.api');
require_once(__DIR__ . '/myUser.model');


class myUsers extends Users {
  public function __construct()
  {
    parent::__construct();
    $this->model = '\Core\Models\myUser';
  }
}
```
This override will contain all of the core methods related to the origin **Users** class, but instead use the **myUser** class for all items. See below for more on models.

## Models
Models represent an individual entity, one row of the database.

### Overriding
An overriding Model will have to be included in a plugin .api file, since models are not used directly outside of APIs.
