<?php
// Form
function rvlvr_get_search_form(){
	$html ="";
	$html .= '<form method="get" id="searchform" role="search" action="' . get_home_url() . '"/>';
	$html .= '<label for="s" class="assistive-text">' . __("Search products", "rvlvr")  . '</label>';
	$html .= '<div class="input-group">';
	$html .= '<input type="text" class="field form-control" name="s" id="s" placeholder="' . __("Search products &hellip;", "rvlvr") . '" />';
	$html .= '<input type="hidden" name="post_type" value="product" />';
	$html .= '<span class="input-group-btn"><input type="submit" class="fa fa-search submit btn btn-primary" name="submit" id="searchsubmit" value="&#xf002;" /></span>';
	$html .= '</div></form>';
	return $html;
}

/*
 action="http://dev3.legofarmen.se/">
	<label class="screen-reader-text" for="woocommerce-product-search-field">Search for:</label>
	<input type="search" id="woocommerce-product-search-field" class="search-field" placeholder="Search Products&hellip;" value="" name="s" title="Search for:" />
	
	
</form>

wc
<form role="search" method="get" class="woocommerce-product-search" action="http://dev3.legofarmen.se/">
	<label class="screen-reader-text" for="woocommerce-product-search-field">Search for:</label>
	<input type="search" id="woocommerce-product-search-field" class="search-field" placeholder="Search Products&hellip;" value="" name="s" title="Search for:" />
	<input type="submit" value="Search" />
	<input type="hidden" name="post_type" value="product" />
</form>


wp
	<form method="get" id="searchform" action="http://dev3.legofarmen.se/" role="search">
		<label for="s" class="assistive-text">Search</label>
		<div class="input-group">
			<input type="text" class="field form-control" name="s" id="s" placeholder="Search &hellip;" />
			<span class="input-group-btn">
				<input type="submit" class="submit btn btn-primary" name="submit" id="searchsubmit" value="Search" />
			</span>
		</div>
	</form>
	
*/