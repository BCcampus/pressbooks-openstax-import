=== Presbooks OpenStax Import ===
Contributors: bdolor, aparedes
Tags: pressbooks, openstax, textbook, import
Requires at least: 4.8.1
Tested up to: 4.8.1
Stable tag: 0.1.0
Requires PHP: 5.6
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

A WordPress plugin that extends [Pressbooks](https://github.com/pressbooks/pressbooks) to let you import books from OpenStax.

== Description ==

Adds an option to the PressBooks import tool named `Zip (OpenStax zip file)`. This new option asks for a link of the OpenStax Zip file. (You can get the link from the downloads section of the OpenStax book you'd like to import.)

**Primary Use Case**

This plugin was built primarily to support the creation, remixing, and distribution of open textbooks.

**Development Sprint**

The inspiration for this plugin came from wanting to improve our own process for bringing in OpenStax Textbooks; a laborious affair. In the fall of 2016 a Development Sprint was held in Houston at Rice University. One of the goals of the sprint was to be able to find a programmatic solution to the problem of converting equations during the import process. The XSL files by Vasil Yaroshevich are the key component used to convert equations to something that can be rendered in Pressbooks. While I was only able to attend the sprint remotely for a couple days, the hard work that other people put into that sprint contributed to this functionality.

== Installation ==

IMPORTANT!

You must first install [Pressbooks](https://github.com/pressbooks/pressbooks). This plugin won't work without it.
The Pressbooks github repository is updated frequently. [Stay up to date](https://github.com/pressbooks/pressbooks/tree/master).

ALSO IMPORTANT!

The value of this plugin is it's ability to transform MathML to LaTeX. Rendering that LaTeX in the browser requires a separate piece of functionality. The best results for rendering LaTeX equations in the browser is with [WP QuickLaTeX](https://wordpress.org/plugins/wp-quicklatex/). For best results, use that plugin. The built-in LaTeX rendering functionality in Pressbooks still works, but does not have as robust support for multi-line equations.

== Frequently Asked Questions ==

**What is an Open Textbook?**

Open Textbooks are open educational resources (OER); they are instructional resources created and shared in ways so that more people have access to them.
OER are defined as “teaching, learning, and research resources that reside in the public domain or have been released under an intellectual property license that permits their free use and re-purposing by others” (Hewlett Foundation).

== Screenshots ==

1. This new option asks for a link of the OpenStax Zip file

== Changelog ==

See: https://github.com/BCcampus/pressbooks-openstax-import/commits/dev for more detail

= 0.1.0 08/30/17 =
* initial release...early, often

= 0.1.0-RC1 =
* Release Candidate
* Will work with the latest development branch of Pressbooks and as yet-to-be released 4.0.0





