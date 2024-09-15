<?php
// Load admin classes.
foreach ( glob( dirname( __FILE__ ) . '/admin/*.php' ) as $filename ) {
	include_once $filename;
}
