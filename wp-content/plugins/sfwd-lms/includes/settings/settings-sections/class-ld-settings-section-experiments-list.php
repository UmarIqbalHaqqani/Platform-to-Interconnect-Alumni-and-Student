<?php
/**
 * LearnDash Settings Section Experiments List.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Settings\Sections
 */

use LearnDash\Core\App;
use LearnDash\Core\Modules\Experiments\Experiments;
use StellarWP\Learndash\lucatume\DI52\ContainerException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'LearnDash_Settings_Section' )
	&& ! class_exists( 'LearnDash_Settings_Section_Experiments_List' )
) {
	/**
	 * LearnDash Settings Section Experiments List.
	 *
	 * @since 4.13.0
	 */
	class LearnDash_Settings_Section_Experiments_List extends LearnDash_Settings_Section {
		/**
		 * Constructor.
		 *
		 * @since 4.13.0
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_experiments';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = $this->settings_page_id;

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = $this->settings_page_id;

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = $this->settings_page_id;

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Experiments', 'learndash' );

			parent::__construct();
		}

		/**
		 * Initializes the metabox settings fields.
		 *
		 * @since 4.13.0
		 *
		 * @throws ContainerException No entry was found for the given identifier.
		 *
		 * @return void
		 */
		public function load_settings_fields() {
			$experiments = App::get( Experiments::class );

			if ( ! $experiments instanceof Experiments ) {
				return;
			}

			foreach ( $experiments->get_experiments() as $experiment ) {
				$this->setting_option_fields[ $experiment->get_id() ] = [
					'id'               => $experiment->get_id(),
					'name'             => $experiment->get_id(),
					'label'            => $experiment->get_title(),
					'help_text'        => $experiment->get_description(),
					'display_callback' => [
						LearnDash_Settings_Fields::get_field_instance( 'checkbox-switch' ),
						'create_section_field',
					],
					'type'             => 'checkbox-switch',
					'value'            => $this->setting_option_values[ $experiment->get_id() ] ?? '',
					'options'          => [
						''   => '',
						'on' => '',
					],
					// Kind of a hack, so we can use this field to grab the url.
					'attrs'            => [
						'url' => $experiment->get_url(),
					],
				];
			}

			parent::load_settings_fields();
		}

		/**
		 * Shows the metaboxes for the section.
		 *
		 * @since 4.13.0
		 *
		 * @param string $page    Page shown.
		 * @param string $section Section shown.
		 *
		 * @throws ContainerException No entry was found for the given identifier.
		 *
		 * @return void
		 */
		public function show_settings_section_fields( $page, $section ) {
			?>
			<div class="sfwd sfwd_options">
				<p>
					<?php
					echo wp_kses(
						sprintf(
							/* Translators: %s: LearnDash Experiments Docs URL. */
							__(
								'Experiments are features that are in beta that we encourage you to try on on your course. You can provide our team feedback by clicking the "Give Feedback" button. Learn more about experiments: <a target="_blank" href="%1$s">%1$s</a>.',
								'learndash'
							),
							esc_url( 'https://www.learndash.com/support/docs/core/experiments' )
						),
						[
							'a' => [
								'href'   => [],
								'target' => [],
							],
						]
					);
					?>
				</p>

				<table class="learndash-settings-table learndash-settings-table-experiments widefat striped" cellspacing="0">
					<thead>
						<tr>
							<th class="col-name-experiment-enabled"></th>
							<th class="col-name-experiment-title">
								<?php esc_html_e( 'Experiment', 'learndash' ); ?>
							</th>
							<th class="col-name-experiment-description">
								<?php esc_html_e( 'Short Description', 'learndash' ); ?>
							</th>
							<th class="col-name-url"></th>
						<tr>
					</thead>
					<tbody>
					<?php if ( empty( $this->setting_option_fields ) ) : ?>
						<tr>
							<td colspan="4">
								<?php esc_html_e( 'No experiments found.', 'learndash' ); ?>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $this->setting_option_fields as $field ) : ?>
							<tr>
								<td class="col-name-experiment-enabled">
									<?php call_user_func( $field['display_callback'], $field ); ?>
								</td>

								<td class="col-name-experiment-title col-valign-middle">
									<?php echo esc_html( $field['label'] ); ?>
								</td>

								<td class="col-name-experiment-description col-valign-middle">
									<?php echo esc_html( $field['help_text'] ); ?>
								</td>

								<td class="col-name-manage col-valign-middle">
									<?php if ( ! empty( $field['attrs'] ) && ! empty( $field['attrs']['url'] ) ) : ?>
										<a
											class="button alignright"
											href="<?php echo esc_url( $field['attrs']['url'] ); ?>"
											target="_blank"
										>
											<?php esc_html_e( 'Give Feedback', 'learndash' ); ?>
										</a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
					</tbody>
				</table>
			</div>
			<?php
		}
	}
}
