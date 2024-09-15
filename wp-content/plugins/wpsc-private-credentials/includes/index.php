<?php

// Load settings.
foreach ( glob( dirname( __FILE__ ) . '/widget/*.php' ) as $filename ) {
	include_once $filename;
}

// Load settings.
foreach ( glob( dirname( __FILE__ ) . '/admin/*.php' ) as $filename ) {
	include_once $filename;
}
