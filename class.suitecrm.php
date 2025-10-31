<?php

require_once( '../ksf_modules_common/class.curl_handler.php' );

//http://support.sugarcrm.com/Documentation/Sugar_Developer/Sugar_Developer_Guide_6.5/Application_Framework/Web_Services/Examples/REST/PHP/Creating_or_Updating_a_Record/

/**//************************************************************************************************
 * This class is the connetor to SuiteCRM.  It uses CURL and the REST interface.
 *
 * Someday I might adapt this to use SOAP.  Apparantly you can do SOAP over email.
 *
 * TODO:
 * 	Clean up Exceptions to include codes
 * 	Clean up logging
 * 	Provide Function Documentation
 *
 * */
class suitecrm
{
	protected $url;
	protected $username;
	protected $password;
	protected $user_id;
	protected $session_id;
	protected $loggedin;
	protected $module_name;
	protected $response;
        protected $obj_var = array( "url", "username", "password",  );
	protected $crm_var = array( "crm_api", "un",   "p",         );
	protected $curl_http_code;
	protected $curl_curlinfoarray;
	protected $curl_response_headers;
//SEARCH
	protected $search_string;
	protected $search_modules_array;
	protected $search_offset;
	protected $search_max_results;
	protected $search_return_fields_array;
	protected $unified_search_only;
	protected $search_favorites_only;
	protected $search_id;
//
	protected $isHtaccessProtected;
	protected $htaccessUsername;
	protected $htaccessPassword;
	protected $curl;	//<! Curl object
	protected $name_value_list;	//!< Used during set_entry to create a record
	var $id;
	var $debug_level;
	protected $attach_to_id;	//!< string the ID of the note/document record we are attaching the uploaded file to.
	protected $upload_method;	//!< string the "method" in the call used to upload the file
	protected $save_filename;
	protected $revision;
	protected $file_upload_path;
	protected $related_module;	//!< string module that we are associating this record to. (set_relationship)
	protected $related_ids_array;	//!< array 1D array of IDs in the related module


    function __construct( $url, $username, $password, $module_name )
    {
	    $this->debug_level = 0;
	    $this->url = $url;
	    $this->username = $username;
	    $this->password = $password;
	    $this->module_name = $module_name;
	    $this->loggedin = false;
	    $this->search_id = "";
	//SEARCH defaults
		$this->search_favorites_only = false;
		$this->unified_search_only = false;
		$this->search_offset = 0;
		$this->search_max_results = 10;
    }
	/**//******************************************************************************
	 * Initialize our connection to CURL if it hasn't been done.
	 *
	 * Will set/reset CURL options even if the connection has been initialized earlier
	 * Our connection to WooCommerce needs us to then set CURLOPT_HEADER to False after
	 * running this function
	 *
	 * @param method string HTTP Method (Post/Put/Get/...)
	 * @param params array Passed to constructor of curl_handler
	 * @param headers array Passed to constructor of curl_handler
	 * @params data array Passed to constructor of curl_handler
	 * @return null.  Sets ->curl
	 * */
	function init_curl( $method = "POST", $params, $headers, $data )
	{

		if( !isset( $this->curl ) )
			$this->curl = new curl_handler( $this->debug_level, $this->url, "POST", $params, $headers, $data );
		else{

		    //CURL already initialized
		}
		$this->curl->curl_setopt( CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0 );
		$this->curl->curl_setopt( CURLOPT_HEADER, TRUE );	//False for WOO
		$this->curl->curl_setopt( CURLOPT_SSL_VERIFYPEER, FALSE );
		$this->curl->curl_setopt( CURLOPT_RETURNTRANSFER, TRUE );
		$this->curl->curl_setopt( CURLOPT_FOLLOWLOCATION, FALSE );