<?php
/*
Plugin Name: Emanaku's Reordering of Pages in CoursePress Pro 2.0
Plugin URI: 
Description: Gives a (crude) possibility to reorder pages (also called section units) in CoursePress Pro without copy&paste modules. This is tentative software! USE IT AT YOUR OWN RISK!
Version: 0.5
Author: Peter "Emanáku" Kursawe
License: GPL2
*/

// ------------------- Load additional CSS and JavaScript on the right admin pages --------

add_action( 'admin_enqueue_scripts', 'ema_modal_window_admin_style' );

function ema_modal_window_admin_style($hook) {
	// Load only on Course Unit tab!!
	if( !ema_is_course_units_page() ) return;
	
	wp_enqueue_style(  'ema_modal_css', 		plugins_url('ema-cp-reorder-pages-css.css', __FILE__) );
	
	// slip package for moving the blocks in the modal window
	wp_enqueue_style(  'ema_slip_css', 			plugins_url('/slip/css/slip.css', __FILE__) );
	wp_enqueue_script( 'ema_slip_script', 		plugin_dir_url( __FILE__ ) . '/slip/js/slip.js', array(), '1.0' );
	
	wp_enqueue_script( 'ema_reorder_script', 	plugin_dir_url( __FILE__ ) . 'ema-cp-reorder-pages-js.js', array(), '1.0' );
	
	wp_enqueue_script( 'ema_modal_script', 		plugin_dir_url( __FILE__ ) . 'ema-cp-reorder-pages-modalWindow-js.js', array(), '1.0', true );
	
	
	
}


// ------------------- Give the Unit-Builder a Reorder Button ---------------------------
	// in the footer of the admin page for Unit-Builder
	add_action('admin_footer', 'ema_add_modal_window');
	
	function ema_add_modal_window() {
		// recognize the page
		if( !ema_is_course_units_page() ) return;
		
		// it is a course, and we are on the units page!
		// Let's do it!
		
		// (copied from the index.html of draggable-google-modal-master and adapted)
		?>
		
		<!-- ema-modal -->
			<div class="ema-modal">
				<header class="ema-modal-header">
					<h1 class="ema-modal-header-title left">Reorder Unit Sections (Pages) of UNIT: <span class="ema-unit-name"></span></h1>
					<div class="ema-modal-header-buttons">
						<button class="ema-modal-header-btn ema-modal-close" title="Close Modal" onclick=EmaModal.close();>Cancel</button>
						<form id="ema-reorder-form" method="get" onsubmit="return emaValidateReorderData()" action="<?php echo ((isset($_SERVER['HTTPS']) ? "https" : "http") . "://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'])?>">
							<input type="submit" class="ema-modal-header-btn ema-modal-close" value="Reorder!" onclick=EmaModal.close();>
							<?php 
							// get the actual parameter and put them in hidden fields
							foreach( $_GET as $var => $val) {
								if($var != 'page' && $var !='id')
								echo '<input type="hidden" name="'.$var.'" value="'.$val.'">'."\n";
							}
							?>
							<input type="hidden" name="ematype" value="reorder">
							<input type="hidden" id="emaunit" name="emaunit" value="">
							<input type="hidden" id="emaarray" name="emaarray" value="">
						</form>
					</div>
				</header>
				<div class="ema-modal-body">
					<section class="ema-modal-content">
						<p class="ema-red-alert">Reordering only works for CoursePress Version 2.1.x (tested up to 2.1.0.1)<br>If your CoursePress Version is older:<br><span class="ema-bigger">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Don't do it!</span></p>
						<p class="ema-red-alert">Have you saved your units? All unsaved changes will be lost!!</p>
						<p>Please reorder your pages by drag and drop. Then click the <em>Reorder!</em> button.</p>
						<ol id="ema-page-list" class="slippylist" tabindex="0">
						</ol>
					</section>
					<script>
						setupSlip(document.getElementById('ema-page-list'));
					</script>
				</div>
			</div>
			<!-- Scripts -->
			<script>
				/* window.onload = function(e){ EmaModal.init(); }; */

				var emaButtonEnvelope = document.createElement('div');
				emaButtonEnvelope.setAttribute('class', 'ema-button-envelope');
				emaButtonEnvelope.innerHTML = '<button class="ema-modal-header-btn ema-modal-trigger btn-fixed" onclick=emaPrepareAndOpenModal();>Reorder Pages</button>';
				var	emaFind = document.querySelector('h1.wp-heading-inline'); 
				emaFind.appendChild(emaButtonEnvelope);
				
			</script>
		<!-- ema-modal -->
		<?php 
	}


// ------------------- Before Unit-Builder builds up ------------------------------------
	// in the header of the admin page for Unit-Builder
	add_action('admin_init', 'ema_cp_reorder_sections_wrap' , 1 );
	
	function ema_cp_reorder_sections_wrap() {
		$etext = ema_cp_reorder_sections(true);
		if($etext != "") {
			echo $etext;
			die();
		} else {
			ema_cp_reorder_sections(false);
		}
	}
	
	function ema_cp_reorder_sections($check) {
		// check = true - check all transformations
		//				if errors occur, print them and quit. Dont do changes
		// check = false - perform all transformations

		$errortext = "";
		
		// test?
		
		$test = false;
		
		if($test) print("<p>check: $check</p>");
		
		// create redirect URL (URL without ema... variables)
		// this is where we redirect to at the end
		
		parse_str( $_SERVER['QUERY_STRING'] , $queryarr );
		
		// remove all ema... variables from the query string
		$querystring = "";
		foreach($queryarr as $var => $val) {
			if(substr($var, 0, 3) != 'ema') {
				$querystring .= "&$var=$val";
			}
		}
		if(substr($querystring, 0 , 1 ) == '&') {
			$querystring = substr($querystring, 1 );
		}
		
		// put the redirect URL together
		$url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?$querystring";
		
	// ---------------------- check the parameters ---------------------------	
		// recognize the reorder command
		
		if($test && !array_key_exists('emaarray', $_GET)) $test = false;
		
		if($test) print_r( $_GET);
		
		if($test) print("<p>new URL: $url</p>");
		
		if(!isset($_GET['ematype']) || !$_GET['ematype'] == 'reorder') 
													return;
		if(!isset($_GET['post'])) 					return;
		$courseid = $_GET['post'];
		
		if(!isset($_GET['emaunit'])) 				return;
		$unitid = $_GET['emaunit'];
		$unit = get_post( $_GET['emaunit'] );
		
		if(!isset($_GET['emaarray'])) 				return;
		$emaarray = $_GET['emaarray'];
		$newarray = explode(',' , $_GET['emaarray'] );
		
		$normarray = [];
		$trans = [];
		foreach( $newarray as $index => $value) {
			$normarray[] = ($index + 1);
			$trans[$value] = ($index + 1);
		}
		$trans[0] = 0;		// for entries in database having a 0 as page number
							// do not change them!
		
		// printable form of trans for error messages
		$transprint = "";
		foreach($trans as $k => $v) {
			$transprint .= "($k -> $v) ";
		}
		
	// ---------------------- read the meta data -----------------------------
		// Read the Data Structure from the Database
		
		// Data from Course -----------------------------------
		$courseMeta = get_post_meta( $courseid );
		$courseSettings = unserialize( $courseMeta[ 'course_settings' ][0]);
		
		if ($test) {
			print('<div style="margin-left:50px;">');
			print_r($newarray); print('<br>');
			print_r($normarray); print('<br>');
			print_r($trans); print('<br>');
			print("transprint: $transprint");
		}
		if ($test) print('<br><br>Course Visible Units<br>');
		$cp_structure_visible_units_array = unserialize($courseMeta['cp_structure_visible_units'][0]);
		if ($test) { print_r( $cp_structure_visible_units_array );print("<br>");
					 print_r($courseSettings['structure_visible_units']); }
		
		
		if ($test) print('<br><br>Course Preview Units<br>');
		$cp_structure_preview_units_array = unserialize($courseMeta['cp_structure_preview_units'][0]);
		if ($test) { print_r( $cp_structure_preview_units_array );print("<br>");
					 print_r($courseSettings['structure_preview_units']); }
		
		
		if ($test) print("<br><br>Course Visible Pages<br>");
		$cp_structure_visible_pages_array =  unserialize( $courseMeta['cp_structure_visible_pages'][0] );
		if(!isset($cp_structure_visible_pages_array) || is_null($cp_structure_visible_pages_array)) 
			$cp_structure_visible_pages_array = [];
		$trans_cp_structure_visible_pages_array = [];
		$upmNew = "";
		foreach($cp_structure_visible_pages_array as $upm => $visibility) {
			$tmparr = explode('_', $upm);
			if($tmparr[0] == "$unitid") {
				if(array_key_exists($tmparr[1] , $trans))
					$upmNew = $tmparr[0].'_'.$trans[$tmparr[1]];
				else
					$errortext .= "<p>Error EMA01 'visible pages': Old page no $tmparr[1] (from $upm) does not exist in transform array $transprint</p>";
				$trans_cp_structure_visible_pages_array[$upmNew] = $visibility;
			} else {
				$trans_cp_structure_visible_pages_array[$upm] = $visibility;
			}
		}
		if ($test) { print_r( $cp_structure_visible_pages_array); print("<br>"); }
		if(!is_null($trans_cp_structure_visible_pages_array))	
			ksort( $trans_cp_structure_visible_pages_array ); 
		if ($test) { print_r( $trans_cp_structure_visible_pages_array ); print("<br>");
					 print_r($courseSettings['structure_visible_pages']); }
		// write new visible page order 1. own meta key 2. in course_settings;
		if(!$check) update_post_meta( $courseid , 'cp_structure_visible_pages' , $trans_cp_structure_visible_pages_array );
		$courseSettings['structure_visible_pages'] = $trans_cp_structure_visible_pages_array;
					 
		
		if ($test) print("<br><br>Course Preview Pages<br>");
		$cp_structure_preview_pages_array =  unserialize( $courseMeta['cp_structure_preview_pages'][0] );
		if(!isset($cp_structure_preview_pages_array) || is_null($cp_structure_preview_pages_array)) 
			$cp_structure_preview_pages_array = [];
		$trans_cp_structure_preview_pages_array = [];
		$upmNew = "";
		foreach($cp_structure_preview_pages_array as $upm => $visibility) {
			$tmparr = explode('_', $upm);
			if($tmparr[0] == "$unitid") {
				if(array_key_exists($tmparr[1] , $trans))
					$upmNew = $tmparr[0].'_'.$trans[$tmparr[1]];
				else
					$errortext .= "<p>Error EMA02 'preview pages': Old page no $tmparr[1] (from $upm) does not exist in transform array $transprint</p>";
				$trans_cp_structure_preview_pages_array[$upmNew] = $visibility;
			} else {
				$trans_cp_structure_preview_pages_array[$upm] = $visibility;
			}
		}
		if ($test)  { print_r( $cp_structure_preview_pages_array);print("<br>"); }
		if(!is_null($trans_cp_structure_preview_pages_array))
			ksort( $trans_cp_structure_preview_pages_array);
		if ($test) { print_r( $trans_cp_structure_preview_pages_array);print("<br>");
					 print_r($courseSettings['structure_preview_pages']); }
		// write new preview page order 1. own meta key 2. in course_settings;
		if(!$check) update_post_meta( $courseid , 'cp_structure_preview_pages' , $trans_cp_structure_preview_pages_array );
	 	$courseSettings['structure_preview_pages'] = $trans_cp_structure_preview_pages_array;
	 
									 
		if ($test) print("<br><br>Course Visible Modules<br>");
		$cp_structure_visible_modules_array =  unserialize( $courseMeta['cp_structure_visible_modules'][0]);
		if(!isset($cp_structure_visible_modules_array) || is_null($cp_structure_visible_modules_array))
			$cp_structure_visible_modules_array = [];
		$trans_cp_structure_visible_modules_array = [];
		$transd = 0;
		foreach($cp_structure_visible_modules_array as $upm => $visibility) {
			$tmparr = explode('_', $upm);
			if($tmparr[0] == "$unitid") {
				if(array_key_exists($tmparr[1] , $trans))
					$transd = $trans[$tmparr[1]];
				else
					$errortext .= "<p>Error EMA03 'visible modules': Old page no $tmparr[1] (from $upm) does not exist in transform array $transprint</p>";
				if( "$transd" != "$tmparr[1]") {
					$upmNew = $tmparr[0].'_'.$transd.'_'.$tmparr[2];
					$trans_cp_structure_visible_modules_array[$upmNew] = $visibility;
				}
			} else {
				$trans_cp_structure_visible_modules_array[$upm] = $visibility;
			}
		}
		if ($test) { print_r( $cp_structure_visible_modules_array);print("<br>"); }
		if(!is_null($trans_cp_structure_visible_modules_array))
			ksort( $trans_cp_structure_visible_modules_array);	
		if ($test) { print_r( $trans_cp_structure_visible_modules_array);print("<br>"); 
					 print_r($courseSettings['structure_visible_modules']); }
  		// write new visible module order 1. own meta key 2. in course_settings;
		if(!$check) update_post_meta( $courseid , 'cp_structure_visible_modules' ,  $trans_cp_structure_visible_modules_array );
		$courseSettings['structure_visible_modules'] = $trans_cp_structure_visible_modules_array;
			  		 
		
		if ($test) { print("<br><br>Course Preview Modules<br>"); }
		$cp_structure_preview_modules_array =  unserialize( $courseMeta['cp_structure_preview_modules'][0]);
		if(!isset($cp_structure_preview_modules_array) || is_null($cp_structure_preview_modules_array))
			$cp_structure_preview_modules_array = [];
		// print_r($cp_structure_preview_modules_array);
		$trans_cp_structure_preview_modules_array = [];
		$transd = 0;
		foreach($cp_structure_preview_modules_array as $upm => $visibility) {
			$tmparr = explode('_', $upm);
			if($tmparr[0] == "$unitid") {
				if(array_key_exists($tmparr[1] , $trans))
					$transd = $trans[$tmparr[1]];
				else
					$errortext .= "<p>Error EMA04 'preview modules': Old page no $tmparr[1] (from $upm) does not exist in transform array $transprint</p>";
				if( "$transd" != "$tmparr[1]") {
					$upmNew = $tmparr[0].'_'.$transd.'_'.$tmparr[2];
					$trans_cp_structure_preview_modules_array[$upmNew] = $visibility;
				}
			} else {
				$trans_cp_structure_preview_modules_array[$upm] = $visibility;
			}
		}
		if ($test) { print_r( $cp_structure_preview_modules_array);print("<br>"); }
		if(!is_null($trans_cp_structure_preview_modules_array))
			ksort( $trans_cp_structure_preview_modules_array);	
		if ($test) { print_r( $trans_cp_structure_preview_modules_array);print("<br>");
					 print_r($courseSettings['structure_preview_modules']); }
		// write new preview module order 1. own meta key 2. in course_settings;
		if(!$check) update_post_meta( $courseid , 'cp_structure_preview_modules' ,  $trans_cp_structure_preview_modules_array );
		$courseSettings['structure_preview_modules'] = $trans_cp_structure_preview_modules_array;
		
					 
		// write the course_settings back
		
		if(!$check) update_post_meta($courseid, 'course_settings', $courseSettings );

		
		//  Data from affected modules -----------------------------------
		//	these are all posts, which have the unit as parent and are of type module
		
		if ($test) { print("<br><br>Affected Modules<br>"); }
		$query_args = array(
				 'post_type' => 'module'
				,'post_parent' => $unitid
				,'nopaging' => true
			);
		
		$list_modules_affected = [];
		$ema_query = new WP_Query( $query_args );
		while($ema_query->have_posts()) {
			$ema_query->the_post();
			$list_modules_affected[] = $ema_query->post->ID; 
		}
		
		if($test) {
			print("<br>(( unit-ID:$unitid - ");
			print_r($list_modules_affected);
			print(" ))<br>");
		}
		
		// Update the module_page in every affected module
		$trans_mpage = 0;
		foreach( $list_modules_affected as $key => $mid ){
			$meta = get_post_meta( $mid );
			if(isset($meta['module_page'])) {
				$mpage = $meta['module_page'][0];
				if ($test) { print("<p>mid: $mid  - mpage: "); print($mpage); }
				if(array_key_exists($mpage, $trans)) {
					$trans_mpage = $trans[$mpage];
					if($test) print(" -> $trans_mpage");
				} else {
					$errortext .= "<p>Error EMA05 'in modules': Old page no $mpage (from module $mid) does not exist in transform array $transprint</p>";
				}
				if($test) print("</p>");
				// update page info in modules
				if(!$check)  update_post_meta( $mid , 'module_page', $trans_mpage);	
			} else {
				if ($test) print_r("<br>Post ($mid) does not exist or does not have meta key module_page.");
			}
		} 

		
		// Data from Unit ---------------------------------------
		$unitMeta = get_post_meta( $unitid );
		
		if ($test) print("<br><br>Unit Page Titles<br>");
		$page_title_array = unserialize( $unitMeta['page_title'][0] );
		if(!isset($page_title_array) || is_null($page_title_array))
			$page_title_array= [];
		$trans_page_title_array = [];
		$upmNew = "page_0";
		foreach($page_title_array as $pm => $text) {
			$tmparr = explode('_', $pm);
			if(array_key_exists($tmparr[1], $trans))
				$upmNew = $tmparr[0].'_'.$trans[$tmparr[1]];
			else
				$errortext .= "<p>Error EMA06 'in unit page titles': Old page no $tmparr[1] (from page $pm) does not exist in transform array $transprint</p>";
			$trans_page_title_array[$upmNew] = $text;
		}
		if ($test) { print_r( $page_title_array);print("<br>"); }
		if(!is_null($trans_page_title_array))
			ksort( $trans_page_title_array);
		if ($test) print_r( $trans_page_title_array); 
		// update Unit Page Titles
		if(!$check) update_post_meta($unitid, 'page_title', $trans_page_title_array);
		
		
		if ($test) print("<br><br>Unit Show Page Titles<br>");
		$show_page_title_array = unserialize( $unitMeta['show_page_title'][0] );
		if(!isset($show_page_title_array) || is_null($show_page_title_array))
			$show_page_title_array= [];
		$trans_show_page_title_array = [];
		$newkey = "0";
		
		// normalizing the show_page_title_array
		// the array 
		// 		can have gaps 			=> 				fill gaps with value 1 (preset for "show page")
		// 		can have missing entries at the end => 	create them with value 1 (preset for "show page")
		// 		and can have more entries than pages =>	leave them unchanged (who knows, if they are important or not)
		// it should be at least the same length as the trans array
		// and it should have no gaps from 0 to length(trans)-1
		// so the the pages 1...n are in the show_page_title_array on keys 0...(n-1)
		
		ksort($show_page_title_array);
		$spta_normal = [];
		$lentrans = sizeof($trans)-1;	// do not take into account the trans[0] = 0 !
		
		// fill gaps and add missing entries
		for($i=0; $i<$lentrans ; $i++) {
			if(array_key_exists( $i , $show_page_title_array)) {
				$spta_normal[$i] = $show_page_title_array[$i];
			} else {
				$spta_normal[$i] = true;
			}
		}
		// copy elements with keys higher than length of trans array
		foreach($show_page_title_array as $key => $tf) {
			if($key>=$lentrans) $spta_normal[$key] = $tf; 
		}
		
		// now transform the normalized array
		foreach($spta_normal as $key => $tf) {
			$pagenumber = $key+1;
			if($pagenumber <= $lentrans ) {
				if(array_key_exists( $pagenumber , $trans)) {
					$newpagenumber = $trans[$pagenumber];
					$newkey = $newpagenumber - 1;
				} else { 
					$errortext .= "<p>Error EMA07 'in unit show page titles': Old page no $key does not exist in transform array $transprint</p>";
				}
				$trans_show_page_title_array[$newkey] = $tf;
			} else { // last element(s) untouched; not clear what it is good for
				$trans_show_page_title_array[$key] = $tf;
			}
		}
		if ($test) { print_r( $show_page_title_array);print("<br> Normalized: "); print_r($spta_normal);print("<br>"); }
		if(!is_null($trans_show_page_title_array))
			ksort( $trans_show_page_title_array);
		if ($test) print_r( $trans_show_page_title_array);
		// update Unit Page Titles
		if(!$check) update_post_meta($unitid, 'show_page_title', $trans_show_page_title_array);
			
		
		
		
		if ($test) print("<br><br>Unit Page Descriptions<br>");
		$page_desc_array = unserialize( $unitMeta['page_description'][0] );
		if(!isset($page_desc_array) || is_null($page_desc_array))
			$page_desc_array= [];
		$trans_page_desc_array = [];
		$upmNew = "";
		foreach($page_desc_array as $pm => $text) {
			$tmparr = explode('_', $pm);
			if(array_key_exists($tmparr[1], $trans))
				$upmNew = $tmparr[0].'_'.$trans[$tmparr[1]];
			else
				$errortext .= "<p>Error EMA08 'in unit page descriptions': Old page no $tmparr[1] (from page $pm) does not exist in transform array $transprint</p>";
			$trans_page_desc_array[$upmNew] = $text;
		}
		if ($test) { print_r( $page_desc_array);print("<br>"); }
		if(!is_null($trans_page_desc_array))
			ksort( $trans_page_desc_array);
		if ($test) print_r( $trans_page_desc_array);
		// update Unit Page Descriptions
		if(!$check) update_post_meta($unitid, 'page_description', $trans_page_desc_array );
		
		
		if ($test) print("<br><br></div>");
		
		// print link to proceed to the actual edit units page
		if($check && $errortext != "") {
			$errortext.= "<a href=\"$url\">Click to return to Unit Builder (without changes)</a>";
			return $errortext;
		}
		
		if ($test) { 
			print("<p>Errortext: $errortext</p>");
			die();		
		}
		
		if (!$check) { 
			header("location: $url");
		// Now the Unit-Builder is reloading and should use the new order from the database
			return "";
		}
	}


// Helper Functions

	function ema_is_course_units_page() {
		if(!isset($_GET['tab']) ) 		return false;
		if( $_GET['tab'] != 'units') 	return false;
		if(!isset($_GET['post'])) 		return false;
		if( get_post_type( $_GET['post'] ) != 'course') return false;
		return true;
	}

	function ema_print_nice($arg, $level = "") {
		$here = (is_serialized($arg) ? unserialize($arg) : $arg);
		if (!is_array($here) && !is_object($here)) {
			print($level.$here."<br>");
			return;
		}
		foreach($here as $key => $val) {
			print($level.$key." = <br>");
			ema_print_nice($val, $level."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
		}
	}
	
	
	
	
	
	
	