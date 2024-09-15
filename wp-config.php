<?php
define( 'WP_CACHE', true );
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'u141005733_gRey6' );

/** Database username */
define( 'DB_USER', 'u141005733_SdKCL' );

/** Database password */
define( 'DB_PASSWORD', 'tXY5SadE1w' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '<hQvG1SDs0 Vsl:1lXsB(BYcq%Mp^5jR]_kEB17xu<:H`Q%8h;h}yAp#rg8tz$H&' );
define( 'SECURE_AUTH_KEY',   'h8S3?l!a=amVSoKM|I;v2ZNK)} }m}Bz}w ZN>+%;ou/URE^fCNfe,cXY4c.Z9(6' );
define( 'LOGGED_IN_KEY',     'Mqf](5]n5Yt/3_+r#ko_*bv=37#588_hsdS3Wz9Vxw):#8)f.h|3V.s//DZ_h|0g' );
define( 'NONCE_KEY',         'e&7CQE*7w_WlJU5kB1/ahX??lovtB`j+NQbH~64#D]]== X}M&HQd4>`=}9UN*C=' );
define( 'AUTH_SALT',         '[ZpBj03^-HT|gd@Fvx#AS!i[e)<P8`2qeZ%P7!;_w%3hQ#c96o#9gzy2H4c6*T.&' );
define( 'SECURE_AUTH_SALT',  '2SNgL5*wo>O,8qoiH|5p#qak1fKDS=t fNB&AsH$S]IY <OrG>V=W|#Bf$_}wo=u' );
define( 'LOGGED_IN_SALT',    '&=a[`(cxAyn<uVou[Uto0k=sGhw1zMz0-o!ICJ)O3pBHCA!cv@B|*0{UPRM%x!=n' );
define( 'NONCE_SALT',        '*NI7:t[85`lgh%K5_}-IXShKfrUbieM25w=I.TbX{f]fmWno{?&D(#[Yju~+8}KD' );
define( 'WP_CACHE_KEY_SALT', '79c]J&p]1y}4,d{zm.=z]L{cd{V^T5@Ug0-I{nj1-iGI:c|y8lzSM$73SF_=Q;{x' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'FS_METHOD', 'direct' );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
