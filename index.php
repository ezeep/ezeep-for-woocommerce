<?php
/*
Plugin Name:  ezeep
Plugin URI:   http://teknohus.com/
Description:  A short little description of the plugin. It will be displayed on the Plugins page in WordPress admin area. 
Version:      1.0
Author:       Teknohus 
Author URI:   http://teknohus.com/
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  ezeep
Domain Path:  /languages
*/


function ezeep_get_access_token() {
	if( isset($_GET['code']) ) {
		add_option('ezeep_code', $_GET['code']);
		$url = 'https://account.ezeep.com/oauth/access_token/';
		$request_method = 'POST';

		$ezeep_client_id_cleint_secret = esc_attr( get_option('ezeep_client_id') ) .':'. esc_attr( get_option('ezeep_client_secret') );
		$ezeep_base64_client_id_cleint_secret = base64_encode($ezeep_client_id_cleint_secret);
		$headers[] = 'Authorization: Basic ' . $ezeep_base64_client_id_cleint_secret;

		$fields['grant_type'] = 'authorization_code';
		$fields['scope'] = 'printing';
		$fields['code'] = $_GET['code'];
		$fields['redirect_uri'] = esc_attr( get_option('ezeep_redirect_uri') );

		$response = ezeep_call_api($url, $request_method, $fields, $headers);
		if( !isset($response->error) ) {
			set_transient('ezeep_access_token', $response->access_token, 59 * MINUTE_IN_SECONDS);
			update_option('ezeep_refresh_token', $response->refresh_token);
		}

	}
}
function custom_redirects() {
	if ( is_front_page() && isset($_GET['code']) ) {
		ezeep_get_access_token();
		wp_redirect( admin_url( 'admin.php?page=ezeep-settings&authorization=true' ) );
		die;
	}

}
add_action( 'template_redirect', 'custom_redirects' );
function ezeep_refresh_access_token() {
	$url = 'https://account.ezeep.com/oauth/access_token/';
	$request_method = 'POST';

	$ezeep_client_id_cleint_secret = esc_attr( get_option('ezeep_client_id') ) .':'. esc_attr( get_option('ezeep_client_secret') );
	$ezeep_base64_client_id_cleint_secret = base64_encode($ezeep_client_id_cleint_secret);
	$headers[] = 'Authorization: Basic ' . $ezeep_base64_client_id_cleint_secret;

	$fields['grant_type'] = 'refresh_token';
	$fields['scope'] = 'printing';
	$fields['refresh_token'] = esc_attr( get_option('ezeep_refresh_token') );

	$response = ezeep_call_api($url, $request_method, $fields, $headers);
	if( !isset($response->error) ) {
		set_transient('ezeep_access_token', $response->access_token, 59 * MINUTE_IN_SECONDS);
		update_option('ezeep_refresh_token', $response->refresh_token);
	}
}

function ezeep_get_printers() {
	if(get_transient('ezeep_access_token') == false){
		// var_dump('get_transient("ezeep_access_token")');
		// die();
		ezeep_refresh_access_token();
	}
	$url = 'https://printapi.ezeep.com/sfapi/GetPrinter/';
	$request_method = 'GET';


	$headers[] = 'Authorization: Bearer ' . get_transient('ezeep_access_token');
	$response = ezeep_call_api($url, $request_method, $fields, $headers);
	update_option('ezeep_printers', $response);
	// var_dump($response);
	// die();
	return $response;
}

function ezeep_call_api($url, $request_method, $fields, $headers) {

	$curl = curl_init();

	$curl_attributes = array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => $request_method,
		CURLOPT_POSTFIELDS => $fields,
		CURLOPT_HTTPHEADER => $headers,
	);
	// var_dump($curl_attributes);
	// die();
	curl_setopt_array($curl, $curl_attributes);

	$response = curl_exec($curl);

	curl_close($curl);
	$response = json_decode($response);
	// var_dump($response);
	// die();
	return $response;

}

// create custom plugin settings menu
add_action('admin_menu', 'ezeep_plugin_create_menu');

function ezeep_plugin_create_menu() {

	//create new top-level menu
	add_menu_page(
		'ezeep Plugin Settings', // page <title>Title</title>
		'ezeep Settings', // link text
		'manage_options', // user capabilities
		'ezeep-settings', // page slug
		'ezeep_plugin_settings_page', // this function prints the page content
		'dashicons-admin-settings', // icon (from Dashicons for example)
		// 4 // menu position
	);

	//call register settings function
	add_action( 'admin_init', 'register_ezeep_plugin_settings' );

	/*if( get_transient('ezeep_access_token') == false ) {
		//call create access token function
		add_action( 'admin_init', 'ezeep_get_access_token' );
	}
	else {
		//call refresh access token function
		add_action( 'admin_init', 'ezeep_refresh_token' );
	}*/
}


function register_ezeep_plugin_settings() {
	//register our settings
	register_setting( 'ezeep-plugin-settings-group', 'ezeep_client_id' );
	register_setting( 'ezeep-plugin-settings-group', 'ezeep_client_secret' );
	register_setting( 'ezeep-plugin-settings-group', 'ezeep_redirect_uri' );
		add_settings_error(
			'ezeep-plugin-settings-errors',
			'not-enough', // part of error message ID id="setting-error-not-enough"
			'The minimum amount of slides should be at least 2!',
			'error' // success, warning, info
		);
}

function ezeep_plugin_settings_page() { ?>
	<?php
		// var_dump(get_transient('ezeep_access_token'));
		// var_dump(get_option('ezeep_refresh_token'));
	?>
	<div class="wrap">
		<h1>ezeep Settings</h1>

		<form method="post" action="options.php">
		    <?php settings_fields( 'ezeep-plugin-settings-group' ); ?>
		    <?php do_settings_sections( 'ezeep-plugin-settings-group' ); ?>
		    <table class="form-table">
		        <tr valign="top">
			        <th scope="row">Client ID</th>
			        <td>
			        	<input type="text" name="ezeep_client_id" value="<?php echo esc_attr( get_option('ezeep_client_id') ); ?>" />
			        </td>
		        </tr>
		         
		        <tr valign="top">
			        <th scope="row">Client Secret</th>
			        <td>
			        	<input type="text" name="ezeep_client_secret" value="<?php echo esc_attr( get_option('ezeep_client_secret') ); ?>" />
			        </td>
		        </tr>
		        
		        <tr valign="top">
			        <th scope="row">Redirect URI to be registerd</th>
			        <td>
			        	<?php echo home_url(); ?>
			        	<input style="display: none;" type="text" name="ezeep_redirect_uri" value="<?php echo home_url(); ?>" />
			        </td>
		        </tr>
		        
		    	<?php
		    	if( empty(get_option('ezeep_code')) && !(empty(get_option('ezeep_client_id')) || empty(get_option('ezeep_client_secret')) || empty(get_option('ezeep_redirect_uri'))) ) { ?>
			        <tr valign="top">
				        <th scope="row">Authorization</th>
				        <td>
				        	<a href="https://account.ezeep.com/oauth/authorize/?response_type=code&client_id=<?php echo esc_attr( get_option('ezeep_client_id') ); ?>&redirect_uri=<?php echo esc_attr( get_option('ezeep_redirect_uri') ); ?>">Authorize</a>
				        </td>
			        </tr>
				<?php } else if(get_option('ezeep_code')) { ?>
			        <tr valign="top">
				        <th scope="row">Authorization</th>
				        <td>
				        	<a href="<?php echo admin_url( 'admin.php?page=ezeep-settings&authorization=false' ); ?>">Unauthorize</a>
				        </td>
			        </tr>
				<?php } ?>
		    </table>
		    
		    <?php submit_button(); ?>

		</form>
	</div>
<?php } ?>
<?php
function ezeep_print_file() {
	echo "under development";
	die();
	if(get_transient('ezeep_access_token') == false){
		ezeep_refresh_access_token();
	}
	$url = 'https://printapi.ezeep.com/sfapi/Print/';
	$request_method = 'POST';

	$headers[] = 'Content-Type: application/json';
	$headers[] = 'Authorization: Bearer ' . get_transient('ezeep_access_token');

	$fields['fileurl'] = 'https://file-examples.com/storage/fe1aa0c9d563ea1e4a1fd34/2017/02/file_example_CSV_5000.csv';
	$fields['type'] = 'csv';
	$fields['printerid'] = $_GET['ezeep_printer_id'];
	$fields = json_encode($fields);

	$response = ezeep_call_api($url, $request_method, $fields, $headers);
	var_dump($response);
	die();
}
add_action( 'manage_posts_extra_tablenav', 'admin_order_list_top_bar_button', 20, 1 );
function admin_order_list_top_bar_button( $which ) {
    global $typenow;

    if ( 'shop_order' === $typenow && 'top' === $which ) {
		$printers = ezeep_get_printers();
		// var_dump($printers);
		// die();
    	if( isset($_GET['ezeep_export']) && isset($_GET['ezeep_printer_id']) )
    		ezeep_print_file();
        ?>
        <?php if(is_array($printers)) { ?>
        <div class="alignleft actions custom">
        	<select name="ezeep_printer_id">
        		<?php foreach ($printers as $printer) { ?>
        			<option value="<?php echo $printer->id; ?>"><?php echo $printer->name; ?></option>
        		<?php } ?>
        	</select>
            <button type="submit" name="ezeep_export" style="height:32px;" class="button">
            	<?php echo __( 'Export', 'ezeep' ); ?>
			</button>
        </div>
        <?php } ?>
        <?php
    }
}
add_action( 'admin_notices', 'rudr_notice' );

function rudr_notice() {

	if( isset( $_GET[ 'page' ] ) && 'ezeep-settings' == $_GET[ 'page' ] ) {
		if( isset( $_GET[ 'settings-updated' ] ) && true == $_GET[ 'settings-updated' ] ) {
			?>
				<div class="notice notice-success is-dismissible">
					<p>
						<strong>ezeep settings saved.</strong>
					</p>
				</div>
			<?php
		}
		else if( isset( $_GET[ 'authorization' ] ) && "true" == $_GET[ 'authorization' ] ) {
			?>
				<div class="notice notice-success is-dismissible">
					<p>
						<strong>ezeep authorization done.</strong>
					</p>
				</div>
			<?php
		}
		else if( isset( $_GET[ 'authorization' ] ) && "false" == $_GET[ 'authorization' ] ) {
				delete_option('ezeep_code');
				delete_transient('ezeep_access_token');
				delete_option('ezeep_refresh_token');
			?>
				<div class="notice notice-success is-dismissible">
					<p>
						<strong>ezeep unauthorization done.</strong>
					</p>
				</div>
			<?php
		}
	}

}