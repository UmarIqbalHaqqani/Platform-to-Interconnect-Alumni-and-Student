<?php

// Load common classes.
foreach ( glob( dirname( __FILE__ ) . '/admin/*.php' ) as $filename ) {
	include_once $filename;
}

// Load custom field types classes.
foreach ( glob( dirname( __FILE__ ) . '/custom-field-types/*.php' ) as $filename ) {
	include_once $filename;
}

// Load model classes.
foreach ( glob( dirname( __FILE__ ) . '/model/*.php' ) as $filename ) {
	include_once $filename;
}

// Load report classes.
foreach ( glob( dirname( __FILE__ ) . '/reports/*.php' ) as $filename ) {
	include_once $filename;
}
