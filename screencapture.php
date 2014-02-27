<?php
/**
 * Plugin Name: Screen Capture
 * Plugin URI: https://github.com/bgcom/bgp-wp-screencapture
 * Description: A screen capture plugin for Wordpress based on PhantomJS
 * Version: 1.0
 * Author: Guillaume Molter for B+G & Partners SA
 * Author URI: http://bgcom.ch
 * License: WTFPL
 */
 
/**
 * A toolkit function to get the post slug 
 */

function bgp_scr_get_the_slug($postID){
  $slug = basename(get_permalink($postID));
  do_action('before_slug', $slug);
  $slug = apply_filters('slug_filter', $slug);
  do_action('after_slug', $slug);
  return $slug;
}


/**
 * Add a custom query var to WP that will be passed to Posts when they are screen captured 
 */
function bgp_scr_parameter_queryvars( $qvars ){
	$qvars[] = 'bgpscrcapt';
	return $qvars;
}
add_filter('query_vars', 'bgp_scr_parameter_queryvars' );

/**
 * Add a custom body class that will be added to Posts when they are screen captured
 */
function bgp_rep_body_class($classes = '') {
	if(isset($_GET["bgpscrcapt"]) && $_GET["bgpscrcapt"]=="true"){
		$classes[] = 'bgpscrcapt';	
	}
	return $classes;
}
add_filter('body_class','bgp_rep_body_class');

/**
 * The function to call to create screen capture
 * Create a screencapture, create an attachment with it linked to the post and return the attachement ID.
 */
function bgp_scr_create_screen($postID,$format="png"){
	
	/* We declare the Errors */
	$bgp_scr_errors = new WP_Error();
	$bgp_scr_errors->add('not_a_post', __('Param is not a post or page','bgp_scr'));
	$bgp_scr_errors->add('uploads_not_writable', __('Can\'t create /wp-content/cache/bgp_scr/ check your folders permissions','bgp_scr'));
	$bgp_scr_errors->add('no_screen', __('The screen capture couldn\'t be created...','bgp_scr'));
	
	/* check if the param is a post*/	
	if(is_numeric($postID) && get_post($postID)){
		
		/* we validate the format or default to PNG*/
		if($format == "jpg"){
			$extension = ".".$format;
			$mime = "image/jpeg";
		}
		elseif($format == "pdf"){
			$extension = ".".$format;
			$mime = "application/pdf";
		}
		else{
			$extension = ".png";
			$mime = "image/png";
		}
		
		/* get the permalink with our custom query var*/
		$permalink=get_permalink($postID)."?bgpscrcapt=true";
		
		/* we get the post slug that will be used for filenames */
		$slug=bgp_scr_get_the_slug($postID);			
		/* we set up the name of our screen capture */
		$bgp_scr=$slug.date("His").$extension;
		/*and the name of our js script that will be read by PhantomJS */
		$bgp_scr_jobfile=$slug.date("His").".js";
		/*and store a few usefull directories */
		$bgp_scr_bin_files = plugin_dir_path( __FILE__ )."bin/phantomjs";
		$upload_dir = wp_upload_dir();
		$bgp_scr_upload_dir = substr($upload_dir["url"],strlen(site_url()))."/";
		$jobfolder = WP_CONTENT_DIR."/cache/bgp_scr/";		
		
		/*we check rigths and create folders if necessary */
		if (wp_mkdir_p($jobfolder) && wp_is_writable($jobfolder)){
			if(!is_file($jobfolder . 'index.php')){
				file_put_contents($jobfolder . 'index.php', '<?php exit(); ?>');
			}
			
			
			/*What follows is a hack/tweak because phantomJS the document root used by page.render() to generate the image is relative to $_SERVER['SCRIPT_NAME'] position*/
			/*therefore this tweak create a relative wp-content url based on $_SERVER['SCRIPT_NAME'] to be used by page.render()  */
			$chars = preg_split('/\//', $_SERVER['SCRIPT_NAME'], -1, PREG_SPLIT_NO_EMPTY);
			$back="";
			$i = sizeof($chars)-1;
			if($i>0){
				for($i;$i>0;$i--){
					$back.="../";
				}
			}
			else{
				$back=".";
			}
									
			/* the js script that will be read by PhantomJS */
			/* Note: the setTimout gives PhantomJS extratime to load the page which prevent some pages to be cut-off. */
			$src = "var page = require('webpage').create(); page.open('".$permalink."', function () { window.setTimeout(function () { page.render('".$back.$bgp_scr_upload_dir.$bgp_scr."'); phantom.exit(); }, 2000); });";
			/* store the script */
			file_put_contents($jobfolder.$bgp_scr_jobfile, $src);
			/* and read it with phantomJS */
			exec(escapeshellcmd($bgp_scr_bin_files.' '.$jobfolder.$bgp_scr_jobfile));
			/* we check that the file was created */
			if (is_file($upload_dir["path"]."/".$bgp_scr)) {
				/* and create an attachement for the original post  */
				$wp_filetype = wp_check_filetype($upload_dir["path"]."/".$bgp_scr, null );
				$attachment = array(
				     'guid' => $upload_dir["url"]."/".$bgp_scr, 
				     'post_mime_type' => $wp_filetype['type'],
				     'post_title' => "Screen Capture of ".$slug,
				     'post_content' => '',
				     'post_status' => 'inherit'
				);
				$attach_id = wp_insert_attachment( $attachment, false, $postID );
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				$attach_data = wp_generate_attachment_metadata( $attach_id, $upload_dir["path"]."/".$bgp_scr );
				wp_update_attachment_metadata( $attach_id, $attach_data );
				 
				/* finally return the attachement ID  */
				return array("attachment_id"=>$attach_id,"url"=>$upload_dir["url"]."/".$bgp_scr);
			}
			else{
				echo $bgp_scr_errors->get_error_message( 'no_screen' );
				exit;
			}

			
		}
		else{
			echo $bgp_scr_errors->get_error_message( 'uploads_not_writable' );
			exit;
		}
			
	}
	else{
		echo $bgp_scr_errors->get_error_message( 'not_a_post' );
		exit;
	}

}

?>