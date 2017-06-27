# Presbooks OpenStax Import #
**Contributors:** [(this should be a list of wordpress.org userid's)](https://profiles.wordpress.org/(this should be a list of wordpress.org userid's))  
**Donate link:** http://example.com/  
**Tags:** comments, spam  
**Requires at least:** 4.4  
**Tested up to:** 4.8  
**Stable tag:** 0.1.0  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

A WordPress plugin for PressBooks that lets you import books from OpenStax.   

## Description ##

Adds an option to the PressBooks import tool named `Zip (OpenStax zip file)`. This new option asks for a link of the OpenStax Zip file. (You can get the link from the downloads section of the OpenStax book you'd like to import.)     

## Installation ##

1. Upload `pressbooks-openstax-import` to the `/wp-content/plugins/` directory
1. Run `composer install` in this plugins directory to install dependencies
1. Optional: install and network activate [WP QuickLaTeX](https://wordpress.org/plugins/wp-quicklatex/) to enable suport for multi-line math formulas and svg image export. 
1. Network Activate pressbooks-openstax-import through the 'Plugins' menu in PressBooks
1. Navigate to `tools -> Import` and select `Zip (OpenStax zip file)` from the dropdown menu 

## Screenshots ##
![screenshot](/pb-os-import.png?raw=true "import screenshot")
