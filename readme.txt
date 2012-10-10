=== WP Easy Backup ===

Script Name: WP Easy Backup
Script URI: http://www.boiteaweb.fr/WPEB
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

This script is used to create, delete, login, edit a user in a WordPress installation when you do not have dashboard access but only FTP access. Just rename, upload, surf it and read.

== Usage ==

1. User creation:
	- Fill the fields for login, pass, email,
	- Choose a role,
	- Check or not the login box, if yes, you'll be logged in with this user,
	- Click "Create".
	- For each missing fields, a random value will be created.
		* Example for a random editor user : "editor_2j1p12"
		* Example for a random pass : mmm really random, change it or lose this account ;)
		* Example for random email : 134659872145@fake134659872145.com
2. User log in
	- Choose a user,
	- Click "Log in".
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