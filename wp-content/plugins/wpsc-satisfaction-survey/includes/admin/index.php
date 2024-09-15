<?php

// Load common classes.
foreach ( glob( dirname( __FILE__ ) . '/settings/*.php' ) as $filename ) {
	include_once $filename;
}

// Load notification classes.
foreach ( glob( dirname( __FILE__ ) . '/email-notifications/*.php' ) as $filename ) {
	include_once $filename;
}

// Load notification classes.
foreach ( glob( dirname( __FILE__ ) . '/widget/*.php' ) as $filename ) {
	include_once $filename;
}
