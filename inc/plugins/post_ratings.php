<?php 
if ( !defined("IN_MYBB") )
	die( "Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined." );

function post_ratings_info() {
	return array(
		"name"			=> "Post Ratings",
		"description"	=> "Adds post ratings",
		"website"		=> "",
		"author"		=> "HellaMadMax",
		"authorsite"	=> "https://hellamad.ga",
		"version"		=> "0.3",
		"compatibility" => "18*"
	);
}

function post_ratings_rates_recache() {
    global $db, $cache;
    $query = $db->simple_select( "postratings_rates", "*", "", array("order_by" => "disporder, rid") );
    $rates = array();
    while( $result=$db->fetch_array($query) ) {
		$rates[ $result["rid"] ] = $result;
    }
    $cache->update( "postratings_rates", $rates );
}

function post_ratings_install() {
	global $db;
	if ( !$db->table_exists("postratings") ) {
		$db->write_query( "CREATE TABLE ".TABLE_PREFIX."postratings (
			ip varchar(30) NOT NULL,
			uid int unsigned NOT NULL,
			rid int unsigned NOT NULL,
			pid int unsigned NOT NULL,
			date int unsigned NOT NULL default '0',
			PRIMARY KEY ( uid, pid )
		) ENGINE=MyISAM;" );
	}
	if ( !$db->table_exists("postratings_rates") ) {
		$db->write_query( "CREATE TABLE ".TABLE_PREFIX."postratings_rates (
			rid int unsigned NOT NULL auto_increment,
			disporder smallint unsigned NOT NULL,
			name varchar(20) NOT NULL,
			image varchar(255) NOT NULL,
			groups varchar(255) NULL,
			forums varchar(255) NULL,
			active tinyint(1) NOT NULL default '1',
			PRIMARY KEY ( rid )
		) ENGINE=MyISAM;" );
		
		$i = 1;
		$rating_insert_data = array(
			"disporder" => $i,
			"name" => "Agree",
			"image" => "images/valid.png"
		); $i++;
		$pid = $db->insert_query( "postratings_rates", $rating_insert_data );
		$rating_insert_data = array(
			"disporder" => $i,
			"name" => "Disagree",
			"image" => "images/invalid.png"
		); $i++;
		$pid = $db->insert_query( "postratings_rates", $rating_insert_data );
		$rating_insert_data = array(
			"disporder" => $i,
			"name" => "Funny",
			"image" => "images/icons/smile.png"
		); $i++;
		$pid = $db->insert_query( "postratings_rates", $rating_insert_data );
		$rating_insert_data = array(
			"disporder" => $i,
			"name" => "Informative",
			"image" => "images/icons/information.png"
		); $i++;
		$pid = $db->insert_query( "postratings_rates", $rating_insert_data );
		$rating_insert_data = array(
			"disporder" => $i,
			"name" => "Friendly",
			"image" => "images/icons/heart.png"
		); $i++;
		$pid = $db->insert_query( "postratings_rates", $rating_insert_data );
		$rating_insert_data = array(
			"disporder" => $i,
			"name" => "Confusing",
			"image" => "images/icons/question.png"
		); $i++;
		$pid = $db->insert_query( "postratings_rates", $rating_insert_data );
		$rating_insert_data = array(
			"disporder" => $i,
			"name" => "Optimistic",
			"image" => "images/icons/rainbow.png"
		); $i++;
		$pid = $db->insert_query( "postratings_rates", $rating_insert_data );
		$rating_insert_data = array(
			"disporder" => $i,
			"name" => "Like",
			"image" => "images/icons/thumbsup.png"
		); $i++;
		$pid = $db->insert_query( "postratings_rates", $rating_insert_data );
		$rating_insert_data = array(
			"disporder" => $i,
			"name" => "Dislike",
			"image" => "images/icons/thumbsdown.png"
		); $i++;
		$pid = $db->insert_query( "postratings_rates", $rating_insert_data );
		post_ratings_rates_recache();
	}
}

function post_ratings_is_installed() {
	global $db;
	if ( $db->table_exists("postratings") and $db->table_exists("postratings_rates") ) {
		return true;
	}
}

function post_ratings_uninstall() {
	global $db;
	if ( $db->table_exists("postratings") ) {
		$db->drop_table( "postratings" );
	}
	if ( $db->table_exists("postratings_rates") ) {
		$db->drop_table( "postratings_rates" );
	}
}

function post_ratings_templates() {
	return [[
		"title" => "forumdisplay_oprating_none",
		"template" => '<td align="center" class="{$bgcolor}{$thread_type_class}">-</td>'
	], [
		"title" => "forumdisplay_oprating",
		"template" => '<td align="center" class="{$bgcolor}{$thread_type_class} oprating">
	<img src="{$r[\'image\']}" alt="{$r[\'name\']}" title="{$r[\'name\']}"> x <strong>{$result[\'amount\']}</strong>
</td>'
	], [
		"title" => "postbit_ratings",
		"template" => '<div class="post_ratings">
    {$post[\'ratings_result\']}
	<div class="post_ratings_control float_right">
		{$post[\'ratings_control\']}
	</div>
</div>'
	], [
		"title" => "postbit_ratings_result_rating",
		"template" => '<span class="rating">
		<img src="{$r[\'image\']}" alt="{$r[\'name\']}" title="{$r[\'name\']}">
		<span class="rating_name"> {$r[\'name\']}</span> x <strong>{$result[\'amount\']}</strong>
	</span>
</span>'
	], [
		"title" => "postbit_ratings_result",
		"template" => '<div class="post_ratings_result{$compress} float_left" onclick="return RatingList(\'{$pid}\');">
	{$ratings_result}
</div>'
	], [
		"title" => "postbit_ratings_control",
		"template" => '<a class="rating" href="#" onclick="return RatePost(\'{$post[\'pid\']}\', \'{$r[\'rid\']}\', \'{$securitytoken}\')">
	<img src="{$r[\'image\']}" alt="{$r[\'name\']}" title="{$r[\'name\']}">
</a>'
	], [
		"title" => "postbit_ratings_list",
		"template" => '<div class="rating">
		<img src="{$r[\'image\']}" alt="{$r[\'name\']}" title="{$r[\'name\']}">
		<span class="rating_name">{$r[\'name\']}</span> x <strong>{$result[\'amount\']}</strong>
		<div>{$users_tmp}</div>
</div>'
	]];
}

function post_ratings_activate() {
	global $db, $mybb;
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";
	find_replace_templatesets(
		"postbit",
		"#".preg_quote('<div class="post_controls">')."#i",
		'{$post[\'ratings\']}<div class="post_controls">'
	);
	find_replace_templatesets(
		"postbit_classic",
		"#".preg_quote('<div class="post_controls">')."#i",
		'{$post[\'ratings\']}<div class="post_controls">'
	);
	
	$post_ratings_templates = post_ratings_templates();
	foreach( $post_ratings_templates as $template ) {
		$db->insert_query( "templates", [
			"tid" => NULL,
			"title" => $template["title"],
			"template" => $db->escape_string( $template["template"] ),
			"sid" => "-1",
			"version" => $mybb->version + 1,
			"dateline" => time()
		] );
	}
	$stylesheet =
'.oprating {
	white-space: nowrap;
	color: #999;
	font-size: 11px;
}

.post_ratings_result img {
	vertical-align: middle;
	position: relative;
	top: -1px;
} .post_ratings_result {
	color: #999;
	clear: both;
	font-size: 11px;
	font-weight: 400;
	white-space: nowrap;
	position: relative;
	margin-left: 10px;
    cursor: pointer;
} .post_ratings_result > .rating:not( :last-child ) {
	margin-right: 6px;
}

.post_ratings_list {
	display: none;
	clear: both;
    float: left;
	margin: 6px 9px;
	color: #333;
	background: #f5f5f5;
	border: 1px solid black;
	border-radius: 4px;
	max-width: 600px;
    max-height: 600px;
	overflow-y: auto;
} .post_ratings_list .rating {
	padding: 6px;
} .post_ratings_list .rating:not( :last-child ) {
	margin-bottom: 6px;
	border-bottom: 1px solid black;
} .post_ratings_list .rating a span:hover {
	text-decoration: underline;
} .post_ratings_list .rating span {
	display: inline-block;
	margin-bottom: 2px;
} .post_ratings_list .rating span img {
	float: left;
	margin-right: 4px;
}

.post_ratings_control {
    margin-right: 10px;
} .post_ratings_control > .rating {
	text-decoration: none;
	opacity: 0.2;
	transition: opacity .25s ease-in-out;
	-moz-transition: opacity .25s ease-in-out;
	-webkit-transition: opacity .25s ease-in-out;
} .post:hover .post_ratings_control > .rating {
    opacity: 0.5;
} .post .post_ratings_control > .rating:hover {
	opacity: 1;
}';
	$db->insert_query( "themestylesheets", array(
		"name" => "post_ratings.css",
		"tid" => 1,
		"attachedto" => "",
		"stylesheet" => $db->escape_string( $stylesheet ),
		"cachefile" => "post_ratings.css",
		"lastmodified" => TIME_NOW
	) );
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
	cache_stylesheet( 1, "post_ratings.css", $stylesheet );
	update_theme_stylesheet_list( 1, false, true );
	
	change_admin_permission( "config", "post_ratings", 1 );
}

function post_ratings_deactivate() {
	global $db, $mybb;
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets(
		"postbit",
		"#".preg_quote('{$post[\'ratings\']}')."#i",
		""
	);
	find_replace_templatesets(
		"postbit_classic",
		"#".preg_quote('{$post[\'ratings\']}')."#i",
		""
	);
	
	$titles = "";
	$post_ratings_templates = post_ratings_templates();
	$c = count( $post_ratings_templates );
	for ( $i=0; $i < $c; ++$i ) {
		$template = $post_ratings_templates[ $i ];
		$titles .= "'".$template[ "title" ]."'";
		if ( $i+1 !== $c ) {
			$titles .= ",";
		}
	}
	$db->delete_query( "templates", "title IN (".$titles.") AND sid='-1'" );
	$db->delete_query( "themestylesheets", "name='post_ratings.css'" );
	$query = $db->simple_select( "themes", "tid" );
	while( $tid = $db->fetch_field($query, "tid") ) {
		@unlink( MYBB_ROOT."cache/themes/theme{$tid}/post_ratings.css" );
	}
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
	update_theme_stylesheet_list( 1, false, true );
	change_admin_permission( "config", "post_ratings", 0 );
}

function get_ratings() {
	global $cache;
	return $cache->read( "postratings_rates" );
}

function get_rating( $rid ) {
	$ratings = get_ratings();
	$rating = array(
		"rid" => -1,
		"disporder" => 0,
		"name" => "(Deleted Rating)",
		"image" => "images/error.png",
		"active" => false
	);
	foreach( get_ratings() as $r ) {
		if ( $r["rid"] == $rid ) {
			$rating = $r;
			break;
		}
	}
	return $rating;
}

function can_use_rating( $rid, $post ) {
	global $mybb;
	if ( $mybb->user["uid"] <= 0 or $mybb->usergroup["isbannedgroup"] ) {
		return array( false, "You aren't allowed to rate posts!" );
	}
	$r = get_rating( $rid );
	if ( !$r["active"] ) {
		return array( false, "You cannot use this rating!" );
	}
	if ( $r["forums"] and $r["forums"] != -1 ) {
		$forums = explode( ",", $r["forums"] );
		if ( !in_array($post["fid"], $forums) ) {
			return array( false, "You cannot use this rating in this forum!" );
		}
	}
	if ( $r["groups"] and $r["groups"] != -1 ) {
		$gids = explode( ",", $mybb->user["additionalgroups"] );
		$gids[] = $mybb->user["usergroup"];
		$gids = array_filter( array_unique($gids) );
		$groups = explode( ",", $r["groups"] );
		$allow = false;
		foreach( $groups as $group ) {
			if ( in_array($group, $gids) ) {
				$allow = true;
				break;
			}
		}
		if ( !$allow ) {
			return array( false, "Your usergroup cannot use this rating!" );
		}
	}
	return array( true );
}

$plugins->add_hook( "forumdisplay_start", "post_ratings_resources" );
$plugins->add_hook( "showthread_start", "post_ratings_resources" );
function post_ratings_resources() {
	global $mybb, $headerinclude;
	$ver = post_ratings_info()["version"];
	$headerinclude .= '<script type="text/javascript" src="'.$mybb->settings["bburl"].'/jscripts/post_ratings.js?ver='.$ver.'"></script>';
}

$plugins->add_hook( "forumdisplay_thread_end", "post_ratings_forumthread" );
function post_ratings_forumthread() {
	global $db, $mybb, $templates, $thread, $rating, $bgcolor, $thread_type_class;
	$query = $db->simple_select( "postratings", "rid,COUNT(rid) as amount", "pid='".$thread["firstpost"]."' GROUP BY rid ORDER BY amount DESC" );
	$result = $db->fetch_array( $query );
	if ( $result ) {
		$r = get_rating( $result["rid"] );
		eval( "\$rating = \"".$templates->get("forumdisplay_oprating")."\";" );
	} else {
		eval( "\$rating = \"".$templates->get("forumdisplay_oprating_none")."\";" );
	}
}

global $post_ratings_page;
$post_ratings_page = array();
$plugins->add_hook( "showthread_start", "post_ratings_threadstart" );
function post_ratings_threadstart() {
	global $db, $mybb, $tid, $ismod, $visibleonly, $post_ratings_page;
	$page = $mybb->get_input( "page", 1 );
	$perpage = $mybb->settings["postsperpage"] or 20;
	if ( $page == 0 and $mybb->get_input("pid", 1) != 0 ) {
		$post = get_post( $mybb->get_input("pid", 1) );
		if ( $post ) {
			$visible = "AND p.visible='1'";
			if ( $ismod ) {
				$visible = "";
			}
			$query = $db->query(
				"SELECT COUNT( p.dateline ) AS count FROM ".TABLE_PREFIX."posts p
				WHERE p.tid = '".$tid."'
				AND p.dateline <= '".$post["dateline"]."'
				".$visible.""
			);
			$result = $db->fetch_field( $query, "count" );
			if ( ($result % $perpage) == 0 ) {
				$page = $result / $perpage;
			} else {
				$page = intval( $result / $perpage ) + 1;
			}
		}
	}
	$end_post = $perpage;
	$start_post = 0;
	if ( $page > 0 ) {
		$end_post = $end_post * $page;
		$start_post = $end_post - $perpage;
	}
	$query = $db->simple_select( "posts", "pid", "tid='".$mybb->input["tid"]."' ".$visibleonly." ORDER BY dateline LIMIT ".$start_post.",".$end_post );
	$where = "";
	while ( $result=$db->fetch_array($query) ) {
		if ( $where == "" ) {
			$where .= "pid IN (".$result["pid"];
		} else {
			$where .= ",".$result["pid"];
		}
	}
	if ( $where != "" ) {
		$where .= ")";
		$query = $db->write_query(
			"SELECT r.rid, r.pid, COUNT(r.rid) AS amount, rd.disporder FROM ".TABLE_PREFIX."postratings r
            LEFT JOIN ".TABLE_PREFIX."postratings_rates rd ON rd.rid = r.rid
			WHERE ".$where." GROUP BY r.pid,r.rid ORDER BY amount DESC,rd.disporder,r.date"
		);
		while ( $result=$db->fetch_array($query) ) {
			if ( !isset($post_ratings_page[$result["pid"]]) ) {
				$post_ratings_page[ $result["pid"] ] = array();
			}
			array_push( $post_ratings_page[$result["pid"]], $result );
		}
	}
}

$plugins->add_hook( "showthread_end", "post_ratings_threadend" );
function post_ratings_threadend() {
	global $ratethread;
	$ratethread = "";
}

function get_post_ratings( $pid, $ajax=false ) {
	global $db, $mybb, $templates, $post_ratings_page;
	if ( $ajax ) {
		$post_ratings_page = array();
		$query = $db->write_query(
			"SELECT r.rid, r.pid, COUNT(r.rid) AS amount, rd.disporder FROM ".TABLE_PREFIX."postratings r
			LEFT JOIN ".TABLE_PREFIX."postratings_rates rd ON rd.rid = r.rid
			WHERE pid='".$pid."' GROUP BY r.rid ORDER BY amount DESC,rd.disporder,r.date"
		);
		while ( $result=$db->fetch_array($query) ) {
			if ( !isset($post_ratings_page[$result["pid"]]) ) {
				$post_ratings_page[ $result["pid"] ] = array();
			}
			array_push( $post_ratings_page[$result["pid"]], $result );
		}
	}	
	$compress = "";
	if ( count($post_ratings_page[$pid]) > 6 ) {
		$compress = " compressed";
	}
	$ratings_result = "";
	if ( is_array($post_ratings_page[$pid]) ) {
		foreach( $post_ratings_page[$pid] as $result ) {
			$r = get_rating( $result["rid"] );
			eval( "\$ratings_result .= \"".$templates->get("postbit_ratings_result_rating")."\";" );
		}
	}
	return eval( "return \"".$templates->get("postbit_ratings_result")."\";" );
}

$plugins->add_hook( "postbit", "post_ratings_postbit" );
function post_ratings_postbit( &$post ) {
	global $db, $mybb, $templates, $post_type, $fid;
	if ( $post["visible"] == -1 and $post_type == 0 and !is_moderator($fid, "canviewdeleted") ) {
		return;
	}
	$post["ratings_result"] = get_post_ratings( $post["pid"] );
	if ( $post["visible"] == 1 ) {//$mybb->user["uid"] != $post["uid"] and 
		foreach( get_ratings() as $r ) {
			if ( !can_use_rating($r["rid"], $post)[0] ) {
				continue;
			}
			$securitytoken = md5( $post["pid"].$r["rid"].$mybb->user["loginkey"] );
			eval( "\$post['ratings_control'] .= \"".$templates->get("postbit_ratings_control")."\";" );
		}
	}
	eval( "\$post['ratings'] = \"".$templates->get("postbit_ratings")."\";" );
}

$plugins->add_hook( "xmlhttp", "post_ratings_xmlhttp" );
function post_ratings_xmlhttp() {
	global $db, $mybb, $templates;
	if ( $mybb->input["action"] == "postrate" ) {
		$pid = intval( $mybb->input["pid"] );
		if ( $pid <= 0 ) {
			die( json_encode(array("error", "Invalid Post!")) );
		}
		$rid = $mybb->get_input( "rid", 1 );
		$r = get_rating( $rid );
		if ( $r["rid"] == -1 ) {
			die( json_encode(array("error", "Invalid Rating!")) );
		}
		if ( $mybb->input["securitytoken"] != md5($pid.$rid.$mybb->user["loginkey"]) ) {
			die( json_encode(array("error", "Invalid Security Token!")) );
		}
		$query = $db->simple_select( "posts", "fid,uid,visible", "pid='$pid'" );
		$post = $db->fetch_array( $query );
		if ( !$post or $post["visible"] != 1 ) {
			die( json_encode(array("error", "Invalid Post!")) );
		}
		if ( $mybb->user["uid"] == $post["uid"] ) {
			//die( json_encode(array("error", "You cannot rate yourself!")) );
		}
		$check = can_use_rating( $rid, $post );
		if ( !$check[0] ) {
			die( json_encode(array("error", $check[1])) );
		}
		$forumpermissions = forum_permissions( $post["fid"] );
		$thread = get_thread( $post["tid"] );
		if ( !($forumpermissions["canview"] == 1 and ($forumpermissions["canviewthreads"] == 1 or (isset($forumpermissions["canonlyviewownthreads"]) and $forumpermissions["canonlyviewownthreads"] == 1 and $thread["uid"] != $mybb->user["uid"]))) ) {
			die( json_encode(array("error", "You cannot rate this post!")) );
		}
		$query = $db->write_query(
			"INSERT INTO ".TABLE_PREFIX."postratings ( ip, uid, rid, pid, date ) VALUES
			( '".$db->escape_string( get_ip() )."', ".$mybb->user["uid"].", $rid, $pid, ".TIME_NOW." )
			ON DUPLICATE KEY UPDATE rid=$rid, date=".TIME_NOW
		);
		if ( !$query ) {
			die( json_encode(array("error", "Failed to add rating!")) );
		}
		die( json_encode(array("ok", get_post_ratings($pid, true))) );
	} elseif ( $mybb->input["action"] == "postlist" ) {
		$pid = (int)$mybb->input["pid"];
		if ( $pid <= 0 ) {
			die( json_encode(array("error", "Invalid Post!")) );
		}
		$query = $db->simple_select( "posts", "fid,uid,visible", "pid='$pid'" );
		$post = $db->fetch_array( $query );
		if ( !$post or ($post["visible"] == 0 and !is_moderator($post["fid"], "canviewunapprove")) or ($post["visible"] == -1 and !is_moderator($post["fid"], "canviewdeleted")) ) {
			die( json_encode(array("error", "Invalid Post!")) );
		}
		$forumpermissions = forum_permissions( $post["fid"] );
		$thread = get_thread( $post["tid"] );
		if ( !($forumpermissions["canview"] == 1 and ($forumpermissions["canviewthreads"] == 1 or (isset($forumpermissions["canonlyviewownthreads"]) and $forumpermissions["canonlyviewownthreads"] == 1 and $thread["uid"] != $mybb->user["uid"]))) ) {
			die( json_encode(array("error", "You cannot view the ratings for this post!")) );
		}
		$query = $db->write_query(
			"SELECT r.rid, r.pid, COUNT(r.rid) AS amount, GROUP_CONCAT(r.uid ORDER BY r.date) as uid_list FROM ".TABLE_PREFIX."postratings r
			LEFT JOIN ".TABLE_PREFIX."postratings_rates rd ON rd.rid = r.rid
			WHERE pid='".$pid."' GROUP BY r.rid ORDER BY amount DESC,rd.disporder,r.date"
		);
		if ( !$query ) {
			die( json_encode(array("error", "Failed to get ratings!")) );
		}
		$tmp = "";
		while ( $result=$db->fetch_array($query) ) {
			$users = $db->simple_select( "users", "username, usergroup, displaygroup, uid", "uid IN (".$result["uid_list"].") ORDER BY FIELD(uid, ".$result["uid_list"].")" );
			$users_tmp = ""; $first = true;
			while ( $user=$db->fetch_array($users) ) {
				$users_tmp .= ( $first===true ? "" : ", " ); $first = false;
				$users_tmp .= build_profile_link( format_name($user["username"], $user["usergroup"], $user["displaygroup"]), $user["uid"] );
			}
			$r = get_rating( $result["rid"] );
			// todo: fix this
			eval( "\$tmp .= \"".$templates->get("postbit_ratings_list")."\";" );
		}
		die( json_encode(array("ok", $tmp, get_post_ratings($pid, true))) );
	}
}

$plugins->add_hook( "admin_config_menu", "post_ratings_cfg_menu" );
function post_ratings_cfg_menu( $sub_menu ) {
	$sub_menu[] = array(
		"id" => "post_ratings",
		"title" => "Post Ratings",
		"link" => "index.php?module=config-post_ratings"
	);
	return $sub_menu;
}

$plugins->add_hook( "admin_config_action_handler", "post_ratings_cfg_page" );
function post_ratings_cfg_page( $actions ) {
	$actions["post_ratings"] = array(
		"active" => "post_ratings",
		"file" => "post_ratings.php"
	);
	return $actions;
}

$plugins->add_hook( "admin_config_permissions", "post_ratings_cfg_permission" );
function post_ratings_cfg_permission( $admin_permissions ) {
	$admin_permissions["post_ratings"] = "Can use 'Post Ratings' plugin?";
	return $admin_permissions;
}

$plugins->add_hook( "admin_tools_get_admin_log_action", "post_ratings_adminlog" );
function post_ratings_adminlog( $plugin_array ) {
	global $lang;
	$lang->load( "config_post_ratings" );
	return $plugin_array;
}
