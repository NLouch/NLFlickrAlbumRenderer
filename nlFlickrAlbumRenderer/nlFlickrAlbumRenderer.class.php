<?php
require_once("nlFlickrApi.class.php");


/**
 * Class to consume the nlFlickrAPI
 *
 * @package nlFlickrAlbumRenderer
 */
class nlFlickrAlbumRenderer{

// Properties

    private $nlFlickrApi = NULL;
    private $cacheFolder = NULL;




// Methods

/**
 *      Constructor function when instantiating the class
 *      Tries to establish a connection to the FlickrAPI, validating inputs of apiKey and optionally apiUserId
 * 
 *      @param string   $apiKey       The FlickrAPI key used to authenticate for calls
 *      @param string   $apiUserId    The FlickrAPI userId used to dertermine the caller
 *      @param string   $cacheFolder  The path to use for caching of data
 * 
 *      @return boolean               Representing whether the connection has been validated and set
 */
    public function __construct($apiKey = NULL, $apiUserId = NULL, $cacheFolder = NULL){
        // Instantiate the nlFlickrConnector class, validating it also
        try{
            $nlFlickrApi = new nlFlickrApi(trim($apiKey), trim($apiUserId));
        }
        catch(Exception $e){
            print "Could not connect to FlickrAPI: " . $e->getMessage();
            return false;
        }

        // If the class is not successfully instantiated, throw an exception
        if(!isset($nlFlickrApi)){
            throw new Exception('Could not connect to FlickrAPI');
        }

        // Set the cache location and create it if necessary
        unset($this->cacheFolder);
        if(isset($cacheFolder)){
            if(!is_dir($cacheFolder)){
                mkdir($cacheFolder);
            }
            if(is_dir($cacheFolder)){
                $this->cacheFolder = $cacheFolder;
            }
        }

        // The class is successfully validated so store it as a class parameter and return success
        $this->nlFlickrApi = $nlFlickrApi;
        return true;
    }




/**
 *      Function to determine whether we have a working connection to the FlickrAPI
 *
 *      @return boolean               Representing whether the connection exists
 */
    public function isConnected(){
        // If the class parameter of the api connection is not set or connected, return false
        return $this->nlFlickrApi->isConnected();
    }




/**
 *      Function to retrieve a full photoset from the FlickrApi, including photoset details, photos, photo info and photo sizes
 *
 *      @param string   $photosetId      The Id of the photoset
 * 
 *      @return array                    Object containing the full photoset
 */
    function getFullPhotosetFromApi($photosetId){
        // Create an array to hold the data
        $fullPhotoset = NULL;

        // Get the photoset
        $photosetInfo = $this->nlFlickrApi->getPhotosetInfo($photosetId);
        If(isset($photosetInfo["photoset"]["id"]) && $photosetInfo["photoset"]["id"] == $photosetId){
            $fullPhotoset = $photosetInfo["photoset"];
            
            // Get the photos
            $photosetPhotos = $this->nlFlickrApi->getPhotosetPhotos($fullPhotoset["id"]);
            If(isset($photosetPhotos[0]["id"])){
                $fullPhotoset["photos"] = $photosetPhotos;

                // Loop through the photos
                ForEach($fullPhotoset["photos"] as $fullPhotosetPhotoKey => $fullPhotosetPhotoVal){

                    // Get the info for the photo
                    $photosetPhotoInfo = $this->nlFlickrApi->getPhotoInfo($fullPhotosetPhotoVal["id"],$fullPhotosetPhotoVal["secret"]);
                    If(isset($photosetPhotoInfo["photo"]["id"]) && $photosetPhotoInfo["photo"]["id"] == $fullPhotosetPhotoVal["id"]){
                        $fullPhotoset["photos"][$fullPhotosetPhotoKey]["info"] = $photosetPhotoInfo["photo"];

                        // Get the sizes for the photo
                        $photosetPhotoSizes = $this->nlFlickrApi->getPhotoSizes($fullPhotosetPhotoVal["id"]);
                        If(isset($photosetPhotoSizes["sizes"])){
                            $fullPhotoset["photos"][$fullPhotosetPhotoKey]["sizes"] = $photosetPhotoSizes["sizes"];
                        }
                    }
                }
            }
        }

        // Convert the array to an object
        $fullPhotoset = json_decode(json_encode($fullPhotoset), FALSE);

        // Return the output
        return $fullPhotoset;
    }

    function getFullPhotoset($photosetId){
        // Check if we have cache
        if(isset($this->cacheFolder)){
            if(file_exists($this->cacheFolder . "photoset_" . $photosetId . ".json")){
                try{
                    // Load the cache temporarily
                    $cachePhotosetData = json_decode(file_get_contents($this->cacheFolder . "photoset_" . $photosetId . ".json"));
                    
                    // Make sure it matches the ID
                    if(isset($cachePhotosetData->id) && $cachePhotosetData->id == trim($photosetId)){

                        // Load the photoset from the FlickrAPI
                        $photosetInfoFromApi = $this->nlFlickrApi->getPhotosetInfo($photosetId);

                        // Compare the two dates to ensure the cache is still valid
                        if(isset($photosetInfoFromApi["photoset"]["date_update"]) && isset($cachePhotosetData->date_update) && $cachePhotosetData->date_update == $photosetInfoFromApi["photoset"]["date_update"]){
                            
                            // Assign the temporary cache data into the required output data
                            $photosetData = $cachePhotosetData;
                        }
                    }
                }
                catch(Exception $e){
                    // Delete the cache file as it seems invalid
                    unlink($this->cacheFolder . "photoset_" . $photosetId . ".json");
                    unset($photosetData);
                }
            }
        }

        // If we don't have the data from cache
        if(!isset($photosetData)){
            try{
                // Get the data from the API
                $apiPhotosetData = $this->getFullPhotosetFromApi($photosetId);

                // Ensure it matches the ID
                if(isset($apiPhotosetData->id) && $apiPhotosetData->id == trim($photosetId)){
                    
                    // Cache the data
                    if(isset($this->cacheFolder)){
                        file_put_contents($this->cacheFolder . "photoset_" . $photosetId . ".json",json_encode($apiPhotosetData,JSON_PRETTY_PRINT));
                    }

                    // Assign the temporary cache data into the required output data
                    $photosetData = $apiPhotosetData;
                }
            }
            catch(Exception $e){
                unset($photosetData);
            }
        }

        // Return the output
        return $photosetData;
    }
}