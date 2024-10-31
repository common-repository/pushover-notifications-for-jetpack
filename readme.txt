=== Pushover Notifications for Jetpack === 
Contributors: cklosows 
Tags: pushover, push notifications, jetpack, stats, visits
Stable tag: 1.0.2.2
Requires at least: 3.4
Tested up to: 4.0
Donate link: https://wp-push.com/donations/
 
Integrates Jetpack with the Pushover Notifications for WordPress plugin.

== Installation ==
1. Activate the plugin
2. Enjoy visits stats pushed to your mobile every day.

== Frequently Asked Questions ==
If you have questions please leave them in the WordPress.org Forums.

== Changelog ==
= 1.0.2.2 =
* NEW: PHPDoc blocks are added
* FIX: Should correct an issue where daily stats cron runs after midnight, not allowing the stats to be correct. The cron will still run after midnight once DST is executed, however if it's detected that the day has changed over, the stats will pull from 'yesterday'

= 1.0.1 =
* FIX: Fixing a spacing issue on the Top Viewed Page entry

= Version 1.0 =
* This is the initial release of the plugin.

== Description ==
Currently only supports the Stats Module of Jetpack, but more are in the works.

Get a daily re-cap of your most viwed page and your total visits pushed directly to your mobile device through Pushover Notifiations for WordPress Jetpack integration. This plugin requires that Pushover Notifiations for WordPress be activated and configured to work correctly.
