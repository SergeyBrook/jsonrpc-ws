# Examples

## Contents
- `console.php` - Example console application.
- `index.html` - Example web page to demonstrate sending requests to service.
- `service.php` - Example service implementation.
- `[inc]` - Includes classes used by `service.php` to demonstrate some possibilities.
- `[tests]` - Includes [Postman](https://www.postman.com/) collection with test requests.

## Run console application
From library examples dir run:
```shell
php ./console.php
```

## Run example service
From library examples dir run PHP Development Server:
```shell
php -S localhost:80
```
The example service will be available at `http://localhost/service.php`.

## View in web-browser
While example service running:
- In web-browser navigate to `http://localhost`.
- Press `Single request`, `Batch request` and `Invalid request` buttons to make requests.
- Response will be shown in textarea below the buttons. 

## Test requests
Import `tests/jsonrpc-ws.postman_collection.json` into Postman.
After importing, it will appear as `JSON-RPC WS Examples` collection in 'Collections' tab.
All requests configured to work with example service. 
