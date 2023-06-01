# PHP Process Parameters

This is a small library to simplify parameters and headers validation.
It automatically handles errors for defined parameters and returns JSON response.

### Configuration file parameters
|Name|Default|Description|
|---|---|---|
|result_code_root|result|Element for result code in response|
|successful_response_root|body|Root data element for successful response|
|error_response_root|error|Root data element for error response|
|array_delimiter|,|Array delimiter for parameters|
|use_post_method|false|Set to "true" to use POST request method|
|response_headers|n/a|Array with headers to add to response|
|parameters|n/a|List of parameters for validation and processing|
|headers|n/a|List of HTTP headers for validation and processing|
|php_error_reporting|n/a|Set error reporting level|
|php_set_time_limit|n/a|Set script execurion time limit|
|php_memory_limit|n/a|Set script memory limit|

### Parameter and header types
|Name|Type|Example|
|---|---|---|
|string|String|Hello, World!|
|integer|Integer|42|
|float|Float|3.14|
|boolean|Boolean|true|
|array|Array|one,two,three|
|json|JSON|{"foo": "bar"}|
|file|File|... **Only for "POST" requests**|

### Available options for parameters and hearers
|Name|Type|Description|
|---|---|---|
|is_required|Boolean|Creates a required parameter
|default|Any Type|Set default value for parameter or header
|min|Integer or Float|Minimal value for Integer or Float types, minimal string length for String type or array length for Array type|
|max|Integer or Float|Maximal value for Integer or Float types, maximal string length for String type or array length for Array type|
|regex|String or Array|RegEx expressions to validate parameter value of string or each item in array


## cURL Example
```
$ curl example/index.php?email=some@email.com&age=18&height=11.5&active=false&features=red,green,blue&config={"body": {"key": "value"}}
```

## Succesfull Response Example
```
{
  "result": 200,
  "body": {
    "processed_parameters": {
      "email": "some@email.com",
      "age": 18,
      "active": true,
      "height": 11.5,
      "features": [
        "red",
        "green",
        "blue"
      ],
      "config": {
        "body": {
          "key": "value"
        }
      },
      "nonce": 0
    },
    "processed_headers": {
      "Accept-Language": "ru,en;q=0.9,ru-RU;q=0.8,en-US;q=0.7,az;q=0.6",
      "Connection": "keep-alive",
      "Authorization": "Bearer 76d80224611fc919a5d54f0ff9fba446"
    }
  }
}
```

## Unsuccessful Response Example
```
{
  "result": 400,
  "error": "Wrong parameter type: email"
}
```