<?php
/**
 * The template for displaying search forms in xtremelysocial
 *
 * @package totomo
 */
?>

<form role="search" method="get" id="searchform" class="large-search-form search-form-margin" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<input type="text" value="" name="s" id="s" placeholder="<?php esc_attr_e( 'Search...', 'totomo' ); ?>">
</form>
