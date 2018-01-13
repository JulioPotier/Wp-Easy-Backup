<?php
/*********************************************
=== WP Easy Backup ===

Script Name: WP Easy Backup
Script URI: http://www.boiteaweb.fr/WPEB
Author URI: http://www.boiteaweb.fr
Author: Julio Potier
Infos: based on Jonathan Buttigieg project https://github.com/GeekPress/WP-BackUp/
Version: 1.1
Tags: wordpress, security, admin, db
License: GPL
**********************************************/

// Security: Force the file to be renamed for security reasons
if( strtolower( basename( __FILE__ ) ) == 'wp-easy-backup.php' )
	die( 'EN: Please rename the file before use! FR : Merci de renommer le fichier avant utilisation !' );

// Security: Semi-random folder name to avoid crawlers
DEFINE( 'BACKUP_DIR', dirname( __FILE__ ) . '/wp-easy-backup-' . substr( md5( __FILE__ ), 0, 8 ) );

// Do this before doing a backup: Creation of backup dir, creation of htaccess file
function pre_backup()
{
	if( !is_dir( BACKUP_DIR ) ) 
		mkdir( BACKUP_DIR, 0750 );
	if( !is_dir( BACKUP_DIR ) ) 
		die( 'EN: Impossible to create the backup folder, please check your rights (CHMOD)! FR : Impossible de creer le dossier de sauvegarde, merci de verifier vos droits (CHMOD) !' );

	$htaccess_file = BACKUP_DIR . '/.htaccess';
	if( !file_exists( $htaccess_file ) ):
		$htaccess_file_content  = "Order Allow, Deny\n";
		$htaccess_file_content .= "Deny from all";
		file_put_contents( $htaccess_file, $htaccess_file_content );
	endif;
}

// Do this after backup : Delete old files (7 days or more)
function post_backup()
{
	$backup_max_life = 60 * 60 * 24 * 7; // 7 days
	foreach ( glob( BACKUP_DIR . '/*.{zip,sql}', GLOB_BRACE ) as $file )
		if( time() - filemtime( $file ) > $backup_max_life )
			@unlink( $file );
}

// Backup the DB
function backup_db()
{
	global $wpdb;

	$buffer = '';
	$backup_file = 'db-' . date( 'Y-m-d-H-i-s' ) . '.sql';

	// Start the loop on tables
	foreach ( $wpdb->tables() as $table ):
		// The real query, it gets all data from tables
		$table_data = $wpdb->get_results( 'SELECT * FROM ' . $table, ARRAY_A );
		
		$buffer .= '# Dump of table ' . $table . "\n";
		$buffer .= "# ------------------------------------------------------------ \n\n";
		$buffer .= 'DROP TABLE IF EXISTS ' . $table . ';';
		// Query for "Create table"
		$show_create_table = $wpdb->get_row( 'SHOW CREATE TABLE ' . $table, ARRAY_A );
		$buffer .= "\n\n" . $show_create_table['Create Table'] . ";\n\n";
		
		if( $table_data ):
			// Start to write the INSERT lines (1 per 50 data)
			$buffer .= 'INSERT INTO ' . $table . ' VALUES' . "\n";
			$mod = 0;
			$i = 1;
			$nb_data = count( $table_data );
			foreach ( $table_data as $row ):
				$mod++;
				$values = '(';
				foreach ( $row as $key => $value )
					 $values .= '"' . $wpdb->escape( $value ) . '", ';
				$buffer .= trim( $values, ', ' );
				if( $i == $nb_data ) {
					$to_end = ');';
				}else{
					$to_end = '),';
				}
				
				$buffer .= $mod % 50 == 0 ? ');' . "\n" . 'INSERT INTO ' . $table . ' VALUES' : $to_end . "\n";
				$i++;
			endforeach;
			// $buffer .= ');';
			$buffer .= "\n\n";
		
		endif;
		
	endforeach;
	// Put buffer content in file
	file_put_contents( BACKUP_DIR . '/' . $backup_file, $buffer );
	// Zip it if possible
	if( class_exists( 'ZipArchive' ) ) {
		$zip = new ZipArchive();
		if( $zip->open( BACKUP_DIR . '/' . $backup_file . '.zip', ZipArchive::CREATE ) === true ):
			$zip->addFile( BACKUP_DIR . '/' . $backup_file,$backup_file );
			$zip->close();
			@unlink( BACKUP_DIR . '/' . $backup_file ); // delete the .sql, we just created a .sql.zip
		endif;
		$backup_file .= '.zip';
	}
	return $backup_file;
}

// Backup the website's file
function backup_website() {

	$backup_file = 'web-' . date( 'Y-m-d-H-i-s' );
	// Can only be saved if Zip is available
	if( class_exists( 'ZipArchive' ) ) {
		// Extends class to create a recursive function
		class ZipRecursif extends ZipArchive {
			public function addDirectory( $dir )
			{
				foreach( glob( $dir . '/*' ) as $file ):
					if( is_dir( $file ) ):
						// Avoid including the backup dir
						if( basename( $file ) == basename( BACKUP_DIR ) ) continue;
						$this->addDirectory( $file );
					else:
						// Avoid including this script file
						if( basename( $file ) == basename( __FILE__ ) ) continue;
						$this->addFile( $file );
					endif;
				endforeach;
			}
		}
		$zip = new ZipRecursif;
		// Zip creation
		if( $zip->open( BACKUP_DIR . '/' . $backup_file . '.zip' , ZipArchive::CREATE ) === true ) {
			$zip->addDirectory( '.' );
			$zip->close();
		}
		return $backup_file . '.zip';
	}
}

// Used to download a requested file
function force_download_file( $file, $delete=false )
{
	// Security : force the location
	$file = BACKUP_DIR . '/' . basename( $file );
	if( !file_exists( $file ) )
		die( 'EN: Incorrect filename! FR : Nom de fichier incorrect !' );
	// Do not use compression
	if( ini_get( 'zlib.output_compression' ) ) 
		ini_set( 'zlib.output_compression', 'Off' );
	// Zip or not ?
	$mime = class_exists( 'ZipArchive' ) ? 'application/zip' : 'application/force-download';
	header( 'Pragma: public' );
	header( 'Expires: 0' );
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header( 'Last-Modified: ' . gmdate ( 'D, d M Y H:i:s', filemtime( $file ) ) . ' GMT' );
	header( 'Cache-Control: private', false );
	header( 'Content-Type: ' . $mime );
	header( 'Content-Disposition: attachment; filename="' . basename( $file ) . '"' );
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Content-Length: ' . filesize( $file ) );
	header( 'Connection: close' );
	readfile( $file );
	if( $delete )
		@unlink( $file );
	die();
}

// This is usef to create a zip with different other files (array)
function create_zip( $backup_file, $files, $delete=false )
{
	if( class_exists( 'ZipArchive' ) ) {
		$zip = new ZipArchive();
		if( $zip->open( BACKUP_DIR . '/' . $backup_file . '.zip', ZipArchive::CREATE ) === true ):
			// Loop on each file
			foreach( $files as $file )
				$zip->addFile( BACKUP_DIR . '/' . basename( $file ), basename( $file ) );
			$zip->close();
			if( $delete )
				foreach( $files as $file )
					@unlink( BACKUP_DIR . '/' . basename( $file ) );
			return BACKUP_DIR . '/' . $backup_file . '.zip';
		endif;
	}
}

// Load WordPress
define( 'SHORT_INIT', true );
while( !is_file( 'wp-load.php' ) ) {
	if( is_dir( '..' ) ) 
		chdir( '..' );
	else
		die( 'EN: Could not find WordPress! FR : Impossible de trouver WordPress !' );
}
require_once( 'wp-load.php' );

// Manage requested actions
if( isset( $_REQUEST['action'] ) ):
	
	$msg = '';
	// If possible, do not time limit the script
	if( !ini_get('safe_mode') )
		set_time_limit( 0 );
		
	switch( $_REQUEST['action'] ):
		
		// BACKUP
		case 'backup':
			// Delete this script file is requested
			if( isset( $_REQUEST['unlink'] ) && $_REQUEST['unlink'] == 1 ):
				@unlink( __FILE__ );
				$msg .= 'File deleted and ';
			endif;
			// Correct backup mode
			$backup_mode =  isset( $_REQUEST['backup_mode'] ) ? $_REQUEST['backup_mode'] : 'db';
			// Which mode ?
			switch( $backup_mode ):
				// ALL
				case 'all':
					$backup_file = 'all-' . date( 'Y-m-d-H-i-s' );
					pre_backup();
					$db_file = backup_db();
					$web_file = backup_website();
					post_backup();
					$files = array( $db_file, $web_file );
					create_zip( $backup_file, $files, true );
					if( isset( $_REQUEST['download'] ) && $_REQUEST['download']=='1' )
						force_download_file( BACKUP_DIR . '/' . $backup_file . '.zip' );
					$msg .= 'DB and Files saved.';
				break;
				// FILES
				case 'files':
					pre_backup();
					$web_file = backup_website();
					post_backup();
					if( isset( $_REQUEST['download'] ) && $_REQUEST['download']=='1' )
						force_download_file( BACKUP_DIR . '/' . $web_file );
					$msg .= 'Files saved.';
				break;				
				// DB
				case 'db':
					pre_backup();
					$db_file = backup_db();
					post_backup();
					if( isset( $_REQUEST['download'] ) && $_REQUEST['download']=='1' )
						force_download_file( BACKUP_DIR . '/' . $db_file );
					$msg .= 'DB saved.';
				break;
			endswitch;
		break;
		// DOWNLOAD
		case 'download':
			if( isset( $_REQUEST['file'] ) && is_array( $_REQUEST['file'] ) ):
				if( count( $_REQUEST['file'] ) == 1 ):
					force_download_file( BACKUP_DIR . '/' . $_REQUEST['file'][0], isset( $_REQUEST['delete'] ) && $_REQUEST['delete']=='1' );
				else:
					$backup_file = 'pack-' . date( 'Y-m-d-H-i-s' );
					$file = create_zip( $backup_file, $_REQUEST['file'], isset( $_REQUEST['delete'] ) && $_REQUEST['delete']=='1' );
					force_download_file( $file );
				endif;
			elseif( isset( $_REQUEST['deleteall'] ) ): // Delete all files in backup folder
				foreach ( glob( BACKUP_DIR . '/*.{zip,sql}', GLOB_BRACE ) as $file )
					@unlink( $file );
				$msg = 'Folder cleared.';
			endif;
		break;
		case 'delete_file':
			// just unlink the file
			@unlink( __FILE__ );
			$msg = 'File deleted!';
		break;
	endswitch;
endif;
// HTML CODE
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//FR" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title>WP Easy Backup v1.0</title>
		<style>
			* { font-family: verdana, tahoma, arial; }
			h1 { color: #000000; text-shadow: 0 0 4px white, 0 -5px 4px #FFFF33, 2px -10px 6px #FFDD33, -2px -15px 11px #FF8800, 2px -25px 18px #FF2200; }
			h1 a { text-shadow: none; }
			div { box-shadow: 2px 2px 1px #000000; float: left; height: 290px; width: 270px; padding: 10px; margin: 10px; border-radius: 5px; border: 2px dotted #cccccc; background-color: #eeeeee; }
			h2 { text-shadow: -1px -1px 1px #ffffff; padding: 0px; margin: 0px;border-bottom: 2px dotted #ffffff; }
			em {font-size: x-small; }
			.footer { clear: both; font-style: italic; font-size: small; padding-top: 15px; border-top: 1px dotted #000000; }
			.warning{ max-width: 1215px; background-color: #F54747; border: 1px dashed #6F0202; border-radius: 5px 5px 5px 5px; clear: both; font-weight: bold; margin: 10px; padding: 10px; }
			h1 > span { font-size:x-small; }
			p { line-height: 1em;}
			label.small{ font-size: small; }
			.slogan{ margin-top: -20px; font-style: italic; font-size: small; color: #cccccc;}
			.files{ font-size: smaller; height: 100px; overflow: auto; border: 1px solid #ccc; background-color: #fff; }
			.red { font-weight: bold; color: red;}
		</style>
	</head>
	<body>
	<h1><nobr>WP Easy Backup</nobr> <span><nobr><a href="<?php echo admin_url(); ?>">Go to admin dashboard</a> - <a href="<?php echo $_SERVER['PHP_SELF']; ?>">Refresh page</a></span></nobr></h1>
	<p class="slogan">"Save your time, save your data!"</p>
	<?php if( !class_exists( 'ZipArchive' ) ) : ?>
	<p class="warning">Can't backup files ? 'ZipArchive' is not installed!</p>
	<?php endif; ?>
	<?php if( isset( $_REQUEST['action'] ) ): ?>
		<p class="warning"><?php echo $msg; ?></p>
	<?php endif; ?>
		<div>
			<h2>Backup Mode</h2>
			<form method="post">
			<?php
				// Modify checkbox depending on ZipArchive avaibility
				$disabled = class_exists( 'ZipArchive' ) ? '' : ' disabled="disabled"';
				$all_checked = !class_exists( 'ZipArchive' ) ? '' : ' checked="checked"';
				$db_checked = class_exists( 'ZipArchive' ) ? '' : ' checked="checked"';
			?>
				<p>What do you want to save?</p>
				<p><label class="small"><input type="radio" name="backup_mode" value="all"<?php echo $disabled; ?><?php echo $all_checked; ?> /> All (Database + Files)</label></p>
				<p><label class="small"><input type="radio" name="backup_mode" value="db"<?php echo $db_checked; ?> /> Only Database</label></p>
				<p><label class="small"><input type="radio" name="backup_mode" value="files"<?php echo $disabled; ?> /> Only Files</label></p>
				<p><label class="small"><input type="checkbox" name="download" value="1" checked="checked" /> Download file after backup</label></p>
				<p><input type="submit" value="Backup Now!"/></p>
				<label class="small red"><input type="checkbox" name="unlink" value="1" checked="checked" /> Delete this script after use.</label>
				<input type="hidden" name="action" value="backup" />
			</form>
		</div>		
		<div>
			<h2>Actual Backups</h2>
			<form method="post">
			<?php
			// Get all zip/sql files in backup folder
			$files = array_map( 'basename', glob( BACKUP_DIR . '/*.{zip,sql}', GLOB_BRACE ) );
			$_files = $disabled = '';
			if( !empty( $files ) ): 
				foreach( $files as $k=>$file )
					$_files .= '<label><input type="checkbox" name="file[]" value="' . $file . '" /> ' . $file . '</label><br />';
			else: 
				$_files = '~ No backup yet ~';
				$disabled = ' disabled="disabled"';
			endif;
			?>
				<p><label>Files list: <p class="files"><?php echo $_files; ?></p></label></p>
				<input type="hidden" name="action" value="download" /> 
				<p><input type="submit" value="Download!"<?php echo $disabled; ?> /> <label class="small"><input type="checkbox" name="delete" value="1" /> delete it/them after</label></p>
				<p><input type="submit" value="Delete all these files"<?php echo $disabled; ?> name="deleteall" /></p>
				<p><em>Older files (7 days) are deleted on backup action.</em></p>
		</div>
		<p class="warning">Do not forget to delete this file after use! <a href="?action=delete_file">Click here to delete it now!</a></p>
		<p class="footer"> ~ <a href="https://github.com/BoiteAWeb/WP-Easy-Backup/" target="_blank">WP Easy Backup v1.0</a> ~ <a href="http://www.boiteaweb.fr" target="_blank">http://www.boiteaweb.fr</a> ~ <a href="http://twitter.com/boiteaweb" target="_blank">@boiteaweb</a> ~
		<br /><br />~ Based on <a href="http://www.geekpress.fr">Jonathan Buttigieg</a>'s project on Github: <a href="https://github.com/GeekPress/WP-BackUp/">WP-BackUp</a> ~</p>
		</form>
	</body>
</html>
