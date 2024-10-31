<?php
/*
Plugin Name: Peer Feedback
Description: Create project groups and allow students to give feedback on peer performance
Version: 0.2
Author: Alex Furr
License: GPL
*/


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Global defines
define( 'PFEEDBACK_PLUGIN_URL', plugins_url('peer-feedback' , dirname( __FILE__ )) );
define( 'PFEEDBACK_PATH', plugin_dir_path(__FILE__) );

// Table Names
define('DBTABLE_PEER_FEEDBACK', 'as_peer_feedback');
define('DBTABLE_PEER_FEEDBACK_GROUPS', 'as_peer_feedback_groups');
define('DBTABLE_PEER_FEEDBACK_USERS', 'as_peer_feedback_users');

define('DBTABLE_PEER_FEEDBACK_CRITERIA', 'as_peer_feedback_criteria');
define('DBTABLE_PEER_FEEDBACK_RESPONSE_OPTIONS', 'as_peer_feedback_response_options');
define('DBTABLE_PEER_FEEDBACK_CITERIA_DESCRIPTORS', 'as_peer_feedback_criteria_descriptors');


include_once( PFEEDBACK_PATH . 'classes/class-peer-feedback.php' );
include_once( PFEEDBACK_PATH . 'classes/class-draw.php' );
include_once( PFEEDBACK_PATH . 'classes/class-cpt-admin-setup.php' );
include_once( PFEEDBACK_PATH . 'classes/class-queries.php' );
include_once( PFEEDBACK_PATH . 'classes/class-utils.php' );
require_once PFEEDBACK_PATH.'classes/class-ajax.php'; #Code for all the ajax calls
require_once PFEEDBACK_PATH.'classes/class-actions.php'; #Code for all the ajax calls
include_once( PFEEDBACK_PATH . '/google-charts/class-googlecharts.php');




if ( is_admin() ) {
	//include_once( PFEEDBACK_PATH . '/settings-tabs.php' );
//	include_once( PFEEDBACK_PATH . '/settings-user.php' );
}



$INITIALIZE_CPTs = new peerFeedback_CPT();
$INITIALIZE_SETTINGS = new peerFeedback();
$INITIALIZE_SETTINGS = new peerFeedback_AJAX();


$ASPF_gCharts = new ASPF_GCHARTS();




?>