<?php
class peerFeedback
{
	var $version 		= '0.1';
	var $pluginFolder 	= '';
	var $opName 		= 'as_peer_feedback_ops';
	var $ops 			= false;
	var $dbug 			= '';
	
	
	//~~~~~
	function __construct ()
	{
		$this->pluginFolder = plugins_url('', __FILE__);
		$this->ops = $this->checkCompat();
		$this->addWPActions();
		
		
		$this->deltaWPtables();
		
	}
	
	
	//~~~~~
	function defaults ()
	{
		/*
		$defaults = array(
			'version' 			=> $this->version,
			'navButtonLocation'	=> 'both',
			'nextLinkText'		=> 'Next',
			'backLinkText'		=> 'Previous',
			'buttonIconID'		=> '1',
			'showQuickJumpList'	=> 'true',
			'unMarkedText'		=> 'Mark as read',
			'markedText'		=> 'Completed',
			'showStudentProgress' => 'bar',
			'readButtonLocation' => 'top',
			'startLinkText'		=> 'Click here to start',
			'miniMenuLocation'		=> 'Top',
			'subpageListStyle'	=> 'twoCol'
		);
		*/
		
		$defaults="";
		return $defaults;
	}
		
	
	
	
/*	---------------------------
	PRIMARY HOOKS INTO WP 
	--------------------------- */	
	function addWPActions ()
	{
				
		//Frontend
		add_action( 'wp_footer', array( $this, 'frontendEnqueues' ) );
		//add_action( 'wp_footer', array( $this, 'frontendInlineScript' ), 100 ); //later than enqueues
		
		add_action( 'admin_enqueue_scripts', array( $this, 'adminSettingsEnqueues' ) );
		
		
		// Function that check sfor custom GET actions etc		
		add_action( 'load-edit.php', array( $this,'checkForActions'));	

		// Front end drawing of the content page for peer projects		
		add_action( 'the_content', array( $this, 'peer_project_front_draw' ), 100 );
	
	}
	
	//~~~~~
	function peer_project_front_draw( $theContent )
	{
		return ASPFdraw::drawProjectPage( $theContent );
	}	
	
	
	//~~~~~
	function frontendEnqueues ()
	{
		//Scripts
		wp_enqueue_script('jquery');
		
		//Styles
		wp_enqueue_style( 'peer-feedback-front-css', PFEEDBACK_PLUGIN_URL . '/css/frontend.css' );
		wp_enqueue_style( 'peer-feedback-shared-css', PFEEDBACK_PLUGIN_URL . '/css/shared.css' );
		wp_enqueue_style('pure-style','https://cdnjs.cloudflare.com/ajax/libs/pure/0.6.0/buttons.css');		
		
		
		// Register Ajax script for front end
		wp_enqueue_script('js_custom_ajax', PFEEDBACK_PLUGIN_URL.'/scripts/ajax.js', array( 'jquery' ) ); #Custom JS functions
		wp_enqueue_script('js_custom', PFEEDBACK_PLUGIN_URL.'/scripts/js-functions.js'); #Custom JS functions	
		
		
		//Localise the JS file
		$params = array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'ajax_nonce' => wp_create_nonce('pf_ajax_nonce')
		);
		wp_localize_script( 'js_custom_ajax', 'frontEndAjax', $params );		
		
		
			
		
	}
	
	
/*	---------------------------
	ADMIN-SIDE MENU / SCRIPTS 
	--------------------------- */
	
	//~~~~~
	function adminSettingsEnqueues ()
	{
		//WP includes
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-widget');
		wp_enqueue_script('jquery-ui-mouse');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('jquery-touch-punch');	
		wp_enqueue_script('jquery-ui-datepicker');
	
		
		//Plugin folder js
		//wp_enqueue_script( 'page_tracker_settings', $this->pluginFolder . '/scripts/settings.js' );
		
		//Plugin folder css
		wp_enqueue_style( 'peer_feedback_admin_css', PFEEDBACK_PLUGIN_URL . '/css/admin.css' );
		wp_enqueue_style( 'peer-feedback-shared-css', PFEEDBACK_PLUGIN_URL . '/css/shared.css' );
		//wp_enqueue_style( 'page_tracker_progressBars', $this->pluginFolder . '/css/progress-bar.css' );		
		
		
		//DataTables js
		wp_register_script( 'datatables', ( '//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js' ), false, null, true );
		wp_enqueue_script( 'datatables' );
		
		//DataTables css
		wp_enqueue_style('datatables-style','//cdn.datatables.net/1.10.7/css/jquery.dataTables.min.css');
		
		
		
		//Load the jquery ui theme
		global $wp_scripts;	
		$queryui = $wp_scripts->query('jquery-ui-core');
		$url = "https://ajax.googleapis.com/ajax/libs/jqueryui/".$queryui->ver."/themes/smoothness/jquery-ui.css";	
		wp_enqueue_style('jquery-ui-smoothness', $url, false, null);	


		// Add Thickbox		
		add_thickbox(); 
	}
	
	

	
/*	--------------------------------------------
	PLUGIN COMPATIBILITY AND UPDATE FUNCTIONS 
	-------------------------------------------- */	
	//~~~~~
	function getCharsetCollate () 
	{
		global $wpdb;
		$charset_collate = '';
		if ( ! empty( $wpdb->charset ) )
		{
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) 
		{
			$charset_collate .= " COLLATE $wpdb->collate";
		}
		return $charset_collate;
	}
	
	//~~~~~
	function checkCompat ()
	{
	
		$this->dbug .= '#checkCompat.';
		$ops = get_option( $this->opName );
		

		if ( empty( $ops ) ) {
			$ops = $this->defaults();
			update_option( $this->opName, $ops );
			$this->deltaWPtables();
		}
		else {
			if ( $ops['version'] < $this->version ) { //Never downgrade!
				$ops = $this->update( $ops );
			}
		}
		return $ops;
	}
	
	
	//~~~~~
	function update ( $old )
	{
		$this->dbug .= '#update.';
		$ops = $this->defaults();
		
		foreach ( $ops as $k => $op ) {
			if ( array_key_exists( $k, $old ) ) {
				$ops[$k] = $old[$k];
			}
		}
		
		$ops['version'] = $this->version; //set last!
		update_option( $this->opName, $ops );
		$this->deltaWPtables();
		return $ops;
	}
	

	//~~~~~
	function deltaWPtables ()
	{

		
		global $wpdb;
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		$WPversion = substr( get_bloginfo('version'), 0, 3);
		$charset_collate = ( $WPversion >= 3.5 ) ? $wpdb->get_charset_collate() : $this->getCharsetCollate();
		
		$feedbackTable = $wpdb->prefix . DBTABLE_PEER_FEEDBACK;
		//feedback table
		$sql = "CREATE TABLE $feedbackTable (
			feedbackID mediumint(9) NOT NULL AUTO_INCREMENT,
			userID mediumint(9),
			projectID mediumint(9),
			targetUserID mediumint(9),
			submitDate datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			feedbackText longtext,
			feedbackValue mediumint(9),
			PRIMARY KEY  (feedbackID)
			) $charset_collate;";
		dbDelta( $sql );
		//$this->dbug .= '#delta tables.';
		
		$groupsTable = $wpdb->prefix . DBTABLE_PEER_FEEDBACK_GROUPS;
		//groups table
		$sql = "CREATE TABLE $groupsTable (
			groupID mediumint(9) NOT NULL AUTO_INCREMENT,
			groupName varchar(255),
			projectID mediumint(9),
			PRIMARY KEY  (groupID)
			) $charset_collate;";
			
		dbDelta( $sql );
		//$this->dbug .= '#delta tables.';	
		
		
		$usersTable = $wpdb->prefix . DBTABLE_PEER_FEEDBACK_USERS;
		//groups table
		$sql = "CREATE TABLE $usersTable (
			ID mediumint(9) NOT NULL AUTO_INCREMENT,
			groupID mediumint(9),
			firstName varchar(255),
			lastName varchar(255),
			email varchar(255),
			password varchar(255),
			PRIMARY KEY  (ID)			
			) $charset_collate;";
			
			dbDelta( $sql );
//			print_r($msg);
//		echo 'called';
		//die();			
			
			
		
		

		$criteriaTable = $wpdb->prefix . DBTABLE_PEER_FEEDBACK_CRITERIA;
		//groups table
		$sql = "CREATE TABLE $criteriaTable (
			criteriaID mediumint(9) NOT NULL AUTO_INCREMENT,
			projectID mediumint(9),
			criteria longtext,
			criteriaOrder mediumint(9),
			PRIMARY KEY  (criteriaID)
			) $charset_collate;";
			
		dbDelta( $sql );
		
		



		$responseOptionsTable = $wpdb->prefix . DBTABLE_PEER_FEEDBACK_RESPONSE_OPTIONS;
		//groups table
		$sql = "CREATE TABLE $responseOptionsTable (
			optionID mediumint(9) NOT NULL AUTO_INCREMENT,
			projectID mediumint(9),
			responseOption longtext,
			optionOrder mediumint(9),
			PRIMARY KEY  (optionID)
			) $charset_collate;";
			
		dbDelta( $sql );
		
		
		$descriptorsTable = $wpdb->prefix . DBTABLE_PEER_FEEDBACK_CITERIA_DESCRIPTORS;
		//groups table
		$sql = "CREATE TABLE $descriptorsTable (
			criteriaID mediumint(9),
			optionID mediumint(9),
			projectID mediumint(9),
			descriptor longtext
			) $charset_collate;";
			
		dbDelta( $sql );		
	}
	
	
	// Check for custom actions on the admin screens	
	function checkForActions()
	{
		$screen = get_current_screen(); 
		
		
		// Only edit post screen:
		if( 'edit-peer_projects' === $screen->id )
		{
			// Before:
			add_action( 'all_admin_notices', array($this, 'applyActions'));
			//add_action( 'load-edit.php', array( $this,'checkForActions'));		
			
		}
	}
	
	function applyActions()
	{
		if(isset($_GET['myAction']))
		{
			
			$myAction = $_GET['myAction'];
			switch ($myAction)
			{
				
				case "launchProject":
					// Get the project ID and set it to live
					$projectID = $_GET['projectID'];					
					peerfeedbackActions::projectLaunch($projectID);
					
				break;
				
				
				case "disableProject":
					// Get the project ID and set it to live
					$projectID = $_GET['projectID'];
					update_post_meta( $projectID, 'project_status', 0 );
					update_post_meta( $projectID, 'feedback_status', 0 ); // Disable feedback in case its on fopr any reason
					echo '<div class="updated notice"><p>Project Disabled</p></div>';
					
				break;
				
				case "enableFeedback":
				
					$projectID = $_GET['projectID'];				
					peerfeedbackActions::enableFeedback($projectID);
					
				break;				
				
				case "disableFeedback":
					// Get the project ID and set it to live
					$projectID = $_GET['projectID'];
					update_post_meta( $projectID, 'feedback_status', 0 ); // Disable feedback in case its on fopr any reason
					echo '<div class="updated notice"><p>Feedback Disabled</p></div>';					
					
				break;				
				
				
			}
		}		
		
	}	
	


				
	
	
} //Close class
?>