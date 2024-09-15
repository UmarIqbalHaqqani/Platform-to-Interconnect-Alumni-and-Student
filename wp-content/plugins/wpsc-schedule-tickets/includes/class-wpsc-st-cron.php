<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_ST_Cron' ) ) :

	final class WPSC_ST_Cron {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			add_action( 'wpsc_cron_daily', array( __CLASS__, 'schedule' ), 11 );
		}

		/**
		 * Schedule tickets for today
		 *
		 * @return void
		 */
		public static function schedule() {

			$rules = get_option( 'wpsc-st-rules', array() );
			$tz    = wp_timezone();
			$today = new DateTime( 'now', $tz );
			$today->setTime( 0, 0, 0 );

			foreach ( $rules as $rule ) :

				if ( ! self::is_valid_recurrece( $rule, $today, $tz ) ) {
					continue;
				}

				switch ( $rule['recurrence-period'] ) {

					case 'daily':
						self::schedule_daily_ticket( $rule, $today, $tz );
						break;

					case 'weekly':
						self::schedule_weekly_ticket( $rule, $today, $tz );
						break;

					case 'monthly':
						self::schedule_monthly_ticket( $rule, $today, $tz );
						break;

					case 'yearly':
						self::schedule_yearly_ticket( $rule, $today, $tz );
						break;
				}

			endforeach;
		}

		/**
		 * Check whether recurrence period is valid or not
		 *
		 * @param array        $rule - rule.
		 * @param DateTime     $today - date today.
		 * @param DateTimeZone $tz - timezone.
		 * @return boolean
		 */
		private static function is_valid_recurrece( $rule, $today, $tz ) {

			// check starts on.
			$starts_on = new DateTime( $rule['starts-on'] . ' 00:00:00', $tz );
			if ( $today < $starts_on ) {
				return false;
			}

			// check ends on.
			if ( $rule['ends-on'] == 'ends-after-times' ) {

				return $rule['ticket-count'] < $rule['ends-after-times'] ? true : false;

			} elseif ( $rule['ends-on'] == 'end-date' ) {

				$end_date = new DateTime( $rule['end-date'] . ' 00:00:00', $tz );
				return $today <= $end_date ? true : false;

			} else { // no-end-date.

				return true;
			}
		}

		/**
		 * Schedule daily recurring tickets
		 *
		 * @param array        $rule - rule.
		 * @param DateTime     $today - date today.
		 * @param DateTimeZone $tz - timezone.
		 * @return void
		 */
		private static function schedule_daily_ticket( $rule, $today, $tz ) {

			$last_scheduled = isset( $rule['last-scheduled'] ) ? new DateTime( $rule['last-scheduled'] . ' 00:00:00', $tz ) : '';

			if ( $rule['daily-recurrence-type'] == 'daily-every-day' ) {

				// create ticket if not already scheduled.
				if ( ! is_object( $last_scheduled ) ) {
					self::create_new_ticket( $rule );
					return;
				}

				// check daily x days.
				$expected_last_scheduled = ( clone $today )->sub( new DateInterval( 'P' . $rule['daily-x-days'] . 'D' ) );
				if ( $last_scheduled <= $expected_last_scheduled ) {
					self::create_new_ticket( $rule );
				}
			} else { // daily-work-day.

				$wh_today = WPSC_Working_Hour::get_working_hrs_by_date( $today );
				if ( $wh_today !== false ) {

					// create ticket if not already scheduled.
					if ( ! is_object( $last_scheduled ) ) {
						self::create_new_ticket( $rule );
						return;
					}

					// calculate expected last scheduled.
					$x_days                  = 0;
					$expected_last_scheduled = clone $today;
					do {
						$expected_last_scheduled->sub( new DateInterval( 'P1D' ) );
						$expected_wh = WPSC_Working_Hour::get_working_hrs_by_date( $expected_last_scheduled );
						if ( $expected_wh !== false ) {
							$x_days++;
						}
					} while ( $x_days < $rule['daily-x-work-days'] );

					// create ticket.
					if ( $last_scheduled <= $expected_last_scheduled ) {
						self::create_new_ticket( $rule );
					}
				}
			}
		}

		/**
		 * Schedule weekly recurring tickets
		 *
		 * @param array        $rule - rule.
		 * @param DateTime     $today - date today.
		 * @param DateTimeZone $tz - timezone.
		 * @return void
		 */
		private static function schedule_weekly_ticket( $rule, $today, $tz ) {

			// return if today is not checked.
			if ( ! in_array( intval( $today->format( 'N' ) ), $rule['weekly-days'] ) ) {
				return;
			}

			// last scheduled.
			$last_scheduled = isset( $rule['last-scheduled'] ) ? new DateTime( $rule['last-scheduled'] . ' 00:00:00', $tz ) : '';

			// create ticket if not already scheduled.
			if ( ! is_object( $last_scheduled ) ) {
				self::create_new_ticket( $rule );
				return;
			}

			// calculate week difference.
			$diff = self::date_diff_in_weeks( $last_scheduled, $today );
			if ( $diff >= $rule['weekly-x-weeks'] ) {
				self::create_new_ticket( $rule );
			}
		}

		/**
		 * Schedule monthly recurring tickets
		 *
		 * @param array        $rule - rule.
		 * @param DateTime     $today - date today.
		 * @param DateTimeZone $tz - timezone.
		 * @return void
		 */
		private static function schedule_monthly_ticket( $rule, $today, $tz ) {

			// last scheduled.
			$last_scheduled = isset( $rule['last-scheduled'] ) ? new DateTime( $rule['last-scheduled'] . ' 00:00:00', $tz ) : '';

			if ( $rule['monthly-recurrence-type'] == 'monthly-day-number' ) {

				// return if day not matched.
				$day = intval( $today->format( 'd' ) );
				if ( $rule['monthly-day-number-day'] != $day ) {
					return;
				}

				// create ticket if not already scheduled.
				if ( ! is_object( $last_scheduled ) ) {
					self::create_new_ticket( $rule );
					return;
				}

				// create ticket if x-months match.
				$diff = $last_scheduled->diff( $today );
				if ( $diff->m >= $rule['monthly-day-number-x-months'] ) {
					self::create_new_ticket( $rule );
				}
			} else {

				// return if day not matched.
				$day = intval( $today->format( 'N' ) );
				if ( $day != $rule['monthly-week-number-day'] ) {
					return;
				}

				// return if weekday occurence not matches.
				if ( self::get_weekday_occurence_of_month( $today ) != $rule['monthly-week-number-occurrence'] ) {
					return;
				}

				// create ticket if not already scheduled.
				if ( ! is_object( $last_scheduled ) ) {
					self::create_new_ticket( $rule );
					return;
				}

				// create ticket if x-months match.
				$last_scheduled->sub( new DateInterval( 'P' . ( intval( $last_scheduled->format( 'd' ) ) - 1 ) . 'D' ) );
				$today_clone = ( clone $today )->sub( new DateInterval( 'P' . ( intval( $today->format( 'd' ) ) - 1 ) . 'D' ) );
				$diff        = $last_scheduled->diff( $today_clone );
				if ( $diff->m >= $rule['monthly-week-number-x-months'] ) {
					self::create_new_ticket( $rule );
				}
			}
		}

		/**
		 * Schedule yearly recurring tickets
		 *
		 * @param array        $rule - rule.
		 * @param DateTime     $today - date today.
		 * @param DateTimeZone $tz - timezone.
		 * @return void
		 */
		private static function schedule_yearly_ticket( $rule, $today, $tz ) {

			// last scheduled.
			$last_scheduled = isset( $rule['last-scheduled'] ) ? new DateTime( $rule['last-scheduled'] . ' 00:00:00', $tz ) : '';

			if ( $rule['monthly-recurrence-type'] == 'yearly-day-number' ) {

				// return if month not matched.
				$month = intval( $today->format( 'm' ) );
				if ( $rule['yearly-day-number-month'] != $month ) {
					return;
				}

				// return if day not matched.
				$day = intval( $today->format( 'd' ) );
				if ( $rule['yearly-day-number-day'] != $day ) {
					return;
				}

				// create ticket if not already scheduled.
				if ( ! is_object( $last_scheduled ) ) {
					self::create_new_ticket( $rule );
					return;
				}

				// create ticket if x-years match.
				$diff = $last_scheduled->diff( $today );
				if ( $diff->y >= $rule['yearly-day-number-x-years'] ) {
					self::create_new_ticket( $rule );
				}
			} else {

				// return if month not matched.
				$month = intval( $today->format( 'm' ) );
				if ( $rule['yearly-week-number-month'] != $month ) {
					return;
				}

				// return if day not matched.
				$day = intval( $today->format( 'N' ) );
				if ( $day != $rule['yearly-week-number-day'] ) {
					return;
				}

				// return if weekday occurence not matches.
				if ( self::get_weekday_occurence_of_month( $today ) != $rule['yearly-week-number-occurrence'] ) {
					return;
				}

				// create ticket if not already scheduled.
				if ( ! is_object( $last_scheduled ) ) {
					self::create_new_ticket( $rule );
					return;
				}

				// create ticket if x-years match.
				$last_scheduled->sub( new DateInterval( 'P' . ( intval( $last_scheduled->format( 'd' ) ) - 1 ) . 'D' ) );
				$today_clone = ( clone $today )->sub( new DateInterval( 'P' . ( intval( $today->format( 'd' ) ) - 1 ) . 'D' ) );
				$diff        = $last_scheduled->diff( $today_clone );
				if ( $diff->y >= $rule['yearly-week-number-x-years'] ) {
					self::create_new_ticket( $rule );
				}
			}
		}

		/**
		 * Create new ticket from rule
		 *
		 * @param array $rule - rule.
		 * @return void
		 */
		private static function create_new_ticket( $rule ) {

			if (
				! isset( $rule['customer'] ) ||
				! isset( $rule['subject'] ) ||
				! isset( $rule['description'] )
			) {
				return;
			}

			$customer = new WPSC_Customer( $rule['customer'] );
			$now      = ( new DateTime() )->format( 'Y-m-d H:i:s' );

			$data = array(
				'customer'     => $customer->id,
				'subject'      => $rule['subject'],
				'user_type'    => $customer->user->ID ? 'registered' : 'guest',
				'source'       => 'st',
				'date_created' => $now,
				'date_updated' => $now,
			);

			// custom fields.
			foreach ( WPSC_Custom_Field::$custom_fields as $cf ) {

				if (
					! class_exists( $cf->type ) ||
					! in_array( $cf->field, array( 'ticket', 'agentonly' ) ) ||
					in_array( $cf->type::$slug, WPSC_ST_Settings_CRUD::$ignore_cft )
				) {
					continue;
				}

				$data[ $cf->slug ] = isset( $rule[ $cf->slug ] ) && $rule[ $cf->slug ] ? $rule[ $cf->slug ] : $cf->type::get_default_value( $cf );
			}

			$data['assigned_agent'] = '';

			// create new ticket.
			$ticket = WPSC_Ticket::insert( $data );

			// Create report thread.
			WPSC_Thread::insert(
				array(
					'ticket'   => $ticket->id,
					'customer' => $ticket->customer->id,
					'type'     => 'report',
					'body'     => $rule['description'],
					'source'   => 'st',
				)
			);

			do_action( 'wpsc_create_new_ticket', $ticket );
		}

		/**
		 * Return week difference in two days
		 *
		 * @param Date $date1 - date1.
		 * @param Date $date2 - date2.
		 * @return int
		 */
		public static function date_diff_in_weeks( $date1, $date2 ) {

			// calculate monday of date1.
			$first        = ( clone $date1 )->setTime( 0, 0, 0 );
			$day_of__week = intval( $first->format( 'N' ) );
			$first->sub( new DateInterval( 'P' . ( $day_of__week - 1 ) . 'D' ) );

			// calculate monday of date2.
			$second       = ( clone $date2 )->setTime( 0, 0, 0 );
			$day_of__week = intval( $second->format( 'N' ) );
			$second->sub( new DateInterval( 'P' . ( $day_of__week - 1 ) . 'D' ) );

			return floor( $first->diff( $second )->days / 7 );
		}

		/**
		 * Return occurence of weekday in month of given date. e.g. first monady, last friday, etc.
		 *
		 * @param DateTime $today - clone today date.
		 * @return integer
		 */
		public static function get_weekday_occurence_of_month( $today ) {

			$dt        = clone $today;
			$month     = intval( $dt->format( 'm' ) );
			$occurence = 0;
			do {
				$dt->sub( new DateInterval( 'P7D' ) );
				$occurence++;
			} while ( intval( $dt->format( 'm' ) ) == $month );
			return $occurence;
		}
	}
endif;

WPSC_ST_Cron::init();
