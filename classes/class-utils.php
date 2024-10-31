<?php
class peerFeedback_utils
{
	
	public static function sanitizeTextImport( $input )
	{
		//$output = preg_replace("/[^A-Za-z0-9 @/\:\.'\-]/", '', trim(mysql_real_escape_string($input)));
		
		$output = preg_replace("![^A-Za-z0-9 @/\:\.'-;\&\(\)\"#\*]!", '', trim( ( $input ) ));
		
		//$output = $input;
		return $output;
	}
	
	public static function generatePassword($length = 16) {
		$chars = 'abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789';
		$count = mb_strlen($chars);
	
		for ($i = 0, $result = ''; $i < $length; $i++) {
			$index = rand(0, $count - 1);
			$result .= mb_substr($chars, $index, 1);
		}
	
		return $result;
	}	
	
	public static function processDatabaseText($input)
	{
		$output = wpautop(stripslashes($input));
		return $output;
		
	}
	
	public static function securityCheckAdminPages($varName)
	{
		
		if ( ! defined( 'ABSPATH' ) ) 
		{
			die();	// Exit if accessed directly
		}
		
		// Only let them view if admin		
		if(!current_user_can('manage_options'))
		{
			die();
		}		
		
		
		// Only Load page if the GET is a valid number
		if(isset($_GET[$varName]))
		{
			$$varName = $_GET[$varName];
			
			if(!is_numeric($$varName))
			{
				die();
			}
			else
			{
				
				return $$varName;
			}
		}
		else
		{
			die();
		}

	}
	
	public static function validateInputDate($input)
	{
		$d = DateTime::createFromFormat('Y-m-d', $input);
		return $d && $d->format('Y-m-d') === $input;
	}
	
	public static function validateInputCheckbox($input)
	{
		$output="";
		if($input=="on")
		{
			$output = $input;
		}
		return $output;
	}	
	
	public static function validateInputNumber($input)
	{
		$output="";
		if(is_numeric($input)){$output = $input;}
		return $output;
	}



	
	
} //Close class
?>