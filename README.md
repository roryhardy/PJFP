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

## Demos & Example

* [GneatGeek Central](http://people.oregonstate.edu/~croninhr/)
* [OSU Foundation](http://osufoundation.org/fundraisingpriorities/facilities/lpsc/landing.htm)

The below example generates HTML for a javascript slide show.

```php
<?php
require_once('PJFP.php'); # Include PJFP
try{
	$gallery = new PJFP(ALBUMID); # Pass in the album ID of your gallery
	$data    = $gallery->get_data(); # Numeric Keys
	foreach($data as $val){
		printf("<div><img src=\"%s\" width=\"%s\" height=\"%s\" alt=\"Gallery Photo\"><br><p>%s</p></div>\n",
			$val[0],
			$val[1],
			$val[2],
			htmlentities($val[3])
		);
	}
}catch (Exception $e){
	echo("An Error occurred. Caught Exception: {$e->getMessage()}");	
}
?>
```

## Notes

PJFP\_config.php __IS REQUIRED__ by PJFP.php. Be sure to have it available or this system will not function! You can specify an alternate filename and/or path in the class variables.

The __album ID__ of your gallery can be located in the url on the __RSS__ feed.   
picasaweb.google.com/data/feed/base/user/113393706713351407880/albumid/__5677978555944856817__?alt=rss&kind=photo&hl=en_US

If you prefer to use .inc instead of .php for include files,

Adding: 

    <FilesMatch "\.inc$">
        Order allow,deny  
        Deny from all  
    </FilesMatch>

to your .htaccess file is recommended.
If a .inc file is requested without this rule all of the code may be revealed as plain text (which is usually very undesirable).
