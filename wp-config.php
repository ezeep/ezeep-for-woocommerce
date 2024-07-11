<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */


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

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

/* Add any custom values between this line and the "stop editing" line. */
define('AUTH_KEY', 'Cmu:6VZ4QGipql1~2#P_9+|g12n2L[!i,)2}X(5[4e`F+vrORwHa*D1Fq%{ra=nx');
define('SECURE_AUTH_KEY', 'Gx*<k49. smG-k4?V`{cpX/V7:TbK)FH-!.UTt|<(1IFkder+K,DDA2~+DReSmB1');
define('LOGGED_IN_KEY', 'B0+r=PSkU0+vzq,+QB|It9>X~ez-TPCwt>8bS@8JJ&_8PF|(%M63`QqI+<{^U+A{');
define('NONCE_KEY', 'I9X8t%4|&=T*j1$Fp2.-#4JO*a2_-]DDiFJ2 B2`Viu+CqZEMhkT#/G]$v_5mf+h');
define('AUTH_SALT', '4xwMC6;1f`[:Pd!gsoMe487}nZX_!/FWB|>*c dB|U]X=1vv/X#_5<a{]?+$^)Z|');
define('SECURE_AUTH_SALT', '^$dU82ZBa|j`Z[lwQXfT~~TZQ-+kZ:-?,N^<{J^J_izx3~t{;H:KwF*2_qm,JxoE');
define('LOGGED_IN_SALT', 'Sn3zgX9[n_S+A_[SeGzin|Um-_I 9+[20z00L`DZ+N]t|DO!wipH%!Ld^*ud]xx9');
define('NONCE_SALT', '<QZZ~-C5Ad:7.Vn*YQ`L1,iye|J,SRQLh$Y3koLiWv~eD/y9*W3H&=fL8m7Z9+_V');

define('DB_NAME', 'wordpress');
define('DB_USER', 'root');
define('DB_PASSWORD', 'root');
define('DB_HOST', 'db:3306');
define('DB_CHARSET', 'utf8');
define('DB_COLLATE', '');
define('EZEEP_CLIENT_ID', 'Add the client id');
define('EZEEP_CLIENT_SECRET', 'add the client secret');

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH')) {
  define('ABSPATH', __DIR__ . '/');
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

