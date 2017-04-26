<?php
define( "IN_MYBB", 1 );
require_once "./global.php";
add_breadcrumb( "Ratings Log", "ratingslog.php" );

if ( $mybb->user["uid"] <= 0 ) {
	error_no_permission();
}

if ( !function_exists(get_rating) ) {
	error( "Ratings plugin not active" );
}

$select = "r.*, u.username, u.usergroup, u.displaygroup,
p.uid as pu_uid, t.fid, t.tid, t.prefix as t_prefix, t.subject as t_subject, t.visible as t_visible";
$from = TABLE_PREFIX."postratings r
LEFT JOIN ".TABLE_PREFIX."users u ON (r.uid=u.uid)
LEFT JOIN ".TABLE_PREFIX."posts p ON (r.pid=p.pid)
LEFT JOIN ".TABLE_PREFIX."threads t ON (p.tid=t.tid)";
$where = "p.visible=1";

$theader = "";
if ( $mybb->usergroup["cancp"] ) {
	$viewall = $mybb->get_input( "all", 1 );
	if ( $viewall === 1 ) {
		$select .= ", pu.username AS pu_name, pu.usergroup AS pu_group, pu.displaygroup AS pu_dgroup";
		$from .= " LEFT JOIN ".TABLE_PREFIX."users pu ON (p.uid=pu.uid)";
		$theader .= '<tr><td colspan="2" class="tcat">Viewing all ratings</td></tr>';
	} else {
		$rater = get_user( $mybb->get_input("rater", MyBB::INPUT_INT) );
		if ( $rater ) {
			$where = "r.uid=".$rater["uid"]." AND ".$where;
			$rater_link = build_profile_link( format_name($rater["username"], $rater["usergroup"], $rater["displaygroup"]), $rater["uid"] );
		}
		
		$rated = get_user( $mybb->get_input("rated", MyBB::INPUT_INT) );
		if ( $rated ) {
			$where = "p.uid=".$rated["uid"]." AND ".$where;
			$rated_link = build_profile_link( format_name($rated["username"], $rated["usergroup"], $rated["displaygroup"]), $rated["uid"] );
		}
		
		if ( $rater and $rated ) {
			$theader .= '<tr><td colspan="2" class="tcat">Viewing ratings by '.$rater_link.' for '.$rated_link.'</td></tr>';
		} elseif ( $rater ) {
			$select .= ", pu.username AS pu_name, pu.usergroup AS pu_group, pu.displaygroup AS pu_dgroup";
			$from .= " LEFT JOIN ".TABLE_PREFIX."users pu ON (p.uid=pu.uid)";
			$theader .= '<tr><td colspan="2" class="tcat">Viewing ratings by '.$rater_link.'</td></tr>';
		} elseif ( $rated ) {
			$theader .= '<tr><td colspan="2" class="tcat">Viewing ratings for '.$rated_link.'</td></tr>';
		}
	}
}
if ( !$viewall and $where === "p.visible=1" ) {
	$where = "p.uid=".$mybb->user["uid"]." AND ".$where;
}

if ( !isset($parser) ) {
	require_once MYBB_ROOT."inc/class_parser.php";
	$parser = new postParser;
}
$curdate = "";
$content = "";
$query = $db->write_query("SELECT ".$select." FROM ".$from." WHERE ".$where." ORDER BY DATE DESC LIMIT 100" );
while ( $result=$db->fetch_array($query) ) {
	$date = my_date("l, jS \of F Y", $result["date"]);
	if ( $curdate !== $date ) {
		$curdate = $date;
		$content .= '<tr class="date"><td colspan="2" class="tcat">'.$date.'</td></tr>';
	}
	
	if ( $rater and $rater["uid"] === $result["uid"]) {
		$result["username"] = $rater["username"];
		$result["usergroup"] = $rater["usergroup"];
		$result["displaygroup"] = $rater["displaygroup"];
	}
	$user1 = "User #".$result["uid"];
	if ( $result["uid"] === $mybb->user["uid"] ) {
		$user1 = "You";
	} elseif ( isset($result["username"]) ) {
		$user1 = build_profile_link( format_name($result["username"], $result["usergroup"], $result["displaygroup"]), $result["uid"] );
	}
	
	if ( $rated and $rated["uid"] === $result["pu_uid"] ) {
		$result["pu_name"] = $rated["username"];
		$result["pu_group"] = $rated["usergroup"];
		$result["pu_dgroup"] = $rated["displaygroup"];
	}
	$user2 = "User #".$result["pu_uid"]."'s";
	if ( $result["pu_uid"] === $mybb->user["uid"] ) {
		$user2 = "your";
	} elseif ( isset($result["pu_name"]) ) {
		$user2 = build_profile_link( format_name($result["pu_name"], $result["pu_group"], $result["pu_dgroup"]), $result["pu_uid"] )."'s";
	}
	
	$result["t_subjectname"] = "";
	if ( isset($result["t_prefix"]) ) {
		$threadprefix = build_prefixes( $result["t_prefix"] );
		if ( !empty($threadprefix) ) {
			$result["t_subjectname"] = '<span class="prefix">'.$threadprefix["displaystyle"].'</span>';
		}
	}
	$result["t_subjectname"] .= htmlspecialchars_uni( $parser->parse_badwords($result["t_subject"]) );
	if ( !empty($result["t_subjectname"]) ) {
		$result["t_link"] = '<a href="'.get_post_link($result["pid"], $result["tid"]).'#pid'.$result["pid"].'">'.$result["t_subjectname"].'</a>';
		$forumpermissions = forum_permissions( $result["fid"] );
		if ( isset($forumpermissions) and ($forumpermissions["canview"] != 1 or $forumpermissions["canviewthreads"] != 1) ) {
			$result["t_link"] = '<span style="color: orange" title="You cannot view this thread">(Private Thread)</span>';
		} elseif ( isset($forumpermissions) and ($forumpermissions["canonlyviewownthreads"] == 1 and $result["t_uid"] != $mybb->user["uid"]) ) {
			$result["t_link"] = '<span style="color: orange" title="You cannot view this thread">(Private Thread)</span>';
		} elseif ( $result["t_visible"] == -1 and !is_moderator($result["fid"], "canviewdeleted") )  {
			$result["t_link"] = '<span style="color: #FF8080" title="This thread has been deleted">'.$result['t_subjectname'].'</span>';
		} elseif ( $result["t_visible"] == 0 and !is_moderator($result["fid"], "canviewunapprove") )  {
			$result["t_link"] = '<span style="color: #FF8080" title="This thread is unapproved">'.$result['t_subjectname'].'</span>';
		}
	} else {
		$result["t_link"] = '<span style="color: red" title="This thread has been removed">(Removed Thread)</span>';
	}
	
	$foruminfo = get_forum( $result["fid"] );
	if ( $foruminfo ) {
		$result["f_name"] = preg_replace( "#&(?!\#[0-9]+;)#si", "&amp;", $foruminfo["name"] );
	} else {
		$result["f_name"] = "(Unknown Forum)";
	}
	
	$r = get_rating( $result["rid"] );
	$content .= '<tr class="rating">
	<td class="time">'.my_date("h:i:s A", $result["date"]).'</td>
	<td>
		<img src="'.$r["image"].'" alt="'.$r["name"].'" title="'.$r["name"].'">
		<span>'.$user1.' rated '.$user2.' post <strong class="name name_'.$result["rid"].'">'.$r["name"].'</strong> in the thread '.$result["t_link"].' within <a href="forumdisplay.php?fid='.$result["fid"].'">'.$result["f_name"].'</a></span>
	</td>
</tr>';
}
if ( $content === "" ) {
	$content = '<tr><td colspan="2">No Results</td></tr>';
}

output_page( '<html>
	<head>
		<title>'.$mybb->settings["bbname"].' - Ratings Log</title>
		'.$headerinclude.'
	</head>
	<body>
		'.$header.'
		<table id="ratingslog" class="tborder" cellspacing="0" cellpadding="0" border="0">
			<thead>
				<tr>
					<td colspan="5" class="thead">
						<div>
							<strong>'.$mybb->settings["bbname"].' Ratings Log</strong>
							<br>
							<div class="smalltext"></div>
						</div>
					</td>
				</tr>
				'.$theader.'
			</thead>
			<tbody id="ratingslog_e">
				'.$content.'
			</tbody>
		</table>
		'.$footer.'
	</body>
</html>' );
