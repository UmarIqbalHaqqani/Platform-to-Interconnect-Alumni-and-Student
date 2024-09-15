<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly!
}

if ( ! class_exists( 'WPSC_WF_Process_Triggers' ) ) :

	final class WPSC_WF_Process_Triggers {

		/**
		 * Initialize this class
		 *
		 * @return void
		 */
		public static function init() {

			add_action( 'wpsc_create_new_ticket', array( __CLASS__, 'create_new_ticket' ), 999 );
			add_action( 'wpsc_post_reply', array( __CLASS__, 'reply_ticket' ), 999 );
			add_action( 'wpsc_submit_note', array( __CLASS__, 'submit_note' ), 999 );
			add_action( 'wpsc_change_ticket_subject', array( __CLASS__, 'change_subject' ), 999, 4 );
			add_action( 'wpsc_change_ticket_status', array( __CLASS__, 'change_status' ), 999, 4 );
			add_action( 'wpsc_change_ticket_category', array( __CLASS__, 'change_category' ), 999, 4 );
			add_action( 'wpsc_change_ticket_priority', array( __CLASS__, 'change_priority' ), 999, 4 );
			add_action( 'wpsc_change_assignee', array( __CLASS__, 'change_assignee' ), 999, 4 );
			add_action( 'wpsc_change_ticket_fields', array( __CLASS__, 'change_ticket_fields' ), 999, 2 );
			add_action( 'wpsc_change_agentonly_fields', array( __CLASS__, 'change_agentonly_fields' ), 999, 2 );
		}

		/**
		 * Process create new ticket workflow actions
		 *
		 * @param WPSC_Ticket $ticket - ticket object.
		 * @return void
		 */
		public static function create_new_ticket( $ticket ) {

			self::execute_trigger( 'create-ticket', $ticket );
		}

		/**
		 * Process reply ticket workflow
		 *
		 * @param WPSC_Thread $thread - thread.
		 * @return void
		 */
		public static function reply_ticket( $thread ) {

			$ticket = $thread->ticket;
			self::execute_trigger( 'reply-ticket', $ticket );
		}

		/**
		 * Process private note ticket workflow
		 *
		 * @param WPSC_Thread $thread - thread.
		 * @return void
		 */
		public static function submit_note( $thread ) {

			$ticket = $thread->ticket;
			self::execute_trigger( 'submit-note', $ticket );
		}

		/**
		 * Process workflows for change subject
		 *
		 * @param WPSC_Ticket $ticket - ticket info..
		 * @param string      $prev - previous value.
		 * @param string      $new - new value id.
		 * @param integer     $customer_id - customer model id.
		 * @return void
		 */
		public static function change_subject( $ticket, $prev, $new, $customer_id ) {

			self::execute_trigger( 'change-ticket-subject', $ticket );
		}

		/**
		 * Process workflows for change status
		 *
		 * @param WPSC_Ticket $ticket - ticket info.
		 * @param integer     $prev - status model id.
		 * @param integer     $new - status model id.
		 * @param integer     $customer_id - customer model id.
		 * @return void
		 */
		public static function change_status( $ticket, $prev, $new, $customer_id ) {

			self::execute_trigger( 'change-ticket-status', $ticket );
		}

		/**
		 * Process workflows for change category trigger event
		 *
		 * @param WPSC_Ticket $ticket -ticket info.
		 * @param integer     $prev - category model id.
		 * @param integer     $new - category model id.
		 * @param integer     $customer_id - customer model id.
		 * @return void
		 */
		public static function change_category( $ticket, $prev, $new, $customer_id ) {

			self::execute_trigger( 'change-ticket-category', $ticket );
		}

		/**
		 * Process workflows for change priority trigger event
		 *
		 * @param WPSC_Ticket $ticket - ticket info..
		 * @param integer     $prev - priority model id.
		 * @param integer     $new - priority model id.
		 * @param integer     $customer_id - customer model id.
		 * @return void
		 */
		public static function change_priority( $ticket, $prev, $new, $customer_id ) {

			self::execute_trigger( 'change-ticket-priority', $ticket );
		}

		/**
		 * Process workflows for change assignee trigger event
		 *
		 * @param WPSC_Ticket $ticket -ticket info.
		 * @param array       $prev - array of agent models.
		 * @param array       $new - array of agent models.
		 * @param integer     $customer_id - ID of customer model.
		 * @return void
		 */
		public static function change_assignee( $ticket, $prev, $new, $customer_id ) {

			self::execute_trigger( 'change-assignee', $ticket );
		}

		/**
		 * Process workflows for change ticket fields event
		 *
		 * @param WPSC_Ticket $prev - ticket object before changes.
		 * @param WPSC_Ticket $ticket - ticket object after changes.
		 * @return void
		 */
		public static function change_ticket_fields( $prev, $ticket ) {

			self::execute_trigger( 'change-ticket-fields', $ticket );
		}

		/**
		 * Process workflows for change agentonly fields event
		 *
		 * @param WPSC_Ticket $prev - ticket object before changes.
		 * @param WPSC_Ticket $ticket - ticket object after changes.
		 * @return void
		 */
		public static function change_agentonly_fields( $prev, $ticket ) {

			self::execute_trigger( 'change-agentonly-fields', $ticket );
		}

		/**
		 * Execute the workflows that matches the trigger supplied
		 *
		 * @param string      $trigger - current trigger.
		 * @param WPSC_Ticket $ticket - current trigger ticket object.
		 * @return void
		 */
		public static function execute_trigger( $trigger, $ticket ) {

			WPSC_Individual_Ticket::$ticket = $ticket;
			WPSC_Individual_Ticket::$reply_profile = 'agent';

			$workflows = get_option( 'wpsc-wf-automatic', array() );
			foreach ( $workflows as $workflow ) {

				if (
					! $workflow['status'] ||
					$workflow['trigger'] != $trigger ||
					! self::is_valid_current_user( $workflow ) ||
					! WPSC_Ticket_Conditions::is_valid( $workflow['conditions'], $ticket )
				) {
					continue;
				}

				WPSC_WF_Actions::execute( $workflow['actions'], $ticket, $workflow );
			}
		}

		/**
		 * Check whether or not current user is allowed to trigger supplied workflow
		 *
		 * @param array $workflow - workflow settings.
		 * @return boolean
		 */
		public static function is_valid_current_user( $workflow ) {

			$current_user = WPSC_Current_User::$current_user;
			$flag = false;

			switch ( $workflow['current-user-operator'] ) {

				case 'any':
					$flag = true;
					break;

				case 'matches':
					if (
						( $current_user->is_agent && in_array( $current_user->agent->role, $workflow['current-user'] ) ) ||
						( ! $current_user->is_agent && in_array( 'customer', $workflow['current-user'] ) )
					) {
						$flag = true;
					}
					break;

				case 'not-matches':
					if ( ! (
						( $current_user->is_agent && in_array( $current_user->agent->role, $workflow['current-user'] ) ) ||
						( ! $current_user->is_agent && in_array( 'customer', $workflow['current-user'] ) )
					) ) {
						$flag = true;
					}
					break;
			}

			return $flag;
		}
	}

endif;

WPSC_WF_Process_Triggers::init();
