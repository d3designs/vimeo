<?php
/**
 * File: api-vimeo
 * 	Handle the Vimeo API.
 *
 * Version:
 * 	2009.11.13
 *
 * Copyright:
 * 	2009 Jay Williams
 *
 * License:
 * 	Simplified BSD License - http://opensource.org/licenses/bsd-license.php
 */


/*%******************************************************************************************%*/
// CORE DEPENDENCIES

// Include the config file
if (file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.inc.php'))
{
	include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.inc.php';
}


/*%******************************************************************************************%*/
// EXCEPTIONS

class Vimeo_Exception extends Exception {}


/*%******************************************************************************************%*/
// CONSTANTS

/**
 * Constant: VIMEO_NAME
 * 	Name of the software.
 */
define('VIMEO_NAME', 'api-vimeo');

/**
 * Constant: VIMEO_VERSION
 * 	Version of the software.
 */
define('VIMEO_VERSION', '1.0');

/**
 * Constant: VIMEO_BUILD
 * 	Build ID of the software.
 */
define('VIMEO_BUILD', gmdate('YmdHis', strtotime(substr('$Date$', 7, 25)) ? strtotime(substr('$Date$', 7, 25)) : filemtime(__FILE__)));

/**
 * Constant: VIMEO_URL
 * 	URL to learn more about the software.
 */
define('VIMEO_URL', 'http://github.com/jaywilliams/vimeo/');

/**
 * Constant: VIMEO_USERAGENT
 * 	User agent string used to identify the software
 */
define('VIMEO_USERAGENT', VIMEO_NAME . '/' . VIMEO_VERSION . ' (Vimeo Toolkit; ' . VIMEO_URL . ') Build/' . VIMEO_BUILD);


/*%******************************************************************************************%*/
// CLASS

/**
 * Class: Vimeo
 */
class Vimeo
{
	/**
	 * Property: subclass
	 * 	The API subclass (e.g. album, artist, user) to point the request to.
	 */
	var $subclass;

	/**
	 * Property: output
	 * 	The output format (e.g. XML, JSON, PHP)
	 */
	var $output;

	/**
	 * Property: test_mode
	 * 	Whether we're in test mode or not.
	 */
	var $test_mode;

	/**
	 * Property: api_version
	 * The supported API version. This is inherited by all service-specific classes.
	 */
	var $api_version = null;

	/**
	 * Property: set_hostname
	 * 	Stores the alternate hostname to use, if any. This is inherited by all service-specific classes.
	 */
	// var $hostname = null;


	/*%******************************************************************************************%*/
	// CONSTRUCTOR

	/**
	 * Method: __construct()
	 * 	The constructor.
	 *
	 * Access:
	 * 	public
	 *
	 * Parameters:
	 * 	key - _string_ (Optional) Your Vimeo API Key. If blank, it will look for the <AWS_KEY> constant.
	 * 	secret_key - _string_ (Optional) Your Vimeo API Secret Key. If blank, it will look for the <AWS_SECRET_KEY> constant.
	 * 	subclass - _string_ (Optional) Don't use this. This is an internal parameter.
	 *
	 * Returns:
	 * 	boolean FALSE if no valid values are set, otherwise true.
	 */
	public function __construct($subclass = null)
	{
		// Set default values
		$this->subclass = (array) $subclass;
		$this->output = 'xml';
		$this->api_version = 'v2';

		return true;
	}


	/*%******************************************************************************************%*/
	// SETTERS

	/**
	 * Method: set_api_version()
	 * 	Sets a new API version to use in the request.
	 *
	 * Parameters:
	 * 	api_version - _string_ (Required) The version to use (e.g. 2.0).
	 *
	 * Returns:
	 * 	void
	 */
	public function set_api_version($api_version)
	{
		$this->api_version = $api_version;
	}

	/**
	 * Method: test_mode()
	 * 	Enables test mode within the API. Enabling test mode will return the request URL instead of requesting it.
	 *
	 * Access:
	 * 	public
	 *
	 * Parameters:
	 * 	enabled - _boolean_ (Optional) Whether test mode is enabled or not.
	 *
	 * Returns:
	 * 	void
	 */
	public function test_mode($enabled = true)
	{
		// Set default values
		$this->test_mode = $enabled;
	}


	/*%******************************************************************************************%*/
	// MAGIC METHODS

	/**
	 * Handle requests to properties
	 */
	public function __get($var)
	{
		
		// Determine the name of this class
		$class_name = get_class($this);
		
		$subclass = (array) $this->subclass;
		$subclass[] = strtolower($var);

		// Re-instantiate this class, passing in the subclass value
		$ref = new $class_name($subclass);
		$ref->test_mode($this->test_mode); // Make sure this gets passed through.
		$ref->set_api_version($this->api_version); // Make sure this gets passed through.

		return $ref;
	}

	/**
	 * Handle requests to methods
	 */
	public function __call($name, $args)
	{
	
		// Change the names of the methods to match what the API expects
		$name = strtolower($name);

		$method = implode('/',$this->subclass) . '/' . $name . '.' . $this->output;

		// Construct the rest of the query parameters with what was passed to the method
		$query = '';
		if ((count($args) > 0))
			$query = '?' . http_build_query($args[0], '', '&');

		// Construct the URL to request
		$api_call = 'http://vimeo.com/api/' . $this->api_version . '/' . $method  . $query;

		// Return the value
		return $this->request($api_call);
	}


	/*%******************************************************************************************%*/
	// REQUEST/RESPONSE

	/**
	 * Method: request()
	 * 	Requests the data, parses it, and returns it. Requires RequestCore and SimpleXML.
	 *
	 * Parameters:
	 * 	url - _string_ (Required) The web service URL to request.
	 *
	 * Returns:
	 * 	ResponseCore object
	 */
	public function request($url)
	{
		if (!$this->test_mode)
		{
			if (class_exists('RequestCore'))
			{
				$http = new RequestCore($url);
				$http->set_useragent(VIMEO_USERAGENT);
				$http->send_request();

				$response = new stdClass();
				$response->header = $http->get_response_header();
				$response->body = $this->parse_response($http->get_response_body());
				$response->status = $http->get_response_code();

				return $response;
			}

			throw new Exception('This class requires RequestCore. http://github.com/skyzyx/requestcore.');
		}

		return $url;
	}

	/**
	 * Method: parse_response()
	 * 	Default method for parsing the response data. You can extend the class and override this method for other response types.
	 *
	 * Parameters:
	 * 	data - _string_ (Required) The data to parse.
	 *
	 * Returns:
	 * 	mixed data
	 */
	public function parse_response($data)
	{
		return new SimpleXMLElement($data, LIBXML_NOCDATA);
	}
}
