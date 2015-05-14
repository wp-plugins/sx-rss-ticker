<?php
/*
Plugin Name: Sx RSS Ticker
Plugin URI: http://www.sabri-elgueder.tn/wordpress-plugins/sx-rss-ticker/
Author URI: http://www.sabri-elgueder.tn/
Description: Sx RSS Ticker allows you to place the contents of an RSS feed into your pages or posts. It uses the jQuery easy ticker plugin to add a news ticker like effect to the RSS feeds.
Author: Sabri El Gueder
Version: 1.1
*/

if (!defined('WP_CONTENT_URL')) {
    $srt_url = get_option('siteurl') . '/wp-content/plugins/' . plugin_basename(dirname(__FILE__)) . '/';
} else {
    $srt_url = WP_CONTENT_URL . '/plugins/' . plugin_basename(dirname(__FILE__)) . '/';
}

define('SRT_VERSION', '1.1');
define('SRT_AUTHOR', 'Sabri El Gueder');
define('SRT_URL', $srt_url);

// # Include the required scripts

function srt_public_scripts(){
    // jQuery
    wp_enqueue_script('jquery');
	
    // Super RSS Reader JS and CSS
    wp_register_script('sx-rss-ticker-js', SRT_URL . 'public/srt-js.js');
    wp_enqueue_script(array('jquery','sx-rss-ticker-js'));
}

add_action('wp_enqueue_scripts', 'srt_public_scripts');

// # Include the required styles
function srt_public_styles(){
    wp_register_style('sx-rss-ticker-css', SRT_URL . 'public/srt-css.css');
    wp_enqueue_style('sx-rss-ticker-css');
}

add_action('wp_enqueue_scripts', 'srt_public_styles');

class sx_rss_ticker {
	
    function parse_content($content) {
        if (strpos($content, '<!--sx_rss') === false) {
            return $content;
        }

        preg_match_all('#<!--sx_rss url="([^"]+)"-->#i', $content, $matches);
        $urls = $matches[1];
        foreach($urls as $url) {
            $feed_contents = sx_rss_ticker::get_rss($url);
            $content = str_replace('<!--sx_rss url="' . $url . '"-->', $feed_contents, $content);
        }

        $sign = ''; //<p>Feeds listed by <a href="http://www.sabri-elgueder.tn/">Sabri El Gueder</a>.</p>
        $content.= $sign . "\n";
        return $content;
    }

    function get_rss($url) {

        // Get RSS Feed(s)
        include_once (ABSPATH . WPINC . '/feed.php');
        define("MAGPIE_OUTPUT_ENCODING", "UTF-8");

        // Get a SimplePie feed object from the specified feed source.
        $rss = fetch_feed($url);
		
        $maxitems = 10;
        $title_length = 0; // != 0 forces a given length
		
        if (!is_wp_error($rss)): // Checks that the object is created correctly
            // Figure out how many total items there are, but limit it to 5.
            $maxitems = $rss->get_item_quantity(5);
            // Build an array of all the items, starting with element 0 (first element).
            $rss_items = $rss->get_items(0, $maxitems);
        endif;
		
        $feed_contents = '<!-- Start - Sx RSS Ticker v' . SRT_VERSION . '-->';
		
        $feed_contents.= "<div class='sx-ticker'><ul class='srt-clearfix'>\n";
		
        if ($maxitems == 0):
            $feed_contents.= '<li>' . _e('No items', 'my-text-domain') . '</li>';
        else:
            // Loop through each feed item and display each item as a hyperlink.
            foreach($rss_items as $item):
                $feed_contents.= '<li><a href="' . esc_url($item->get_permalink()) . '">';
                $feed_contents.= $item->get_date(get_option( 'date_format' )) . ' : ';
                if ($title_length != 0) {
                    $feed_contents.= substr(esc_html($item->get_title()) , 0, $title_length);
                } else {
                    $feed_contents.= esc_html($item->get_title());
                }
                $feed_contents.= "</a></li>\n";
            endforeach;
        endif;

        $feed_contents.= "</ul></div>\n";
		
        $feed_contents.= '<!-- End - Sx RSS Ticker -->';
		
        return $feed_contents;
    }
}

add_filter('the_content', array('sx_rss_ticker','parse_content'));
?>