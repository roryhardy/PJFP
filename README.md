# Picasa JSON Feed Parser (PJFP)

PJFP was developed to capture the RSS feed from Picasa in JSON. It currently parses images, image width, image height, and captions from the specified Picasa galleries. It returns a data array. You can use it to include Picasa Album information in your gallery or website.

## Quick start

Clone the git repo - `git clone git@github.com:gneatgeek/PJFP.git` - or [download the current tag](https://github.com/gneatgeek/PJFP/zipball/v1.5)

## Features

* Album authkey support for non-public albums
* Built in error handling via exceptions
* Can access Picasa via curl or sockets
* Get the data as a numeric, associative, or a merged array
* Get the URL, Width, Height, and Caption information from images in your Picasa Web Album
* Loose coupling design makes it easy to use in your project.
* Inline and accompanying documentation.
* Override default config by simply passing an associative array of config options to the constructer

## Notes

PJFP\_config.php __IS REQUIRED__ by PJFP.php
Be sure to have it available or this system will not function! You can specify an alternate filename and/or path in the class variables.

If you prefer to use .inc instead of .php for include files,

Adding: 

>\<FilesMatch "\\.inc$"\>

>>Order allow,deny

>>Deny from all

>\</FilesMatch\>

to your .htaccess file is recommended.
If a .inc file is requested, all of the code will be revealed as plain text (which is usually very undesirable).
