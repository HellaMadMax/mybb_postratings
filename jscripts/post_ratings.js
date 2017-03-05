function RatePost( pid, rid, securitytoken ) {
	$( "#post_"+pid+" .post_ratings_control" ).html( '<img title="Loading..." alt="Loading..." src="images/spinner.gif">' );
	$.post( "xmlhttp.php", {"action": "postrate", pid: pid, rid: rid, securitytoken: securitytoken}, function( data ) {
		if ( data[0] == "ok" ) {
			$( "#post_"+pid+" .post_ratings_control" ).html( '<span class="text">Rated!</span>' );
			if ( data[1] ) {
				$( "#post_"+pid+" .post_ratings_result" )[0].outerHTML = data[1];
				if ( $("#post_"+pid+" .post_ratings_list:visible")[0] ) {
					RatingList( pid, true );
				} else if ( $("#post_"+pid+" .post_ratings_list")[0] ) {
					$( "#post_"+pid+" .post_ratings_list" ).remove();
				}
			}
		} else {
			$( "#post_"+pid+" .post_ratings_control" ).html( '<span class="text">'+data[1]+'</span>' );
		}
	}, "json" );
	return false;
}

function RatingList( pid, force ) {
	if ( $("#post_"+pid+" .post_ratings_list")[0] && !force ) {
		$( "#post_"+pid+" .post_ratings_list" ).slideToggle( "fast" );
		return false;
	}
	
	if ( !force ) {
		$( "#post_"+pid+" .post_ratings" ).append( '<div class="post_ratings_list float_left" style="display: none;"></div>' );
	}
	$( "#post_"+pid+" .post_ratings_list" ).html( '<img title="Loading..." alt="Loading..." src="images/spinner.gif">' );
	$( "#post_"+pid+" .post_ratings_list" ).show( "fast" );
	$.post( "xmlhttp.php", {"action": "postlist", pid: pid}, function( data ) {
		if ( data[1] ) {
			if ( data[0] == "ok" ) {
				$( "#post_"+pid+" .post_ratings_list" ).html( data[1] );
			} else {
				$( "#post_"+pid+" .post_ratings_list" ).html( '<div class="text">'+data[1]+'</div>' );
			}
			$( "#post_"+pid+" .post_ratings_list" ).show( "fast" );
		} else {
			$( "#post_"+pid+" .post_ratings_list" ).remove();
		}
		if ( data[2] ) {
			$( "#post_"+pid+" .post_ratings_result" )[0].outerHTML = data[2];
		}
	}, "json" );
	return false;
}
