<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Gayan Chathuranga
 * @link   http://cits.lk
 */

class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }


    /* ------------- `s00_01_member ` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($name, $email, $password, $tel, $picture, $longit, $latit ) {

        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);

            // Generating API key
            $api_key = $this->generateApiKey();

           
            //7.073033, 79.996028


            // insert query
            $stmt = $this->conn->prepare("INSERT INTO s00_01_member (
                                                                     first_name, 
                                                                     last_name, 
                                                                     email, 
                                                                     password, 
                                                                     contact_no, 
                                                                     picture,                                                                     
                                                                     api_key, 
                                                                     activation_code,
                                                                     forgotten_password_code,
                                                                     forgotten_password_time,
                                                                     remember_code,                                                                     
                                                                     status,
                                                                     created_at,
                                                                     last_login
                                                                     ) values
                                                                    (?, NULL ,? ,? ,? ,? , ?, NULL, NULL, NULL, NULL, 1, date('y:m:d') , date('y:m:d'))");

            $stmt->bind_param("ssssss",$name, $email, $password_hash, $tel,$picture, $api_key);

            $result = $stmt->execute();

            $stmt->close(); 

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin($email, $password) {
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password FROM s00_01_member WHERE email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
        $stmt = $this->conn->prepare("SELECT id from s00_01_member WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT id, first_name, email, contact_no,  api_key, picture,  status, created_at  FROM s00_01_member WHERE email = ?");        
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
             //$user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($id, $first_name, $email, $contact_no, $api_key, $picture, $status, $created_at);
            $stmt->fetch();
            $user = array();
            $user["id"] = $id;
            $user["name"] = $first_name;
            $user["email"] = $email;
            $user["contact_no"] = $contact_no;
            $user["api_key"] = $api_key;
            $user["picture"] = $picture;
            $user["status"] = $status;
            $user["created_at"] = $created_at;

            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM s00_01_member WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id FROM s00_01_member WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        if ($stmt->execute()) {
            $stmt->bind_result($user_id);
            $stmt->fetch();
            // TODO
            // $user_id = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user_id;
        } else {
            return NULL;
        }
    }

    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id from s00_01_member WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }




    /* ------------- `s00_02_locations ` table method ------------------ */
    /**
     * Creating new Location
     * @param String $name Shop Full Name
     * @param Decimal $longit longitiude value
     * @param Decimal $latit latitude value
     * @param Int $contact_no Location Contact No
     * @param Decimal $open_hours Open Hours
     * @param Decimal $closed_hours Closed Hours
     * @param Tinyint $availability Location Availability
     */
    public function createLocation($name, $longit, $latit,$contact_no,$open_hours, $closed_hours, $availability){
    }

    /**
     * Fetching Shops within radium Kilometers of 5K
     * @param Decimal $sourceLon  User longit
     * @param Decimal $sourceLat  User latit
     * @param Decimal $radiusKm   User Radius Kilometers to fetch
     */
    public function getLocations($sourceLat='',$sourceLon='',$radiusKm  = 5){

        require_once 'GoogleMap.php';
        $location = array();
        $proximity = GoogleMap::mathGeoProximity($sourceLat, $sourceLon, $radiusKm);        

        $SQL = "SELECT * FROM   s00_02_locations WHERE  (latit BETWEEN " . number_format($proximity['latitudeMin'], 12, '.', '') . "
                                    AND " . number_format($proximity['latitudeMax'], 12, '.', '') . ")
                                    AND (longit BETWEEN " . number_format($proximity['longitudeMin'], 12, '.', '') . "
                                    AND " . number_format($proximity['longitudeMax'], 12, '.', '') . ") ";


        // $SQL = "SELECT * , ( 3959 * ACOS( COS( RADIANS( 6.843419 ) ) * COS( RADIANS(  $sourceLat ) ) * COS( RADIANS( $sourceLon ) - RADIANS( 79.957329 ) ) + SIN( RADIANS( 6.843419 ) ) * SIN( RADIANS( $sourceLat ) ) ) ) AS distance
        //         FROM s00_02_locations
        //         WHERE  `shop_code` =  '$shop_code'
        //         HAVING distance < $radiusKm";
    
       $rslt = mysqli_query($this->conn,$SQL);
       while($row = mysqli_fetch_assoc($rslt)){
            $objLocation = new stdClass();
            $objLocation->shop_code  = $row['shop_code'];
            $objLocation->name = $row['name'];
            $objLocation->description = $row['description'];
            $objLocation->address = $row['address'];
            $objLocation->featured = $row['featured'];
            $objLocation->longit = $row['longit'];
            $objLocation->latit = $row['latit'];
            $objLocation->contact_no = $row['contact_no'];
            $objLocation->open_hours = $row['open_hours'];
            $objLocation->closed_hours = $row['closed_hours'];
            $objLocation->availability = $row['availability'];
            $objLocation->categories = $this->getLocationCategories($row['shop_code']);
            $objLocation->created_at  = $row['created_at'];
            $objLocation->saving_upto  = $row['saving_upto'];
            $objLocation->images = $this->getImages($row['shop_code'],'s00_02_locations');

            array_push($location, $objLocation);
        }
        return $location;      
    }

    /**
     * Fetch Nearest Locations
     * @param sourceLat Decimal
     * @param sourceLon Decimal
     * @param radiusKm (optional)
     */
    public function fetchNearestLocations($sourceLat, $sourceLon,$radiusKm){
        require_once 'GoogleMap.php';
        $locations = array();
        $proximity = GoogleMap::mathGeoProximity($sourceLat, $sourceLon, $radiusKm);        

        $SQL = "SELECT * FROM s00_02_locations WHERE  (latit BETWEEN " . number_format($proximity['latitudeMin'], 12, '.', '') . "
                                    AND " . number_format($proximity['latitudeMax'], 12, '.', '') . ")
                                    AND (longit BETWEEN " . number_format($proximity['longitudeMin'], 12, '.', '') . "
                                    AND " . number_format($proximity['longitudeMax'], 12, '.', '') . ")";

        // $SQL = "SELECT * , ( 3959 * ACOS( COS( RADIANS( 6.843419 ) ) * COS( RADIANS(  $sourceLat ) ) * COS( RADIANS( $sourceLon ) - RADIANS( 79.957329 ) ) + SIN( RADIANS( 6.843419 ) ) * SIN( RADIANS( $sourceLat ) ) ) ) AS distance
        //         FROM s00_02_locations
        //         WHERE  `shop_code` =  '$shop_code'
        //         HAVING distance < $radiusKm";

        $slt = mysqli_query($this->conn,$SQL);
        while ($row = mysqli_fetch_assoc($rslt)) {
            $objLocation = new stdClass();
            $objLocation->shop_code  = $row['shop_code'];
            $objLocation->name = $row['name'];
            $objLocation->description = $row['description'];
            $objLocation->address = $row['address'];
            $objLocation->featured = $row['featured'];
            $objLocation->longit = $row['longit'];
            $objLocation->latit = $row['latit'];
            $objLocation->contact_no = $row['contact_no'];
            $objLocation->open_hours = $row['open_hours'];
            $objLocation->closed_hours = $row['closed_hours'];
            $objLocation->availability = $row['availability'];
            $objLocation->categories = $this->getLocationCategories($row['shop_code']);
            $objLocation->item_categories  = $this->getLocationCategories($row['shop_code'],'item');
            $objLocation->created_at  = $row['created_at'];
            $objLocation->saving_upto  = $row['saving_upto'];
            $objLocation->images = $this->getImages($row['shop_code'],'s00_02_locations');

            array_push($location, $objLocation);
        }

        return $locations;
    }

    /**
     * Fetching Location Detail
     * @param String $location  Location ID
     */
    public function getLocationInfo($shop_code){
        require_once 'GoogleMap.php';
        $location = array();     

        $SQL = "SELECT * FROM s00_02_locations WHERE  `shop_code` = '$shop_code'";
    
           $rslt = mysqli_query($this->conn,$SQL);
           while($row = mysqli_fetch_assoc($rslt)){
                $objLocation = new stdClass();
                $objLocation->shop_code  = $row['shop_code'];
                $objLocation->name = $row['name'];
                $objLocation->description = $row['description'];
                $objLocation->address = $row['address'];
                $objLocation->featured = $row['featured'];
                $objLocation->longit = $row['longit'];
                $objLocation->latit = $row['latit'];
                $objLocation->contact_no = $row['contact_no'];
                $objLocation->open_hours = $row['open_hours'];
                $objLocation->closed_hours = $row['closed_hours'];
                $objLocation->availability = $row['availability'];
                $objLocation->categories     = $this->getLocationCategories($row['shop_code']);
                $objLocation->item_categories  = $this->getLocationCategories($row['shop_code'],'item');
                $objLocation->created_at  = $row['created_at'];
                $objLocation->saving_upto  = $row['saving_upto'];
                $objLocation->images = $this->getImages($row['shop_code'],'s00_02_locations');

                array_push($location, $objLocation);
            }
        return $location;
    }

    /**
     * Fetching Location Detail
     * @param String $location  Location ID
     */
    public function getLocation($shop_code){
        require_once 'GoogleMap.php';
        $location = array();     

        $SQL = "SELECT * FROM s00_02_locations WHERE  `shop_code` = '$shop_code'";
    
           $rslt = mysqli_query($this->conn,$SQL);
           while($row = mysqli_fetch_assoc($rslt)){
                $objLocation = new stdClass();
                //$objLocation->categories     = $this->getLocationCategories($row['shop_code']);
                $objLocation->categories  = $this->getLocationCategories($row['shop_code'],'item');
                

                array_push($location, $objLocation);
            }
        return $location;
    }

    /**
     * Fetching Location Detail
     * @param String $location  Location ID
     */
    public function getLocationMinimInfo($shop_code){
        require_once 'GoogleMap.php';
        $location = array();     

        $SQL = "SELECT * FROM s00_02_locations WHERE  `shop_code` = '$shop_code'";
    
           $rslt = mysqli_query($this->conn,$SQL);
           while($row = mysqli_fetch_assoc($rslt)){
                $objLocation = new stdClass();
                $objLocation->shop_code  = $row['shop_code'];
                $objLocation->name = $row['name'];
                $objLocation->description = $row['description'];
                $objLocation->address = $row['address'];
                $objLocation->featured = $row['featured'];
                $objLocation->longit = $row['longit'];
                $objLocation->latit = $row['latit'];
                $objLocation->contact_no = $row['contact_no'];
                $objLocation->open_hours = $row['open_hours'];
                $objLocation->closed_hours = $row['closed_hours'];
                $objLocation->availability = $row['availability'];
                // $objLocation->categories     = $this->getLocationCategories($row['shop_code']);
                // $objLocation->item_categories  = $this->getLocationCategories($row['shop_code'],'item');
                $objLocation->created_at  = $row['created_at'];
                $objLocation->saving_upto  = $row['saving_upto'];
                $objLocation->images = $this->getImages($row['shop_code'],'s00_02_locations');

                array_push($location, $objLocation);
            }
        return $location;
    }


    /**
     * Check if the location is in neartest
     * @param String  location code
     * @param Decimal Source Latitude
     * @param Decimal Source Longatiude
     * @param Int Radius KM
     */
    public function isNearest($shop_code, $sourceLat, $sourceLon, $radiusKm = 5){
        require_once 'GoogleMap.php';
        $location = array();
        $proximity = GoogleMap::mathGeoProximity($sourceLat, $sourceLon, $radiusKm);       

        $SQL = "SELECT * FROM  `s00_02_locations` WHERE  `shop_code` =  '$shop_code'
                                    AND (latit BETWEEN " . number_format($proximity['latitudeMin'], 12, '.', '') . "
                                    AND " . number_format($proximity['latitudeMax'], 12, '.', '') . ")
                                    AND (longit BETWEEN " . number_format($proximity['longitudeMin'], 12, '.', '') . "
                                    AND " . number_format($proximity['longitudeMax'], 12, '.', '') . ") ";

        // $SQL = "SELECT * , ( 3959 * ACOS( COS( RADIANS( 6.843419 ) ) * COS( RADIANS(  $sourceLat ) ) * COS( RADIANS( $sourceLon ) - RADIANS( 79.957329 ) ) + SIN( RADIANS( 6.843419 ) ) * SIN( RADIANS( $sourceLat ) ) ) ) AS distance
        //         FROM s00_02_locations
        //         WHERE  `shop_code` =  '$shop_code'
        //         HAVING distance < $radiusKm";

        $rslt = mysqli_query($this->conn,$SQL);                           
        $num_rows = mysqli_num_rows($rslt);

        return ($num_rows) ? $num_rows : 0;
    }


    


    /* ------------- `m00_03_images` table method ------------------ */
    /**
     * Fetching Location Images
     * @param String  location code
     */
    public function getImages($record_id, $table){
       
       $image = array();
       $num_rows = 0;

       $SQL = "SELECT * FROM   m00_03_images WHERE `table` = '$table' AND `record_id` = '$record_id'";

       $rslt = mysqli_query($this->conn,$SQL);
       $num_rows = mysqli_num_rows($rslt);

       while($row = mysqli_fetch_assoc($rslt)){
         $objImg = new stdClass();
         $objImg->image = FILE_URL.$row['image'];
         $objImg->display_order = $row['display_order']; 

         array_push($image, $objImg);

       }
       return $image;
    }




   /* ------------- `s00_03_location_categories ` table method ------------------ */

     /**
     * Fetching Location Categories
     * @param String  location code
     */
    public function getLocationCategories($location,$type='location'){
       
       $data = array();
       $num_rows = 0;

       $SQL = "SELECT * FROM  s00_03_location_categories WHERE `location` = '$location' AND type= '$type'";

       $rslt = mysqli_query($this->conn,$SQL);
       $num_rows = mysqli_num_rows($rslt);

       while($row = mysqli_fetch_assoc($rslt)){
         $objCategory = new stdClass();
         $objCategory->categoryInfo = $this->getLocationCategoryInfo($row['category']);
         $objCategory->plans = $this->getLocationPlans($location,$row['category']);
         $objCategory->items = $this->getLocationItems($location,$row['category']);

         array_push($data, $objCategory);

       }
       return $data;

    }

    /**
     * Fetching All Location Categories
     * @param Decimal Latitude
     * @param Decimal Longitude
     * @param Int Category ID
     * @param Int Radius KM
    */
    public function getCategoryLocations($lat,$lon,$category_id,$type='location'){
       $data = array();
       $num_rows = 0;

       $SQL = "SELECT * FROM  s00_03_location_categories WHERE category = $category_id AND type= '$type'";

       $rslt = mysqli_query($this->conn,$SQL);
       $num_rows = mysqli_num_rows($rslt);

       while($row = mysqli_fetch_assoc($rslt)){
         $objLocations = new stdClass();
         if($lat != NULL || $lon != NULL){
            if($this->isNearest($row['location'], $lat, $lon)){
                $objLocations->info = $this->getLocationMinimInfo($row['location']);
                array_push($data, $objLocations);
            }  
         }else{ //if the nearest location list is empty push default list
                $objLocations->info = $this->getLocationMinimInfo($row['location']);
                array_push($data, $objLocations);
         }   
       }

         return $data;
    }





   /* ------------- `ms00_04_category ` table methods ------------------ */

    /**
     * Fetching All Location Categories
     * @param Int Category ID
     */
    public function getLocationCategoryInfo($category_id){
       $data = array();
       $num_rows = 0;

       $SQL = "SELECT * FROM  s00_04_category WHERE id = $category_id";

       $rslt = mysqli_query($this->conn,$SQL);
       $num_rows = mysqli_num_rows($rslt);

       while($row = mysqli_fetch_assoc($rslt)){
         $objCategory = new stdClass();
         $objCategory->id = $row['id'];
         $objCategory->category_name = $row['category_name'];
         $objCategory->category_description = $row['category_description']; 
         $objCategory->sub_category_description = $row['sub_category_description']; 
         $objCategory->status  = $row['status']; 
         $objCategory->display_order  = $row['display_order'];
         $objCategory->saving_upto  = $row['saving_upto'];
         $objCategory->images = $this->getImages($row['id'],'s00_04_category');

         array_push($data, $objCategory);
       }
       return $data;
    }


    /**
     * Fetching Category By ID
     * @param Int Category ID
     * @param Int Parent
     */
    public function getCategoryById($category_id,$parent=0,$type='location'){
       
       $data = array();
       $num_rows = 0;

       $SQL = "SELECT * FROM  s00_04_category WHERE id = $category_id AND parent_category= $parent AND type= '$type'";

       $rslt = mysqli_query($this->conn,$SQL);
       $num_rows = mysqli_num_rows($rslt);

       while($row = mysqli_fetch_assoc($rslt)){
         $objCategory = new stdClass();
         $objCategory->id = $row['id'];
         $objCategory->category_name = $row['category_name'];
         $objCategory->category_description = $row['category_description']; 
         $objCategory->sub_category_description = $row['sub_category_description']; 
         $objCategory->status  = $row['status']; 
         $objCategory->display_order  = $row['display_order'];
         $objCategory->saving_upto  = $row['saving_upto'];
         $objCategory->images = $this->getImages($row['id'],'s00_04_category');

         array_push($data, $objCategory);

       }
       return $data;

    }


    /**
     * Fetching Category By ID
     * @param Int Category ID
     */
    public function getCategoryList($parent=0,$type='location'){
       
       $data = array();
       $num_rows = 0;

       $SQL = "SELECT * FROM  s00_04_category WHERE parent_category = $parent AND type= '$type'";

       $rslt = mysqli_query($this->conn,$SQL);
       $num_rows = mysqli_num_rows($rslt);

       while($row = mysqli_fetch_assoc($rslt)){

         $objCategory = new stdClass();
         $objCategory->id = $row['id'];
         $objCategory->category_name = $row['category_name'];
         $objCategory->category_description = $row['category_description']; 
         $objCategory->sub_category_description = $row['sub_category_description']; 
         $objCategory->status  = $row['status']; 
         $objCategory->display_order  = $row['display_order'];
         $objCategory->saving_upto  = $row['saving_upto'];
         $objCategory->images = $this->getImages($row['id'],'s00_04_category');

         array_push($data, $objCategory);

       }
       return $data;

    }





    /* ------------- `m00_02_item ` table method ------------------ */ 

    /**
     * Fetching Items to the given Location
     * @param Int Location
     */
    public function getLocationItems($location,$category=0){
       $data = array();
       $num_rows = 0;

       $SQL = "SELECT * FROM  m00_02_item  WHERE `location` = '$location'";

       if($category){
        $SQL .= " AND category_id = $category";
       }

       $rslt = mysqli_query($this->conn,$SQL);
       $num_rows = mysqli_num_rows($rslt);

       while($row = mysqli_fetch_assoc($rslt)){
         $objItems = new stdClass();
         $objItems->item_code = $row['item_code'];
         $objItems->name = $row['name'];
         $objItems->description = $row['description'];
         $objItems->quantity     = $row['quantity'];
         $objItems->reorder_level = $row['reorder_level'];
         $objItems->price = $row['price'];
         $objItems->category = $row['category'];
         $objItems->location = $row['location'];
         $objItems->images = $this->getImages($row['item_code'],'m00_02_item');
         array_push($data, $objItems);

       }
       return $data;
    }

    /**
     * Fetching Single Item data to the given Location
     * @param Int Location
     * @param Char Item Code
     */
    public function getLocationItem($item_code, $location){
       $data = array();
       $num_rows = 0;

       $SQL = "SELECT * FROM  m00_02_item  WHERE `location` = '$location' AND `item_code` = '$item_code'";

       $rslt = mysqli_query($this->conn,$SQL);
       $num_rows = mysqli_num_rows($rslt);

       while($row = mysqli_fetch_assoc($rslt)){
         $objItems = new stdClass();
         $objItems->item_code = $row['item_code'];
         $objItems->name = $row['name'];
         $objItems->description = $row['description'];
         $objItems->quantity     = $row['quantity'];
         $objItems->reorder_level = $row['reorder_level'];
         $objItems->price = $row['price'];
         $objItems->category = $row['category'];
         $objItems->location = $row['location'];
         $objItems->images = $this->getImages($row['item_code'],'m00_02_item');

         array_push($data, $objItems);

       }
       return $data;
    }


    
     
  

   /* ------------- `m00_05_plan ` table method ------------------ */
   public function getLocationPlans($location,$category){

       $data = array();
       $num_rows = 0;

       $SQL = "SELECT * FROM  m00_05_plan  WHERE `location` = '$location'";

       if($category){
        $SQL .= " AND category_id = $category";
       }

       $rslt = mysqli_query($this->conn,$SQL);
       $num_rows = mysqli_num_rows($rslt);

       while($row = mysqli_fetch_assoc($rslt)){


         $objPlan = new stdClass();
         $objPlan->plan_id = $row['plan_id'];
         $objPlan->name  = $row['name'];
         $objPlan->description = $row['description'];
         $objPlan->quantity     = $row['quantity'];
         $objPlan->price = $row['price'];
         $objPlan->frequency = $row['frequency'];
         $objPlan->start_date = $row['start_date'];
         $objPlan->end_date = $row['end_date'];
         $objPlan->location = $row['location'];
         $objPlan->category = $row['category'];
         $objPlan->saving_upto = $row['saving_upto'];
         $objPlan->status = $row['status'];
         $objPlan->images = $this->getImages($row['plan_id'],'m00_05_plan');
         $objPlan->items = $this->fetchPlanItems($row['plan_id']);
         

          array_push($data, $objPlan);

       }
       return $data;
   }

   /*
   * When Plan given return plan location info 
   */
   public function getPlanLocation($plan_id){

       $data = array();
       $num_rows = 0;

       $SQL = "SELECT location FROM  m00_05_plan  WHERE `plan_id` = '$plan_id'";


       $rslt = mysqli_query($this->conn,$SQL);
       $num_rows = mysqli_num_rows($rslt);

       while($row = mysqli_fetch_assoc($rslt)){


         $objPlan = new stdClass();
         $objPlan = $this->getLocationMinimInfo($row['location']);
                  

          array_push($data, $objPlan);

       }
       return $data;
   }

 



 /* ------------- `trn00_01_plan_items ` table method ------------------ */ 
   public function fetchPlanItems($plan_id){
        $data = array();
       $num_rows = 0;

       $SQL = "SELECT * FROM  trn00_01_plan_items WHERE `plan_id` = '$plan_id'";

       $rslt = mysqli_query($this->conn,$SQL);
       $num_rows = mysqli_num_rows($rslt);

       while($row = mysqli_fetch_assoc($rslt)){
         $objPlanItem = new stdClass();
         $objPlanItem->item_code = $row['item_code'];
         $objPlanItem->item_name = $row['item_name'];
         $objPlanItem->quantity = $row['quantity'];
         $objPlanItem->price = $row['price'];
         $objPlanItem->saving_upto = $row['saving_upto'];
         $objPlanItem->images = $this->getImages($row['plan_id'],'trn00_01_plan_items');

         array_push($data, $objPlanItem);

       }
       return $data;
   }

   public function getPlanItem($item_code){
       $data = array();
       $num_rows = 0;

       $SQL = "SELECT * FROM  trn00_01_plan_items WHERE `item_code` = '$item_code'";

       $rslt = mysqli_query($this->conn,$SQL);
       $num_rows = mysqli_num_rows($rslt);

       while($row = mysqli_fetch_assoc($rslt)){
         $objPlanItem = new stdClass();
         $objPlanItem->item_code = $row['item_code'];
         $objPlanItem->item_name = $row['item_name'];
         $objPlanItem->quantity = $row['quantity'];
         $objPlanItem->price = $row['price'];

         array_push($data, $objPlanItem);

       }
       return $data;
   }



  


   /* -------------------  m00_01_member_subscription  --------------- */
    
    /*Get Memener subscribed locations*/
    public function getMemberLocationPlans($member){
        $lcSubscriptions = array();
        $SQL = "SELECT L.name, L.description, L.shop_code, L.address, L.contact_no, L.longit, L.latit, L.open_hours, L.closed_hours, L.saving_upto, M.plan_id FROM   s00_02_locations  L
                INNER JOIN m00_05_plan P ON  L.shop_code = P.location
                INNER JOIN m00_01_member_subscription M ON P.plan_id = M.plan_id
                WHERE M.member_id = $member
                GROUP BY shop_code
        ";
    
       $rslt = mysqli_query($this->conn,$SQL);
       while($row = mysqli_fetch_assoc($rslt)){

            $objLocation = new stdClass();
            $objLocation->shop_code  = $row['shop_code'];
            $objLocation->name = $row['name'];
            $objLocation->description = $row['description'];
            $objLocation->address = $row['address'];
            $objLocation->long = $row['longit'];
            $objLocation->lat = $row['latit'];
            $objLocation->contact_no = $row['contact_no'];
            $objLocation->open_hours = $row['open_hours'];
            $objLocation->closed_hours = $row['closed_hours'];
           

            $objLocation->saving_upto  = $row['saving_upto'];
            $objLocation->images = $this->getImages($row['shop_code'],'s00_02_locations');
            $objLocation->user_plans = $this->getMemberSubscriptions($member,$row['shop_code']);

            array_push($lcSubscriptions, $objLocation);
        }
        return $lcSubscriptions;
    }

    public function getMemberSubscriptions($member_id,$location){
       $data = array();
       $num_rows = 0;

       $SQL = "SELECT DISTINCT(P.plan_id) FROM  m00_01_member_subscription M
               INNER JOIN m00_05_plan P ON M.plan_id = P.plan_id
               WHERE M.member_id = $member_id AND M.active = 1 AND P.location = '$location'
               ORDER BY P.plan_id 
               ";

       $rslt = mysqli_query($this->conn,$SQL);
       $num_rows = mysqli_num_rows($rslt);

       while($row = mysqli_fetch_assoc($rslt)){
         $objMemSubscriptions = new stdClass();

         $objMemSubscriptions->plan = $this->getPlan($row['plan_id']);
         $objMemSubscriptions->plan_items = $this->getMemberPlanItems($member_id,$row['plan_id']);
         array_push($data, $objMemSubscriptions);

       }
       return $data;
    }

    public function getMemberPlanItems($member,$plan_id){
        $data = array();
           $num_rows = 0;

           $SQL = "SELECT * FROM  m00_01_member_subscription WHERE member_id = $member AND plan_id = '$plan_id'";
          // echo $SQL; exit();
           $rslt = mysqli_query($this->conn,$SQL);
           $num_rows = mysqli_num_rows($rslt);

           while($row = mysqli_fetch_assoc($rslt)){
             $objPlanItem = new stdClass();
             $objPlanItem->subscription_id = $row['subscription_id'];
             $objPlanItem->item_code = $row['item_code'];
             $objPlanItem->name = $row['subscription'];
             $objPlanItem->images = $this->getImages($row['item_code'],'m00_02_item');
             $objPlanItem->usage = $row['usage'];       

             array_push($data, $objPlanItem);

           }
           return $data;
    }

    public function getSubscription($subscription){
        $data = array();
       $num_rows = 0;

       $SQL = "SELECT * FROM  m00_01_member_subscription WHERE subscription_id = $subscription";

       $rslt = mysqli_query($this->conn,$SQL);
       $num_rows = mysqli_num_rows($rslt);

       while($row = mysqli_fetch_assoc($rslt)){
         $objSubscriptions = new stdClass();
         $objSubscriptions->subscription = $row['subscription_id'];
         $objSubscriptions->plan_id = $row['plan_id'];
         $objSubscriptions->item_code = $row['item_code'];
         $objSubscriptions->usage = $row['usage'];
         $objSubscriptions->subscribed_date = $row['subscribed_date'];
         array_push($data, $objSubscriptions);

       }
       return $data;
    }

    




    //-------------m00_05_plan ------------------
    public function getPlan($plan_id){
        $data = array();
           $num_rows = 0;

           $SQL = "SELECT * FROM  m00_05_plan WHERE plan_id = '$plan_id'";

           $rslt = mysqli_query($this->conn,$SQL);
           $num_rows = mysqli_num_rows($rslt);

           while($row = mysqli_fetch_assoc($rslt)){
             $objPlan = new stdClass();
             $objPlan->plan_id = $row['plan_id'];
             $objPlan->name = $row['name'];
             $objPlan->description = $row['description'];
             $objPlan->price = $row['price'];
             $objPlan->quantity = $row['quantity'];
             $objPlan->frequency = $row['frequency'];
             $objPlan->category = $row['category'];


             array_push($data, $objPlan);

           }
           return $data;
    }

    public function doConsume($subscription,$member,$quantity,$item_code, $mode='TAKEAWAY', $quantity,$comment,$pickup_time,$pickup_date){
            $today = date('y:m:d H:i:s');
            $SQL = "INSERT INTO `trn00_02_order_member_subscription` (`id`, `subscrb_id`, `member_id`, `item_code`, `mode`, `order_date`, `order_status`, `quantity`, `comments`, `pickup_time`, `pickup_date`) VALUES  ('', $subscription, $member, '$item_code', '$mode' ,'$today', 'PENDING', $quantity, '$comment', '$pickup_time', '$pickup_date');";
          //  echo $SQL;exit();
            if($rslt = mysqli_query($this->conn,$SQL)){
                $SQL = "UPDATE m00_01_member_subscription  SET  `usage` = `usage` - $quantity WHERE subscription_id = $subscription";
                return $rslt = mysqli_query($this->conn,$SQL);
            }
            return false;
            
   }

    public function addReview($member, $location, $comment, $star){
            $today = date('y:m:d');
            $SQL = "INSERT INTO `trn00_03_reviews`
            (`id`,
            `added_on`,
            `added_by`,
            `subject`,
            `description`,
            `location`,
            `IP`,
            `url`,
            `rating`,
            `modified_by`,
            `modified_on`,
            `is_approved`)
            VALUES
            (
            '',
            '$today',
            $member,
            '',
            '$comment',
            '$location',
            '',
            '',
            $star,
            '',
            '',
            'Approved'
            );";

    return $rslt = mysqli_query($this->conn,$SQL);
   }

   public function getReviews($location){
       $data = array();
       $num_rows = 0;

       $SQL = "SELECT * FROM  trn00_03_reviews WHERE location = '$location'";

       $rslt = mysqli_query($this->conn,$SQL);
       $num_rows = mysqli_num_rows($rslt);

       while($row = mysqli_fetch_assoc($rslt)){
         $objLocation = new stdClass();
         $objLocation->added_on = $row['added_on'];
         $objLocation->added_by = $row['added_by'];
         $objLocation->description = $row['description'];
         $objLocation->rating = $row['rating'];
         array_push($data, $objLocation);
       }
       return $data;
   }


   public function setPasswordCode($email,$forgotten_password_code){
        $SQL = "UPDATE `s00_01_member` SET `forgotten_password_code` = $forgotten_password_code WHERE `email` = '$email'";
        return $rslt = mysqli_query($this->conn,$SQL);
   }


   public function sendeMail($email,$forgotten_password_code){
    $to      = $email;
    $subject = 'Coffee App has sent your password reset code';

    $message = "\r\n".
               'Your Password Reset Code is '.$forgotten_password_code. "\r\n" .
               'This is a temporary reset code you can use within next 48 hours. Please use this code in your Mobile App to reset your password' . "\r\n \r\n" .
               'Thank You'. "\r\n \r\n" .
               'Rights Reserved 2014'. "\r\n" .
               'CofeeApp Team.';
    $headers = 'From: noreply@cofeeapp.com' . "\r\n" .
        'Reply-To: noreply@cofeeapp.com' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    if(mail($to, $subject, $message, $headers)){
        return true;
    }
   }

   public function isMailExists($email){
    return $this->isUserExists($email);
   }

   //Order related functions
   public function addOrder($order_id,$user_id,$shop_id,$total,$discount,$vat){
            $today = date('y:m:d');
            $SQL = "INSERT INTO `cofeeapp_order`
            (`order_id`,
            `member`,
            `order_date`,
            `order_status`,
            `sub_total`,
            `grand_total`,
            `vat`,
            `discount`,
            `shop_id`)
            VALUES
            (
            '$order_id',
            $user_id,
            '$today',
            'PROCESSING',
            $total,
            $total,            
            $vat,
            $discount,
            '$shop_id'
            );";
  // echo  $SQL; exit();
    return $rslt = mysqli_query($this->conn,$SQL);
   }

    public function addOrderItems($order_id ,$shop_id , $item_code,$quantity,$unit_cost){
            $SQL = "INSERT INTO `cofeeapp_order_items`
                    (
                     `id`,
                    `order_id`,
                    `shop_id`,
                    `subscription_id`,
                    `item_id`,
                    `description`,
                    `line`,
                    `quantity_purchased`,
                    `item_price`,
                    `discount_percent`,
                    `item_vat_amount`)
                    VALUES
                    (
                    '',    
                    '$order_id',
                    '$shop_id',
                    '',
                    '$item_code',
                    '',
                    '',
                    $quantity,
                    $unit_cost,
                    0,
                    ''
                    );
                    ";
   //echo  $SQL; exit();
    return $rslt = mysqli_query($this->conn,$SQL);
   }


}

?>