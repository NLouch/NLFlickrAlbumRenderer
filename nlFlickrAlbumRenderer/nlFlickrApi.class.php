<?php
require_once("nlFlickrConnector.class.php");


/**
 * Class to make flickr calls
 *
 * @package nlFlickrApi
 */
class nlFlickrApi{
    
// Properties

    private $flickrApiConnection = NULL;




// Methods

/**
 *      Constructor function when instantiating the class
 *      Tries to establish a connection to the FlickrAPI, validating inputs of apiKey and optionally apiUserId
 * 
 *      @param string   $apiKey       The FlickrAPI key used to authenticate for calls
 *      @param string   $apiUserId    The FlickrAPI userId used to dertermine the caller
 * 
 *      @return boolean               Representing whether the connection has been validated and set
 */
    public function __construct($apiKey = NULL, $apiUserId = NULL){
        // Instantiate the nlFlickr class ensuring it connects
        try{
            $flickrApiConnection = new nlFlickrConnector($apiKey,$apiUserId);
        }
        catch(Exception $e){
            print "Exception caught: " . $e->getMessage();
        }

        // If the class is not successfully instantiated, throw an exception
        if(!isset($flickrApiConnection)){
            throw new Exception('Could not connect to FlickrAPI');
        }

        // The class is successfully validated so store it as a class parameter and return success
        $this->flickrApiConnection = $flickrApiConnection;
        return true;
    }




/**
 *      Function to determine whether we have a working connection to the FlickrAPI
 *
 *      @return boolean               Representing whether the connection exists
 */
    public function isConnected(){
        // If the class parameter of the api connection is not set or connected, return false
        if(!isset($this->flickrApiConnection->connected) || $this->flickrApiConnection->connected != true){
            return false;
        }
        // The class parameter of the api connection is set and connected, return success
        return true;
    }




/**
 *      Function to retrieve a photoset's info
 *
 *      @param string   $photosetId   The ID of the photoset
 * 
 *      @return object                Object containing the photoset info
 */
    public function getPhotosetInfo($photosetId){
        // Make a call to to the FlickrAPI to retrieve the photoset info
        try{
            $photosetInfo = $this->flickrApiConnection->makeFlickrAPICall(array(
                "method" => "flickr.photosets.getInfo",
                "arguments" => array(
                    "photoset_id" => trim($photosetId)
                )
            ));
        }
        catch(Exception $e){
            throw new Exception('Could not retrieve photoset info');
        }

        // Check the call worked correctly, if not then throw an exception
        if(!isset($photosetInfo["stat"]) || $photosetInfo["stat"] != "ok" || !isset($photosetInfo["photoset"]["id"]) || $photosetInfo["photoset"]["id"] != trim($photosetId)){
            throw new Exception('Could not retrieve photoset info');
        }
        
        // No exceptions thrown so return the photoset info
        return $photosetInfo;
    }




/**
 *      Function to retrieve a photoset's photos
 *
 *      @param string   $photosetId   The ID of the photoset
 * 
 *      @return array                 Array containing the photoset photos
 */
    public function getPhotosetPhotos($photosetId){

        // Set variables to hold the data needed for paged calls
        $photosPerCall = 50;
        $pageCalled = 0;
        $allPhotosInPhotoset = array();
        $keepMakingMoreCalls = true;

        // Make repeated calls to the FlickrApi to get the curent page's worth of photos
        do{
            // Increment the page counter
            $pageCalled++;

            // Make a call to to the FlickrAPI to retrieve the photoset photos
            try{
                $photosetInfo = $this->flickrApiConnection->makeFlickrAPICall(array(
                    "method" => "flickr.photosets.getPhotos",
                    "arguments" => array(
                        "photoset_id" => trim($photosetId),
                        "per_page" => $photosPerCall,
                        "page" => $pageCalled
                    )
                ));
            }
            catch(Exception $e){
                throw new Exception('Could not retrieve photoset photos');
            }

            // Check the call worked correctly, if not then throw an exception
            if(!isset($photosetInfo["stat"]) || $photosetInfo["stat"] != "ok" || !isset($photosetInfo["photoset"]["id"]) || $photosetInfo["photoset"]["id"] != trim($photosetId)){
                throw new Exception('Could not retrieve photoset photos');
            }

            // Call worked so page through the photos returned and add them to the total photos
            ForEach($photosetInfo["photoset"]["photo"] as $photo){
                $allPhotosInPhotoset[] = $photo;
            }

            // Do we need to make more calls?
            if(sizeOf($allPhotosInPhotoset) >= $photosetInfo["photoset"]["total"]){
                $keepMakingMoreCalls = false;
            }
        }
        while($keepMakingMoreCalls == true);

        // No exceptions thrown so return the photoset info
        return $allPhotosInPhotoset;
    }



/**
 *      Function to retrieve a photos info
 *
 *      @param string   $photoId      The Id of the photo
 *      @param string   $photoSecret  The Secret of the photo - allows sharing
 * 
 *      @return array                 Object containing the photo info
 */
    public function getPhotoInfo($photoId, $photoSecret){
        // Make a call to to the FlickrAPI to retrieve the photoset info
        try{
            $photoInfo = $this->flickrApiConnection->makeFlickrAPICall(array(
                "method" => "flickr.photos.getInfo",
                "arguments" => array(
                    "photo_id" => trim($photoId),
                    "secret" => trim($photoSecret)
                )
            ));
        }
        catch(Exception $e){
            throw new Exception('Could not retrieve photo info');
        }


        // Check the call worked correctly, if not then throw an exception
        if(!isset($photoInfo["stat"]) || $photoInfo["stat"] != "ok" || !isset($photoInfo["photo"]["id"]) || $photoInfo["photo"]["id"] != trim($photoId)){
            throw new Exception('Could not retrieve photo info');
        }
        
        // No exceptions thrown so return the photoset info
        return $photoInfo;
    }




/**
 *      Function to retrieve a photos sizes
 *
 *      @param string   $photoId      The Id of the photo
 * 
 *      @return array                 Object containing the photo sizes
 */
    public function getPhotoSizes($photoId){
        // Make a call to to the FlickrAPI to retrieve the photoset info
        try{
            $photoSizes = $this->flickrApiConnection->makeFlickrAPICall(array(
                "method" => "flickr.photos.getSizes",
                "arguments" => array(
                    "photo_id" => trim($photoId)
                )
            ));
        }
        catch(Exception $e){
            throw new Exception('Could not retrieve photo sizes');
        }

        // Check the call worked correctly, if not then throw an exception
        if(!isset($photoSizes["stat"]) || $photoSizes["stat"] != "ok"){
            throw new Exception('Could not retrieve photo sizes');
        }
        
        // No exceptions thrown so return the photoset info
        return $photoSizes;
    }

}
?>