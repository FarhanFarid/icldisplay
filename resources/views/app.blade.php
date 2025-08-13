<!DOCTYPE html>
<html>
  <head>
    <title>ICLDISPLAY</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{{asset('icon/ijn-logo.png')}}" />
    @routes
    @vite('resources/js/app.js')
    @vite('resources/css/app.css')
    @inertiaHead
  </head>
  <body>
    @inertia
  </body>
</html>