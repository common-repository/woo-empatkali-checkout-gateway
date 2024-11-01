// This will override the label on "checkout" page
jQuery( 'body' ).on( 'updated_checkout', function() {
	let getTitle = document.querySelector('.wc_payment_method.payment_method_empatkali label').innerHTML.replace('EmpatKali', '');
	document.querySelector('.wc_payment_method.payment_method_empatkali label').innerHTML = getTitle;
});