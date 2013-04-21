=== WP-DenyHost ===
Contributors: PerS
Donate link: http://soderlind.no/donate/
Tags: deny host,spam,akismet,cloudflare
Requires at least: 3.2.0
Tested up to: 3.5.1
Stable tag: trunk

WP-DenyHost denies a spammer from accessing your WordPress blog

== Description ==

Based on a users IP address, WP-DenyHost will block a spammer if he already has been tagged as a spammer. Use it together with the Akismet plugin. Akismet tags the spammer, and WP-DenyHost prevents him from accessing you site.

If you have a [CloudFlare](https://www.cloudflare.com) account, the plugin can add spamers to [CloudFlare Block list](https://www.cloudflare.com/threat-control)

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
In Settings -> WP-DenyHost, set the threshold and response. Default threshold is 3, default response is 403 Forbidden.

If you have a [CloudFlare](https://www.cloudflare.com) account, you can enable CloudFlare and spammers will be added to the [CloudFlare Block list](https://www.cloudflare.com/threat-control)

== Screenshots ==

1. Option Page
2. CloudFlare Block list

== Changelog ==
= 1.2.4 =
* replaced wp_print_scripts hook with admin_enqueue_scripts hook
= 1.2.3 =
* removed PHP 4 "constructor"
= 1.2.2 =
* bug fix
= 1.2.1 =
* added ps_wp_denyhost_admin_init, triggered by admin_init hook
= 1.2.0 =
* Added support for CloudFlare Block list + removed wp deprecated code
= 1.1.3 =
* Fixed minor bug

= 1.1.2 = 
* Added response 403 Forbidden

= 1.1.1 = 
* Added languages/wp-denyhost.pot

= 1.1.0 = 
* Major rewrite. Added option page

= 1.0.1 = 
* Replaced LIKE (‘%$suspect%’) with = ‘$suspect’ i.e. look for exact match

= 1.0 = 
* initial release