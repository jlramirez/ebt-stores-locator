# EBT Stores Locator ##

EBT Stores Locator allows you to easily embed a map of stores that accept EBT (Electronic Benefits Transfer) cards in any Wordpress post or page by using shortcode. This plugin is a great for State services, agencies, non-profit organizations, or assistance programs looking to discover more places for recipients to purchase food locally, or anywhere they might be.  

* #1 Wordpress EBT retailer locator 
* Super fast and easy to use
 
Maps are displayed with the [ebt] shortcode.  

Note: This plugin relies on the [BlackNectar API](http://docs.blacknectarapi.apiary.io/#) service to search for stores in the United States that support EBT and [ArcGIS](https://www.arcgis.com/features/maps/index.html) to include basemaps.  

## Available Parameters ##

**search**  
Searches for stores with this in their names.  
Default: Deli  
Example: [ebt search="Market"]

**lat**  
The latitude decimal. Used in conjunction with longitude to specify a location-based search.  
Default: empty  
Example: [ebt lat="55.6578" lon="-33.7431"]  

**lon**  
The longitude decimal. Used in conjunction with latitude to specify a location-based search. Searches for stores near the specified geo coordinate.  
Default: empty  
Example: [ebt lat="43.6578" lon="-15.9679"]  

**radius**  
Defines the radius (in meters) for the geo-query. Radius can only be used with latitude and longitude.  
Default: empty  
Example: [ebt lat="40.8478522" lon="-73.906" radius="10000"]  

**zip_code**  
Searches for store in this Zip Code. This parameter can be used in place of latitude & longitude. The radius parameter has no effect on this query.    
Default: empty   
Example: [ebt zip_code="10457"]  

**width**  
Specify width of map in px or %.  
Default: 100%  
Example: [ebt width="70%"]  

**height**  
Specify height of map in px or %.  
Default: 500px  
Example: [ebt height="350px"]  

## Installation ##

1. Upload `ebt-stores-locator` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the *Plugins* menu in WordPress.
3. Add the shortcode to a post or page.

## Frequently Asked Questions ##

**What is the maximum number of results returned?**  
The maximum number of results returned is 1000.

**Why isn't a shortcode working as I expected!?**  
Check the spelling of the parameters in the shortcode. Valid parameters are search, lat, lon, radius, zip_code, width and height.
