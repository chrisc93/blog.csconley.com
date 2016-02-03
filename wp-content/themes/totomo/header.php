<?php
/**
 * The header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @package totomo
 */
?>
<?php
	show_admin_bar( false );
	remove_action('wp_head', '_admin_bar_bump_cb');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<title>Blog | Chris Conley</title>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<div id="wrapper">

        <div class="navbar navbar-inverse navbar-static-top">
            <div class="container">
                <div class="navbar-header">
                    <a href="http://csconley.com" class="navbar-brand pull-right">Chris</a>
                </div>
            </div>
        </div>

	<div id="content" class="site-content container">
