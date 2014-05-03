<?php 

    /**
    * Plugin Name: GEO-SEO
    * Plugin URI: http://velinszky.hu/geoseo
    * Description: SEO for different words. You can specify a list of subpages, that should be automatically generated based on keywords and the parent page.
    * Author: Laszlo Velinszky
    * Author URI: http://velinszky.hu/laszlo
    * Version: 1.3
    */
	     
    class GeoSeo
    {
   
        var $parent_post;
        var $table;
        
         
        /**
         * Location
         */
        var $location = 'Budapest';
       
  
        /**
         * Class constructor
         */
        function GeoSeo()
        {
            /**
             * We'll wait til WordPress has looked for posts, and then
             * check to see if the requested url matches our target.
             */
        	global $wpdb;
        	$this->location = get_option('geoseo_title');
        	$this->table = $wpdb->prefix . "geoseo_loc";
        	add_filter('the_posts',array(&$this,'detectPost'));
        	add_filter('rewrite_rules_array',  array(&$this,'insertRules'));
			add_filter('query_vars', array(&$this,'addParam'));
			add_filter('wp_title', array(&$this,'addTitle'));
        	register_activation_hook(__FILE__,array(&$this,'addLoc'));
        	register_deactivation_hook(__FILE__, array(&$this,'dropLoc'));
        	add_shortcode( 'geoseo', array(&$this,'shortHandle') );
        	add_shortcode( 'geoseolist', array(&$this,'shortlistHandle') );
        	// Options page
			add_action('admin_menu', array(&$this,'addAdminPage')); 

            
        }
    	// Adding fake pages' rewrite rules
		function insertRules($rules)
		{
		    $newrules = array();
		    $allpages = get_option('geoseo_pages');
		    foreach ($allpages as $slug )
		        $newrules[$slug.'/([^/]+)/?$'] = 'index.php?pagename='.$slug.'&geoseoparam=$matches[1]';
		 
		    return $newrules + $rules;
		}
		 
    	function addTitle($title)
		{
			if($this->location != get_option('geoseo_title')){
				$title = $this->location. ' - '. $title ;
			}
		    return $title;
		}
		
		// Tell WordPress to accept our custom query variable
		function addParam($vars)
		{
		    array_push($vars, 'geoseoparam');
		    return $vars;
		}

        function addAdminPage(){
        	    add_management_page('GEO-SEO Settings', 'GEO-SEO Settings', 'manage_options', __FILE__,array(&$this, 'adminPage'));
        }
        
        function readValues(){
        	global $wp_rewrite;
           	
        	if (!empty($_FILES['geoseo_file']['tmp_name'])){
        		$fname = $_FILES['geoseo_file']['tmp_name'];
        		//empty table
        		$this->flushLoc();
        		//parse file
        		//echo "Opening $fname.";
        		$fh = fopen($fname,'r');
        		while($arg = fgetcsv($fh)){
        			echo "Inserting $arg[0], $arg[1]<br/>";
        			$this->insertLoc($arg[0], $arg[1]);
        		} 
        		fclose($fh);
        		$wp_rewrite->flush_rules();
        	}

           	update_option('geoseo_wpage',$_POST['geoseo_wpage']);
           	update_option('geoseo_wloc',$_POST['geoseo_wloc']);
        	update_option('geoseo_wsend',$_POST['geoseo_wsend']);
        	update_option('geoseo_pages',$_POST['geoseo_pages']);
        	update_option('geoseo_title',$_POST['geoseo_title']);
        }
        function adminPage(){
        	if ('POST' == $_SERVER['REQUEST_METHOD']) {
        		$this->readValues();	
        	}
        	//HTML FORM
        	?>
        	<h1>GEOSEO Settings</h1>
        	<h2>New parameters:</h2>
 			<form class="geoseo-admin" method="post" enctype="multipart/form-data">
 			<p class="geoseo-admin">
 				<label for="geoseo_file">CSV file with two data each line (budapest, Budapest):</label>
 				<input type="file" name="geoseo_file" id="geoseo_file"/>
 			</p>
 			<p class="geoseo-admin">
 				<label for="geoseo_pages[]">Page for validation:</label><br/>
 			<?php 
 				$pages = get_pages();
 				$validlist =get_option('geoseo_pages');
 				foreach ($pages as $page){
 					?>
 					<input type="checkbox" name="geoseo_pages[]" value="<?php echo $page->post_name ?>"
 					<?php if(in_array($page->post_name,$validlist)){ 
 						echo " checked";}?>
 					/>
 					<?php echo $page->post_title . '('. $page->post_name . ')' ; ?>
 					<br/>
 					<?php  
 				
 				}
 			?>
 			<label for="geoseo_wsend">Default text:</label><input type="text" name="geoseo_title" value="<?php echo get_option('geoseo_title')?>"/><br/>
 			</p>
 			<h2>Widget settings</h2> 
 			<p>
 			<label for="geoseo_wpage">Text before pages:</label><input type="text" name="geoseo_wpage" value="<?php echo get_option('geoseo_wpage')?>"/><br/>
 			<label for="geoseo_wloc">Text before locations:</label><input type="text" name="geoseo_wloc" value="<?php echo get_option('geoseo_wloc')?>"/><br/>
 			<label for="geoseo_wsend">Text on send button:</label><input type="text" name="geoseo_wsend" value="<?php echo get_option('geoseo_wsend')?>"/><br/>
 			</p>
 			<input type="submit" value="Send"/>
 			</form>
 			<h2>Old parameters:</h2>
 			<div class="list">
 			<h3>Locations:</h3>
 			<?php 
 				$list =$this->itemlist();
 				foreach ($list as $item){
 					echo '<div class="geoseo_item">';
 					echo $item->name;
 					echo '- <strong>';
 					echo $item->title;
 					echo '</strong></div>'; 					
 				}
 			?>

 			</div>
 			<h2>Usage</h2>
			EN: Works only on chosen pages. The title of the subpage is inserted on the page by adding the [geoseo] expression. 
			The expression can be surrounded with [geoseo pre='text before' post='and after'] syntax.
 			The list of all terms can be displayed by the [geoseolist name='page name']  expression.</p>
 			<p> HU: A kivalasztott oldalakon alkalmazhato a csel. A [geoseo] kifejezes beilleszti a valasztott telepulesnevet az oldalba.
 			Megadhato elo es utotag is a [geoseo pre='elotag' post='utotag'] szintaxissal.
 			A [geoseolist name='oldal_neve'] legeneralja az oldal_neve kifejezeshez az osszes telepulesnevet.</p>
        	<?php 
         }
        
        public function itemlist(){
        	global $wpdb;
        	$query = "SELECT * FROM $this->table;";
        	$res = $wpdb->get_results( $query );
			return $res;
        }
        
        function shortlistHandle($atts){
        	global $wp_rewrite;
        	$ret="";
        	if(isset($atts['name'])){
        		$name = $atts['name'];
        	    $res = $this->itemlist();
				foreach($res as $elem){
					$ret .= '<div class="geoseo_list"><a class="geoseo_list" href="/'.$name.'/'.$elem->name.'/">'.$elem->title.'</a></div>'; 
				}
        	}
        	return $ret;
       	} 
       	function shortHandle($atts){
       		$pre='';
       		$post='';
       		if(isset($atts['pre'])){
       			$pre = $atts['pre'];
       		}
       		if(isset($atts['post'])){
       			$pre = $atts['post'];
       		}
       		return $pre.$this->location.$post;
       	} 

        function flushLoc(){
       		global $wpdb;
       		$table = $this->table;
       		$wpdb->query( "DELETE FROM $table");
       	}
       	function insertLoc($name, $title){
       		global $wpdb;
       		$wpdb->insert( 
				$this->table, 
				array( 
					'name' => $name, 
					'title' => $title 
				)
			);
       	}

        
        function dropLoc(){
        	global $wpdb;
        	$table = $this->table;
            //Delete any options thats stored also?
			
        	delete_option('geoseo_pages');
        	delete_option('geoseo_title');
        	delete_option('geoseo_wpages');
        	delete_option('geoseo_wloc');
        	delete_option('geoseo_wsend');
        	delete_option( 'geoseo_title');
			$wpdb->query("DROP TABLE IF EXISTS $table");        	
        }
        function addLoc(){
        	global $wpdb;
        	$table = $this->table;
   		add_option( 'geoseo_pages', array('sample_page'));
   		add_option( 'geoseo_title', 'Budapest');
   		add_option('geoseo_wpages','Services:');
        add_option('geoseo_wloc','Location:');
        add_option('geoseo_wsend','Search');
		$sql = "CREATE TABLE $table (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				name tinytext NOT NULL,
				title tinytext NOT NULL,
				INDEX name (name(5)),
				UNIQUE KEY id (id)
				);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   		dbDelta($sql);	
   		$this->insertLoc('budapest', 'Budapest');
		
        }
        function setLocation($loc){
        	global $wpdb;
        	$query = $wpdb->prepare(
				"SELECT * FROM $this->table
				 WHERE name = %s;
				",
	        	$loc 
        	);
        	$res = $wpdb->get_row($query );
        	if ($res != null){
        	$this->location = $res->title;
        		return true;
        	}else{
        		return false;
        	}
        }
     
        function detectPost($posts){
            global $wp;
            global $wp_query;
            /**
             * Check if the requested page matches our target
             */
            $pages = explode('/',$wp->request,2);
            $valids =get_option('geoseo_pages'); //array('automentes','ajtomentes','autozar');
            if (in_array($pages[0],$valids) && $this->setLocation($pages[1]) ){            	
            //}
            //if (strtolower($wp->request) == 'ajtomentes/'.strtolower($this->page_slug) || $wp->query_vars['page_id'] == $this->page_slug){
                //Add the fake post
                $posts=NULL;
                //$posts[]=$this->createPost();
                $post = get_post(get_page_by_path( $pages[0]));
                //$post->post_title = $post->post_title." - ".$this->location;
                $posts[]=$post;
                /**
                 * Trick wp_query into thinking this is a page (necessary for wp_title() at least)
                 * Not sure if it's cheating or not to modify global variables in a filter
                 * but it appears to work and the codex doesn't directly say not to.
                 */
                //$wp_query->found_posts=1;
                //$wp_query->post_count=1;
                $wp_query->is_page = true;
                //Not sure if this one is necessary but might as well set it like a true page
                $wp_query->is_singular = true;
                $wp_query->is_home = false;
                $wp_query->is_archive = false;
                $wp_query->is_category = false;
                //Longer permalink structures may not match the fake post slug and cause a 404 error so we catch the error here
                unset($wp_query->query["error"]);
                $wp_query->query_vars["error"]="";
                $wp_query->is_404=false;
                
               
            }
            return $posts;
        }
    }
    
    class GeoSeoWidget extends WP_Widget{
		
    	public function __construct() {
			// widget actual processes
			parent::__construct('GeoSeoWidget','GeoSeoWidget');
		}
	
	 	public function form( $instance ) {
			// outputs the options form on admin
		}
	
		public function update( $new_instance, $old_instance ) {
			// processes widget options to be saved
		}
	
		public function widget( $args, $instance ) {
			// outputs the content of the widget
			global $geoseo;
			$locs = $geoseo->itemlist();
			$pages = get_option('geoseo_pages');
			?>
			
			<div class="geoseo_widget">
			<script language="JavaScript"> 

			function jump( form ) { 
				var pageix= form.geoseo_page.options.selectedIndex;
				var locix = form.geoseo_loc.options.selectedIndex;
				cururl = '/' + form.geoseo_page.options[pageix].value + '/' + form.geoseo_loc.options[locix].value + '/';; 
				window.location =  cururl ; 
			} 

			</script>
			<form action="#" name="geoseow"><p class="geoseo_widget">
			<label for="geoseo_loc">
			<?php
			echo get_option('geoseo_wloc'); 
			?></label>
			<select name="geoseo_loc">
			<?php 
				foreach ($locs as $loc){
					echo '<option value="'.$loc->name.'">'.$loc->title.'</option>';				
				}
			?>
			</select>
			<br/>
			<label for="geoseo_page">
			<?php
			echo get_option('geoseo_wpage'); 
			?>
			</label>
			<select name="geoseo_page">
			<?php 
				foreach ($pages as $page){
					//$title ="a";
					$title = get_page_by_path($page)->post_title;
					echo '<option value="'.$page.'">'.$title.'</option>';				
				}
			?>
			</select>
			<input type="button" value="<?php echo get_option('geoseo_wsend'); ?>" onclick="jump(this.form);"/>
			</p></form>
			</div>
			<?php 

		}
    }
       
    /**
    * Create an instance of our class.
    */
    $geoseo = new GeoSeo; 
    // register GeoSeoWidget widget
	add_action( 'widgets_init', create_function( '', 'register_widget( "GeoSeoWidget" );' ) );
?>
