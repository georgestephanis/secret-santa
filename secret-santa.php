<?php

/*
 * Plugin Name: Secret Santa
 * Plugin URI: http://github.com/georgestephanis/secret-santa
 * Description: A plugin that lets you organize Secret Santa groups.
 * Author: George Stephanis
 * Version: 0.1-dev
 * Author URI: https://stephanis.info
 */

class Secret_Santa {
	public static function add_hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_shortcode( 'secret-santa', array( __CLASS__, 'shortcode' ) );
		add_shortcode( 'holiday-gift-exchange', array( __CLASS__, 'shortcode' ) );
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'admin_post_secret-santa_signup', array( __CLASS__, 'process_signup' ) );
		add_action( 'wp_ajax_save_elf_assignees', array( __CLASS__, 'wp_ajax_save_elf_assignees' ) );
	}

	public static function register_post_type() {
		register_post_type( 'secret-santa', array(
			'label' => __( 'Secret Santa', 'secret-santa' ),
		) );

		register_meta( 'post', 'secret-santa :: shipping_address', array(
			'type' => 'string',
			'description' => __( 'The full shipping address for the participant.', 'secret-santa' ),
			'single' => true,
		) );

		register_meta( 'post', 'secret-santa :: shipping_country', array(
			'type' => 'ISO 3166-1 alpha-2 country code',
			'description' => __( 'The country to which the participant\'s gift will be sent.', 'secret-santa' ),
			'single' => true,
		) );

		register_meta( 'post', 'secret-santa :: shipping_to', array(
			'type' => 'user_login',
			'description' => __( 'The user login of the recipient that this person will be sending to.', 'secret-santa' ),
			'single' => true,
		) );
	}

	public static function shortcode( $atts ) {
		$state = 2;
		$user_id = get_current_user_id();
		ob_start();
		?>
		<div id="secret-santa-wrap">
			<?php if ( 1 === $state ) : /* stage one -- signups */ ?>
				<?php if ( ! $user_id ) : ?>
					<p><?php esc_html_e( 'Want to sign up? Log in!', 'secret-santa' ); ?></p>
					<?php wp_login_form(); ?>
				<?php else :
					$user = get_userdata( $user_id );
					$found = get_posts( array(
						'author' => $user_id,
						'slug' => $user->user_login,
						'post_type' => 'secret-santa',
						'post_status' => 'publish',
						'posts_per_page' => 1,
					) );
					$countries = self::get_countries();
					$defaults = apply_filters( 'secret-santa_get_sign_up_defaults', array(
						'shipping_address' => null,
						'shipping_country' => null,
					), $user_id, $user );
					$submit_text = __( 'Sign up!', 'secret-santa' );

					if ( ! empty( $found ) ) {
						$user_post = array_shift( $found );
						echo '<p class="alert">' . esc_html__( 'You are already signed up!  You may update your details below:', 'secret-santa' ) . '</p>';
						$submit_text = __( 'Update info!', 'secret-santa' );
						$defaults = array(
							'shipping_address' => get_post_meta( $user_post->ID, 'secret-santa :: shipping_address', true ),
							'shipping_country' => get_post_meta( $user_post->ID, 'secret-santa :: shipping_country', true ),
						);
					}
					?>
					<form id="secret-santa-signup" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST">
						<input type="hidden" name="action" value="secret-santa_signup" />
						<?php wp_nonce_field( 'secret-santa_signup' ); ?>
						<label>
							<?php esc_html_e( 'Your shipping address', 'secret-santa' ); ?>
							<textarea name="shipping_address" required><?php echo esc_textarea( $defaults['shipping_address'] ); ?></textarea>
						</label>
						<label>
							<?php esc_html_e( 'Your shipping country', 'secret-santa' ); ?>
							<select name="shipping_country" required>
								<option value=""><?php esc_html_e( 'Select a country...', 'secret-santa' ); ?></option>
								<?php foreach ( $countries as $code => $country ) : ?>
									<option value="<?php echo esc_attr( $code ); ?>" <?php if ( in_array( $defaults['shipping_country'], array( $code, $country ) ) ) echo ' selected="selected"'; ?> ><?php echo esc_html( $country ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<button type="submit"><?php echo esc_html( $submit_text ); ?></button>
					</form>
					<?php
				endif; ?>
			<?php elseif ( 2 === $state ) : /* stage two -- signups closed, waiting on assignments */ ?>
				<p><?php esc_html_e( 'We are currently sorting out who ships to who and whatnot, and hope to have them available shortly!', 'secret-santa' ); ?></p>
				<?php if ( ! $user_id ) : ?>
					<p><?php esc_html_e( 'Want to confirm whether you had signed up? Log in!', 'secret-santa' ); ?></p>
					<?php wp_login_form(); ?>
				<?php else :
					$user = get_userdata( $user_id );
					$found = get_posts( array(
						'author' => $user_id,
						'slug' => $user->user_login,
						'post_type' => 'secret-santa',
						'post_status' => 'publish',
						'posts_per_page' => 1,
					) );

					if ( ! empty( $found ) ) {
						echo '<p class="alert">' . esc_html__( 'You are already signed up!', 'secret-santa' ) . '</p>';
					} else {
						echo '<p class="alert">' . esc_html__( 'Unfortunately, sign-ups are now closed, and it doesn\'t look like you signed up!', 'secret-santa' ) . '</p>';
					}
				endif; ?>
			<?php elseif ( 3 === $state ) : /* stage three -- assignments available, please ship */ ?>

			<?php elseif ( 4 === $state ) : /* stage four -- reveal */ ?>

			<?php else : /* stage ??? -- something went wrong */ ?>
				<p><?php _e( 'Well, that\'s not supposed to happen...', 'secret-santa' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function process_signup() {
		check_admin_referer( 'secret-santa_signup' );

		$user_id = get_current_user_id();
		$user = get_userdata( $user_id );
		$found = get_posts( array(
			'author' => $user_id,
			'slug' => $user->user_login,
			'post_type' => 'secret-santa',
			'post_status' => 'publish',
			'posts_per_page' => 1,
		) );

		$postarr = array(
			'post_author' => $user_id,
			'post_title' => $user->display_name,
			'post_name' => $user->user_login,
			'post_type' => 'secret-santa',
			'post_status' => 'publish',
		);

		if ( $found ) {
			$postarr['ID'] = $found[0]->ID;
		}

		$post_id = wp_insert_post( $postarr );

		if ( $post_id ) {
			update_post_meta( $post_id, 'secret-santa :: shipping_address', sanitize_text_field( $_POST['shipping_address'] ) );
			update_post_meta( $post_id, 'secret-santa :: shipping_country', sanitize_text_field( $_POST['shipping_country'] ) );
		}

		wp_safe_redirect( $_POST['_wp_http_referer'] . '#elf-' . $post_id );
	}

	public static function admin_menu() {
		add_users_page( __( 'Secret Santa' ), __( 'Secret Santa' ), 'manage_options', 'secret-santa', array( __CLASS__, 'admin_page' ) );
	}

	public static function admin_page() {
		add_action( 'admin_print_footer_scripts', array( __CLASS__, 'js_templates' ), 1 );

		wp_enqueue_style( 'secret-santa', plugins_url( 'admin-page.css', __FILE__ ) );
		wp_enqueue_script( 'secret-santa', plugins_url( 'admin-page.js', __FILE__ ), array( 'wp-util', 'jquery' ), false, true );
		wp_localize_script( 'secret-santa', 'secretSanta', array(
			'elves' => self::get_users(),
			'nonces' => array(
				'save_elf_assignees' => wp_create_nonce( 'save_elf_assignees' ),
			),
		) );
		?>
		<div class="wrap" id="secret-santa-page">
			<h1><?php esc_html_e( 'Secret Santa' ); ?> <a class="page-title-action assign-elves" href="#"><?php esc_html_e( 'Assign Elves', 'secret-santa' ); ?></a></h1>

			<h2><?php esc_html_e( 'The following users are participating in Secret Santa' ); ?></h2>
			<table id="elves-table" class="wp-list-table widefat striped">
				<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'User' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Shipping To' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Receiving From' ); ?></th>
				</tr>
				</thead>
				<tfoot>
				<tr>
					<th scope="col"><?php esc_html_e( 'User' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Shipping To' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Receiving From' ); ?></th>
				</tr>
				</tfoot>
				<tbody>
				<tr class="no-items">
					<td class="colspanchange" colspan="3"><?php esc_html_e( 'No participants yet.' ); ?></td>
				</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function wp_ajax_save_elf_assignees() {
		check_admin_referer( 'save_elf_assignees', '_elfnonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Cheatin\', uh?', 'secret-santa' ) );
		}

		foreach ( $_POST['elf'] as $elf_id => $send_to ) {
			update_post_meta( intval( $elf_id ), 'secret-santa :: shipping_to', sanitize_text_field( $send_to ) );
		}
		wp_send_json_success( sprintf( __( 'Success! %d assignments saved!', 'secret-santa' ), sizeof( $_POST['elf'] ) ) );
	}

	/**
	 * JS Templates.
	 */
	public static function js_templates() {
		?>
		<script type="text/html" id="tmpl-elf-card">
			<div class="elf-card wp-clearfix">
				<div class="elfvatar">
					<img src="{{ data.avatar_url }}" />
				</div>
				<h4 class="name">{{ data.name }}</h4>
				<small class="address">{{ data.address }}</small>
				<strong class="country">{{ data.country }}</strong>
			</div>
		</script>

		<script type="text/html" id="tmpl-elf-row">
			   <tr>
				   <td>{{{ data.elf_card }}}</td>
				   <td>{{{ data.shipping_to_card }}}</td>
				   <td>{{{ data.receiving_from_card }}}</td>
			   </tr>
		</script>
		<?php
	}

	public static function get_users() {
		$users = get_posts( array(
			'post_type' => 'secret-santa',
			'post_status' => 'publish',
			'posts_per_page' => -1,
		) );

		$return = array();

		if ( $users ) {
			foreach ( $users as $user_post ) {
				$user = get_user_by( 'login', $user_post->post_name );
				$return[ $user->user_login ] = array(
					'ID' => $user_post->ID,
					'user_login' => $user->user_login,
					'name' => $user->display_name,
					'avatar_url' => get_avatar_url( $user ),
					'address' => get_post_meta( $user_post->ID, 'secret-santa :: shipping_address', true ),
					'country' => get_post_meta( $user_post->ID, 'secret-santa :: shipping_country', true ),
					'shipping_to' => get_post_meta( $user_post->ID, 'secret-santa :: shipping_to', true ),
				);
			}
		}

		return $return;
	}

	public static function get_countries() {
		$countries = array(
			'AF' => __( 'Afghanistan', 'secret-santa' ),
			'AX' => __( 'Aland Islands', 'secret-santa' ),
			'AL' => __( 'Albania', 'secret-santa' ),
			'DZ' => __( 'Algeria', 'secret-santa' ),
			'AS' => __( 'American Samoa', 'secret-santa' ),
			'AD' => __( 'Andorra', 'secret-santa' ),
			'AO' => __( 'Angola', 'secret-santa' ),
			'AI' => __( 'Anguilla', 'secret-santa' ),
			'AQ' => __( 'Antarctica', 'secret-santa' ),
			'AG' => __( 'Antigua And Barbuda', 'secret-santa' ),
			'AR' => __( 'Argentina', 'secret-santa' ),
			'AM' => __( 'Armenia', 'secret-santa' ),
			'AW' => __( 'Aruba', 'secret-santa' ),
			'AU' => __( 'Australia', 'secret-santa' ),
			'AT' => __( 'Austria', 'secret-santa' ),
			'AZ' => __( 'Azerbaijan', 'secret-santa' ),
			'BS' => __( 'Bahamas', 'secret-santa' ),
			'BH' => __( 'Bahrain', 'secret-santa' ),
			'BD' => __( 'Bangladesh', 'secret-santa' ),
			'BB' => __( 'Barbados', 'secret-santa' ),
			'BY' => __( 'Belarus', 'secret-santa' ),
			'BE' => __( 'Belgium', 'secret-santa' ),
			'BZ' => __( 'Belize', 'secret-santa' ),
			'BJ' => __( 'Benin', 'secret-santa' ),
			'BM' => __( 'Bermuda', 'secret-santa' ),
			'BT' => __( 'Bhutan', 'secret-santa' ),
			'BO' => __( 'Bolivia', 'secret-santa' ),
			'BA' => __( 'Bosnia And Herzegovina', 'secret-santa' ),
			'BW' => __( 'Botswana', 'secret-santa' ),
			'BV' => __( 'Bouvet Island', 'secret-santa' ),
			'BR' => __( 'Brazil', 'secret-santa' ),
			'IO' => __( 'British Indian Ocean Territory', 'secret-santa' ),
			'BN' => __( 'Brunei Darussalam', 'secret-santa' ),
			'BG' => __( 'Bulgaria', 'secret-santa' ),
			'BF' => __( 'Burkina Faso', 'secret-santa' ),
			'BI' => __( 'Burundi', 'secret-santa' ),
			'KH' => __( 'Cambodia', 'secret-santa' ),
			'CM' => __( 'Cameroon', 'secret-santa' ),
			'CA' => __( 'Canada', 'secret-santa' ),
			'CV' => __( 'Cape Verde', 'secret-santa' ),
			'KY' => __( 'Cayman Islands', 'secret-santa' ),
			'CF' => __( 'Central African Republic', 'secret-santa' ),
			'TD' => __( 'Chad', 'secret-santa' ),
			'CL' => __( 'Chile', 'secret-santa' ),
			'CN' => __( 'China', 'secret-santa' ),
			'CX' => __( 'Christmas Island', 'secret-santa' ),
			'CC' => __( 'Cocos (Keeling) Islands', 'secret-santa' ),
			'CO' => __( 'Colombia', 'secret-santa' ),
			'KM' => __( 'Comoros', 'secret-santa' ),
			'CG' => __( 'Congo', 'secret-santa' ),
			'CD' => __( 'Congo, Democratic Republic', 'secret-santa' ),
			'CK' => __( 'Cook Islands', 'secret-santa' ),
			'CR' => __( 'Costa Rica', 'secret-santa' ),
			'CI' => __( 'Cote D\'Ivoire', 'secret-santa' ),
			'HR' => __( 'Croatia', 'secret-santa' ),
			'CU' => __( 'Cuba', 'secret-santa' ),
			'CY' => __( 'Cyprus', 'secret-santa' ),
			'CZ' => __( 'Czech Republic', 'secret-santa' ),
			'DK' => __( 'Denmark', 'secret-santa' ),
			'DJ' => __( 'Djibouti', 'secret-santa' ),
			'DM' => __( 'Dominica', 'secret-santa' ),
			'DO' => __( 'Dominican Republic', 'secret-santa' ),
			'EC' => __( 'Ecuador', 'secret-santa' ),
			'EG' => __( 'Egypt', 'secret-santa' ),
			'SV' => __( 'El Salvador', 'secret-santa' ),
			'GQ' => __( 'Equatorial Guinea', 'secret-santa' ),
			'ER' => __( 'Eritrea', 'secret-santa' ),
			'EE' => __( 'Estonia', 'secret-santa' ),
			'ET' => __( 'Ethiopia', 'secret-santa' ),
			'FK' => __( 'Falkland Islands (Malvinas)', 'secret-santa' ),
			'FO' => __( 'Faroe Islands', 'secret-santa' ),
			'FJ' => __( 'Fiji', 'secret-santa' ),
			'FI' => __( 'Finland', 'secret-santa' ),
			'FR' => __( 'France', 'secret-santa' ),
			'GF' => __( 'French Guiana', 'secret-santa' ),
			'PF' => __( 'French Polynesia', 'secret-santa' ),
			'TF' => __( 'French Southern Territories', 'secret-santa' ),
			'GA' => __( 'Gabon', 'secret-santa' ),
			'GM' => __( 'Gambia', 'secret-santa' ),
			'GE' => __( 'Georgia', 'secret-santa' ),
			'DE' => __( 'Germany', 'secret-santa' ),
			'GH' => __( 'Ghana', 'secret-santa' ),
			'GI' => __( 'Gibraltar', 'secret-santa' ),
			'GR' => __( 'Greece', 'secret-santa' ),
			'GL' => __( 'Greenland', 'secret-santa' ),
			'GD' => __( 'Grenada', 'secret-santa' ),
			'GP' => __( 'Guadeloupe', 'secret-santa' ),
			'GU' => __( 'Guam', 'secret-santa' ),
			'GT' => __( 'Guatemala', 'secret-santa' ),
			'GG' => __( 'Guernsey', 'secret-santa' ),
			'GN' => __( 'Guinea', 'secret-santa' ),
			'GW' => __( 'Guinea-Bissau', 'secret-santa' ),
			'GY' => __( 'Guyana', 'secret-santa' ),
			'HT' => __( 'Haiti', 'secret-santa' ),
			'HM' => __( 'Heard Island & Mcdonald Islands', 'secret-santa' ),
			'VA' => __( 'Holy See (Vatican City State)', 'secret-santa' ),
			'HN' => __( 'Honduras', 'secret-santa' ),
			'HK' => __( 'Hong Kong', 'secret-santa' ),
			'HU' => __( 'Hungary', 'secret-santa' ),
			'IS' => __( 'Iceland', 'secret-santa' ),
			'IN' => __( 'India', 'secret-santa' ),
			'ID' => __( 'Indonesia', 'secret-santa' ),
			'IR' => __( 'Iran, Islamic Republic Of', 'secret-santa' ),
			'IQ' => __( 'Iraq', 'secret-santa' ),
			'IE' => __( 'Ireland', 'secret-santa' ),
			'IM' => __( 'Isle Of Man', 'secret-santa' ),
			'IL' => __( 'Israel', 'secret-santa' ),
			'IT' => __( 'Italy', 'secret-santa' ),
			'JM' => __( 'Jamaica', 'secret-santa' ),
			'JP' => __( 'Japan', 'secret-santa' ),
			'JE' => __( 'Jersey', 'secret-santa' ),
			'JO' => __( 'Jordan', 'secret-santa' ),
			'KZ' => __( 'Kazakhstan', 'secret-santa' ),
			'KE' => __( 'Kenya', 'secret-santa' ),
			'KI' => __( 'Kiribati', 'secret-santa' ),
			'KR' => __( 'Korea', 'secret-santa' ),
			'KW' => __( 'Kuwait', 'secret-santa' ),
			'KG' => __( 'Kyrgyzstan', 'secret-santa' ),
			'LA' => __( 'Lao People\'s Democratic Republic', 'secret-santa' ),
			'LV' => __( 'Latvia', 'secret-santa' ),
			'LB' => __( 'Lebanon', 'secret-santa' ),
			'LS' => __( 'Lesotho', 'secret-santa' ),
			'LR' => __( 'Liberia', 'secret-santa' ),
			'LY' => __( 'Libyan Arab Jamahiriya', 'secret-santa' ),
			'LI' => __( 'Liechtenstein', 'secret-santa' ),
			'LT' => __( 'Lithuania', 'secret-santa' ),
			'LU' => __( 'Luxembourg', 'secret-santa' ),
			'MO' => __( 'Macao', 'secret-santa' ),
			'MK' => __( 'Macedonia', 'secret-santa' ),
			'MG' => __( 'Madagascar', 'secret-santa' ),
			'MW' => __( 'Malawi', 'secret-santa' ),
			'MY' => __( 'Malaysia', 'secret-santa' ),
			'MV' => __( 'Maldives', 'secret-santa' ),
			'ML' => __( 'Mali', 'secret-santa' ),
			'MT' => __( 'Malta', 'secret-santa' ),
			'MH' => __( 'Marshall Islands', 'secret-santa' ),
			'MQ' => __( 'Martinique', 'secret-santa' ),
			'MR' => __( 'Mauritania', 'secret-santa' ),
			'MU' => __( 'Mauritius', 'secret-santa' ),
			'YT' => __( 'Mayotte', 'secret-santa' ),
			'MX' => __( 'Mexico', 'secret-santa' ),
			'FM' => __( 'Micronesia, Federated States Of', 'secret-santa' ),
			'MD' => __( 'Moldova', 'secret-santa' ),
			'MC' => __( 'Monaco', 'secret-santa' ),
			'MN' => __( 'Mongolia', 'secret-santa' ),
			'ME' => __( 'Montenegro', 'secret-santa' ),
			'MS' => __( 'Montserrat', 'secret-santa' ),
			'MA' => __( 'Morocco', 'secret-santa' ),
			'MZ' => __( 'Mozambique', 'secret-santa' ),
			'MM' => __( 'Myanmar', 'secret-santa' ),
			'NA' => __( 'Namibia', 'secret-santa' ),
			'NR' => __( 'Nauru', 'secret-santa' ),
			'NP' => __( 'Nepal', 'secret-santa' ),
			'NL' => __( 'Netherlands', 'secret-santa' ),
			'AN' => __( 'Netherlands Antilles', 'secret-santa' ),
			'NC' => __( 'New Caledonia', 'secret-santa' ),
			'NZ' => __( 'New Zealand', 'secret-santa' ),
			'NI' => __( 'Nicaragua', 'secret-santa' ),
			'NE' => __( 'Niger', 'secret-santa' ),
			'NG' => __( 'Nigeria', 'secret-santa' ),
			'NU' => __( 'Niue', 'secret-santa' ),
			'NF' => __( 'Norfolk Island', 'secret-santa' ),
			'MP' => __( 'Northern Mariana Islands', 'secret-santa' ),
			'NO' => __( 'Norway', 'secret-santa' ),
			'OM' => __( 'Oman', 'secret-santa' ),
			'PK' => __( 'Pakistan', 'secret-santa' ),
			'PW' => __( 'Palau', 'secret-santa' ),
			'PS' => __( 'Palestinian Territory, Occupied', 'secret-santa' ),
			'PA' => __( 'Panama', 'secret-santa' ),
			'PG' => __( 'Papua New Guinea', 'secret-santa' ),
			'PY' => __( 'Paraguay', 'secret-santa' ),
			'PE' => __( 'Peru', 'secret-santa' ),
			'PH' => __( 'Philippines', 'secret-santa' ),
			'PN' => __( 'Pitcairn', 'secret-santa' ),
			'PL' => __( 'Poland', 'secret-santa' ),
			'PT' => __( 'Portugal', 'secret-santa' ),
			'PR' => __( 'Puerto Rico', 'secret-santa' ),
			'QA' => __( 'Qatar', 'secret-santa' ),
			'RE' => __( 'Reunion', 'secret-santa' ),
			'RO' => __( 'Romania', 'secret-santa' ),
			'RU' => __( 'Russian Federation', 'secret-santa' ),
			'RW' => __( 'Rwanda', 'secret-santa' ),
			'BL' => __( 'Saint Barthelemy', 'secret-santa' ),
			'SH' => __( 'Saint Helena', 'secret-santa' ),
			'KN' => __( 'Saint Kitts And Nevis', 'secret-santa' ),
			'LC' => __( 'Saint Lucia', 'secret-santa' ),
			'MF' => __( 'Saint Martin', 'secret-santa' ),
			'PM' => __( 'Saint Pierre And Miquelon', 'secret-santa' ),
			'VC' => __( 'Saint Vincent And Grenadines', 'secret-santa' ),
			'WS' => __( 'Samoa', 'secret-santa' ),
			'SM' => __( 'San Marino', 'secret-santa' ),
			'ST' => __( 'Sao Tome And Principe', 'secret-santa' ),
			'SA' => __( 'Saudi Arabia', 'secret-santa' ),
			'SN' => __( 'Senegal', 'secret-santa' ),
			'RS' => __( 'Serbia', 'secret-santa' ),
			'SC' => __( 'Seychelles', 'secret-santa' ),
			'SL' => __( 'Sierra Leone', 'secret-santa' ),
			'SG' => __( 'Singapore', 'secret-santa' ),
			'SK' => __( 'Slovakia', 'secret-santa' ),
			'SI' => __( 'Slovenia', 'secret-santa' ),
			'SB' => __( 'Solomon Islands', 'secret-santa' ),
			'SO' => __( 'Somalia', 'secret-santa' ),
			'ZA' => __( 'South Africa', 'secret-santa' ),
			'GS' => __( 'South Georgia And Sandwich Isl.', 'secret-santa' ),
			'ES' => __( 'Spain', 'secret-santa' ),
			'LK' => __( 'Sri Lanka', 'secret-santa' ),
			'SD' => __( 'Sudan', 'secret-santa' ),
			'SR' => __( 'Suriname', 'secret-santa' ),
			'SJ' => __( 'Svalbard And Jan Mayen', 'secret-santa' ),
			'SZ' => __( 'Swaziland', 'secret-santa' ),
			'SE' => __( 'Sweden', 'secret-santa' ),
			'CH' => __( 'Switzerland', 'secret-santa' ),
			'SY' => __( 'Syrian Arab Republic', 'secret-santa' ),
			'TW' => __( 'Taiwan', 'secret-santa' ),
			'TJ' => __( 'Tajikistan', 'secret-santa' ),
			'TZ' => __( 'Tanzania', 'secret-santa' ),
			'TH' => __( 'Thailand', 'secret-santa' ),
			'TL' => __( 'Timor-Leste', 'secret-santa' ),
			'TG' => __( 'Togo', 'secret-santa' ),
			'TK' => __( 'Tokelau', 'secret-santa' ),
			'TO' => __( 'Tonga', 'secret-santa' ),
			'TT' => __( 'Trinidad And Tobago', 'secret-santa' ),
			'TN' => __( 'Tunisia', 'secret-santa' ),
			'TR' => __( 'Turkey', 'secret-santa' ),
			'TM' => __( 'Turkmenistan', 'secret-santa' ),
			'TC' => __( 'Turks And Caicos Islands', 'secret-santa' ),
			'TV' => __( 'Tuvalu', 'secret-santa' ),
			'UG' => __( 'Uganda', 'secret-santa' ),
			'UA' => __( 'Ukraine', 'secret-santa' ),
			'AE' => __( 'United Arab Emirates', 'secret-santa' ),
			'GB' => __( 'United Kingdom', 'secret-santa' ),
			'US' => __( 'United States', 'secret-santa' ),
			'UM' => __( 'United States Outlying Islands', 'secret-santa' ),
			'UY' => __( 'Uruguay', 'secret-santa' ),
			'UZ' => __( 'Uzbekistan', 'secret-santa' ),
			'VU' => __( 'Vanuatu', 'secret-santa' ),
			'VE' => __( 'Venezuela', 'secret-santa' ),
			'VN' => __( 'Viet Nam', 'secret-santa' ),
			'VG' => __( 'Virgin Islands, British', 'secret-santa' ),
			'VI' => __( 'Virgin Islands, U.S.', 'secret-santa' ),
			'WF' => __( 'Wallis And Futuna', 'secret-santa' ),
			'EH' => __( 'Western Sahara', 'secret-santa' ),
			'YE' => __( 'Yemen', 'secret-santa' ),
			'ZM' => __( 'Zambia', 'secret-santa' ),
			'ZW' => __( 'Zimbabwe', 'secret-santa' ),
		);

		return $countries;
	}
}

Secret_Santa::add_hooks();
