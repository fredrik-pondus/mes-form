<?php

/**
 * Plugin Name:    Make Equal Story Form
 * Plugin URI:     http://medpondus.se
 * Description:    Add functionality for receiving and handling visitor stories.
 * Version:        1.0
 * License:        GPL-2.0+
 * License URI:    http://www.gnu.org/licenses/gpl-2.0.txt
 * Author:         Fredrik Edvardsson @ Pondus Kommunikation
 * Author URI:     http://medpondus.se
 * Text Domain:    mes-form
 * Domain Path:    /languages
 */

namespace mes_form;


// Not allowed to be called directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'MES_FORM_VER' ) ) {
	define( 'MES_FORM_VER', '1.0' );
}


class Plugin {
	static $instance = false;

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}


	public static function get_instance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}


	public function init() {
		add_action( 'init', array( $this, 'shortcode_form' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_and_styles' ) );
		add_action( 'wp_ajax_nopriv_mes_submit_story', array( $this, 'handle_ajax_form_submission' ) );
		add_action( 'wp_ajax_mes_submit_story', array( $this, 'handle_ajax_form_submission' ) );

		add_filter( 'mes-process-form', array( $this, 'handle_form_submission' ) );

		add_shortcode( 'me-stories-form', array( $this, 'shortcode_form' ) );

		$this->textdomain();

		$this->messages = array(
			'mes_email' => array(
				'required' => __( 'Du måste fylla i e-post.', 'mes-form' ),
				'email'    => __( 'Du måste ange en giltig e-postadress.', 'mes-form' )
			),
			'mes_header' => array(
				'required' => __( 'Du måste fylla i rubrik.', 'mes-form' )
			),
			'mes_story' => array(
				'required' => __( 'Du måste fylla i din berättelse.', 'mes-form' )
			),
			'mes_summary' => array(
				'required' => __( 'Du måste fylla i en kort version av din berättelse.', 'mes-form' )
			),
			'mes_location' => array(
				'required' => __( 'Du måste ange var berättelsen utspelar sig.', 'mes-form' )
			),
			'mes_subject' => array(
				'required' => __( 'Du måste ange vad berättelsen handlar om.', 'mes-form' )
			),
			'mes_interpret' => array(
				'required' => __( 'Du måste ange hur du upplever berättelsen.', 'mes-form' )
			),
			'mes_approve' => array(
				'required' => __( 'Du måste godkänna villkoren.', 'mes-form' )
			)
		);
	}


	public function textdomain() {
		load_textdomain( 'mes-form', dirname( __FILE__ ) . '/languages/' . get_locale() . '.mo' );
	}


	public function shortcode_form() {

		$posted = isset( $_SERVER['REQUEST_METHOD'] ) && strtolower( $_SERVER['REQUEST_METHOD'] ) == 'post';
		$completed = isset( $_GET['completed'] ) && $_GET['completed'] === 'true';

		$result = array(
			'success' => false,
			'errors'  => array()
		);

		if ( $posted ) {
			$result = apply_filters( 'mes-process-form', $result );

			if ( $result['success'] && ( !defined('DOING_AJAX') || !DOING_AJAX )  ) {
				header( 'Location:' . get_permalink() . '?completed=true' ); // Redirect to prevent resubmission
				exit;
			}
		}

		ob_start();

		?>

		<?php if ( $completed ) : ?>

			<div class="mes-completed">
				<p><?php _e( 'Tack för ditt bidrag.', 'mes-form' ) ?></p>
			</div>

		<?php else: ?>

			<?php if ( ! empty( $result['errors'] ) ) : ?>

				<div class="mes-validation-warning mes-validation-warning--visible">
					<p><?php _e( 'Formuläret innehåller saknade eller felaktigt ifyllda fält. Titta igenom dina uppgifter och försök igen.', 'mes-form' ) ?></p>

					<ul class="story-form__errors">
						<?php foreach ( $result['errors'] as $error ) : ?>
						<li><?php echo $error ?></li>
						<?php endforeach; ?>
					</ul>
				</div>

			<?php endif; ?>

			<form id="mes-form" class="mes-form js-mes-form" method="post">

				<?php wp_nonce_field( 'mes-form', 'mes_form_nonce' ) ?>

				<div class="mes-form-section">

					<h2 class="mes-label-header"><label for="mes_email"><?php _e( 'Din e-postadress', 'mes-form' ) ?></label></h2>
					<input id="mes_email" name="mes_email" type="email" placeholder="<?php _e( 'E-postadress', 'mes-form' ) ?>" value="<?php echo !empty( $_POST['mes_email'] ) ? esc_attr( $_POST['mes_email'] ) : '' ?>">

				</div>

				<div class="mes-form-section">

					<h2 class="mes-label-header"><label for="mes_header"><?php _e( 'Berättelsens rubrik', 'mes-form' ) ?></label></h2>
					<input id="mes_header" name="mes_header" type="text" placeholder="<?php _e( 'En gång…', 'mes-form' ) ?>" value="<?php echo !empty( $_POST['mes_header'] ) ? esc_attr( $_POST['mes_header'] ) : '' ?>">

				</div>

				<div class="mes-form-section">

					<h2 class="mes-label-header"><label for="mes_story"><?php _e( 'Din berättelse', 'mes-form' ) ?></label></h2>
					<textarea id="mes_story" name="mes_story"><?php echo !empty( $_POST['mes_story'] ) ? esc_textarea( $_POST['mes_story'] ) : '' ?></textarea>

				</div>

				<div class="mes-form-section">

				<h2 class="mes-label-header"><label for="mes_summary"><?php _e( 'Berättelsen i korthet', 'mes-form' ) ?></label></h2>
				<textarea id="mes_summary" name="mes_summary"><?php echo !empty( $_POST['mes_summary'] ) ? esc_textarea( $_POST['mes_summary'] ) : '' ?></textarea>

				</div>

				<div class="mes-form-section">

					<h2 class="mes-label-header mes-label-header--collection"><?php _e( 'Var hände detta?', 'mes-form' ) ?></h2>

					<div class="mes-explanation js-mes-form-desc">
						<?php _e( 'Bacon ipsum dolor amet cupim ball tip brisket tenderloin capicola. Brisket doner spare ribs bacon. Ball tip fatback prosciutto burgdoggen corned beef.', 'mes-form' ) ?>
					</div>

					<div class="mes-collection">

						<?php $locations = isset( $_POST['mes_location'] ) && is_array( $_POST['mes_location'] ) ? $_POST['mes_location'] : array() ?>

						<label for="mes_location_1">
							<input id="mes_location_1" name="mes_location[]" type="checkbox" value="school" <?php checked( in_array( 'school', $locations ) ) ?>>
							<span><?php _e( 'Skola', 'mes-form' ) ?></span>
						</label>

						<label for="mes_location_2">
							<input id="mes_location_2" name="mes_location[]" type="checkbox" value="organisation" <?php checked( in_array( 'organisation', $locations ) ) ?>>
							<span><?php _e( 'Förening', 'mes-form' ) ?></span>
						</label>

						<label for="mes_location_3">
							<input id="mes_location_3" name="mes_location[]" type="checkbox" value="home" <?php checked( in_array( 'home', $locations ) ) ?>>
							<span><?php _e( 'Hemma', 'mes-form' ) ?></span>
						</label>

						<label for="mes_location_4">
							<input id="mes_location_4" name="mes_location[]" type="checkbox" value="friends" <?php checked( in_array( 'friends', $locations ) ) ?>>
							<span><?php _e( 'I kompisgänget', 'mes-form' ) ?></span>
						</label>

						<label for="mes_location_5">
							<input id="mes_location_5" name="mes_location[]" type="checkbox" value="internet" <?php checked( in_array( 'internet', $locations ) ) ?>>
							<span><?php _e( 'Internet', 'mes-form' ) ?></span>
						</label>

						<label for="mes_location_6">
							<input id="mes_location_6" name="mes_location[]" type="checkbox" value="youth_center" <?php checked( in_array( 'youth_center', $locations ) ) ?>>
							<span><?php _e( 'Fritidsgård', 'mes-form' ) ?></span>
						</label>

						<label for="mes_location_7">
							<input id="mes_location_7" name="mes_location[]" type="checkbox" value="work" <?php checked( in_array( 'work', $locations ) ) ?>>
							<span><?php _e( 'Jobbet', 'mes-form' ) ?></span>
						</label>

						<label for="mes_location_8">
							<input id="mes_location_8" name="mes_location[]" type="checkbox" value="outdoors" <?php checked( in_array( 'outdoors', $locations ) ) ?>>
							<span><?php _e( 'Utomhus', 'mes-form' ) ?></span>
						</label>

						<label for="mes_location_9">
							<input id="mes_location_9" name="mes_location[]" type="checkbox" value="sports_ground" <?php checked( in_array( 'sports_ground', $locations ) ) ?>>
							<span><?php _e( 'Idrottsplats', 'mes-form' ) ?></span>
						</label>

						<label for="mes_location_10">
							<input id="mes_location_10" name="mes_location[]" type="checkbox" value="changing_room" <?php checked( in_array( 'changing_room', $locations ) ) ?>>
							<span><?php _e( 'Omklädningsrum', 'mes-form' ) ?></span>
						</label>

						<label for="mes_location_11">
							<input id="mes_location_11" name="mes_location[]" type="checkbox" value="public_transport" <?php checked( in_array( 'public_transport', $locations ) ) ?>>
							<span><?php _e( 'Kollektivtrafiken', 'mes-form' ) ?></span>
						</label>

						<label for="mes_location_12">
							<input id="mes_location_12" name="mes_location_custom" type="text" value="<?php echo !empty( $_POST['mes_location_custom'] ) ? esc_attr( $_POST['mes_location_custom'] ) : '' ?>">
							<span><?php _e( 'Annat', 'mes-form' ) ?></span>
						</label>

					</div>

				</div>

				<div class="mes-form-section">

					<h2 class="mes-label-header mes-label-header--collection"><?php _e( 'Vad handlar berättelsen om?', 'mes-form' ) ?></h2>

					<div class="mes-explanation js-mes-form-desc">
						<?php _e( 'Bacon ipsum dolor amet pork chop beef pancetta rump, frankfurter flank capicola pork loin shank kielbasa chuck. Pork belly corned beef sirloin shank turkey, flank andouille kevin biltong venison brisket.', 'mes-form' ) ?>
					</div>

					<div class="mes-collection">

						<?php $subjects = isset( $_POST['mes_subject'] ) && is_array( $_POST['mes_subject'] ) ? $_POST['mes_subject'] : array() ?>

						<label for="mes_subject_1">
							<input id="mes_subject_1" name="mes_subject[]" type="checkbox" value="sex" <?php checked( in_array( 'sex', $subjects ) ) ?>>
							<span><?php _e( 'Kön', 'mes-form' ) ?></span>
						</label>

						<label for="mes_subject_2">
							<input id="mes_subject_2" name="mes_subject[]" type="checkbox" value="gender" <?php checked( in_array( 'gender', $subjects ) ) ?>>
							<span><?php _e( 'Könsuttryck', 'mes-form' ) ?></span>
						</label>

						<label for="mes_subject_3">
							<input id="mes_subject_3" name="mes_subject[]" type="checkbox" value="sexuality" <?php checked( in_array( 'sexuality', $subjects ) ) ?>>
							<span><?php _e( 'Sexualitet', 'mes-form' ) ?></span>
						</label>

						<label for="mes_subject_4">
							<input id="mes_subject_4" name="mes_subject[]" type="checkbox" value="function" <?php checked( in_array( 'function', $subjects ) ) ?>>
							<span><?php _e( 'Funktion', 'mes-form' ) ?></span>
						</label>

						<label for="mes_subject_5">
							<input id="mes_subject_5" name="mes_subject[]" type="checkbox" value="age" <?php checked( in_array( 'age', $subjects ) ) ?>>
							<span><?php _e( 'Ålder', 'mes-form' ) ?></span>
						</label>

						<label for="mes_subject_6">
							<input id="mes_subject_6" name="mes_subject[]" type="checkbox" value="faith" <?php checked( in_array( 'faith', $subjects ) ) ?>>
							<span><?php _e( 'Tro/religion', 'mes-form' ) ?></span>
						</label>

						<label for="mes_subject_7">
							<input id="mes_subject_7" name="mes_subject[]" type="checkbox" value="ethnicity" <?php checked( in_array( 'ethnicity', $subjects ) ) ?>>
							<span><?php _e( 'Etnicitet', 'mes-form' ) ?></span>
						</label>

						<label for="mes_subject_8">
							<input id="mes_subject_8" name="mes_subject_custom" type="text" value="<?php echo !empty( $_POST['mes_subject_custom'] ) ? esc_attr( $_POST['mes_subject_custom'] ) : '' ?>">
							<span><?php _e( 'Annat', 'mes-form' ) ?></span>
						</label>

					</div>

				</div>

				<div class="mes-form-section">

					<h2 class="mes-label-header mes-label-header--collection"><?php _e( 'Hur upplever du din berättelse?', 'mes-form' ) ?></h2>

					<div class="mes-explanation js-mes-form-desc">
						<?php _e( 'Handlar berättelsen om något bra som gjorts kryssar du i positiv. Handlar din berättelse om något jobbigt du har upplevt kryssar du för negativ. Din berättelse kan också vara både positiv och negativ.', 'mes-form' ) ?>
					</div>

					<div class="mes-collection">

					<?php $interpretations = isset( $_POST['mes_interpret'] ) && is_array( $_POST['mes_interpret'] ) ? $_POST['mes_interpret'] : array() ?>

					<label for="mes_interpret_1">
						<input id="mes_interpret_1" name="mes_interpret[]" type="checkbox" value="positive" <?php checked( in_array( 'positive', $interpretations ) ) ?>>
						<span><?php _e( 'Positiv', 'mes-form' ) ?></span>
					</label>

					<label for="mes_interpret_2">
						<input id="mes_interpret_2" name="mes_interpret[]" type="checkbox" value="negative" <?php checked( in_array( 'negative', $interpretations ) ) ?>>
						<span><?php _e( 'Negativ', 'mes-form' ) ?></span>
					</label>

					</div>

				</div>

				<div class="mes-form-section mes-form-section--disclaimer">

					<p><?php _e( 'Genom att skicka in din berättelse är du helt anonym. Däremot behöver vi mplector contrpicus colluceo servitium ob lis adparet, beto permutio pruna. Obliviosus cuci, fimus anthropocentrism cantuaria quintum sandyx age diurnalismus bracchialis inaniloquus jubo lixa. Novello simulus inexpugnabilis superlino glisco avis', 'mes-form' ) ?></p>

					<label for="mes_approve">
						<input id="mes_approve" class="js-mes-form-assent" name="mes_approve" type="checkbox" value="1" <?php checked( !empty( $_POST['mes_approve'] ) ) ?>>
						<span><?php _e( 'Godkänn', 'mes-form' ) ?></span>
					</label>

				</div>
				
				<div class="mes-form-section mes-form-section--submit">
					<button id="mes_submit" class="mes-submit js-mes-form-submit" type="submit"><span><?php _e( 'Skicka in min berättelse', 'mes-form' ) ?></span></button>
				</div>

				<div class="mes-validation-warning js-mes-form-submission-errors">
					<p><?php _e( 'Formuläret innehåller saknade eller felaktigt ifyllda fält. Titta igenom dina uppgifter och försök igen.', 'mes-form' ) ?></p>
				</div>

			</form>

		<?php endif; ?>

		<?php return ob_get_clean();
	}


	// Register front scripts & styles
	public function scripts_and_styles() {
		wp_enqueue_script( 'mes-form-js', plugins_url( 'assets/js/mes-form.min.js', __FILE__ ), array( 'jquery' ), MES_FORM_VER, true );

		wp_localize_script( 'mes-form-js', 'mesGlobal', array(
			'pluginPath'        => plugins_url( '', __FILE__ ),
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'completetionUrl'   => get_permalink() . '?completed=true',
			'btnDefaultString'  => __( 'Skicka din berättelse', 'mes-form' ),
			'btnProcesString'   => __( 'Behandlar formulär...', 'mes-form' ),
			'showDescString'    => __( 'Mer information', 'mes-form' ),
			'hideDescString'    => __( 'Dölj', 'mes-form' ),
			'messages'          => $this->messages
		) );
	}


	public function handle_ajax_form_submission() {
		echo json_encode( $this->process_form() );
		exit;
	}


	public function handle_form_submission( $unused ) {
		$result = $this->process_form();
		return $result;
	}


	private function process_form() {

		$errors = array();
		$submission = array(
			'header'            => '',
			'story'             => '',
			'summary'           => '',
			'locations'         => array(),
			'locations_custom'  => array(),
			'subjects'          => array(),
			'subjects_custom'   => array(),
			'interpretation'    => array(),
			'email'             => ''
		);
		$success = false;
		$posted = isset( $_SERVER['REQUEST_METHOD'] ) && strtolower( $_SERVER['REQUEST_METHOD'] ) == 'post';

		if ( $posted && isset( $_POST['mes_form_nonce'] ) && wp_verify_nonce( $_POST['mes_form_nonce'], 'mes-form' ) ) {

			if ( ! empty( $_POST['mes_email'] ) ) {
				$submission['email'] = sanitize_email( $_POST['mes_email'] );

				if ( ! is_email( $submission['email'] ) ) {
					$errors['mes_email'] = $this->messages['mes_email']['email'];
				}

			} else {
				$errors['mes_email'] = $this->messages['mes_email']['required'];
			}


			if ( ! empty( $_POST['mes_header'] ) ) {
				$submission['header'] = sanitize_text_field( $_POST['mes_header'] );
			} else {
				$errors['mes_header'] =$this->messages['mes_header']['required'];
			}

			if ( ! empty( $_POST['mes_story'] ) ) {
				$submission['story'] = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST['mes_story'] ) ) );
			} else {
				$errors['mes_story'] = $this->messages['mes_story']['required'];
			}


			if ( ! empty( $_POST['mes_summary'] ) ) {
				$submission['summary'] = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST['mes_summary'] ) ) );
			} else {
				$errors['mes_summary'] = $this->messages['mes_summary']['required'];
			}


			if ( ! empty( $_POST['mes_location_custom'] ) ) {
				$submission['locations_custom'][] = sanitize_text_field( $_POST['mes_location_custom'] );
			}


			if ( isset( $_POST['mes_location'] ) && is_array( $_POST['mes_location'] ) ) {

				$default_locations = array(
					'school',
					'organisation',
					'home',
					'friends',
					'internet',
					'youth_center',
					'work',
					'outdoors',
					'sports_ground',
					'changing_room',
					'public_transport'
				);

				foreach ( $_POST['mes_location'] as $value) {
					if ( in_array( $value, $default_locations ) ) {
						$submission['locations'][] = sanitize_text_field( $value );
					} else {
						$submission['locations_custom'][] = sanitize_text_field( $value );
					}
				}
			} elseif ( empty( $submission['locations_custom'] ) ) {
				$errors['mes_location[]'] = $this->messages['mes_location']['required'];
			}


			if ( ! empty( $_POST['mes_subject_custom'] ) ) {
				$submission['subjects_custom'][] = sanitize_text_field( $_POST['mes_subject_custom'] );
			}


			if ( isset( $_POST['mes_subject'] ) && is_array( $_POST['mes_subject'] ) ) {

				$default_subjects = array(
					'sex',
					'gender',
					'sexuality',
					'function',
					'age',
					'faith',
					'ethnicity'
				);

				foreach ( $_POST['mes_subject'] as $value) {
					if ( in_array( $value, $default_subjects ) ) {
						$submission['subjects'][] = sanitize_text_field( $value );
					} else {
						$submission['subjects_custom'][] = sanitize_text_field( $value );
					}
				}
			} elseif ( empty( $submission['subjects_custom'] ) ) {
				$errors['mes_subject[]'] = $this->messages['mes_subject']['required'];
			}


			if ( isset( $_POST['mes_interpret'] ) && is_array( $_POST['mes_interpret'] ) ) {

				$default_interpretations = array(
					'positive',
					'negative'
				);

				foreach ( $_POST['mes_interpret'] as $value) {
					if ( in_array( $value, $default_interpretations ) ) {
						$submission['interpretation'][] = sanitize_text_field( $value );
					}
				}
			} else {
				$errors['mes_interpret[]'] = $this->messages['mes_interpret']['required'];
			}


			if ( empty( $_POST['mes_approve'] ) ) {
				$errors['mes_approve'] = $this->messages['mes_approve']['required'];
			}

		} else {
			$errors['mes_submit'] = __( 'Otillåten access. Ladda om sidan och försök igen.', 'mes-form' );
		}


		if ( empty( $errors ) ) {
			$success = $this->save_story( $submission );

			if ( $success !== true ) {
				$errors['mes_submit'] = __( 'Något gick fel och vi kunde inte spara ditt bidrag. Försök igen senare.', 'mes-form' );
			}
		}


		return array(
			'success' => $success,
			'errors'  => $errors
		);

	}


	private function save_story( $submission ) {
		// HANDLE DATA TO YOUR HEART'S CONTENT

		//file_put_contents( dirname( __FILE__ ) . '/test.txt', print_r( $submission, true ) ); // Output to temp file for testing

		// Return boolean depending on success of save
		return true;
	}
}


$MES_Form_Plugin = Plugin::get_instance();