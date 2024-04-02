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
All endpoints url starts with ```Moodle root url``` + ```/local/moopanel/index.php```.


### Endpoint ```/``` or ```/test_connection``` 
#### ```[GET request]```
Return response with basic information about Moodle


### Endpoint ```/dashboard```
#### ```[GET request]```
Return response with some additional information about Moodle


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
Return list of Moodle users.
Filters can be provided in request body.
  - request body example
```json
{
    "data": {
        "search": "abc",
        "confirmed": true,
        "ignoreids": [1,3,5,7,99],
        "sort": "firstname ASC",
        "firstinitial": "A",
        "lastinitial": "B",
        "page": 3,
        "limit": 10,
        "fields": "firstname,lastname,username"
        }
}
```
Available options:
  - string ```"search"``` A simple string to search for
  - bool ```"confirmed"``` A switch to allow/disallow unconfirmed users
  - array ```"ignoreids"``` A list of IDs to ignore, eg 2,4,5,8,9,10
  - string ```"sort"``` A SQL snippet for the sorting criteria to use
  - string ```"firstinitial"``` Users whose first name starts with $firstinitial
  - string ```"lastinitial"``` Users whose last name starts with $lastinitial
  - string ```"page"``` The page or records to return
  - string ```"limit"``` The number of records to return per page
  - string ```"fields"``` A comma separated list of fields to be returned from the chosen table.


Available URL parameters:
  - ```/count``` - return number of users (based on filters from request body) 
  - ```/online``` - return online users (check for access for last 5 minutes)

#### ```[POST request]```
Not implemented yet.


### Endpoint ```/user```
#### ```[GET request]```
Return details for selected user
Filter can be provided in request body.
Please use just one of filters provided in example (```"id"``` or ```"username"``` or ```"email"```)
    - request body example
```json
{
    "data": {
        "id": 123,
        "username": "user123",
        "upn": "user@domain.com"
        }
}
```
#### ```[POST request]```
Not implemented yet.


### Endpoint ```/plugins```
#### ``` [GET request]```
Return list of all moodle plugins
Filters can be provided in URL parmeters.

Available URL parameters:
  - ```/contrib``` - return list of contrib plugins (manually installed plugins, not core plugins)
  - ```/updates``` - return list of plugins which has available updates
  - ```/contrib/updates``` - return list of contrib plugins which has available updates

#### ```[POST request]```
Not implemented yet.


### Endpoint ```/plugin```
#### ```[GET request]```
Return details for selected plugin
Please use component name (frankenstyle) of selected plugin (see example)
 - request body example
```json
{
    "data": {
        "plugin": "theme_boost"
        }
}
```
- Available URL parameters:
  - ```/config``` - return list of current config for selected plugin.

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