<?php
if ( !defined("IN_MYBB") )
	die( "Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined." );

$action = $mybb->get_input( "action" );
$sub_tabs = array();
$sub_tabs["postratings"] = array(
	"title" => $lang->post_ratings,
	"link" => "index.php?module=config-post_ratings",
	"description" => $lang->post_ratings_desc
);
$sub_tabs["postratings_add"] = array(
	"title" => $lang->add_post_rating,
	"link" => "index.php?module=config-post_ratings&action=new",
	"description" => $lang->add_post_rating_desc
);
$page->add_breadcrumb_item( $lang->post_ratings, "index.php?module=config-post_ratings" );

if( !$action and empty($action) ) {
	$page->output_header( $lang->post_ratings );
	$page->output_nav_tabs( $sub_tabs, "postratings" );
	
	$table = new Table;
	$table->construct_header( $lang->icon, array("class" => "align_center", "width" => 1) );
	$table->construct_header( $lang->name );
	$table->construct_header( $lang->order, array("class" => "align_center", "width" => 40) );
	$table->construct_header( $lang->controls, array("class" => "align_center", "width" => 150) );
	
	$form = new Form( "index.php?module=config-post_ratings&action=edit_disporder", "post", "", 1 );
	$query = $db->simple_select( "postratings_rates", "*", "", array("order_by" => "disporder, rid") );
	while( $rating = $db->fetch_array($query) ) {
		// Relative URL
		if ( substr($rating["image"], 0, 4) != "http" ) {
			$rating["image"] = "../".$rating["image"];
		}
		$table->construct_cell( '<img src="'.$rating["image"].'" width=16 height=16>', array("class" => "align_center") );
		$active = "<img src=\"styles/".$page->style."/images/icons/bullet_off.png\" alt=\"(".$lang->alt_disabled.")\" title=\"".$lang->alt_disabled."\"  style=\"vertical-align: middle;\" /> ";
		if ( $rating['active'] == 1 ) {
			$active = "<img src=\"styles/".$page->style."/images/icons/bullet_on.png\" alt=\"(".$lang->alt_enabled.")\" title=\"".$lang->alt_enabled."\"  style=\"vertical-align: middle;\" /> ";
		}
		$table->construct_cell( $active.$rating["name"] );
		$table->construct_cell( $form->generate_text_box('disporder['.$rating["rid"].']', $rating["disporder"], array("class" => "align_center", "style" => "width: 30px"), array("class" => "align_center", "width" => 1)) );
		
		$popup = new PopupMenu( "postratings_".$rating["rid"], $lang->options );
		$popup->add_item( $lang->edit_post_rating, "index.php?module=config-post_ratings&action=edit&rid=".$rating["rid"] );
		if ( $rating['active'] == 1 ) {
			$popup->add_item( $lang->disable_post_rating, "index.php?module=config-post_ratings&action=disable&rid=".$rating["rid"]."&my_post_key=".$mybb->post_code, "return AdminCP.deleteConfirmation(this, '".str_replace("'", "\'", $lang->disable_post_rating_confim)."')" );
		} else {
			$popup->add_item( $lang->enable_post_rating, "index.php?module=config-post_ratings&action=enable&rid=".$rating["rid"]."&my_post_key=".$mybb->post_code, "return AdminCP.deleteConfirmation(this, '".str_replace("'", "\'", $lang->enable_post_rating_confim)."')" );
		}
		$popup->add_item( $lang->delete_post_rating, "index.php?module=config-post_ratings&action=delete&rid=".$rating["rid"]."&my_post_key=".$mybb->post_code, "return AdminCP.deleteConfirmation(this, '".str_replace("'", "\'", $lang->delete_post_rating_confim)."')" );
		$table->construct_cell( $popup->fetch(), array("class" => "align_center") );
		$table->construct_row();
	}
	
	if ( $table->num_rows() == 0 ) {
		$table->construct_cell( $lang->no_post_ratings, array("colspan" => 4) );
		$table->construct_row();
		$table->output( $lang->post_ratings );
	} else {
		$table->output( $lang->post_ratings );
		$buttons[] = $form->generate_submit_button( $lang->update_post_ratings_order );
		$form->output_submit_wrapper( $buttons );
		$form->end();
	}
	$page->output_footer();
} elseif ( $action == "edit_disporder" and $mybb->request_method == "post" ) {
	$disporder = $mybb->input["disporder"];
	if ( is_array($disporder) ) {
		foreach( $disporder as $rid => $n_disporder ) {
			$db->update_query( "postratings_rates", array("disporder" => (int)$n_disporder), "rid=".$rid );
		}
		post_ratings_rates_recache();
		log_admin_action();
		flash_message( $lang->rating_order_updated, "success" );
		admin_redirect( "index.php?module=config-post_ratings" );
	} else {
		flash_message( $lang->rating_order_failed, "error" );
		admin_redirect("index.php?module=config-post_ratings");
	}
} elseif ( $action == "edit" ) {
	$rid = $mybb->get_input( "rid", 1 );
	if ( $mybb->request_method == "post" and $rid ) {
		$name = $mybb->get_input( "name" );
		if ( trim($name) == "" ) {
			$errors[] = $lang->post_rating_error_noname;
		}
		
		$image = $mybb->get_input( "image" );
		if ( trim($image) == "" ) {
			$errors[] = $lang->post_rating_error_noimage;
		}
		
		if ( $mybb->input["forum_type"] == 2 ) {
			if ( count($mybb->input["forum_1_forums"]) < 1 ) {
				$errors[] = $lang->error_no_forums_selected;
			}
			$forum_checked[2] = "checked=\"checked\"";
		} else {
			$forum_checked[1] = "checked=\"checked\"";
			$mybb->input["forum_1_forums"] = "";
		}
		
		if ( $mybb->input["group_type"] == 2 ) {
			if ( count($mybb->input["group_1_groups"]) < 1 ) {
				$errors[] = $lang->error_no_groups_selected;
			}
			$group_checked[2] = "checked=\"checked\"";
		} else {
			$group_checked[1] = "checked=\"checked\"";
			$mybb->input["group_1_forums"] = "";
		}
		
		if ( !$errors ) {
			$update_data = array(
				"name" => $db->escape_string( $name ),
				"image" => $db->escape_string( $image )
			);
			
			$update_data["forums"] = -1;
			if ( $mybb->get_input("forum_type", 1) == 2 and is_array($mybb->input["forum_1_forums"]) ) {
				$checked = array();
				foreach( $mybb->input["forum_1_forums"] as $fid ) {
					$checked[] = (int)$fid;
				}
				$update_data["forums"] = implode( ",", $checked );
			}
			
			$update_data["groups"] = -1;
			if ( $mybb->get_input("group_type", 1) == 2 and is_array($mybb->input["group_1_groups"]) ) {
				$checked = array();
				foreach( $mybb->input["group_1_groups"] as $gid ) {
					$checked[] = (int)$gid;
				}
				$update_data["groups"] = implode( ",", $checked );
			}
			
			$db->update_query( "postratings_rates", $update_data, "rid=".$rid );
			post_ratings_rates_recache();
			log_admin_action( $rid, $name );
			flash_message( $lang->post_rating_updated, "success" );
			admin_redirect( "index.php?module=config-post_ratings" );
		}
	}
	
	$query = $db->simple_select( "postratings_rates", "*", "rid=".$rid );
	$rating = $db->fetch_array( $query );
	if ( $rating ) {
		$page->add_breadcrumb_item( $lang->edit_post_rating );
		$page->output_header( $lang->post_ratings." - ".$lang->edit_post_rating );
		if ( $errors ) {
			$page->output_inline_error( $errors );
			$rating = array_replace( $rating, $mybb->input );
		} else {
			$mybb->input["forum_1_forums"] = explode( ",", $rating["forums"] );
			if ( !$rating["forums"] or $rating["forums"] == -1 ) {
				$forum_checked[1] = "checked=\"checked\"";
				$forum_checked[2] = "";
			} else {
				$forum_checked[1] = "";
				$forum_checked[2] = "checked=\"checked\"";
			}
			
			$mybb->input["group_1_groups"] = explode( ",", $rating["groups"] );
			if ( !$rating["groups"] or $rating["groups"] == -1 ) {
				$group_checked[1] = "checked=\"checked\"";
				$group_checked[2] = "";
			} else {
				$group_checked[1] = "";
				$group_checked[2] = "checked=\"checked\"";
			}
		}
		$sub_tabs = array();
		$sub_tabs["postratings"] = array(
			"title" => $lang->edit_post_rating,
			"link" => "index.php?module=config-post_ratings",
			"description" => $lang->edit_post_rating_desc
		);
		$page->output_nav_tabs( $sub_tabs, "postratings" );
		
		$form = new Form( "index.php?module=config-post_ratings&action=edit&rid=".$rid, "post", "", 1 );
		$form_container = new FormContainer( $lang->edit_post_rating );
		$form_container->output_row( $lang->name." <em>*</em>", $lang->name_desc, $form->generate_text_box("name", $rating["name"]) );
		$form_container->output_row( $lang->image." <em>*</em>", $lang->image_desc, $form->generate_text_box("image", $rating["image"]) );
		
		$actions = "<script type=\"text/javascript\">
		function checkAction( id ) {
			var checked = '';
			$( '.'+id+'s_check' ).each( function(e, val) {
				if ( $(this).prop('checked') == true ) {
					checked = $(this).val();
				}
			} );
			$( '.'+id+'s' ).each( function(e) {
				$( this ).hide();
			} );
			if( $('#'+id+'_'+checked) ) {
				$('#'+id+'_'+checked).show();
			}
		}
		</script>
		<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
			<dt><label style=\"display: block;\"><input type=\"radio\" name=\"forum_type\" value=\"1\" ".$forum_checked[1]." class=\"forums_check\" onclick=\"checkAction('forum');\" style=\"vertical-align: middle;\" /> <strong>".$lang->all_forums."</strong></label></dt>
			<dt><label style=\"display: block;\"><input type=\"radio\" name=\"forum_type\" value=\"2\" ".$forum_checked[2]." class=\"forums_check\" onclick=\"checkAction('forum');\" style=\"vertical-align: middle;\" /> <strong>".$lang->select_forums."</strong></label></dt>
			<dd style=\"margin-top: 4px;\" id=\"forum_2\" class=\"forums\">
				<table cellpadding=\"4\"><tr>
					<td valign=\"top\"><small>".$lang->forums_colon."</small></td>
					<td>".$form->generate_forum_select( "forum_1_forums[]", $mybb->input["forum_1_forums"], array("multiple" => true, "size" => 5))."</td>
				</tr></table>
			</dd>
		</dl>
		<script type=\"text/javascript\">checkAction('forum');</script>";
		$form_container->output_row( 'Available in forums <em>*</em>', "", $actions );
		
		$group_select = "
		<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%\">
			<dt><label style=\"display: block;\"><input type=\"radio\" name=\"group_type\" value=\"1\" ".$group_checked[1]." class=\"groups_check\" onclick=\"checkAction('group');\" style=\"vertical-align: middle;\" /> <strong>".$lang->all_groups."</strong></label></dt>
			<dt><label style=\"display: block;\"><input type=\"radio\" name=\"group_type\" value=\"2\" ".$group_checked[2]." class=\"groups_check\" onclick=\"checkAction('group');\" style=\"vertical-align: middle;\" /> <strong>".$lang->select_groups."</strong></label></dt>
			<dd style=\"margin-top: 4px;\" id=\"group_2\" class=\"groups\">
				<table cellpadding=\"4\"><tr>
					<td valign=\"top\"><small>".$lang->groups_colon."</small></td>
					<td>".$form->generate_group_select( "group_1_groups[]", $mybb->input["group_1_groups"], array("multiple" => true, "size" => 5) )."</td>
				</tr></table>
			</dd>
		</dl>
		<script type=\"text/javascript\">checkAction('group');</script>";
		$form_container->output_row( $lang->available_to_groups." <em>*</em>", "", $group_select );
		$form_container->end();
		
		$buttons[] = $form->generate_submit_button( $lang->edit_post_rating );
		$form->output_submit_wrapper( $buttons );
		$form->end();
		$page->output_footer();
    } else {
		flash_message( $lang->post_rating_error_404, "error" );
		admin_redirect( "index.php?module=config-post_ratings" );
	}
} elseif ( $action == "enable" and $mybb->request_method == "post" ) {
	$rid = $mybb->get_input( "rid", 1 );
	$query = $db->simple_select( "postratings_rates", "*", "rid=".$rid );
	$rating = $db->fetch_array( $query );
	if ( $rating and $rating["active"] != 1 ) {
		$db->update_query( "postratings_rates", array("active" => 1), "rid=".$rid );
		post_ratings_rates_recache();
		log_admin_action( $rid, $rating["name"] );
		flash_message( $lang->post_rating_enabled, "success" );
		admin_redirect( "index.php?module=config-post_ratings" );
	} elseif ( $rating ) {
		flash_message( $lang->post_rating_error_enabled, "error" );
		admin_redirect( "index.php?module=config-post_ratings" );
	} else {
		flash_message( $lang->post_rating_error_404, "error" );
		admin_redirect( "index.php?module=config-post_ratings" );
	}
} elseif ( $action == "disable" and $mybb->request_method == "post" ) {
	$rid = $mybb->get_input( "rid", 1 );
	$query = $db->simple_select( "postratings_rates", "*", "rid=".$rid );
	$rating = $db->fetch_array( $query );
	if ( $rating and $rating["active"] == 1 ) {
		$db->update_query( "postratings_rates", array("active" => 0), "rid=".$rid );
		post_ratings_rates_recache();
		log_admin_action( $rid, $rating["name"] );
		flash_message( $lang->post_rating_disabled, "success" );
		admin_redirect( "index.php?module=config-post_ratings" );
	} elseif ( $rating ) {
		flash_message( $lang->post_rating_error_disabled, "error" );
		admin_redirect( "index.php?module=config-post_ratings" );
	} else {
		flash_message( $lang->post_rating_error_404, "error" );
		admin_redirect( "index.php?module=config-post_ratings" );
	}
} elseif ( $action == "delete" and $mybb->request_method == "post" ) {
	$rid = $mybb->get_input( "rid", 1 );
	$query = $db->simple_select( "postratings_rates", "*", "rid=".$rid );
	$rating = $db->fetch_array( $query );
	if ( $rating ) {
		$db->delete_query( "postratings", "rid=$rid" );
		$db->delete_query( "postratings_rates", "rid=$rid" );
		post_ratings_rates_recache();
		log_admin_action( $rid, $rating["name"] );
		flash_message( $lang->post_rating_deleted, "success" );
		admin_redirect( "index.php?module=config-post_ratings" );
	} else {
		flash_message( $lang->post_rating_error_404, "error" );
		admin_redirect( "index.php?module=config-post_ratings" );
	}
} elseif ( $action == "new" ) {
	if ( $mybb->request_method == "post" ) {
		$name = $mybb->get_input( "name" );
		if ( trim($name) == "" ) {
			$errors[] = $lang->post_rating_error_noname;
		}
		
		$image = $mybb->get_input( "image" );
		if ( trim($image) == "" ) {
			$errors[] = $lang->post_rating_error_noimage;
		}
		
		if ( $mybb->input["forum_type"] == 2 ) {
			if ( count($mybb->input["forum_1_forums"]) < 1 ) {
				$errors[] = $lang->error_no_forums_selected;
			}
			$forum_checked[2] = "checked=\"checked\"";
		} else {
			$forum_checked[1] = "checked=\"checked\"";
			$mybb->input["forum_1_forums"] = "";
		}
		
		if ( $mybb->input["group_type"] == 2 ) {
			if ( count($mybb->input["group_1_groups"]) < 1 ) {
				$errors[] = $lang->error_no_groups_selected;
			}
			$group_checked[2] = "checked=\"checked\"";
		} else {
			$group_checked[1] = "checked=\"checked\"";
			$mybb->input["group_1_forums"] = "";
		}
		
		if ( !$errors ) {
			$insert_data = array(
				"name" => $db->escape_string( $name ),
				"image" => $db->escape_string( $image ),
				"active" => (bool)$mybb->get_input( "active", 1 )
			);
			
			$insert_data["forums"] = -1;
			if ( $mybb->get_input("forum_type", 1) == 2 and is_array($mybb->input["forum_1_forums"]) ) {
				$checked = array();
				foreach( $mybb->input["forum_1_forums"] as $fid ) {
					$checked[] = (int)$fid;
				}
				$insert_data["forums"] = implode( ",", $checked );
			}
			
			$insert_data["groups"] = -1;
			if ( $mybb->get_input("group_type", 1) == 2 and is_array($mybb->input["group_1_groups"]) ) {
				$checked = array();
				foreach( $mybb->input["group_1_groups"] as $gid ) {
					$checked[] = (int)$gid;
				}
				$insert_data["groups"] = implode( ",", $checked );
			}
			
			$rid = $db->insert_query( "postratings_rates", $insert_data );
			post_ratings_rates_recache();
			log_admin_action( $rid, $name );
			flash_message( $lang->post_rating_added, "success" );
			admin_redirect( "index.php?module=config-post_ratings" );
		}
	}
	
	$page->add_breadcrumb_item( $lang->add_post_rating );
	$page->output_header( $lang->post_ratings." - ".$lang->add_post_rating );
	if ( $errors ) {
		$page->output_inline_error( $errors );
		$rating = $mybb->input;
	} else {
		$rating = array();
		$forum_checked[1] = $group_checked[1] = "checked=\"checked\"";
		$forum_checked[2] = $group_checked[2] = "";
	}
	$page->output_nav_tabs( $sub_tabs, "postratings_add" );
	
	$form = new Form("index.php?module=config-post_ratings&action=new", "post", "", 1);
	$form_container = new FormContainer( $lang->add_post_rating );
	$form_container->output_row( $lang->name." <em>*</em>", $lang->name_desc, $form->generate_text_box("name", $rating["name"]) );
	$form_container->output_row( $lang->image." <em>*</em>", $lang->image_desc, $form->generate_text_box("image", $rating["image"]) );
	
	$actions = "<script type=\"text/javascript\">
	function checkAction( id ) {
		var checked = '';
		$( '.'+id+'s_check' ).each( function(e, val) {
			if ( $(this).prop('checked') == true ) {
				checked = $(this).val();
			}
		} );
		$( '.'+id+'s' ).each( function(e) {
			$( this ).hide();
		} );
		if( $('#'+id+'_'+checked) ) {
			$('#'+id+'_'+checked).show();
		}
	}
	</script>
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%;\">
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"forum_type\" value=\"1\" ".$forum_checked[1]." class=\"forums_check\" onclick=\"checkAction('forum');\" style=\"vertical-align: middle;\" /> <strong>".$lang->all_forums."</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"forum_type\" value=\"2\" ".$forum_checked[2]." class=\"forums_check\" onclick=\"checkAction('forum');\" style=\"vertical-align: middle;\" /> <strong>".$lang->select_forums."</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"forum_2\" class=\"forums\">
			<table cellpadding=\"4\"><tr>
				<td valign=\"top\"><small>".$lang->forums_colon."</small></td>
				<td>".$form->generate_forum_select( "forum_1_forums[]", $mybb->input["forum_1_forums"], array("multiple" => true, "size" => 5))."</td>
			</tr></table>
		</dd>
	</dl>
	<script type=\"text/javascript\">checkAction('forum');</script>";
	$form_container->output_row( 'Available in forums <em>*</em>', "", $actions );
	
	$group_select = "
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%\">
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"group_type\" value=\"1\" ".$group_checked[1]." class=\"groups_check\" onclick=\"checkAction('group');\" style=\"vertical-align: middle;\" /> <strong>".$lang->all_groups."</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"group_type\" value=\"2\" ".$group_checked[2]." class=\"groups_check\" onclick=\"checkAction('group');\" style=\"vertical-align: middle;\" /> <strong>".$lang->select_groups."</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"group_2\" class=\"groups\">
			<table cellpadding=\"4\"><tr>
				<td valign=\"top\"><small>".$lang->groups_colon."</small></td>
				<td>".$form->generate_group_select( "group_1_groups[]", $mybb->input["group_1_groups"], array("multiple" => true, "size" => 5) )."</td>
			</tr></table>
		</dd>
	</dl>
	<script type=\"text/javascript\">checkAction('group');</script>";
	$form_container->output_row( $lang->available_to_groups." <em>*</em>", "", $group_select );
	$form_container->output_row( $lang->alt_enabled." <em>*</em>", "", $form->generate_yes_no_radio("active", $rating["active"]) );
	$form_container->end();
	
	$buttons[] = $form->generate_submit_button( $lang->add_post_rating );
	$form->output_submit_wrapper( $buttons );
	$form->end();
	$page->output_footer();
} else {
	admin_redirect( "index.php?module=config-post_ratings" );
}