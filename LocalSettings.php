<?php

# This file was automatically generated by the MediaWiki installer.
# If you make manual changes, please keep track in case you need to
# recreate them later.

$IP = "/var/www/html/hpredictor";
ini_set( "include_path", ".:$IP:$IP/includes:$IP/languages" );
require_once( "includes/DefaultSettings.php" );

# If PHP's memory limit is very low, some operations may fail.
# ini_set( 'memory_limit', '20M' );

if ( $wgCommandLineMode ) {
	if ( isset( $_SERVER ) && array_key_exists( 'REQUEST_METHOD', $_SERVER ) ) {
		die( "This script must be run from the command line\n" );
	}
} elseif ( empty( $wgNoOutputBuffer ) ) {
	## Compress output if the browser supports it
	if( !ini_get( 'zlib.output_compression' ) ) @ob_start( 'ob_gzhandler' );
}

$wgSitename         = "Dokholyan Lab Wiki";

$wgScriptPath	    = "/hpredictor";
$wgScript           = "$wgScriptPath/index.php";
$wgRedirectScript   = "$wgScriptPath/redirect.php";

## If using PHP as a CGI module, use the ugly URLs
$wgArticlePath      = "$wgScript/$1";
# $wgArticlePath      = "$wgScript?title=$1";

$wgStylePath        = "$wgScriptPath/skins";
$wgStyleDirectory   = "$IP/skins";
#$wgLogo             = "$wgStylePath/common/images/wiki.png";
$wgLogo             = "$wgScriptPath/images/dokhlab.png";

$wgUploadPath       = "$wgScriptPath/images";
$wgUploadDirectory  = "$IP/images";

$wgEnableEmail = true;
$wgEnableUserEmail = true;

$wgEmergencyContact = "shantanu@NOSPAMunc.edu";
$wgPasswordSender	= "shantanu@NOSPAMunc.edu";

## For a detailed description of the following switches see
## http://meta.wikimedia.org/Enotif and http://meta.wikimedia.org/Eauthent
## There are many more options for fine tuning available see
## /includes/DefaultSettings.php
## UPO means: this is also a user preference option
$wgEnotifUserTalk = true; # UPO
$wgEnotifWatchlist = true; # UPO
$wgEmailAuthentication = true;

$wgDBserver         = "localhost";
$wgDBname           = "dokhlabwiki";
$wgDBuser           = "other_svc";
$wgDBpassword       = "final-S3cr3t";
$wgDBprefix         = "";

# Force people to register before they are allowed to edit
$wgGroupPermissions['*']['edit'] = false; 
$wgShowIPinHeader = false;

#Disallow creating accounts
$wgGroupPermissions['*']['createaccount'] = false;


$wgUploadSizeWarning = 5000000;
$wgFileExtensions = array( 'png', 'ent', 'gif', 'jpg', 'jpeg', 'pdb', 'zip', 'doc', 'rar', 'xls');
$wgStrictFileExtensions = false;

#Don't use external mime detector (linux)
#$wgMimeDetectorCommand= "file -bi"; 

# If you're on MySQL 3.x, this next line must be FALSE:
$wgDBmysql4 = true;

# Experimental charset support for MySQL 4.1/5.0.
$wgDBmysql5 = false;

## Shared memory settings
$wgMainCacheType = CACHE_NONE;
$wgMemCachedServers = array();

## To enable image uploads, make sure the 'images' directory
## is writable, then uncomment this:
$wgEnableUploads		= true;
$wgUseImageResize		= true;
$wgUseImageMagick = true;
$wgImageMagickConvertCommand = "/usr/bin/convert";

## If you want to use image uploads under safe mode,
## create the directories images/archive, images/thumb and
## images/temp, and make them all writable. Then uncomment
## this, if it's not already uncommented:
# $wgHashedUploadDirectory = false;

## If you have the appropriate support software installed
## you can enable inline LaTeX equations:
$wgUseTeX			= true;
$wgMathPath         = "{$wgUploadPath}/math";
$wgMathDirectory    = "{$wgUploadDirectory}/math";
$wgTmpDirectory     = "{$wgUploadDirectory}/tmp";

$wgLocalInterwiki   = $wgSitename;

$wgLanguageCode = "en";

$wgProxyKey = "8198e2c96125e8d9707886ac7e7c1577223db8b6f6d72b9b59f12cb902739fd1";

## Default skin: you can change the default skin. Use the internal symbolic
## names, ie 'standard', 'nostalgia', 'cologneblue', 'monobook':
# $wgDefaultSkin = 'monobook';

## For attaching licensing metadata to pages, and displaying an
## appropriate copyright notice / icon. GNU Free Documentation
## License and Creative Commons licenses are supported so far.
# $wgEnableCreativeCommonsRdf = true;
$wgRightsPage = ""; # Set to the title of a wiki page that describes your license/copyright
$wgRightsUrl = "";
$wgRightsText = "";
$wgRightsIcon = "";
# $wgRightsCode = ""; # Not yet used

$wgDiff3 = "/usr/bin/diff3";

include("extensions/Graphviz.php");
$wgGraphVizSettings->dotCommand = "/usr/bin/dot";

require_once("extensions/SpecialHpredictor.php");
#include("extensions/SpecialHpredictor.php");
require_once('extensions/googleCalendar.php');
require_once("extensions/Calendar.php");
require_once("extensions/SpecialEvents.php");
# Disable anonymous editing
$wgWhitelistEdit = true;
# Disable reading by anonymous users
$wgGroupPermissions['*']['read'] = false;
# Pages anonymous (not-logged-in) users may see
$wgWhitelistRead = array( ":Main Page", "Special:Hpredictor", "Special:Userlogin", "Wikipedia:Help", "Special:Hpredictor" );
# Prevent new user registrations by anyone
$wgWhitelistAccount = array ( "user" => 0, "sysop" => 1, "developer" => 1 );

?>
