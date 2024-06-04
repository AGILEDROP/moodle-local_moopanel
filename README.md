# MooPanel API

## Overview
This plugin provide an API to enable communication between Moodle and MooPanel Application.

## Install
- Clone this repository into the folder '/local'.
- Access the notification area in moodle and install (or run admin/cli/update.php)

## Getting started
To enable communication  with MooPanel, first go in Moopanel App and follow steps to add new instance (you must copy url of Moodle).
Moopanel App provide you an API-key which you must paste into plugin config.

## Authorisation
Moopanel API use API-key authorisation type. Please add ```X-API-KEY``` key and its value to request header.

## Available endpoints
All endpoints url starts with ```Moodle root url``` + ```/local/moopanel/server.php```.


### Endpoint ```/``` or ```/test_connection``` 
#### ```[GET request]```
Return response with basic information about Moodle installation.


### Endpoint ```/dashboard```
#### ```[GET request]```
Return response with some additional information about Moodle installation.


### Endpoint ```/api_key_status```
#### ```[GET request]```
Return information about API key (currently only 'key_expiration_date')
#### ```[POST request]```
Update existing API key
Request body example 1 (fixed date)
```json
{
    "data": {
        "key_expiration_date": 1234567899
        }
}
```
- request body example 2 (api key is permanent)
```json
{
    "data": {
        "key_expiration_date": null
        }
}
```


### Endpoint ```/users```
#### ```[GET request]```
Return list of all Moodle users.
Filters can be provided in url parameters.
Example:  ```/users?search=ab&sort=firstname DESC&fields=id,firstname,lastname```.

Available parameters:
  - string ```"search"``` A simple string to search for
  - string ```"sort"``` A SQL snippet for the sorting criteria to use
  - string ```"fields"``` A comma separated list of fields to be returned from the chosen table.

#### ```[POST request]```
Not implemented yet.

### Endpoint ```/users/count```
#### ```[GET request]```
Return number of all Moodle users.

### Endpoint ```/users/online```
#### ```[GET request]```
Return number of online users (by default for last 5 minutes).

Example:  ```/users/online?timeStart=1716796022&timeEnd=1716796044```.

Available parameters:
- timestamp ```"timeStart"``` Last access user in greater then timeStart.
- timestamp ```"timeEnd"``` Last access user is lower then timeEnd.
If both parameters provided, return online users between timeStart and timeEnd.

### Endpoint ```/user```
#### ```[GET request]```
Return details for selected user. Filter can be provided in parameters.
Please use JUST ONE of filters provided in example (```"id"``` or ```"username"``` or ```"email"``` or ```"upn"```)

Examples:
- ```/user?id=1234```
- ```/user?username=abcd```
- ```/user?upn=abcd```
- ```/user?email=sample@email.com```

#### ```[POST request]```
Not implemented yet.


### Endpoint ```/plugins```
#### ``` [GET request]```
Return list of all moodle plugins. Display options can be provided in URL parmeters.
Examples:
- ```/plugins?displayupdates```
- ```/plugins?displayupdateslog```
- ```/plugins?displayupdates&displayupdateslog```

Available parameters:
- bool ```"displayupdates"``` Display plugins updates.
- bool ```"displayupdateslog"``` Display plugins updates log.

### Endpoint ```/plugins/updates```
#### ```[POST request]```
Install updates provided in request body.
- request body example
```json
{
    "data": {
      "updates": [
        {
          "model_id": 1234,
          "component": "theme_learnr",
          "version": "2024021300",
          "release": "4.3.3",
          "download" : "https://moodle.org/plugins/download.php/31128/theme_learnr_moodle43_2024021300.zip"
        },
        {
          "model_id": 1234,
          "component": "theme_learnr",
          "version": "2024021300",
          "release": "4.3.3",
          "download" : "https://moodle.org/plugins/download.php/31128/theme_learnr_moodle43_2024021300.zip"
        }
     ]
   }
}
```

### Endpoint ```/plugins/installzip```
#### ```[POST request]```
Install plugins from zip files, provided in request body.
- request body example
```json
{
  "data": {
    "updates": [
      "https://moodle.org/plugins/download.php/31629/theme_adaptable_moodle43_2023111805.zip",
      "https://link2.zip",
      "neki neveljavni link"
    ]
  }
}
```

### Endpoint ```/plugin```
#### ```[GET request]```
Return information for selected plugin. Please use component name (frankenstyle) for selected plugin (see example)
Example:
- ```/plugin?plugin=theme_boost```
- ```/plugin?plugin=theme_boost&displayupdates```
- ```/plugin?plugin=theme_boost&displayupdateslog```
- ```/plugin?plugin=theme_boost&displayconfig```
- ```/plugin?plugin=theme_boost&displayupdates&displayupdateslog&displayconfig```

- Required parameter:
- string ```"plugin"``` Plugin name.

Optional parameters:
- bool ```"displayupdates"``` Display plugin available updates.
- bool ```"displayupdateslog"``` Display plugin updates log.
- bool ```"displayconfig"``` Display plugin current config values.

#### ```[POST request]```
Not implemented yet.

### Endpoint ```/moodle_core```
#### ```[GET request]```
Return:
 - details about current moodle version.
 - check for available updates for moodle core
 - update logs

#### ```[POST request]```
Not implemented yet.