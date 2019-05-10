Facebook Login
=================

Adds the ability for users to log into your site using Facebook's API

## Requirements

* SilverStripe 4.3

## Installation

Installation is supported via composer only

```sh
composer require webbuilders/facebook
```

* Run `dev/build?flush=all` to regenerate the manifest

## Usage

Add your Facebook App ID and Secret to your config yml file

```yml
Webbuilders\Facebook\Extension\FacebookControllerExtension:
  fb_app_id: {your-app-id}
  fb_app_secret: {your-app-secret}
```

To access the Facebook login link use `$FBLoginLink` in your template like so

```html
<a href="$FBLoginLink">Login with Facebook</a>
```

## Reporting an issue

When you're reporting an issue please ensure you specify what version of SilverStripe you are using i.e. 4.0.0,
4.1, 4.3 etc. Also be sure to include any JavaScript or PHP errors you receive, for PHP errors please ensure
you include the full stack trace. You may also be asked to provide some of the classes to aid in re-producing the
issue. Stick with the issue, remember that you've seen the issue not the maintainer of the module so it may take a lot of 
questions to arrive at a fix or answer.

### Notes

* Facebook's API refuses to work with insecure connections, so for testing you will have to set up SSL locally

## Contributing

### Translations

