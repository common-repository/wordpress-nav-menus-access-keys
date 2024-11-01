<?php
/*
Plugin Name: Access Keys for WordPress Menus
Plugin URI: http://aahacreative.com/our-projects/wordpress-access-keys-nav-menus/
Description: This plugin allows you to add Access Keys to navigation menus. Works with multiple menus. (Use version 1.0 if you need access keys on wp_list_pages).
Author: Aaron Harun
Version: 1.6
Author URI: http://aahacreative.com/
*/
//Yes, I understand that it is pseudeo ironic to use Javascript to make the website accessible, but we have to deal with it since the WordPress gods don't allow us to hook in anywhere. I mean that would be just all too logical. No-one would ever want to do things properly.

/*
Installation:
ONE: Upload to your plugin directory.
TWO: Activate.
THREE: A new box will appear in your nav menu for each item.
*/


add_action('admin_head', 'access_keys_nav_page_js');
add_action('wp_update_nav_menu_item', 'update_access_keys'); //Hook in here because we can. We don't actually care about any of the data
add_filter('walker_nav_menu_start_el','access_keys_walker_nav_menu_start_el',10,4);

$access_keys = get_option('nav_menu_access_keys');

/**
* Update the access keys by grabbing the $_POST data
* It's safe because it only runs when WP has validated the
* User Data and the rest of the data.
**/
function update_access_keys($id){
global $access_keys;
static $do_once = false;

	if($do_once) //This is called multiple times, so we ignore the rest of the calls.
		return;

	$access_keys[$id] = $_POST['menu-item-attr-accesskey'];
	update_option('nav_menu_access_keys',$access_keys);
	$do_once = true;

}

/**
Prints all of the JS.
This is done inline because it shouldn't ever be cached.
**/

function access_keys_nav_page_js(){
global $access_keys,$nav_menu_selected_id;

	if(strpos($_SERVER['PHP_SELF'], 'nav-menus') === false)
		return;

	$js="var keys = [];\n\t\t";

	if($nav_menu_selected_id)
		$access_keys = $access_keys[$nav_menu_selected_id];

	if(is_array($access_keys)){
		while(list($id,$key) = each($access_keys)) //Convert our PHP array to a JS array.
			$js .= 'keys['.$id.'] = "'.$key.'";'."\n\t\t";
	}

?>
	<script type="text/javascript">
		<?php echo $js; ?>

		jQuery(document).ready(function(){

		do_accessibility(); //Add inputs for the original items


		jQuery('#menu-to-edit a.item-edit').live('hover',function(){
				var id = jQuery(this).attr('id').split('-')[1];
				if(jQuery('#edit-menu-item-attr-accesskey-'+id).length == 0){

					access_keys_append_input(id); //If an item has been added, add its access key input.
				}

			})
		});

		jQuery('.edit-menu-item-attr-accesskey').live('keyup',function(){

			if(jQuery(this).val().length > 1){
				jQuery(this).css({color: 'red'});
			}else{
				jQuery(this).css({color:'black'});
			}

		});

		function do_accessibility(){
			jQuery('.menu-item-settings').each(function(){access_keys_append_input(jQuery(this).attr('id').split('-')[3])})
		}


		function access_keys_append_input(id){

			var key = '';
			if(keys[id])
				key = keys[id];


			var html = '<p class="description description-thin" style="clear:both;"><label for="edit-menu-item-attr-accesskey-'+id+'">Access Key<br/><input type="text" value="'+key+'" name="menu-item-attr-accesskey['+id+']" class="widefat edit-menu-item-attr-accesskey" id="edit-menu-item-attr-accesskey-'+id+'"/></label></p>';

			jQuery(html).insertBefore(jQuery('#menu-item-settings-'+id+ ' .submitbox '));

		}
	</script>
<?php
}

/**
* Hooks into each menu li item and checks if there is an access key
* If there is, just add it. '<a' is Guaranteed to always be there.
* It's the only part that is.
**/

function access_keys_walker_nav_menu_start_el($output,$item,$depth,$args){
global $access_keys;

	$locations = get_nav_menu_locations();

	if (isset($locations[$args->theme_location])) {
		$menu_id = $locations[$args->theme_location];
	}

	if(count($access_keys[$menu_id])){


		if($access_keys[$menu_id][$item->ID] != ''){

			$output = str_replace('<a', '<a accesskey="'.$access_keys[$menu_id][$item->ID].'"',$output);
		}

	}

	return $output;
}
