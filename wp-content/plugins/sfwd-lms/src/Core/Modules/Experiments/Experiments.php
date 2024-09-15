<?php
/**
 * Experiments.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Experiments;

/**
 * Experiments.
 *
 * @since 4.13.0
 */
class Experiments {
	/**
	 * Contains the list of enabled experiment instances.
	 *
	 * @since 4.13.0
	 *
	 * @var Experiment[]
	 */
	protected $experiments = [];

	/**
	 * Initializes the module.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	public function init(): void {
		$this->load_experiments();

		/**
		 * Fires before the experiments are initialized.
		 *
		 * @since 4.13.0
		 *
		 * @param Experiment[] $experiments List of experiment instances.
		 */
		do_action( 'learndash_experiments_init_before', $this->experiments );

		foreach ( $this->experiments as $experiment ) {
			$experiment->init();
		}

		/**
		 * Fires after the experiments are initialized.
		 *
		 * @since 4.13.0
		 *
		 * @param Experiment[] $experiments List of experiment instances.
		 */
		do_action( 'learndash_experiments_init_after', $this->experiments );
	}

	/**
	 * Gets the list of experiments.
	 *
	 * @since 4.13.0
	 *
	 * @return Experiment[]
	 */
	public function get_experiments(): array {
		return $this->experiments;
	}

	/**
	 * Loads the list of experiments.
	 *
	 * @since 4.13.0
	 *
	 * @return void
	 */
	protected function load_experiments(): void {
		/**
		 * Filters the list of experiments.
		 *
		 * @since 4.13.0
		 *
		 * @param Experiment[] $experiments List of experiment instances.
		 *
		 * @return Experiment[] List of experiment instances.
		 */
		$experiments = apply_filters( 'learndash_experiments', [] );

		$experiments = array_filter(
			$experiments,
			function ( $experiment ): bool {
				return $experiment instanceof Experiment;
			}
		);

		$this->experiments = array_values( $experiments );
	}
}
