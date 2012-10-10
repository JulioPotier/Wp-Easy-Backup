=== WP Easy Backup ===

Script Name: WP Easy Backup
Script URI: http://www.boiteaweb.fr/wpeb
Author URI: http://www.boiteaweb.fr
Author: Julio Potier
Infos: based on Jonathan Buttigieg project https://github.com/GeekPress/WP-BackUp/
Version: 1.0
Tags: wordpress, security, admin, db
License: GPL

== Installation ==

1. Rename this file
2. Upload it in any folder, even at WordPress root install
3. Go to this file in your favorite browser
4. 3 choices, Backup DB, Backup Files, or Both
5. Do not forget to delete the file after use, it will be automatically deleted if you let the "red" checkbox checked.

== Description ==

This script is used to backup your DB and files for a WordPress installation.

== Usage ==

1. Backup DB:
	- All tables from WordPress installation (not those with your DB prefix, but those correctly installed via WordPress core and plugins/themes)
2. Backup Files:
	- Will backup all WordPress 
3. User deletion
	- Choose a user,
	- Choose another user which will receive all posts from the first one
	- Click "Delete"
	- If you do not choose a user for re-attribution, all posts will be deleted.
4. User edition
	- Choose a user,
	- Change his role,
	or/and
	- Change his pass,
	- Click "Edit"

== Hash md5 WP_Backdoor_User.php ==
80eae6d0c165d9772dd674a0beb7c0fe