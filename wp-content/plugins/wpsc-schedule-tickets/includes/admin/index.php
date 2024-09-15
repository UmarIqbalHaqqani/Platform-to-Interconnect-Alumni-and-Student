<?php
// Load setting classes.
foreach ( glob( dirname( __FILE__ ) . '/settings/*.php' ) as $filename ) {
	include_once $filename;
}
