<?php
/*
Plugin Name: Google Rank Badge
Plugin URI: http://www.nickpowers.info
Description: Google Rank Badge displays your PR via shortcode
Author: Nick Powers
Version: 1.0
Author URI: http://www.nickpowers.info
*/

class GRank {

	function GRank() {
		$this->__construct();
	}

	function __construct() {

		// Plugin Settings Link
		$plugin = plugin_basename(__FILE__);
		add_filter( "plugin_action_links_$plugin", array( &$this, 'filterPluginActions' ), 10, 2 );

		// Add shortcode
		add_shortcode( 'grank', array($this, 'shortcode') );

	}

	function shortcode() {

		// Initialize Variables
		$x1 = 51;
		$y1 = 2;
		$x2 = 67;
		$y2 = 17;
		$x3 = 56;
		$y3 = 2;

		$font = 5;

		$output = '';

		// Get Options
		$options['grankcolour'] = get_option( 'grankcolour', '#009933' );
		$options['grank_credit'] = get_option( 'grank_credit', '' );

		// Google Rank
		$rank = trim( $this->GetPageRank( get_permalink() ) );
		if ( empty( $rank ) ) {
			$rank = 0;
		}


		// Google Badge
		$badge = imagecreatefrompng( plugins_url("images/pagerank_blank.png", __FILE__) );
		$colors = $this->html2rgb( $options['grankcolour'] );
		$color = imagecolorallocate( $badge, $colors[0], $colors[1], $colors[2] );
		$white = imagecolorallocate( $badge, 255, 255, 255 );

		imagefilledrectangle( $badge, $x1, $y1, $x2, $y2, $color );

		if ( $rank ) {



			// Calculate Rank Bar Size
			$x1 = 5; $y1 = 14; $y2 = 16;
			$x2 = 40 * $rank * .1 + 4;

			imagefilledrectangle( $badge, $x1, $y1, $x2, $y2, $color );
		}

		if ( $rank == '10' ) {
			$font = 3;
			$x3 = $x3 - 3;
			$y3 = $y3 + 1;
		}

		imagestring( $badge, $font, $x3, $y3, $rank, $white );

		$temp = tempnam ( 'temp', 'png' );
		imagepng( $badge, $temp );
		imagedestroy( $badge );
		$content = file_get_contents( $temp );

		$img_str = base64_encode( $content );


		if ( $options['grank_credit'] ) {

			$output .= '<a href="http://nickpowers.info/wordpress-plugins/grank/" style="border-style: none">';
		}

		$output .= '<img src="data:image/png;base64,'.$img_str.'" />';

		if ( $options['grank_credit'] ) {

			$output .= '</a>';
		}

		return $output;
	}

	function filterPluginActions ( $links, $file ) {

		// Configure Settings Link
		$basename = plugin_basename( __FILE__ );
		$break = Explode( '/', $basename );
		$pfile = $break[count($break) - 1];

		$settings_link = '<a href="options-general.php?page='.$pfile.'">Settings</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	function GetPageRank( $q,$host='toolbarqueries.google.com',$context=NULL ) {

		$seed = "Mining PageRank is AGAINST GOOGLE'S TERMS OF SERVICE. Yes, I'm talking to you, scammer.";
		$result = 0x01020345;
		$len = strlen($q);

		for ($i=0; $i<$len; $i++) {

			$result ^= ord($seed{$i%strlen($seed)}) ^ ord($q{$i});
			$result = (($result >> 23) & 0x1ff) | $result << 9;
		}

		if ( PHP_INT_MAX != 2147483647 ) { $result = -(~($result & 0xFFFFFFFF) + 1); }

		$ch=sprintf( '8%x', $result );
		$url='http://%s/tbr?client=navclient-auto&ch=%s&features=Rank&q=info:%s';
		$url=sprintf( $url,$host,$ch,$q );
		@$pr=file_get_contents( $url,false,$context );
		return $pr?substr( strrchr( $pr, ':' ), 1 ):false;
	}

	function html2rgb( $color ) {
		if ( $color[0] == '#' )
			$color = substr( $color, 1 );

		if ( strlen($color) == 6 )
			list( $r, $g, $b ) = array( $color[0].$color[1],
				$color[2].$color[3],
				$color[4].$color[5] );
		elseif ( strlen($color) == 3 )
			list( $r, $g, $b ) = array( $color[0].$color[0], $color[1].$color[1], $color[2].$color[2] );
		else
			return false;

		$r = hexdec( $r ); $g = hexdec( $g ); $b = hexdec( $b );

		return array( $r, $g, $b );
	}

}

class GRank_Settings {

	function GRank_Settings() {
		$this->__construct();
	}

	function __construct() {
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'admin_print_styles', array( &$this, 'scripts' ) );
		
	}

	function admin_menu() {
		add_options_page( 'Google Rank Badge', 'Google Rank Badge', 'delete_posts', basename(__FILE__), array( $this, 'settings' ) );
	}

	function scripts() {
		wp_enqueue_style( 'farbtastic' );
		wp_enqueue_script( 'farbtastic' );

	}

	function html2rgb( $color ) {
		if ( $color[0] == '#' )
			$color = substr( $color, 1 );

		if ( strlen($color) == 6 )
			list( $r, $g, $b ) = array( $color[0].$color[1],
				$color[2].$color[3],
				$color[4].$color[5] );
		elseif ( strlen($color) == 3 )
			list( $r, $g, $b ) = array( $color[0].$color[0], $color[1].$color[1], $color[2].$color[2] );
		else
			return false;

		$r = hexdec( $r ); $g = hexdec( $g ); $b = hexdec( $b );

		return array( $r, $g, $b );
	}

	function settings() {
		$output  = '';
		$message = '';
		

		// Update options if form submitted
		if ( isset($_POST['action']) && $_POST['action'] == 'update' ) {

			$options['grankcolour'] = $_POST['grankcolour'];
			$options['grank_credit'] = $_POST['grank_credit'];

			(isset($_POST["grank_credit"]) && $_POST["grank_credit"]) == "on" ? update_option("grank_credit", "checked") : update_option("grank_credit", "");
	
			update_option( 'grankcolour', $options['grankcolour'] );

			// Set message
			$message = '<div id="message" class="updated fade"><p><strong>Options Saved</strong></p></div>';
		}
		else {
			$options['grankcolour'] = get_option( 'grankcolour', '#009933' );
			$options['grank_credit'] = get_option( 'grank_credit', '' );
		}

		// Script
		$output .= '<script type="text/javascript">';
		$output .= 'jQuery(document).ready(function($){
			$("#color_picker_color1").farbtastic("#color1");            
			});';
		$output .= '</script>';

		// Opening div and display message
		$output .= '<div class="wrap">'. $message;

		// Icon
		$output .= '<div id="icon-tools" class="icon32"></div>';

		// Title
		$output .= '<h2>Google Rank Badge Settings</h2>';


		// Open form
		$output .= '<form method="post" action="">';
		$output .= '<input type="hidden" name="action" value="update" />';

		// Farbtastic
		$output .= "<input type='text' id='color1' name='grankcolour' value='{$options['grankcolour']}' class='colorwell' />";
		$output .= "  e.g. red hex value = <code>#FF0000</code><br/>";
		$output .= '<div id="color_picker_color1" style="float: right; position: absolute; left:300px;"></div>';

		// Image
		$output .= '<p><img id="piccolor" src="'.plugins_url("images/pagerank_transparent.png", __FILE__).'" STYLE="background-color: '.$options['grankcolour'].'" /></p>';

		// Give plugin credit
		$output .= '<p><input name="grank_credit" type="checkbox" id="grank_credit" '.$options["grank_credit"].' /> Link Google Rank Badge to Plugin Page</p><br />';

		// Save Options button
		$output .= '<input type="submit" class="button-primary" value="Save Changes" />';

		// Close form
		$output .= '</form>';


		// Closing div
		$output .= '</div>';

		echo $output;
	}

}


$GRank = new GRank;
$GRank_Settings = new GRank_Settings;

?>
