# opcache-gui

Original source can be found at https://github.com/amnuts/opcache-gui/

# Description

Port of amnuts/opcache-gui to provide separate model and view.

A clean and responsive interface for Zend OPcache information, showing statistics, settings and cached files, and providing a real-time update for the information (using jQuery and React).

[![Flattr this git repo](http://api.flattr.com/button/flattr-badge-large.png)](https://flattr.com/submit/auto?user_id=acollington&url=https://github.com/amnuts/opcache-gui&title=opcache-gui&language=&tags=github&category=software)

### Getting started

There are two ways to getting started using this gui.

1. Simply to copy/paste or download the `/dist/index.php` to your server.
If you want to set the configuration options just alter the array at the top of `/dist/index.php`:
```php
$options = [
    'allow_filelist'   => true,  // show/hide the files tab
    'allow_invalidate' => true,  // give a link to invalidate files
    'allow_reset'      => true,  // give option to reset the whole cache
    'allow_realtime'   => true,  // give option to enable/disable real-time updates
    'refresh_time'     => 5,     // how often the data will refresh, in seconds
    'size_precision'   => 2,     // Digits after decimal point
    'size_space'       => false, // have '1MB' or '1 MB' when showing sizes
    'charts'           => true,  // show gauge chart or just big numbers
    'debounce_rate'    => 250    // milliseconds after key press to send keyup event when filtering
];
```

2. Install via composer by editing your `composer.json` file
```
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/fyrye/opcache-gui"
    }
],
"require": {
    "fyrye/opcache-gui": "^2.0"
}
```

Then use the following code to display the GUI.
```php
require __DIR__ . '/../vendor/autoload.php';
$options = [
    'allow_filelist'   => true,  // show/hide the files tab
    'allow_invalidate' => true,  // give a link to invalidate files
    'allow_reset'      => true,  // give option to reset the whole cache
    'allow_realtime'   => true,  // give option to enable/disable real-time updates
    'refresh_time'     => 5,     // how often the data will refresh, in seconds
    'size_precision'   => 2,     // Digits after decimal point
    'size_space'       => false, // have '1MB' or '1 MB' when showing sizes
    'charts'           => true,  // show gauge chart or just big numbers
    'debounce_rate'    => 250    // milliseconds after key press to send keyup event when filtering
];
echo \fyrye\OpCacheGui\OpCacheTemplate::render($options);

```

## Releases

Original Source 
https://github.com/amnuts/opcache-gui/releases/

Releases of the GUI are available at:

https://github.com/fyrye/opcache-gui/releases/

# License

MIT: http://acollington.mit-license.org/
