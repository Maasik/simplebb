<?php
/*
=====================================================
 MWS SimpleBB Forum v1.1 - Mehmet Hanoğlu
-----------------------------------------------------
 http://dle.net.tr/ -  Copyright (c) 2014
-----------------------------------------------------
 Mail: mehmethanoglu@dle.net.tr
-----------------------------------------------------
 Lisans : MIT License
=====================================================
*/

if ( ! defined( 'DATALIFEENGINE' ) ) {
	die( "Hacking attempt!" );
}


if ( ! in_array( $dle_module, array() ) ) {

	if ( $dle_module == "main" ) $forum_where = "";

	if ( $forum_compile == "before" ) {

		$forum_main_tpl = file_get_contents( ROOT_DIR . "/templates/" . $config['skin'] . "/forum/main.tpl" );

		if ( $forum_where == "cat" ) {
			$forum_main_tpl = preg_replace( "#\\[depth=1\\](.*?)\\[/depth=1\\]#is", "", $forum_main_tpl );
			$forum_main_tpl = preg_replace( "#\\[depth=2\\](.*?)\\[/depth=2\\]#is", "$1", $forum_main_tpl );
			$forum_main_tpl = preg_replace( "#\\[depth=3\\](.*?)\\[/depth=3\\]#is", "", $forum_main_tpl );
			$forum_main_tpl = preg_replace( "#\\[depth=4\\](.*?)\\[/depth=4\\]#is", "", $forum_main_tpl );

			$config['forum_show_subforums'] = "1";
			$config['forum_show_subcount'] = "1";
			if ( isset( $category_id ) && $config['forum_show_subforums'] ) {
				$subforums = "";
				foreach ( $cat_info as $cid => $cat ) { if ( $cat['parentid'] == $category_id ) { $subcats[] = $cat['id']; } }
				if ( count( $subcats ) > 0 && $config['forum_show_subcount'] ) {
					$subcounts = array();
					$db->query( "SELECT COUNT(id) as count, category FROM " . PREFIX . "_post WHERE category IN(" . implode( ",", $subcats ) . ") GROUP BY category" );
					while( $d = $db->get_row() ) { $subcounts[ $d['category'] ] = $d['count']; }
					$db->free();
				}
				$main_host = str_replace( $cat_info[ $config['forum_id'] ]['alt_name'] . ".", "", $_SERVER['HTTP_HOST'] );
				foreach ( $subcats as $cid ) {
					$parent = $cat_info[ $cat_info[ $cid ]['parentid'] ];
					$_furl = ( $config['allow_alt_url'] == "1" ) ? $cat_info[ $config['forum_id'] ]['alt_name'] . "/" . $parent['alt_name'] . "/" . $cat_info[$cid]['alt_name'] . "/" : "index.php?do=cat&category=" . $cat_info[$cid]['alt_name'];
					$subforums .= "<p><a href=\"http://{$main_host}/{$_furl}\">" . $cat_info[$cid]['name'] . "</a>";
					if ( $config['forum_show_subcount'] ) {
						$subforums .= " ( " . $subcounts[$cid] . " )</p>";
					} else { $subforums .= "</p>"; }
				}
				$forum_main_tpl = str_replace( "{subforums}", $subforums, $forum_main_tpl );
				$forum_main_tpl = preg_replace( "#\\[sub-forums\\](.*?)\\[/sub-forums\\]#is", "$1", $forum_main_tpl );
			} else {
				$forum_main_tpl = preg_replace( "#\\[sub-forums\\](.*?)\\[/sub-forums\\]#is", "", $forum_main_tpl );
			}

			$forum_main_tpl = str_replace( "{threads.tpl}", $tpl->result['content'], $forum_main_tpl );
		} else if ( $forum_where == "forum" ) {
			$forum_main_tpl = preg_replace( "#\\[depth=1\\](.*?)\\[/depth=1\\]#is", "", $forum_main_tpl );
			$forum_main_tpl = preg_replace( "#\\[depth=2\\](.*?)\\[/depth=2\\]#is", "", $forum_main_tpl );
			$forum_main_tpl = preg_replace( "#\\[depth=3\\](.*?)\\[/depth=3\\]#is", "$1", $forum_main_tpl );
			$forum_main_tpl = preg_replace( "#\\[depth=4\\](.*?)\\[/depth=4\\]#is", "", $forum_main_tpl );
			$forum_main_tpl = str_replace( "{threads.tpl}", $tpl->result['content'], $forum_main_tpl );
		} else if ( $forum_where == "thread" ) {
			$forum_main_tpl = preg_replace( "#\\[depth=1\\](.*?)\\[/depth=1\\]#is", "", $forum_main_tpl );
			$forum_main_tpl = preg_replace( "#\\[depth=2\\](.*?)\\[/depth=2\\]#is", "", $forum_main_tpl );
			$forum_main_tpl = preg_replace( "#\\[depth=3\\](.*?)\\[/depth=3\\]#is", "", $forum_main_tpl );
			$forum_main_tpl = preg_replace( "#\\[depth=4\\](.*?)\\[/depth=4\\]#is", "$1", $forum_main_tpl );
			$forum_main_tpl = str_replace( "{post.tpl}", $tpl->result['content'], $forum_main_tpl );
		}
		if ( in_array( $forum_where, array( "cat", "forum", "thread" ) ) ) {
			$tpl->result['content'] = str_replace( "{content}", $tpl->result['content'], $forum_main_tpl );
		}

	} else if ( $forum_compile == "after" ) {

		// Category Echo
		if ( stripos( $tpl->result['main'], "{category" ) !== false ) {
			$tpl->result['main'] = preg_replace_callback ( "#\\{category(.+?)\\}#i", "custom_cat_print", $tpl->result['main'] );
		}
		// Category Echo

		if ( $config['forum_use_subdomain'] AND ! empty( $cat_info[ $config['forum_id'] ]['alt_name'] ) ) {
			$main_host = str_replace( $cat_info[ $config['forum_id'] ]['alt_name'] . ".", "", $_SERVER['HTTP_HOST'] );
			$tpl->result['main'] = str_replace ( "http://www." . $main_host . "/" . $cat_info[ $config['forum_id'] ]['alt_name'], "http://" . $cat_info[ $config['forum_id'] ]['alt_name'] . "." . $main_host, $tpl->result['main'] );
			$tpl->result['main'] = str_replace ( "http://" . $main_host . "/" . $cat_info[ $config['forum_id'] ]['alt_name'], "http://" . $cat_info[ $config['forum_id'] ]['alt_name'] . "." . $main_host, $tpl->result['main'] );
		}

		if ( strpos( $tpl->result['main'], "{forum-stats}" ) !== false ) {
			if ( ! isset( $forum ) ) {
				include_once (ENGINE_DIR . '/modules/show.forum.php');
				$forum = new SimpleBB( $config, $db, $tpl, $cat_info, $user_groups, $member_id );
			}
			$tpl->result['main'] = str_replace( "{forum-stats}", $forum->get_stats(), $tpl->result['main'] );
		}

		if ( stripos( $tpl->result['main'], "[forum" ) !== false ) {
			if ( in_array( $forum_where, array( "main", "cat", "forum", "thread" ) ) || $dle_module == "addpost" ) {
				$tpl->result['main'] = preg_replace( "#\\[forum\\](.*?)\\[/forum\\]#is", "$1", $tpl->result['main'] );
				$tpl->result['main'] = preg_replace( "#\\[not-forum\\](.*?)\\[/not-forum\\]#is", "", $tpl->result['main'] );
			} else {
				$tpl->result['main'] = preg_replace( "#\\[forum\\](.*?)\\[/forum\\]#is", "", $tpl->result['main'] );
				$tpl->result['main'] = preg_replace( "#\\[not-forum\\](.*?)\\[/not-forum\\]#is", "$1", $tpl->result['main'] );
			}
			if ( $forum_where == "forum" OR $forum_where == "cat" ) {
				foreach ( $banners as $name => $value ) {
					$tpl->result['main'] = str_replace ( "{banner_" . $name . "}", $value, $tpl->result['main'] );
					if ( $value ) {
						$tpl->result['main'] = str_replace ( "[banner_" . $name . "]", "", $tpl->result['main'] );
						$tpl->result['main'] = str_replace ( "[/banner_" . $name . "]", "", $tpl->result['main'] );
					}
				}
				if ( stripos( $tpl->result['main'], "{custom" ) !== false) {
					$tpl->result['main'] = preg_replace_callback ( "#\\{custom(.+?)\\}#i", "custom_print", $tpl->result['main'] );
				}
				$tpl->result['main'] = preg_replace( "#\\[forum:inside\\](.*?)\\[/forum:inside\\]#is", "$1", $tpl->result['main'] );
			}
			if ( ! empty( $forum_where ) && $forum_where != "inside" ) {
				$tpl->result['main'] = preg_replace( "#\\[forum:" . $forum_where . "\\](.*?)\\[/forum:" . $forum_where . "\\]#is", "$1", $tpl->result['main'] );
			}
			$tpl->result['main'] = preg_replace( "#(\\[forum:([a-z]+)\\](.*?)\\[/forum:([a-z]+)\\])#is", "", $tpl->result['main'] );
			$tpl->result['main'] = preg_replace( "#(\\[/forum:([a-z]+)\\])#is", "", $tpl->result['main'] );
		}

	}

}

?>
