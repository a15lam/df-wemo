# df-wemo
A DreamFactory service to control [Belkin Wemo](http://www.belkin.com/us/Products/c/home-automation/) devices. It also supports Sonoff devices with custom firmware found [here](https://github.com/a15lam/sonoff/blob/master/arduino/SSDP_and_webserver/SSDP_and_webserver.ino).

# Installation
In your DreamFactory installation root run 

    composer require a15lam/df-wemo
    
Add the following *AT THE END* of the list of <code>providers</code> in config/app.php file.

    a15lam\Wemo\ServiceProvider::class
    
Login to your DreamFactory admin application and head over to the 'Services' tab. You should now see your new <code>wemo</code> service listed here. 

Your service is now configured and ready to use. Head over to the API Docs and expand your wemo service to see all the API endpoints and their usage.

Note: Unlike other services in DreamFactory, wemo service does not require any configuration. The service is auto-configured for you upon installation with the default name wemo. 
If you like to change the default name to something else, you can do so by using the environment option WEMO_SERVICE_NAME in the .env file. 
Once you add your name using this environment option, simply refresh your DreamFactory admin application to see this service with the new name. 
You can now safely delete the other service with the old name.

You can delete this wemo service anytime you want. However, it will keep coming back every time you clear your DreamFactory cache. 
To remove this service permanently comment out the following line from <code>providers</code> list in config/app.php.

    a15lam\Wemo\ServiceProvider::class
