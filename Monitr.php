<?php
/**
 * MonitrApp
 *
 * Client-library for the MonitrApp - http://MonitrApp.com
 *
 * @package		MonitrApp
 * @author		Phillip Dornauer
 * @copyright	Copyright (c) 2010 - 2012, Phillip Dornauer
 * @license		http://www.php.net/license/3_0.txt
 * @link		http://github.com/MonitrApp/monitr-php
 * @since		Version 0.0.2
 * @filesource
 */

// ------------------------------------------------------------------------


if( ! function_exists('curl_init') ){
    throw new Exception('Monitr needs the CURL PHP extension.');
}


/**
 * Monitr Class
 */
class Monitr{
    
    const API_URI = "http://api.monitr.io/1/";
    
    private $api_key = "";
    private $domain = "";
    
    private $errorLevel = E_WARNING;
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @access public
     * @return object
     *
     */
    public static function getInstance(){
        if( self::$instance === null ){
            self::$instance = new self;
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     *
     * @access private
     *
     */
    private function __construct(){}
    
    
    /**
     * Inital function
     * 
     * @access public
     * @param string API Key
     * @param string Domain ID
     *
     */
    public function init( $api_key, $domain, $use_error_handler = true, $track_fatals = true ){
        $this->api_key = $api_key;
        $this->domain = $domain;
        
        
        if( $use_error_handler )
            $this->registerErrorHandler();
            
        if( $track_fatals )
            $this->registerShutdownFunction();
    }
    
    
    /**
     * Set the minimum error level
     * 
     * @access public
     * @param string error level
     *
     */
     public function setErrorLevel( $level ){
         $this->errorLevel = $level;
     }
     
     
     
    /**
     * API Call
     * 
     * @access private
     * @param string API Method
     * @param array Parameters
     *
     */
    private function api( $method, $params ){
        
        $url = self::API_URI . $method;
        
        $codes = array(
            E_ERROR => "Error",
            E_WARNING => "Warning",
            E_PARSE => "Parse",
            E_NOTICE => "Notice",
            E_STRICT => "Strict",
            E_DEPRECATED => "Deprecated"
        );
        
        if( isset( $codes[ $params["code"] ] ) ){
            $params["code"] = $codes[ $params["code"] ];
        }else{
            $params["code"] = "Error";
        }
        
        $params["lang"]    = "php";
        $params["version"] = phpversion();
        $params["api_key"] = $this->api_key;
        $params["domain"] = $this->domain;
        
        $ch = curl_init();
        
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_NOBODY, true );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
        
        $response = curl_exec( $ch );
        $status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        
        
        return $response === "SUCCESS" ? true : false;
    }
    
    /**
     * Log Error
     * 
     * @access public
     * @param string  Error Message
     * @param int     Error code
     *
     */
    public function log( $message, $error_code = E_WARNING ){
        $trace = debug_backtrace();
        
        if( ! $error_code & $this->errorCode )
            return false;
        
        return $this->logError( $message, $error_code, $trace[0]["file"], $trace[0]["line"] );
    }
    
    /**
     * Register Error Handler
     *
     * @access private
     *
     */
    private function registerErrorHandler(){
        
        set_error_handler( "__monitr_error_handler" );
        
    }
    
    /**
     * Register Shutdown Function
     * 
     * @access private
     *
     */
    private function registerShutdownFunction(){
        
        register_shutdown_function( "__monitr_shutdown_function" );
        
    }
    
    
    /**
     * Log Error
     * 
     * @access public
     * @param string Error message
     * @param int    Error code
     * @param string Error file
     * @param int    Error line
     * @return bool  CURL Request successful
     *
     */
    public function logError( $message, $code, $file, $line ){
        
        
        if( ! $code & $this->errorCode )
            return false;
        
        return $this->api( "log", array(
            "message" => $message,
            "code"    => $code,
            "file"    => $file,
            "line"    => $line
        ) );
    }
    
}

function __monitr_error_handler( $code, $message, $file, $line ){
    $monitr = Monitr::getInstance();
    
    $monitr->logError( $message, $code, $file, $line );
}

function __monitr_shutdown_function(){
    $monitr = Monitr::getInstance();
    
    $lastError = error_get_last();
    
    if( $lastError["type"] === E_ERROR ){
        $monitr->logError( $lastError["message"], E_ERROR, $lastError["file"], $lastError["line"] );
    }
}


