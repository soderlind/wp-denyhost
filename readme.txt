=== WP-DenyHost ===
Contributors: PerS
Donate link: http://soderlind.no/donate/
Tags: deny host,spam,akismet
Requires at least: 2.8.0
Tested up to: 2.9
Stable tag: trunk

WP-DenyHost denies a spammer from accessing your WordPress blog

== Description ==

Based on a users IP address, WP-DenyHost will block a spammer if he already has been tagged as a spammer. Use it together with the Akismet plugin. Akismet tags the spammer, and WP-DenyHost prevents him from accessing you site.

== Installation ==

= Manual Installation =
* Upload the files to wp-content/plugins/wp-denyhost/
* Activate the plugin

= Automatic Installation =
* On your WordPress blog, open the Dashboard
* Go to Plugins->Install New
* Search for "wp-denyhost"
* Click on install to install WP-DenyHost

= Configuration =
In Setting->WP-DenyHost, set the threshold and response. WP-DenyHost is inactive until you've set these options.

== Screenshots ==

1. Option Page

== Changelog ==

= 1.1.1 = 
* Added languages/wp-denyhost.pot

= 1.1.0 = 
* Major rewrite. Added option page

= 1.0.1 = 
* Replaced LIKE (‘%$suspect%’) with = ‘$suspect’ i.e. look for exact match

= 1.0 = 
* initial release