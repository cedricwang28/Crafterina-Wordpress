wp.blocks.registerBlockStyle( 'core/quote', {
	name: 'fancy-quote',
	label: 'Fancy Quote',
} );

wp.blocks.registerBlockStyle( 'core/heading', {
	name: 'underline',
	label: 'Underline',
} );

wp.blocks.registerBlockStyle( 'core/cover', {
	name: 'contain',
	label: 'Contain',
} );

wp.domReady( function() {
	wp.blocks.unregisterBlockStyle( 'core/quote', 'large' );
} );