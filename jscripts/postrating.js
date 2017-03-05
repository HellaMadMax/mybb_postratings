function RatePost( pid, rid, securitytoken ) {
	$( "#post_"+pid+" .post_ratingscontrol" ).html( '<img title="Loading..." alt="Loading..." src="images/spinner.gif">' );
	$.post( "xmlhttp.php", {"action": "postrate", pid: pid, rid: rid, securitytoken: securitytoken}, function( data ) {
		if ( data[0] == "ok" ) {
			$( "#post_"+pid+" .post_ratingscontrol" ).html( "Rated!" );
			if ( data[1] ) {
				$( "#post_"+pid+" .post_ratingsresult" ).html( data[1] );
				if ( $("#rating_list_"+pid+":visible")[0] ) {
					RatingList( pid, true );
				} else if ( $("#rating_list_"+pid)[0] ) {
					$( "#rating_list_"+pid ).remove();
				}
			}
		} else {
			$( "#post_"+pid+" .post_ratingscontrol" ).html( data[1] );
		}
	}, "json" );
	return false;
}

function RatingList( pid, force ) {
	if ( $("#rating_list_"+pid)[0] && !force ) {
		$( "#rating_list_"+pid ).slideToggle( "fast" );
		return false;
	}
	
	if ( !force ) {
		$( "#post_"+pid+" .post_ratingsresult" )[0].outerHTML += '<div class="post_ratingslist" style="display: none;">\
			<div class="post_ratingslist_inner"></div>\
		</div>';
	}
	$( "#post_"+pid+" .post_ratingslist_inner" ).html( '<img title="Loading..." alt="Loading..." src="images/spinner.gif">' );
	$( "#post_"+pid+" .post_ratingslist" ).show( "fast" );
	$.post( "xmlhttp.php", {"action": "postlist", pid: pid}, function( data ) {
		if ( data[1] ) {
			$( "#post_"+pid+" .post_ratingslist_inner" ).html( data[1] );
			$( "#post_"+pid+" .post_ratingslist" ).show( "fast" );
		}
	}, "json" );
	return false;
}

$( function() {
	$( "#posts > .post" ).each( function() {
		$( "#rating_controls_"+this.id ).css( "opacity", 0.2 );
		$( this ).hover( function() {
			$( "#rating_controls_"+this.id ).animate( {opacity: 1}, 300 );
		}, function() {
			$( "#rating_controls_"+this.id ).animate( {opacity: 0.2}, 300 );
		} );
	} );
	
	// Fix rating lists going over other rating containers
	$( $(".rating_container").get().reverse() ).each( function(i) {
		$( this ).css( "z-index", i+1 );
	} );
} );