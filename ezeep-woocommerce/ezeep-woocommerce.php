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

global $ezeep_client_id, $ezeep_client_secret;
$ezeep_client_id = defined('EZEEP_CLIENT_ID') ? EZEEP_CLIENT_ID : '';
$ezeep_client_secret = defined('EZEEP_CLIENT_SECRET') ? EZEEP_CLIENT_SECRET : '';
function ezeep_get_base64_client_id_cleint_secret()
{
	global $ezeep_client_id, $ezeep_client_secret;
	$ezeep_client_id_cleint_secret_arr[] = $ezeep_client_id;
	$ezeep_client_id_cleint_secret_arr[] = $ezeep_client_secret;
	$ezeep_client_id_cleint_secret = implode(':', $ezeep_client_id_cleint_secret_arr);
	$ezeep_base64_client_id_cleint_secret = base64_encode($ezeep_client_id_cleint_secret);
	// var_dump($ezeep_base64_client_id_cleint_secret);
	// die();
	return $ezeep_base64_client_id_cleint_secret;
}
function ezeep_get_access_token()
{
	if (isset($_GET['code'])) {
		add_option('ezeep_code', $_GET['code']);
		$url = 'https://account.ezeep.com/oauth/access_token/';
		$request_method = 'POST';

		$ezeep_base64_client_id_cleint_secret = ezeep_get_base64_client_id_cleint_secret();
		$headers[] = 'Authorization: Basic ' . $ezeep_base64_client_id_cleint_secret;

		$fields['grant_type'] = 'authorization_code';
		$fields['scope'] = 'printing';
		$fields['code'] = $_GET['code'];
		$fields['redirect_uri'] = esc_attr(get_option('ezeep_redirect_uri'));

		$response = ezeep_call_api($url, $request_method, $fields, $headers);
		if (!isset($response->error)) {
			set_transient('ezeep_access_token', $response->access_token, 59 * MINUTE_IN_SECONDS);
			update_option('ezeep_refresh_token', $response->refresh_token);
		}

	}
}
function custom_redirects()
{
	if (is_front_page() && isset($_GET['code'])) {
		ezeep_get_access_token();
		wp_redirect(admin_url('admin.php?page=ezeep-settings&authorization=true'));
		die;
	}
}

add_action('template_redirect', 'custom_redirects');

function ezeep_refresh_access_token()
{
	$url = 'https://account.ezeep.com/oauth/access_token/';
	$request_method = 'POST';

	$ezeep_base64_client_id_cleint_secret = ezeep_get_base64_client_id_cleint_secret();
	$headers[] = 'Authorization: Basic ' . $ezeep_base64_client_id_cleint_secret;

	$fields['grant_type'] = 'refresh_token';
	$fields['scope'] = 'printing';
	$fields['refresh_token'] = esc_attr(get_option('ezeep_refresh_token'));

	$response = ezeep_call_api($url, $request_method, $fields, $headers);
	if (!isset($response->error)) {
		set_transient('ezeep_access_token', $response->access_token, 59 * MINUTE_IN_SECONDS);
		update_option('ezeep_refresh_token', $response->refresh_token);
	}
}

function ezeep_get_printers()
{
	if (get_transient('ezeep_access_token') == false) {
		// var_dump('get_transient("ezeep_access_token")');
		// die();
		ezeep_refresh_access_token();
	}
	$url = 'https://printapi.ezeep.com/sfapi/GetPrinter/';
	$request_method = 'GET';


	$headers[] = 'Authorization: Bearer ' . get_transient('ezeep_access_token');
	$response = ezeep_call_api($url, $request_method, [], $headers);
	update_option('ezeep_printers', $response);
	// var_dump($response);
	// die();
	return $response;
}

function ezeep_call_api($url, $request_method, $fields, $headers)
{

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

function ezeep_plugin_create_menu()
{

	//create new top-level menu
	add_menu_page(
		'ezeep Plugin Settings', // page <title>Title</title>
		'ezeep Settings', // link text
		'manage_options', // user capabilities
		'ezeep-settings', // page slug
		'ezeep_plugin_settings_page', // this function prints the page content
		plugin_dir_url(__FILE__) . '/ezeep-menu-icon.png', // icon (from Dashicons for example)
		// 4 // menu position
	);

	//call register settings function
	add_action('admin_init', 'register_ezeep_plugin_settings');

	/*if( get_transient('ezeep_access_token') == false ) {
													 //call create access token function
													 add_action( 'admin_init', 'ezeep_get_access_token' );
												 }
												 else {
													 //call refresh access token function
													 add_action( 'admin_init', 'ezeep_refresh_token' );
												 }*/
}
function register_ezeep_plugin_settings()
{
	//register settings
	register_setting('ezeep-plugin-settings-group', 'ezeep_redirect_uri');
	add_settings_error(
		'ezeep-plugin-settings-errors',
		'not-enough', // part of error message ID id="setting-error-not-enough"
		'The minimum amount of slides should be at least 2!',
		'error' // success, warning, info
	);
}

function ezeep_plugin_settings_page()
{
	global $ezeep_client_id;
	?>
	<?php
	// var_dump(get_transient('ezeep_access_token'));
	// var_dump(get_option('ezeep_refresh_token'));
	?>
	<div class="wrap">
		<h1>ezeep Settings</h1>

		<form method="post" action="options.php">
			<?php settings_fields('ezeep-plugin-settings-group'); ?>
			<?php do_settings_sections('ezeep-plugin-settings-group'); ?>
			<table class="form-table">

				<tr valign="top">
					<th scope="row">Redirect URI to be registerd</th>
					<td>
						<?php echo home_url(); ?>
						<input style="display: none;" type="text" name="ezeep_redirect_uri" value="<?php echo home_url(); ?>" />
					</td>
				</tr>

				<?php
				// If the code is not set, display the authorize button
				if (empty(get_option('ezeep_code')) && !(empty(get_option('ezeep_redirect_uri')))) { ?>
					<tr valign="top">
						<th scope="row">Authorization</th>
						<td>
							<a
								href="https://account.ezeep.com/oauth/authorize/?response_type=code&client_id=<?php echo $ezeep_client_id; ?>&redirect_uri=<?php echo esc_attr(get_option('ezeep_redirect_uri')); ?>">Authorize</a>
						</td>
					</tr>
					<?php
				}
				// if the code is set, display the unauthorize button
				else if (get_option('ezeep_code')) { ?>
						<tr valign="top">
							<th scope="row">Authorization</th>
							<td>
								<a href="<?php echo admin_url('admin.php?page=ezeep-settings&authorization=false'); ?>">Unauthorize</a>
							</td>
						</tr>
				<?php } ?>
			</table>

			<?php submit_button(); ?>

		</form>
	</div>
<?php } ?>
<?php
function ezeep_get_html()
{

	$orders = wc_get_orders(array());

	$html = '<!DOCTYPE html>
	<html>

	<head>
		<style>
		h2 {
			text-align: center;
		}
		table {
			font-family: arial, sans-serif;
			border-collapse: collapse;
			width: 100%;
		}

		td,
		th {
			border: 1px solid #dddddd;
			text-align: left;
			padding: 8px;
		}

		tr:nth-child(even) {
			background-color: #dddddd;
		}
		</style>
	</head>

	<body>
		<h2>ezeep WooCommerce Order Report</h2>
		<table>';
	$html .= '<tr>
				<th>Order ID</th>
				<th>Product</th>
				<th>Quantity</th>
				<th>Customer Phone</th>
				<th>Customer Email</th>
			</tr>';
	foreach ($orders as $key => $order) {
		$html .= '<tr>';
		$html .= '<td>#' . $order->get_id() . '</td>';
		// $order = wc_get_order($order->get_id());
		// var_dump($order->get_items());
		// die();

		// Iterating through each WC_Order_Item_Product objects
		foreach ($order->get_items() as $item_key => $item_values):

			$product_id = $item_values->get_product_id(); // the Product id
			$product = $item_values->get_product(); // the WC_Product object
			## Access Order Items data properties (in an array of values) ##
			$item_data = $item_values->get_data();

			$product_name = $item_data['name'];
			$product_id = $item_data['product_id'];
			$quantity = $item_data['quantity'];
			$product_title = $product->get_title();
			$html .= '<td><ul style="list-style: none;">';
			$html .= '<li>#' . $product_id . ' - ' . $product_title . '</li>';
			if ($product->is_type('variation')) {
				// Get the variation attributes
				$variation_attributes = $product->get_variation_attributes();
				// Loop through each selected attributes
				foreach ($variation_attributes as $attribute_taxonomy => $term_slug) {
					// Get product attribute name or taxonomy
					$taxonomy = str_replace('attribute_', '', $attribute_taxonomy);
					// The label name from the product attribute
					$attribute_name = wc_attribute_label($taxonomy, $product);
					// The term name (or value) from this attribute
					if (taxonomy_exists($taxonomy)) {
						$html .= '<li>' . $attribute_name . ': ' . get_term_by('slug', $term_slug, $taxonomy)->name . '</li>';
					} else {
						$html .= '<li>' . $attribute_name . ': ' . $term_slug . '</li>'; // For custom product attributes
					}
				}
			}
			$html .= '</td></ul>';
			$html .= '<td>' . $quantity . '</td>';
			// var_dump($item_name);
			// die();

		endforeach;
		$order_data = $order->get_data();
		$order_billing_phone = $order_data['billing']['phone'];
		$order_billing_email = $order_data['billing']['email'];
		$html .= '<td>' . $order_billing_phone . '</td>';
		$html .= '<td>' . $order_billing_email . '</td>';

		$html .= '</tr>';
	}
	$html .= '</table>
	</body>

	</html>';
	return $html;
}
function ezeep_print_file()
{
	// var_dump($_GET['properties']);
	// echo "under development";
	// die();

	require_once __DIR__ . '/mpdf/vendor/autoload.php';

	$mpdf = new \Mpdf\Mpdf();

	$orders = wc_get_orders(array());

	$html = ezeep_get_html();
	$mpdf->WriteHTML($html);
	$location = __DIR__ . '/files/';
	$uniqid = uniqid();
	$mpdf->Output($location . 'orders-' . $uniqid . '.pdf', 'F');

	if (get_transient('ezeep_access_token') == false) {
		ezeep_refresh_access_token();
	}
	$url = 'https://printapi.ezeep.com/sfapi/Print/';
	$request_method = 'POST';

	$headers[] = 'Content-Type: application/json';
	$headers[] = 'Authorization: Bearer ' . get_transient('ezeep_access_token');

	$fileurl = plugin_dir_url(__FILE__) . 'files/orders-' . $uniqid . '.pdf';
	$fields['fileurl'] = $fileurl;
	$fields['type'] = 'pdf';
	$fields['printerid'] = $_GET['ezeep_printer_id'];
	$fields['properties'] = $_GET['properties'];
	$fields = json_encode($fields);

	$response = ezeep_call_api($url, $request_method, $fields, $headers);
	// var_dump($response);
	// die();
}

add_action('manage_posts_extra_tablenav', 'admin_order_list_top_bar_button', 20, 1);

function admin_order_list_top_bar_button($which)
{
	global $typenow;

	if ('shop_order' === $typenow && 'top' === $which) {
		$printers = ezeep_get_printers();
		// var_dump($printers);
		// die();
		if (isset($_GET['ezeep_export']) && isset($_GET['ezeep_printer_id']))
			ezeep_print_file();
		?>
		<?php if (is_array($printers)) { ?>
			<style>
				/* The Modal (background) */
				.modal {
					display: none;
					/* Hidden by default */
					position: absolute;
					/* Stay in place */
					z-index: 1;
					/* Sit on top */
					padding-top: 60px;
					/* Location of the box */
					left: 0;
					top: 0;
					width: 100%;
					/* Full width */
					height: 100vh;
					/* Full height */
					overflow: auto;
					/* Enable scroll if needed */
					background-color: rgb(0, 0, 0);
					/* Fallback color */
					background-color: rgba(0, 0, 0, 0.4);
					/* Black w/ opacity */
				}

				/* Modal Content */
				.modal-content {
					position: relative;
					background-color: #fefefe;
					margin: auto;
					padding: 0;
					border: 1px solid #888;
					width: 350px;
					box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
					-webkit-animation-name: animatetop;
					-webkit-animation-duration: 0.4s;
					animation-name: animatetop;
					animation-duration: 0.4s
				}

				/* Add Animation */
				@-webkit-keyframes animatetop {
					from {
						top: -300px;
						opacity: 0
					}

					to {
						top: 0;
						opacity: 1
					}
				}

				@keyframes animatetop {
					from {
						top: -300px;
						opacity: 0
					}

					to {
						top: 0;
						opacity: 1
					}
				}

				/* The Close Button */
				span.close {
					color: white;
					float: right;
					font-size: 28px;
					font-weight: bold;
				}

				.close:hover,
				.close:focus {
					color: #000;
					text-decoration: none;
					cursor: pointer;
				}

				.modal-header {
					padding: 20px 16px;
					background-color: #0091b4;
					color: white;
				}

				.modal-body {
					padding: 10px 16px;
				}

				.modal-body select,
				.modal-body input {
					width: 100%;
				}

				.modal-body label {
					width: 100px;
				}

				.modal-footer {
					padding: 10px 16px;
					background-color: #0091b4;
					color: white;
				}

				.modal-footer .print {
					float: right;
				}
			</style>
			<div class="alignleft actions custom">
				<select id="ezeep_printer_id" name="ezeep_printer_id">
					<?php foreach ($printers as $printer) { ?>
						<option value="<?php echo $printer->id; ?>"><?php echo $printer->name; ?></option>
					<?php } ?>
				</select>
				<span class="hidden-fields-wrapper">
					<input id="paper" type="hidden" name="properties[paper]">
					<!-- <input id="paperid" type="hidden" name="properties[paperid]"> -->
					<input id="color" type="hidden" name="properties[color]">
					<input id="duplex" type="hidden" name="properties[duplex]">
					<input id="orientation" type="hidden" name="properties[orientation]">
					<input id="copies" type="hidden" name="properties[copies]">
					<input id="resolution" type="hidden" name="properties[resolution]">
				</span>
				<!-- Trigger/Open The Modal -->
				<button type="button" name="ezeep_export" id="myBtn" style="height:32px;" class="button">
					<?php echo __('Export', 'ezeep'); ?>
				</button>
			</div>

			<!-- The Modal -->
			<div id="myModal" class="modal">

				<!-- Modal content -->
				<div class="modal-content">
					<div class="modal-header">
						<span class="close">&times;</span>
						<h2 style="margin: 0; color: white;">Select Printer Properties</h2>
					</div>
					<div class="modal-body">
						<p id="wait">Please wait properties are being loaded...</p>
						<div id="printer-properties" style="display: none;">
							<div id="paper-wrapper">
								<div class="paper-label-wrapper">
									<div style="display: flex; align-items: center; padding-bottom: 10px;">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="gray" xmlns="http://www.w3.org/2000/svg">
											<path fill-rule="evenodd" clip-rule="evenodd"
												d="M4 4V10L6.29289 7.70711L9.29289 10.7071C9.68342 11.0976 10.3166 11.0976 10.7071 10.7071C11.0976 10.3166 11.0976 9.68342 10.7071 9.29289L7.70711 6.29289L10 4H4ZM4 20V14L6.29289 16.2929L9.29289 13.2929C9.68342 12.9024 10.3166 12.9024 10.7071 13.2929C11.0976 13.6834 11.0976 14.3166 10.7071 14.7071L7.70711 17.7071L10 20H4ZM20 10V4H14L16.2929 6.29289L13.2929 9.29289C12.9024 9.68342 12.9024 10.3166 13.2929 10.7071C13.6834 11.0976 14.3166 11.0976 14.7071 10.7071L17.7071 7.70711L20 10ZM20 20V14L17.7071 16.2929L14.7071 13.2929C14.3166 12.9024 13.6834 12.9024 13.2929 13.2929C12.9024 13.6834 12.9024 14.3166 13.2929 14.7071L16.2929 17.7071L14 20H20Z">
											</path>
										</svg>
										<label>Paper</label>
										<select id="paper" name="paper"></select>
									</div>
								</div>
							</div>
							<div id="duplex-wrapper">
								<div class="duplex-label-wrapper">
									<div style="display: flex; align-items: center; padding-bottom: 10px;">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="gray" xmlns="http://www.w3.org/2000/svg">
											<path fill-rule="evenodd" clip-rule="evenodd"
												d="M4 4V5V19V20H5H11H11.4142L11.7071 19.7071L15.7071 15.7071L16 15.4142V15L16 12H14L14 14H10V18H6L6 6H14L14 7H16L16 5V4H15L5 4H4ZM8 10L12 7V9H15.5V9.00002C18.5376 9.00003 21 11.4625 21 14.5C21 17.5376 18.5375 20 15.5 20C14.714 20 13.9665 19.8352 13.2903 19.5381L14.8826 17.9458C15.083 17.9814 15.2893 18 15.5 18C17.433 18 19 16.433 19 14.5C19 12.567 17.433 11 15.5 11V11H12V13L8 10Z">
											</path>
										</svg>
										<label>Duplex</label>
										<select id="duplex" name="duplex">
											<option value="0">No</option>
											<option value="1">Yes</option>
										</select>
									</div>
								</div>
							</div>
							<div id="color-wrapper">
								<div class="color-label-wrapper">
									<div style="display: flex; align-items: center; padding-bottom: 10px;">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="gray" xmlns="http://www.w3.org/2000/svg">
											<path fill-rule="evenodd" clip-rule="evenodd"
												d="M9.70711 2.29289C9.31658 1.90237 8.68342 1.90237 8.29289 2.29289C7.90237 2.68342 7.90237 3.31658 8.29289 3.70711L9.58579 5L2.29289 12.2929C1.90237 12.6834 1.90237 13.3166 2.29289 13.7071L10.2929 21.7071C10.6834 22.0976 11.3166 22.0976 11.7071 21.7071L19.7071 13.7071C20.0976 13.3166 20.0976 12.6834 19.7071 12.2929L11.7071 4.29289L9.70711 2.29289ZM18 19.4998C18 17.8498 20.5 14 20.5 14C20.5 14 23 17.8498 23 19.4998C23 20.8799 21.8797 22 20.5 22C19.1203 22 18 20.8799 18 19.4998ZM4.41421 13L11 19.5858L17.5858 13L11 6.41421L4.41421 13ZM5.82843 13L11 18.1716L16.1716 13H5.82843Z">
											</path>
										</svg>
										<label>Color</label>
										<select id="color" name="color">
											<option value="0">Grayscale</option>
											<option value="1">Color</option>
										</select>
									</div>
								</div>
							</div>
							<div class="orientation-label-wrapper">
								<div style="display: flex; align-items: center; padding-bottom: 10px;">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="gray" xmlns="http://www.w3.org/2000/svg">
										<path fill-rule="evenodd" clip-rule="evenodd"
											d="M13 6H5V7V17V19H3V17V6V4H5H13H15V6V7H13V6ZM6 20H7H20H21V19V13V12.5858L20.7071 12.2929L16.7071 8.29289L16.4142 8H16H7H6V9V19V20ZM8 18V10H15V14H19V18H8Z">
										</path>
									</svg>
									<label>Orientation</label>
									<select id="orientation" name="orientation"></select>
								</div>
							</div>
							<div class="resolution-label-wrapper">
								<div style="display: flex; align-items: center; padding-bottom: 10px;">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="gray" xmlns="http://www.w3.org/2000/svg">
										<path fill-rule="evenodd" clip-rule="evenodd"
											d="M17.3199 15.9056C18.3729 14.551 19 12.8487 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19C12.8487 19 14.551 18.3729 15.9056 17.3199L19.2929 20.7071C19.6834 21.0976 20.3166 21.0976 20.7071 20.7071C21.0976 20.3166 21.0976 19.6834 20.7071 19.2929L17.3199 15.9056ZM17 11C17 14.3137 14.3137 17 11 17C7.68629 17 5 14.3137 5 11C5 7.68629 7.68629 5 11 5C14.3137 5 17 7.68629 17 11ZM10 8V6H12V8H10ZM10 10V8H8V10H6V12H8V14H10V16H12V14H14V12H16V10H14V8H12V10H10ZM10 12H8V10H10V12ZM12 12V14H10V12H12ZM12 12V10H14V12H12Z">
										</path>
									</svg>
									<label>Resolution</label>
									<select id="resolution" name="resolution"></select>
								</div>
							</div>
							<div class="copies-wrapper">
								<div class="copies-label-wrapper">
									<div style="display: flex; align-items: center;">
										<svg width="24" height="24" viewBox="0 0 24 24" fill="gray" xmlns="http://www.w3.org/2000/svg">
											<path fill-rule="evenodd" clip-rule="evenodd"
												d="M5 20L5 4L3 4L3 20L5 20ZM8 4L8 20L6 20L6 4L8 4ZM9 19L9 20L10 20L20 20L21 20L21 19L21 9L21 8.58579L20.7071 8.29289L16.7071 4.29289L16.4142 4L16 4L10 4L9 4L9 5L9 19ZM19 18L11 18L11 6L15 6L15 10L19 10L19 18Z">
											</path>
										</svg>
										<label>Copies</label>
										<input type="number" id="copies" name="copies" min="1" value="1">
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" style="height:32px; min-width: 100px;" class="button print">
							<?php echo __('Print', 'ezeep'); ?>
						</button>
						<button type="button" style="height:32px; min-width: 100px;" class="button close">
							<?php echo __('Cancel', 'ezeep'); ?>
						</button>
					</div>
				</div>

			</div>
			<script>
				// Get the modal
				var modal = document.getElementById("myModal");

				// Get the button that opens the modal
				var btn = document.getElementById("myBtn");

				// When the user clicks the button, open the modal
				btn.onclick = function () {
					modal.style.display = "block";
					var settings = {
						"url": "https://printapi.ezeep.com/sfapi/GetPrinterProperties/?id=" + jQuery('#ezeep_printer_id').val(),
						"method": "GET",
						"timeout": 0,
						"headers": {
							"Content-Type": "application/json",
							"Authorization": "Bearer <?php echo get_transient('ezeep_access_token'); ?>"
						},
					};

					jQuery.ajax(settings).done(function (response) {
						response.forEach((printerProperties, index) => {
							jQuery('#wait').hide();
							jQuery('#printer-properties').show();
							console.log(printerProperties);
							console.log(printerProperties['MediaSupported']);
							if (printerProperties['DuplexSupported'] !== true) {
								jQuery('#duplex-wrapper').remove();
								jQuery('input#duplex').remove();
								console.log('DuplexSupported', DuplexSupported);
							}
							if (printerProperties['Color'] !== true) {
								jQuery('#color-wrapper').remove();
								jQuery('input#color').remove();
							}
							jQuery('select#paper').empty();
							jQuery('select#orientation').empty();
							jQuery('select#resolution').empty();
							printerProperties['MediaSupported'].forEach((MediaSupported, index) => {
								console.log('MediaSupported', MediaSupported);
								jQuery('select#paper').append(jQuery('<option>', {
									// value: 1,
									text: MediaSupported
								}));
							});
							printerProperties['OrientationsSupported'].forEach((Orientation, index) => {
								console.log('Orientation', Orientation);
								jQuery('select#orientation').append(jQuery('<option>', {
									// value: 1,
									text: Orientation
								}));
							});
							printerProperties['Resolutions'].forEach((Resolution, index) => {
								console.log('Resolution', Resolution);
								jQuery('select#resolution').append(jQuery('<option>', {
									// value: 1,
									text: Resolution
								}));
							});
						});
					});
				}

				// When the user clicks on <span> (x), close the modal
				jQuery('.close').click(function () {
					modal.style.display = "none";
				});

				// When the user clicks on <span> (x), close the modal
				jQuery('.print').click(function () {
					jQuery('.modal-body select, .modal-body input').each(function (index) {
						console.log(index + ": " + jQuery(this).val());
						jQuery('.hidden-fields-wrapper #' + jQuery(this).attr('id')).val(jQuery(this).val());
					});
					jQuery('#myBtn').attr('type', 'submit');
					jQuery('#myBtn').trigger('click');
				});

				// When the user clicks anywhere outside of the modal, close it
				window.onclick = function (event) {
					if (event.target == modal) {
						modal.style.display = "none";
					}
				}
			</script>
		<?php } ?>
	<?php
	}
}
add_action('admin_notices', 'rudr_notice');

function rudr_notice()
{

	if (isset($_GET['page']) && 'ezeep-settings' == $_GET['page']) {
		if (isset($_GET['settings-updated']) && true == $_GET['settings-updated']) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<strong>ezeep settings saved.</strong>
				</p>
			</div>
			<?php
		} else if (isset($_GET['authorization']) && "true" == $_GET['authorization']) {
			?>
				<div class="notice notice-success is-dismissible">
					<p>
						<strong>ezeep authorization done.</strong>
					</p>
				</div>
			<?php
		} else if (isset($_GET['authorization']) && "false" == $_GET['authorization']) {
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
	} else if (isset($_GET['post_type']) && 'shop_order' == $_GET['post_type']) {
		if (isset($_GET['ezeep_export'])) {
			?>
				<div class="notice notice-success is-dismissible">
					<p>
						<strong>ezeep print request has been sent.</strong>
					</p>
				</div>
			<?php
		}
	}

}
