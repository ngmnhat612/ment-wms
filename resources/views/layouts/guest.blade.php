<!DOCTYPE html>
  <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="csrf-token" content="{{ csrf_token() }}">
      <title>{{ config('app.name', 'Ment WMS') }}</title>
      @include('layouts.partials.head')
  </head>
  <body class="bg-body-secondary min-vh-100 d-flex align-items-center">
      <div class="container">
          <div class="row justify-content-center">
              <div class="col-12 col-sm-8 col-md-5 col-lg-4">
                  {{ $slot }}
              </div>
          </div>
      </div>
      @include('layouts.partials.footer')
  </body>
  </html>