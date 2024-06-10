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
By default, all responses are in ``` json ``` format.

### Endpoint ```/``` or ```/test_connection``` 
 
```http
GET / 
GET /test_connection
```
Return response with basic information about Moodle installation. No parameters needed.

#### Example response:
```json
{
  "url": "https://moodle.ddev.site",
  "site_fullname": "Moodle test page",
  "logo": "https://moodle.ddev.site/pluginfile.php/1/theme_mtul_slim/logo/300x300/1717771447/logo.png",
  "theme": "mtul_slim",
  "moodle_version": "4.3.2 (Build: 20231222)"
}
```

### Endpoint ```/dashboard```
```http
GET /dashboard
```
Return response with some additional information about Moodle installation. No parameters needed.

#### Example response:
```json
{
  "url": "https://moodle.ddev.site",
  "site_fullname": "Moodle test page",
  "site_shortname": "TitleShort",
  "logo": "https://moodle.ddev.site/pluginfile.php/1/theme_mtul_slim/logo/300x300/1717771447/logo.png",
  "logocompact": "https://moodle.ddev.site/pluginfile.php/1/theme_mtul_slim/logo/300x300/1717771447/logocompact.png",
  "theme": "mtul_slim",
  "moodle_version": "4.3.2 (Build: 20231222)"
}
```

### Endpoint ```/api_key_status```
```http
GET /api_key_status
```
Return information about API key (currently only 'key_expiration_date').
#### Example 1 response:
```json
{
  "key_expiration_date": "04/10/2024 13:34:50"
}
```

#### Example 2 response:
```json
{
  "key_expiration_date": "permanent"
}
```

```http
POST /api_key_status
```
Update existing API key.
#### Request body parameters
| Parameter             | Type                 | Description                                                        |
|:----------------------|:---------------------|:-------------------------------------------------------------------|
| `key_expiration_date` | `timestamp` \ `null` | **Required**. Validation end date timestamp or null for permanent. |

#### Possible response errors
| Status code | Status        | Message                          |
|:------------|:--------------|:---------------------------------|
| `400`       | `bad request` | Missing API key expiration_date. |


#### Request body example 1 (fixed date)
```json
{
    "data": {
        "key_expiration_date": 1234567899
        }
}
```
#### request body example 2 (api key is permanent)
```json
{
    "data": {
        "key_expiration_date": null
        }
}
```

#### Response example
```json
{
    "status": true,
    "key_expiration_date": "permanent"
}
```

### Endpoint ```/users```
```http
GET /users
```
Return list of all Moodle users with all available fields if no parameters provided.
#### Available parameters
| Parameter | Type     | Description                                                                          | Example                       |
|:----------|:---------|:-------------------------------------------------------------------------------------|:------------------------------|
| `search`  | `string` | **Optional**. A simple string to search for users.                                   | ?search=abc                   |
| `sort`    | `string` | **Optional**. A SQL snippet for the sorting criteria to use.                         | ?sort=firstname               |
| `fields`  | `string` | **Optional**. A comma separated list of fields to be returned from the chosen table. | ?fields=id,firstname,lastname |

#### Example request:
```http
GET /users?search=ab&sort=firstname DESC&fields=id,firstname,lastname
```

#### Example response:
```json
{
    "number_of_users": 2,
    "users": [
        {
            "id": "1234",
            "firstname": "Name 1",
            "lastname": "Lastname 1"
        },
        {
            "id": "5678",
            "firstname": "Name 2",
            "lastname": "Lastname 2"
        }
    ]
}
```

### Endpoint ```/users/online```
```http
GET /users/online
```
Return number of online users. If no parameters provided, get online users for last 5 minutes.
If both parameters provided, return online users between timeStart and timeEnd.

#### Available parameters
| Parameter   | Type        | Description                                            | Example               |
|:------------|:------------|:-------------------------------------------------------|:----------------------|
| `timeStart` | `timestamp` | **Optional**. User last access greater than timeStart. | ?timeStart=1716796022 |
| `timeEnd`   | `timestamp` | **Optional**. User last access lower then timeEnd.     | ?timeEnd=1716796022   |

#### Possible response errors
| Status code | Status        | Message                              |
|:------------|:--------------|:-------------------------------------|
| `400`       | `bad request` | StartTime must be less than endTime. |

#### Example request
```http
GET /users/online?timeStart=1716796022&timeEnd=1716796022
```
#### Example response
```json
{
    "all_users": 1136,
    "online": 719
}
```

### Endpoint ```/users/count```
```http
GET /users/count
```
Return number of all Moodle users. No parameters needed.
#### Exapmpe response
```json
{
    "number_of_users": 1135
}
```

### Endpoint ```/user```
#### ```[GET request]```
Return details for selected user. Filter can be provided in parameters. One of available parameters ins required.

#### Available parameters
| Parameter  | Type      | Description                | Example                 |
|:-----------|:----------|:---------------------------|:------------------------|
| `id`       | `integer` | Id of selected user.       | ?id=1234                |
| `username` | `string`  | Username of selected user. | ?username=user123       |
| `upn`      | `string`  | Upn of selected user.      | ?upn=user123            |
| `email`    | `email`   | Email of selected user.    | ?email=user@example.com |

#### Possible response errors
| Status code | Status        | Message                 |
|:------------|:--------------|:------------------------|
| `400`       | `bad request` | No parameters provided. |
| `400`       | `bad request` | User not found.         |

#### Examples request: 
```http
GET /user?email=user1@example.com
```

#### Example response
```json
{
    "user": {
        "id": "2",
        "auth": "manual",
        "username": "user1",
        "firstname": "Username 1",
        "lastname": "Lastname 1",
        "email": "user1@example.com",
        "...": "..."
    }
}

```

### Endpoint ```/plugins```
```http
GET /plugins
```
Return list of all moodle plugins. Display options can be provided via parameters. Parameters are like flags, no need for special values.

#### Available parameters
| Parameter           | Type   | Description                                              |
|:--------------------|:-------|:---------------------------------------------------------|
| `displayupdates`    | `flag` | **Optional**. Display available updates for each plugin. |
| `displayupdateslog` | `flag` | **Optional**. Display updates log for each plugin.       |

### Exaples reqest:
```html
GET /plugins?displayupdates
GET /plugins?displayupdateslog
GET /plugins?displayupdates&displayupdateslog
```

### Example response:
```json
{
    "last_check_for_updates": "1718014816",
    "plugins": [
        {
          "plugin": "multitopic",
          "plugintype": "format",
          "display_name": "Multitopic format",
          "component": "format_multitopic",
          "version": "2023111301",
          "enabled": true,
          "is_standard": false,
          "has_updates": true,
          "settings_section": "formatsettingmultitopic",
          "directory": "/course/format/multitopic",
          "update_available": [
            {
              "component": "format_multitopic",
              "version": "2024051601",
              "release": "v4.4.1",
              "maturity": 200,
              "url": "https://moodle.org/plugins/pluginversion.php?id=32099",
              "download": "https://moodle.org/plugins/download.php/32099/format_multitopic_moodle44_2024051601.zip",
              "downloadmd5": "bca35e6f08eb2749376c492219058042",
              "type": "plugin"
            }
          ],
          "update_log": [
            {
              "id": 1343,
              "type": 0,
              "plugin": "format_multitopic",
              "version": "2023111301",
              "targetversion": "2023111301",
              "info": "Plugin installed",
              "details": null,
              "backtrace": "",
              "userid": 2,
              "timemodified": 1707745358,
              "username": "admin",
              "email": "admin@example.com"
            }
          ]
        }
    ]
}
```

### Endpoint ```/plugins/updates```
```http
POST /plugins/updates
```
Install plugin updates provided in request body.

#### Possible response errors
| Status code | Status        | Message               |
|:------------|:--------------|:----------------------|
| `400`       | `bad request` | No updates specified. |

#### Example request body
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
          "model_id": 4567,
          "component": "theme_learnr",
          "version": "2024021300",
          "release": "4.3.3",
          "download" : "https://moodle.org/plugins/download.php/31128/theme_learnr_moodle43_2024021300.zip"
        }
     ]
   }
}
```
#### Example response
```json
{
    "updates": [
        {
            "1234": {
                "status": true,
                "component": "tool_opencast",
                "error": null
            }
        },
        {
            "4567": {
                "status": false,
                "component": "tool_opencast",
                "error": "Plugin not exist in Moodle."
            }
        }
    ]
}
```

### Endpoint ```/plugins/installzip```
```html
POST /plugins/installzip
```
Install plugins from zip files, provided in request body.

#### Possible response errors
| Status code | Status        | Message                      |
|:------------|:--------------|:-----------------------------|
| `400`       | `bad request` | No zip files urls specified. |

#### Example request body
```json
{
  "data": {
    "updates": [
      "https://moodle.org/plugins/download.php/31629/theme_adaptable_moodle43_2023111805.zip",
      "https://link2.zip"
    ]
  }
}
```

#### Example response body
```json
{
  "updates": {
    "https://moodle.org/plugins/download.php/31629/theme_adaptable_moodle43_2023111805.zip": {
      "component": "theme_adaptable",
      "status": true,
      "error": null
    },
    "https://link2.zip": {
      "component": null,
      "status": false,
      "error": "Invalid zip file."
    }
  }
}
```

### Endpoint ```/plugin```
```html
GET /plugin
```
Return detailed information for selected plugin. Please use component name (frankenstyle) for selected plugin (see example)

#### Available parameters
| Parameter           | Type      | Description                              | Example            |
|:--------------------|:----------|:-----------------------------------------|:-------------------|
| `plugin`            | `integer` | **Required**. Plugin component name..    | ?plugin=theme_mtul |
| `displayupdates`    | `flag`    | **Optional**. Display available updates. | ?displayupdates    |
| `displayupdateslog` | `flag`    | **Optional**. Display updates log.       | ?displayupdateslog |
| `displayconfig`     | `flag`    | **Optional.**. Display current config.   | ?displayconfig     |

#### Possible response errors
| Status code | Status        | Message               |
|:------------|:--------------|:----------------------|
| `400`       | `bad request` | Plugin not specified. |
| `400`       | `bad request` | Plugin not exist.     |

#### Example request:
```http
GET /plugin?plugin=theme_mtul&displayupdates&displayupdateslog&displayconfig
```

#### Example response:
```json
{
    "plugininfo": {
        "type": "theme",
        "typerootdir": "/var/www/html/public/theme",
        "name": "mtul",
        "displayname": "MTUL",
        "source": "ext",
        "rootdir": "/var/www/html/public/theme/mtul",
        "versiondisk": 2024032700,
        "versiondb": "2024032700",
        "versionrequires": 2016070700,
        "pluginsupported": null,
        "pluginincompatible": null,
        "release": "1.1.0",
        "instances": null,
        "sortorder": null,
        "supported": null,
        "incompatible": null,
        "component": "theme_mtul"
    },
    "last_check_for_updates": "1718015575",
    "update_available": [],
    "update_log": [
        {
            "id": 1550,
            "type": 0,
            "plugin": "theme_mtul",
            "version": "2024032700",
            "targetversion": "2024032700",
            "info": "Plugin upgraded",
            "details": null,
            "backtrace": "",
            "userid": null,
            "timemodified": 1711534904,
            "username": null,
            "email": null
        }
    ],
    "pluginconfig": {
        "brandcolor": "",
        "faq_type": "login_page",
        "logo": "/UL_AGRFT-logoVER-RGB_barv.svg",
        "logocompact": "/UL_AGRFT_logoHOR-RGB_barv.svg",
        "preset": "default.scss",
        "summaryheight": "50px",
        "welcome_message_en": "Welcome in English language."
    }
}
```

### Endpoint ```/moodle_core```
```http
GET /moodle_core
```
Return:
 - details about current moodle version.
 - check for available updates for moodle core
 - update logs

#### ToDo
- [ ] create parameters (displayupdates, displayupdateslog)

### Endpoint ```/moodle_core/update```
```http
GET /moodle_core
```
Update Moodle Core. Not implemented yet.

#### ToDo
- [ ] create update install script

### Endpoint ```/admin_presets```
```http
GET /admin_presets
```
Create request for generate admin presets xml configuration export for Moodle core and all installed plugins.
Return status for accepted request and then make ```post``` request to Moopanel app when xml is generated. 

#### Available parameters
| Parameter    | Type      | Description                                          | Example          |
|:-------------|:----------|:-----------------------------------------------------|:-----------------|
| `instanceid` | `integer` | **Required**. Id of Moodle instance page in Moopanel | ?instanceid=1234 |

#### Possible response errors
| Status code | Status                | Message                                |
|:------------|:----------------------|:---------------------------------------|
| `501`       | `not implemented`     | Admin presets plugin not found.        |
| `503`       | `service unavailable` | Service Unavailable - try again later. |
| `403`       | `bad request`         | Please provide a valid instance ID.    |

#### Example request
```http
GET /admin_presets?instanceid=1234
```

#### Example response
```json
{
    "status": true,
    "message": "Admin presets creation in progress"
}
```
#### ToDo
- [ ] send POST request custom headers (status )
- [ ] send POST request custom headers (X-API-KEX )

