<?php
/**
 * Virtual Instructor experiment.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor;

use LearnDash\Core\Modules\Experiments\Experiment as Experiment_Base;

/**
 * Virtual Instructor experiment class.
 *
 * @since 4.13.0
 */
class Experiment extends Experiment_Base {
	/**
	 * Constructor.
	 *
	 * @since 4.13.0
	 */
	public function __construct() {
		$this->id          = 'virtual_instructor';
		$this->title       = __( 'Virtual Instructor', 'learndash' );
		$this->description = __( 'Virtual instructors to interact with your students and assist with their learning.', 'learndash' );
		$this->url         = 'https://forms.gle/MYbATTwntU3kZeabA';
	}

	/**
	 * Sets up the hooks.
	 *
	 * @since 4.13.0
	 */
	protected function setup_hooks(): void {
		learndash_register_provider( Provider::class );
	}
}
