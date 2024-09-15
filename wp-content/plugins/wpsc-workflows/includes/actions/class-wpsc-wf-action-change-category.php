<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_Action_Change_Category' ) ) :

	final class WPSC_WF_Action_Change_Category {

		/**
		 * Slug for this action
		 *
		 * @var string
		 */
		public static $slug = 'change-category';

		/**
		 * Print input field
		 *
		 * @param array $action - pre-defined json value.
		 * @return void
		 */
		public static function print( $action = array() ) {

			$unique_id = uniqid( 'wpsc_' );
			?>
			<div class="wf-action-item" data-slug="<?php echo esc_attr( self::$slug ); ?>">
				<div class="wf-action-header">
					<span class="wf-action-title"><?php echo esc_attr( WPSC_WF_Actions::$actions[ self::$slug ]['title'] ); ?></span>
					<span class="wf-remove-action" onclick="wpsc_wf_remove_action(this)"><?php WPSC_Icons::get( 'times-circle' ); ?></span>
				</div>
				<div class="wf-action-body">
					<?php $value = isset( $action['category'] ) ? $action['category'] : ''; ?>
					<div class="wpsc-input-group">
						<div class="label-container">
							<label for="">
								<?php echo esc_attr( wpsc__( 'Category', 'wpsc-workflows' ) ); ?>
								<span class="required-char">*</span>
							</label>
						</div>
						<select name="actions[<?php echo esc_attr( self::$slug ); ?>][category]">
							<?php
							$categories = WPSC_Category::find( array( 'items_per_page' => 0 ) )['results'];
							foreach ( $categories as $category ) {
								?>
								<option <?php selected( $category->id, $value, true ); ?> value="<?php echo intval( $category->id ); ?>"><?php echo esc_attr( $category->name ); ?></option>
								<?php
							}
							?>
						</select>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Sanitize action input data to store in db
		 *
		 * @param array $action - actioin input array of this type.
		 * @return array
		 */
		public static function sanitize_action( $action ) {

			$category = isset( $action['category'] ) ? intval( $action['category'] ) : 0;
			if ( ! $category ) {
				wp_send_json_error( esc_attr__( 'Category not set!', 'wpsc-workflows' ), 400 );
			}

			return array(
				'category' => $category,
			);
		}

		/**
		 * Execute the action of this type
		 *
		 * @param array       $action - action details.
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @param array       $workflow - workflow array.
		 * @return void
		 */
		public static function execute( $action, $ticket, $workflow ) {

			$categories = array_map(
				fn( $category ) => $category->id,
				WPSC_Category::find( array( 'items_per_page' => 0 ) )['results']
			);

			if ( ! in_array( $action['category'], $categories ) || $ticket->category->id == $action['category'] ) {
				return;
			}

			$customer_id = $workflow['type'] == 'manual' ? WPSC_Current_User::$current_user->customer->id : 0;
			WPSC_Individual_Ticket::change_category( $ticket->category->id, $action['category'], $customer_id );
		}
	}

endif;
