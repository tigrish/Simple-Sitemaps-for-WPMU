<?php
/*
Plugin Name:  Simple Sitemaps For WPMU
Description:  On-demand sitemaps for WPMU.
Version:      2009.10.04 17:00 CET
Author:       Christopher Dell (tigrish)
Author URI:   http://tigrish.com/
*/

/* 
Copyright 2009 Tigrish (http://tigrish.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class Tigrish_SimpleSitemaps {
	var $totalposts = 25; // Number of posts to display
	var $maxlinks = 500; // Maximum number of links to include in the sitemap file

	// Plugin initialization
	function Tigrish_SimpleSitemaps() {
		// Delete cached sitemaps on new post or post delete
		add_action( 'publish_post', array(&$this, 'DeleteSitemap'), 15 );
		add_action( 'publish_page', array(&$this, 'DeleteSitemap'), 15 );
		add_action( 'delete_post', array(&$this, 'DeleteSitemap'), 15 );

		// Ping Google on new post or post delete
		add_action( 'publish_post', array(&$this, 'PingEngines'), 16 );
		add_action( 'publish_page', array(&$this, 'PingEngines'), 16 );
		add_action( 'delete_post', array(&$this, 'PingEnginese'), 16 );
	}


	// Delete this blog's sitemap
	function DeleteSitemap() {
		global $wpdb;
		@unlink( ABSPATH . 'wp-content/blogs.dir/' . $wpdb->blogid . '/files/sitemap.xml' );
	}

	// Notify search engines of a sitemap change
	function PingEngines() {
		$this->PingGoogle();
		$this->PingBing();
		$this->PingAsk();
	}

	// Notify Google of a sitemap change
	function PingGoogle() {
		global $wpdb;
		$pingurl = 'http://www.google.com/webmasters/sitemaps/ping?sitemap=' . urlencode( get_bloginfo('url') . '/sitemap.xml' );
		@file_get_contents( $pingurl ); // Until WP(MU) 2.7 comes along with it's HTTP API, file_get_contents() should do for now (hopefully)
	}
	
	// Notify Bing of a sitemap change
	function PingBing() {
		global $wpdb;
		$pingurl = 'http://www.bing.com/webmaster/ping.aspx?siteMap=' . urlencode( get_bloginfo('url') . '/sitemap.xml' );
		@file_get_contents( $pingurl ); // Until WP(MU) 2.7 comes along with it's HTTP API, file_get_contents() should do for now (hopefully)
	}
	
	// Notify Ask of a sitemap change
	function PingAsk() {
		global $wpdb;
		$pingurl = 'http://submissions.ask.com/ping?sitemap=' . urlencode( get_bloginfo('url') . '/sitemap.xml' );
		@file_get_contents( $pingurl ); // Until WP(MU) 2.7 comes along with it's HTTP API, file_get_contents() should do for now (hopefully)
	}


	// Generate the contents of the sitemap and cache it to a file
	function GenerateSitemap( $blogid ) {
		global $wpdb;

		switch_to_blog( $wpdb->blogid );

		$site_pages   = get_posts( 'numberposts=-1&post_type=page&orderby=menu_order' );
		$numberposts  = $this->maxlinks - count($site_pages);
		$site_posts   = get_posts( 'numberposts=' . $numberposts . '&orderby=date&order=DESC' );
		$linked_items = array_merge($site_posts, $site_pages); // posts are first

		$content  = '<?xml version="1.0" encoding="UTF-8"?' . ">\n";
		$content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

		$priority = 1;
		$prioritydiff = 1 / count($linked_items);

		foreach ( $linked_items as $post ) {
			$content .= "	<url>\n";
			$content .= '		<loc>' . get_permalink( $post->ID ) . "</loc>\n";
			$content .= '		<lastmod>' . mysql2date( 'Y-m-d\TH:i:s', $post->post_modified_gmt ) . "+00:00</lastmod>\n";
			$content .= '		<priority>' . number_format( $priority, 1 ) . "</priority>\n";
			$content .= "	</url>\n";

			$priority = $priority - $prioritydiff;
		}

		$content .= '</urlset>';

		// Write to the sitemap file
		$result = $this->writefile( ABSPATH . 'wp-content/blogs.dir/' . $wpdb->blogid . '/files/sitemap.xml', $content );

		return ( FALSE === $result ) ? FALSE : $content;
	}


	// Write a file, create directories as needed
	// Written by Trent Tompkins: http://www.php.net/manual/en/function.file-put-contents.php#84180
	function writefile( $filename, $content ) {
		$parts = explode( '/', $filename );
		$file = array_pop( $parts );
		$filename = '';
		foreach ( $parts as $part ) {
			if ( !is_dir( $filename .= "/$part" ) )
				mkdir($filename);
		}
		file_put_contents( "$filename/$file", $content );
	}
}

// Start this plugin after everything else is loaded
add_action( 'plugins_loaded', 'Tigrish_SimpleSitemaps' ); function Tigrish_SimpleSitemaps() { global $Tigrish_SimpleSitemaps; $Tigrish_SimpleSitemaps = new Tigrish_SimpleSitemaps(); }





// $Id: file_put_contents.php,v 1.27 2007/04/17 10:09:56 arpad Exp $

if (!defined('FILE_USE_INCLUDE_PATH')) {
    define('FILE_USE_INCLUDE_PATH', 1);
}

if (!defined('LOCK_EX')) {
    define('LOCK_EX', 2);
}

if (!defined('FILE_APPEND')) {
    define('FILE_APPEND', 8);
}

/**
 * Replace file_put_contents()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @license     LGPL - http://www.gnu.org/licenses/lgpl.html
 * @copyright   2004-2007 Aidan Lister <aidan@php.net>, Arpad Ray <arpad@php.net>
 * @link        http://php.net/function.file_put_contents
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 1.27 $
 * @internal    resource_context is not supported
 * @since       PHP 5
 * @require     PHP 4.0.0 (user_error)
 */
function php_compat_file_put_contents($filename, $content, $flags = null, $resource_context = null)
{
    // If $content is an array, convert it to a string
    if (is_array($content)) {
        $content = implode('', $content);
    }

    // If we don't have a string, throw an error
    if (!is_scalar($content)) {
        user_error('file_put_contents() The 2nd parameter should be either a string or an array',
            E_USER_WARNING);
        return false;
    }

    // Get the length of data to write
    $length = strlen($content);

    // Check what mode we are using
    $mode = ($flags & FILE_APPEND) ?
                'a' :
                'wb';

    // Check if we're using the include path
    $use_inc_path = ($flags & FILE_USE_INCLUDE_PATH) ?
                true :
                false;

    // Open the file for writing
    if (($fh = @fopen($filename, $mode, $use_inc_path)) === false) {
        user_error('file_put_contents() failed to open stream: Permission denied',
            E_USER_WARNING);
        return false;
    }

    // Attempt to get an exclusive lock
    $use_lock = ($flags & LOCK_EX) ? true : false ;
    if ($use_lock === true) {
        if (!flock($fh, LOCK_EX)) {
            return false;
        }
    }

    // Write to the file
    $bytes = 0;
    if (($bytes = @fwrite($fh, $content)) === false) {
        $errormsg = sprintf('file_put_contents() Failed to write %d bytes to %s',
                        $length,
                        $filename);
        user_error($errormsg, E_USER_WARNING);
        return false;
    }

    // Close the handle
    @fclose($fh);

    // Check all the data was written
    if ($bytes != $length) {
        $errormsg = sprintf('file_put_contents() Only %d of %d bytes written, possibly out of free disk space.',
                        $bytes,
                        $length);
        user_error($errormsg, E_USER_WARNING);
        return false;
    }

    // Return length
    return $bytes;
}

// Define
if (!function_exists('file_put_contents')) {
    function file_put_contents($filename, $content, $flags = null, $resource_context = null)
    {
        return php_compat_file_put_contents($filename, $content, $flags, $resource_context);
    }
}

?>