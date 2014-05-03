GEO-SEO Wordpress plugin
========================

Installation
____________
You can install the plugin by uploading the ZIP using wordpress module installer.

If you have modified the source simply ZIP the folder and upload that to your WP site.

The installation creates some setting variable and the geoseo_loc table that is removed automatically when uninstalling.

Usage
_____
This is a plugin that is intended to create subpages of given pages. It multiplicates a page where you can insert a custom expression for each version.
You can recreate a subpage by changing an expression in it. The list of the expressions and the url of the subpage can be uploaded in a CSV file.

This should help in SEO purposes if you have a service that you provide in many locations (cities). 
For example: you only have to create plumbing and plumbing/sandiego plumbing/losangeles is automatically generated for you with a custom title.

The module has a widget that allows the users to choose their locations of a dropdown list.
You can insert your custom text by placing the [geoseo] code in your page. 
The expression can be surrounded by using the [geoseo pre='We have great services for plumbing in ' post=' as well.'] syntax. 
The result is We have great services for plumbing in San Diego as well.
If you want an automatic list of the generated pages, you can do this by inserting the [geoseolist name='plumbing'] code inside your page.

The settings are found in the Tools->GEOSEO settings menu. The first field enables the upload of the list that contains the expressions and the subpage alias in the form of:
"budapest","Budapest"
"gyor","Gyõr"

The file should be encoded in UTF-8 without BOM.

You can check which pages should be used by this plugin. 
The uploaded expressions are displayed on the bottom of the page. All changes are stored after pressing the SEND button.

Early version created in 2012.07.12