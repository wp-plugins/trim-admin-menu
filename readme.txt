=== Trim Admin Menu ===
Contributors: lantash
Tags: admin, unclutter, zen, minimize, menu, reduce, hide, simple, easier, cms
Requires at least: 2.8
Tested up to: 2.8.4
Stable tag: 1.3

Hide menu items in the admin interface from non-admin users.

== Description ==
Hide menu items in the admin interface from non-admin users. It's still possible
for them to access the corresponding pages by entering the URL directly.

== Installation ==

Install `Trim Admin Menu` directly from the plug-in section in your Wordpress
admin environment.

== Frequently Asked Questions ==

= Why isn't it possible to hide the Dashboard? =

A future version of this plug-in might support it. It would be necessary
to specify what page non-admin users should be redirected to when logging in.

== Screenshots ==

1. Both the Links and Comments sections have been marked to be hidden.
   Please note that the administrator doesn't see the changes.

== Changelog ==

= 1.3 =
* The logic that automatically activates/deactivates related checkboxes has been
  fixed. If a main menu item is hidden and the user decides to unhide a submenu
  item, also unhide the main menu item.

= 1.2 =
* Screenshot added.

= 1.1 =
* Make use of JQuery to deactivate the checkboxes of all submenu items if the
  corresponding main menu item is hidden.
* German translation added.
* First FAQ added.

= 1.0 =
* Initial version.
