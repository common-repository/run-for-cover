<?php
/*
Plugin Name: Run For Cover
Plugin URI: http://jeroensmeets.net/run-for-cover-20/
Description: Adds a sidebar widget that shows the covers of cds you listen the most to. It uses information from the last.fm API.
Author: Jeroen Smeets
Version: 2.0.6
Author URI: http://jeroensmeets.net/
*/

$rfc_widget_version = "2.0.6";
define("CONNECT_TO_LASTFM", true);

# version 2.0   start from scratch (almost, kept the widget code)
#               now that Last.Fm Records is widely used but changed to PHP5, this is an attempt to
#               get Run For Cover working again by letting javascript do all the hard work.
#               The fun part? To stay PHP4, I moved parsing the XML to jQuery!
# version 2.0.1 make it work in IE
#               option to show album title and artist next to image
#               option to use it with lightbox plugin (not included)
# version 2.0.2 removed some debug code (oops, sorry!)
# version 2.0.3 completed lightbox code (requires lightbox plugin)
#               added option for highslide (requires highslide plugin)
# version 2.0.4 skipped
# version 2.0.5 show artist photo when cd cover is missing
# version 2.0.6 fixed issue with all images on page being replaced
#               when no artist image is available, fall back on default 'missing' image from last.fm

# Request for artist info
if (array_key_exists('rfc_artist', $_GET)) {
  # TODO: make sure it's a correct mbid
  $_artist_name = urlencode($_GET['rfc_artist']);

  header('Content-Type: text/xml');

  if (CONNECT_TO_LASTFM) {
    echo rfc_loadurl(
      'http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&artist=' . $_artist_name . '&api_key=fbfa856cc3af93c43359b57921b1e64e'
    );
  } else {
  	if (file_exists('./rfc_development/' . $_artist_name . '.xml')) {
  	  echo(file_get_contents('./rfc_development/' . $_artist_name . '.xml'));
  	} else {
  	  touch('./rfc_development/' . $_artist_name . '.xml');
  	  echo(file_get_contents('./rfc_development/artistinfo.xml'));
  	}
  }
  exit;
}

# Proxy for last.fm feed
if (array_key_exists('rfc_user', $_GET)) {
  # get the data and forward it to the requester
  # remember, we have no wordpress environment in this if{} block

  # TODO: only allow characters last.fm allows
  $rfc_user  = $_GET['rfc_user'];
  $rfc_count = $_GET['rfc_count'];

  # IE wants to know
  header('Content-Type: text/xml');

  # TODO: check for errors and send error message back to jQuery based on answer from last.fm api
  if (CONNECT_TO_LASTFM) {
    echo rfc_loadurl(
      'http://ws.audioscrobbler.com/2.0/?method=user.getrecenttracks&user=' . $rfc_user . '&api_key=fbfa856cc3af93c43359b57921b1e64e&limit=' . $rfc_count
    );
  } else {
    echo(file_get_contents('./rfc_development/recenttracks.xml'));
  }
  exit;
}

# non proxy request, let's do some widget-y stuff
function widget_runforcover_init() {

  if ( !function_exists('register_sidebar_widget') )
    return;

  # output for sidebar
  function widget_runforcover($args) {
    extract($args);

    $options = get_option('widget_runforcover');

    echo "\n\n" . $before_widget . $before_title . $options['title'] . $after_title . "\n";
    echo "  <ol id='runforcover'></ol>\n";
    echo $after_widget . "\n\n";
  }

  function widget_runforcover_control() {

    // Get our options and see if we're handling a form submission.
    $options = get_option('widget_runforcover');
    if ( !is_array($options) ) {
      $options = array('title'     => 'run for cover', 
                       'username'  => '', 
                       'count'     => '6', 
                       'imgwidth'  => '70', 
                       'noimages'  => 'No images to display');
    }

    if ( $_POST['runforcover-submit'] ) {
      $options['title']       = strip_tags(stripslashes($_POST['runforcover-title']));
      $options['username']    = strip_tags(stripslashes($_POST['runforcover-username']));
      $options['imgwidth']    = intval($_POST['runforcover-imgwidth']);
      if ($options['imgwidth'] < 10) {
        $options['imgwidth'] = 0;
      }
      $options['count']       = intval($_POST['runforcover-count']);
      if ($options['count'] < 1) {
        $options['count'] = 6;
      }
      $options['style']       = strip_tags(stripslashes($_POST['runforcover-style']));
      $options['linkto']      = strip_tags(stripslashes($_POST['runforcover-linkto']));
      $options['noimages']    = strip_tags(stripslashes($_POST['runforcover-noimages']));

      update_option('widget_runforcover', $options);
    }

    // Be sure you format your options to be valid HTML attributes.
    $title        = htmlspecialchars($options['title'], ENT_QUOTES);
    $username     = htmlspecialchars($options['username'], ENT_QUOTES);
    $imgwidth     = htmlspecialchars($options['imgwidth'], ENT_QUOTES);
    $count        = htmlspecialchars($options['count'], ENT_QUOTES);
    $noimages     = htmlspecialchars($options['noimages'], ENT_QUOTES);

?>
    <p style="text-align:right;">
      <label for="runforcover-title">Widget title: 
        <input style="width: 200px;" id="runforcover-title" name="runforcover-title" type="text" value="<?php echo $title ?>" />
      </label>
    </p>
    <p style="text-align:right;">
      <label for="runforcover-username">Last.fm username: 
        <input style="width: 200px;" id="runforcover-username" name="runforcover-username" type="text" value="<?php echo $username ?>" />
      </label>
    </p>
    <p style="text-align:right;">
      <label for="runforcover-count">Max. displayed: 
        <input style="width: 200px;" id="runforcover-count" name="runforcover-count" type="text" value="<?php echo $count ?>" />
      </label>
    </p>
    <p style="text-align:right;">
      <label for="runforcover-imgwidth">Thumbnail size: 
        <input style="width: 200px;" id="runforcover-imgwidth" name="runforcover-imgwidth" type="text" value="<?php echo $imgwidth ?>" /><br />
        (You can set this to zero and use<br />img.runforcover in your stylesheet)
      </label>
    </p>
    <p style="text-align:right;">
      <label for="runforcover-style">Style:
        <select style="width: 200px;" id="runforcover-style" name="runforcover-style">
          <option value="justimgs">Only cd covers</option>
          <option value="imgwithtxt"<?php if ('imgwithtxt' == $options['style']) { echo ' selected'; } ?>>Show artist and title track with cd covers</option>
        </select>
      </label>
    </p>
    <p style="text-align:right;">
      <label for="runforcover-linkto">Link:
        <select style="width: 200px;" id="runforcover-linkto" name="runforcover-linkto">
          <option value="lastfm">last.fm page for this track</option>
          <option value="lightbox"<?php if ('lightbox' == $options['linkto']) { echo ' selected'; } ?>>lightbox (plugin not included)</option>
          <option value="highslide"<?php if ('highslide' == $options['linkto']) { echo ' selected'; } ?>>highslide (plugin not included)</option>
        </select>
      </label>
    </p>
    <p style="text-align:right;">
      <label for="runforcover-noimages">Error message:
        <input style="width: 200px;" id="runforcover-noimages" name="runforcover-noimages" type="text" value="<?php echo $noimages ?>" />
      </label>
    </p>
    <input type="hidden" id="runforcover-submit" name="runforcover-submit" value="1" />
<?php
  }
  
  // This registers our widget so it appears with the other available
  // widgets and can be dragged and dropped into any active sidebars.
  register_sidebar_widget('Run For Cover', 'widget_runforcover');

  register_widget_control('Run For Cover', 'widget_runforcover_control', 375, 380);
}

function rfc_head() {
    $options = get_option('widget_runforcover');
?>

    <!-- added by widget Run For Cover -->
    <style type="text/css">
      #runforcover          { padding-bottom: 10px; }
      #runforcover li       { list-style-type: none; display: inline; }
      #runforcover img      { height: <?php echo $options['imgwidth'] ?>px; width: <?php echo $options['imgwidth'] ?>px; margin: 0px 5px 5px 0px; border: 0px; }
    </style>
    <script type='text/javascript' src='<?php echo rfc_siteurl(); ?>wp-includes/js/jquery/jquery.js'></script>
    <script type='text/javascript' src='<?php echo rfc_siteurl(); ?>wp-content/plugins/runforcover.js'></script>
    <script type='text/javascript'>
      /* <![CDATA[ */
      RunForCover.settings.username   = '<?php echo $options['username']; ?>';
      RunForCover.settings.count      = <?php echo $options['count']; ?>;
      RunForCover.settings.style      = '<?php echo $options['style']; ?>';
      RunForCover.settings.linkto     = '<?php echo $options['linkto']; ?>';
      RunForCover.settings.gmt_offset = <?php echo get_option('gmt_offset'); ?>;
      <?php
        // uploaded in subdir of wp-content/plugins ?
        $rfc_plugindir = ('.' != dirname(plugin_basename(__FILE__))) ? dirname(plugin_basename(__FILE__)) . DIRECTORY_SEPARATOR : '';
      ?>
      RunForCover.settings.ajaxuri    = '<?php echo rfc_siteurl(); ?>wp-content/plugins/<?php echo $rfc_plugindir; ?>runforcover.php';

      jQuery(document).ready( function() { RunForCover.start(); });
      /* ]]> */
    </script>
<?php
}

// Run our code later in case this loads prior to any required plugins.
add_action('plugins_loaded', 'widget_runforcover_init');

# add stylesheet and javascript to head of page
add_action('wp_head', 'rfc_head');

function rfc_siteurl() {
  // some trickery to get full paths in javascript
  $_path    = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  $_file    = pathinfo($_path, PATHINFO_BASENAME);

  $_siteurl = substr($_path, 0, -1 * strlen($_file));
  if (substr($_siteurl, -1) != '/') {
    $_siteurl .= '/';
  }

  return $_siteurl;
}

function rfc_loadurl($_url) {
  $_result = false;

  # added curl for Dreamhost etc.
  if (function_exists('curl_exec')) {
    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_URL, $_url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
    # removed exception handling, as that is PHP5
    $_result = @curl_exec($ch);
    curl_close($ch);
  } else {
    $fp = @fopen($_url, 'r');
    if ($fp) {
      $_result = "";
      while ($data = fgets($fp)) {
        $_result .= $data;
      }
      fclose($fp);
    }
  }

  return $_result;
}

?>