# Openstax Import for Pressbooks #
[![Build Status](https://travis-ci.com/BCcampus/pressbooks-openstax-import.svg?branch=dev)](https://travis-ci.com/BCcampus/pressbooks-openstax-import)

A WordPress plugin that extends [Pressbooks](https://github.com/pressbooks/pressbooks) to let you import openly licensed books from OpenStax.

## Description ##

Adds an option to the PressBooks import tool named `Zip (OpenStax zip file)`. This new option asks for a link of the OpenStax Zip file. (You can get the link from the downloads section of the OpenStax book you'd like to import.)     

**Primary Use Case**

This plugin was built primarily to support the creation, remixing, and distribution of open textbooks.

**Development Sprint**

The inspiration for this plugin came from wanting to improve our own process for bringing in OpenStax Textbooks. In the fall of 2016 a Development Sprint was held in Houston at Rice University. One of the goals of the sprint
was to be able to find a programmatic solution to the problem of converting equations during the import process. The XSL files
by Vasil Yaroshevich are the key component used to convert equations to something that can be rendered in Pressbooks. While I was only able to
attend the sprint for a couple days remotely, would like to acknowledge the work that other people did at that sprint.

FAQ
------------

**What is an Open Textbook?**

Open Textbooks are open educational resources (OER); they are instructional resources created and shared in ways so that more people have access to them.
OER are defined as “teaching, learning, and research resources that reside in the public domain or have been released under an intellectual property license that permits their free use and re-purposing by others” (Hewlett Foundation).

## Installation ##

IMPORTANT!

You must first install [Pressbooks](https://github.com/pressbooks/pressbooks). This plugin won't work without it.
The Pressbooks github repository is updated frequently. [Stay up to date](https://github.com/pressbooks/pressbooks/tree/master).

ALSO IMPORTANT!

The value of this plugin is it's ability to transform MathML to LaTeX. Rendering that LaTeX in the browser
requires a separate piece of functionality. The best results for rendering LaTeX equations in the browser is with
[WP QuickLaTeX](https://wordpress.org/plugins/wp-quicklatex/). For best results, use that plugin. The built-in LaTeX rendering
functionality in Pressbooks still works, but does not have as robust support for multi-line equations.

## Using Git ##

1. cd /wp-content/plugins
2. git clone https://github.com/BCcampus/pressbooks-openstax-import.git
3. Activate the plugin at the network level, through the 'Plugins' menu in WordPress
4. Navigate to `tools -> Import` and select `Zip (OpenStax zip file)` from the dropdown menu 

## OR, upload manually ##

1. Unzip and Upload the latest release of `pressbooks-openstax-import` to the `/wp-content/plugins/` directory
2. Activate the plugin at the network level, through the 'Plugins' menu in WordPress
3. Navigate to `tools -> Import` and select `Zip (OpenStax zip file)` from the dropdown menu

## Developers ##
1. cd /wp-content/plugins
2. git clone https://github.com/BCcampus/pressbooks-openstax-import.git
3. Run `composer install --dev` in this plugins directory to install dependencies
4. Optional: install [WP QuickLaTeX](https://wordpress.org/plugins/wp-quicklatex/) and activate at the book level to enable support for multi-line math formulas and svg image export.
6. Navigate to `tools -> Import` and select `Zip (OpenStax zip file)` from the dropdown menu

## Screenshots ##
![screenshot](/assets/img/pb-os-import.png?raw=true "import screenshot")
