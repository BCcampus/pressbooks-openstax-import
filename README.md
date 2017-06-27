# Presbooks OpenStax Import #

A WordPress plugin that extends [Pressbooks](https://github.com/pressbooks/pressbooks) to let you import books from OpenStax. 

## Description ##

Adds an option to the PressBooks import tool named `Zip (OpenStax zip file)`. This new option asks for a link of the OpenStax Zip file. (You can get the link from the downloads section of the OpenStax book you'd like to import.)     

**Primary Use Case**

This plugin was built primarily to support the creation, remixing, and distribution of open textbooks.

FAQ
------------

**What is an Open Textbook?**

Open Textbooks are open educational resources (OER); they are instructional resources created and shared in ways so that more people have access to them. 
That’s a different model than traditionally-copyrighted materials. 
OER are defined as “teaching, learning, and research resources that reside in the public domain or have been released under an intellectual property license that permits their free use and re-purposing by others” (Hewlett Foundation).

## Installation ##

1. Upload `pressbooks-openstax-import` to the `/wp-content/plugins/` directory
1. Run `composer install` in this plugins directory to install dependencies
1. Optional: install and network activate [WP QuickLaTeX](https://wordpress.org/plugins/wp-quicklatex/) to enable suport for multi-line math formulas and svg image export. 
1. Network Activate pressbooks-openstax-import through the 'Plugins' menu in PressBooks
1. Navigate to `tools -> Import` and select `Zip (OpenStax zip file)` from the dropdown menu 

## Screenshots ##
![screenshot](/pb-os-import.png?raw=true "import screenshot")
