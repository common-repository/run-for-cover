=== Run for Cover ===
Contributors: Jeroen Smeets
Donate link: http://jeroensmeets.net/
Tags: widget, lastfm, last-fm, cd-cover, cd, cover, amazon
Requires at least: 2.5
Tested up to: 2.7
Stable tag: 2.0.6

Trying to get this plugin running again. Less in beta.

== Description ==

This widget shows the covers for cds you listened to recently. It requests this info @ last.fm, and if image info is available, displays the cd cover and the artist and title of the track.

== Installation ==

Save in your plugins folder and activate the widget.

Should you get an error about loading jQuery, your theme also loads it. Solution for now is to remove line 141

`<script type='text/javascript' src='<?php echo rfc_siteurl(); ?>/wp-includes/js/jquery/jquery.js'></script>`

from runforcover.php