<?php
/**
 * Class to connect to flickr
 *
 * @package nlFlickrConnector
 */
class nlFlickrConnector{
    
// Properties

    private $apiBaseUrl = "https://www.flickr.com/services/rest/";
    private $apiKey = NULL;
    private $apiUserId = NULL;

    public $connected = false;




// Methods

/**
 *      Constructor function when instantiating the class
 *      Validates a FlickrAPI key and userId, setting them as class properties if valid
 * 
 *      @param string   $apiKey       The FlickrAPI key used to authenticate for calls
 *      @param string   $apiUserId    The FlickrAPI userId used to dertermine the caller
 * 
 *      @return boolean               Representing whether the values have been set and class can be used
 */
    public function __construct($apiKey = NULL, $apiUserId = NULL){
        // Set the class parameter of connected to false
        $this->connected = false;

        // If an apiKey is provided, set it if it validates
        if(isset($apiKey)){
            if($this->setFlickrApiKey($apiKey) != true){
                throw new Exception('Could not validate apiKey');
            }
        }
        
        // If an apiUserId is provided, set it if it validates
        if(isset($apiUserId)){
            if($this->setFlickrApiUser($apiUserId) != true){
                throw new Exception('Could not validate apiUser');
            }
        }
        
        // No exceptions thrown so we have validated and set relevant class properties - set the class parameter of connected to true return success
        $this->connected = true;
        return true;
    }




/**
 *      Validates a FlickrAPI key, setting as class property if valid
 * 
 *      @param string   $apiKey       The FlickrAPI key used to authenticate for calls
 * 
 *      @return boolean               Representing whether the value have been set
 */
    public function setFlickrApiKey($apiKey){
        // If apiKey is blank, throw an exception
        if(trim($apiKey) == ""){
            throw new InvalidArgumentException('No apiKey provided');
        }

        // Set the apiKey as a class property
        $this->apiKey = trim($apiKey);

        // Make a call to to the FlickrAPI to check the apiKey
        try{
            $flickrApiCallOutput = $this->makeFlickrAPICall(
                array(
                    "method" => "flickr.test.echo",
                    "arguments" => array()
                )
            );
        }
        catch(Exception $e){
            $this->apiKey = NULL;
            throw new Exception('Could not validate apiKey');
        }

        // Check the apiKey worked correctly, if not then unset the class property and throw an exception
        if(!isset($flickrApiCallOutput["stat"]) || $flickrApiCallOutput["stat"] != "ok" && !isset($flickrApiCallOutput["api_key"]["_content"]) || $flickrApiCallOutput["api_key"]["_content"] != $this->apiKey){
            $this->apiKey = NULL;
            throw new Exception('Could not set apiKey, potentially invalid - check at https://www.flickr.com/services/apps/by/me');
        }

        // No exceptions thrown so we have validated and set class property - return success
        return true;
    }




/**
 *      Validates a FlickrAPI userId, setting as class property if valid
 * 
 *      @param string   $apiUserId    The FlickrAPI userId used to dertermine the caller
 * 
 *      @return boolean               Representing whether the value have been set
 */
    public function setFlickrApiUser($apiUserId){
        // If apiUserId is blank, throw an exception
        if(trim($apiUserId) == ""){
            throw new InvalidArgumentException('No apiUserId provided');
        }

        // Set the apiUserId as a class property
        $this->apiUserId = trim($apiUserId);

        // Make a call to to the FlickrAPI to check the apiUserId
        try{
            $flickrApiCallOutput = $this->makeFlickrAPICall(
                array(
                    "method" => "flickr.profile.getProfile",
                    "arguments" => array(
                        "user_id" => $this->apiUserId
                    )
                )
            );
        }
        catch(Exception $e){
            $this->apiUserId = NULL;
            throw new Exception('Could not validate userId');
        }

        // Check the apiUserId worked correctly, if not then unset the class property and throw an exception
        if(!isset($flickrApiCallOutput["stat"]) || $flickrApiCallOutput["stat"] != "ok" && !isset($flickrApiCallOutput["profile"]["nsid"]) || $flickrApiCallOutput["profile"]["nsid"] != $this->apiUserId){
            $this->apiUserId = NULL;
            throw new Exception('Could not set userId, potentially invalid - check at https://www.flickr.com/me');
        }
        
        // No exceptions thrown so we have validated and set class property - return success
        return true;
    }




/**
 *      Builds and makes calls to the FlickrAPI
 * 
 *      @param array   $callVars   Array of variables to be used within the call including: method - the method of the API to call, arguments - additional arguments to add to the call
 * 
 *      @return object             Object built from the JSON retuned by the call
 */
    public function makeFlickrAPICall($callVars){
        // Ensure we have a base url set for the FlickrAPI
        if(!isset($this->apiBaseUrl) || trim($this->apiBaseUrl) == ""){
            throw new Exception('No apiBaseUrl set');
        }

        // Ensure we have an API key set for the FlickrAPI
        if(!isset($this->apiKey) || trim($this->apiKey) == ""){
            throw new Exception('No apiKey set');
        }

        // Ensure that we have a method provided
        if(!isset($callVars["method"]) || trim($callVars["method"]) == ""){
            throw new InvalidArgumentException('No method provided');
         }

         // Ensure that we have arguments provided
        if(!isset($callVars["arguments"]) || !is_array($callVars["arguments"])){
            throw new InvalidArgumentException('No arguments provided');
         }

        // Build the URL for the API call - starting with the base url for the FlickrAPI
        $apiCallUrl = $this->apiBaseUrl;

        // Add the method required
        $apiCallUrl .= "?method=" . trim($callVars["method"]);

        // Add the API key
        $apiCallUrl .= "&api_key=" . $this->apiKey;

        // Requesting JSON as a format for returned data
        $apiCallUrl .= "&format=json";

        // We have no need for a callback
        $apiCallUrl .= "&nojsoncallback=1";

        // Add the arguments provided
        forEach($callVars["arguments"] as $argumentKey => $argumentVal){
            $apiCallUrl .= "&" . trim($argumentKey) . "=" . trim($argumentVal);
        }

        // Try to make the call
        try{
            $apiCallResponse = file_get_contents($apiCallUrl);
        }
        catch(Exception $e) {
            throw new Exception("Could not call FlickrAPI");
        }

        // Ensure the response is JSON
        try{
            $apiCallResponseData = json_decode($apiCallResponse, true);
        }
        catch(Exception $e) {
            throw new Exception("Could not parse FlickrAPI response as JSON");
        }

        // Return the response
        return $apiCallResponseData;
    }
}
?>