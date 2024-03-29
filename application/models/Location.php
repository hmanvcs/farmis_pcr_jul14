<?php
/**
 * Model for a location
 *
 */

class Location extends BaseEntity {
	
	public function setTableDefinition() {
		#add the table definitions from the parent table
		parent::setTableDefinition();
		
		$this->setTableName('location');
		$this->hasColumn('name', 'string', 255, array('notnull' => true, 'notblank' => true));
		$this->hasColumn('description', 'string', 500);
		$this->hasColumn('locationtype', 'tinyint');
		// 1=Region, 2=District, 3=County, 4=Subcounty, 5=Parish, 6=Village, 7=Municipality
		$this->hasColumn('country', 'string', 2, array('default' => 'UG'));
		$this->hasColumn('regionid','integer', null, array('default' => NULL));
		$this->hasColumn('districtid','integer', null, array('default' => NULL));
		$this->hasColumn('countyid','integer', null, array('default' => NULL));
		$this->hasColumn('subcountyid','integer', null, array('default' => NULL));
		$this->hasColumn('parishid','integer', null, array('default' => NULL));
		$this->hasColumn('gpslat', 'string', 15);
		$this->hasColumn('gpslng', 'string', 15);
		$this->hasColumn('parishname', 'string', 255);
		$this->hasColumn('villagename', 'string', 255);
	}
	/**
	 * Contructor method for custom functionality - add the fields to be marked as dates
	 */
	public function construct() {
		parent::construct();
		// set the custom error messages
       	$this->addCustomErrorMessages(array(
       									"name.notblank" => $this->translate->_("region_name_error")
       	       						));
	}
	public function setUp() {
		parent::setUp(); 
		# the relationships to the different location types
		$this->hasOne('Location as region',
						 array(
								'local' => 'regionid',
								'foreign' => 'id'
							)
					); 
		$this->hasOne('Location as district',
						 array(
								'local' => 'districtid',
								'foreign' => 'id'
							)
					); 
		$this->hasOne('Location as county',
						 array(
								'local' => 'countyid',
								'foreign' => 'id'
							)
					); 
		$this->hasOne('Location as subcounty',
						 array(
								'local' => 'subcountyid',
								'foreign' => 'id'
							)
					); 
		$this->hasOne('Location as parish',
						 array(
								'local' => 'parishid',
								'foreign' => 'id'
							)
					); 
	}
	/*
	 * 
	 */
	function processPost($formvalues){
		// Custom processing for integer and enum fields
		if (!isArrayKeyAnEmptyString('name', $formvalues)) {
			$formvalues['name'] = trim($formvalues['name']);
		}
		if (isArrayKeyAnEmptyString('regionid', $formvalues)) {
			$formvalues['regionid'] = NULL;
		}
		if (isArrayKeyAnEmptyString('districtid', $formvalues)) {
			$formvalues['districtid'] = NULL;
		}
		if (isArrayKeyAnEmptyString('countyid', $formvalues)) {
			$formvalues['countyid'] = NULL;
		}
		if (isArrayKeyAnEmptyString('subcountyid', $formvalues)) {
			$formvalues['subcountyid'] = NULL;
		}
		if (isArrayKeyAnEmptyString('parishid', $formvalues)) {
			$formvalues['parishid'] = NULL;
		}
		if (isArrayKeyAnEmptyString('villageid', $formvalues)) {
			$formvalues['villageid'] = NULL;
		}
		// debugMessage($formvalues);
		parent::processPost($formvalues);
	}
	/*
	 * 
	 */
	function validate() {
		// execute validation in parent
		parent::validate();
		
		# check that region is unique for locationtype = 1
		if (!$this->locationExists()) {
			$this->getErrorStack()->add("name.unique", sprintf($this->translate->_("location_unique_name_label"), $this->getName()));
		}
	}
	/*
	 * Validate that the location name is unique depending on the location type 
	 */
	function locationExists() {
		// connection		
		$conn = Doctrine_Manager::connection();
		
		// query for check if location exists
		$unique_query = "SELECT id FROM location WHERE name = '".$this->getName()."' 
			AND districtid = '".$this->getDistrictID()."'
			AND countyid = '".$this->getCountyID()."'
			AND subcountyid = '".$this->getSubCountyID()."'
			AND parishid = '".$this->getParishID()."' 
			AND locationtype = '".$this->getLocationType()."' 
			AND id <> '".$this->getID()."' ";
		$result = $conn->fetchOne($unique_query);
		//debugMessage($unique_query);
		//debugMessage($result);
		if(!isEmptyString($result)){ 
			return false;
		}
		
		return true;
	}
	
	# determine commodityid from searchable name
    function findByName($name, $type) {
    	$str_len = strlen($name);
    	trim($name);
    	$name = str_replace('district', '', strtolower($name));
    	
		$conn = Doctrine_Manager::connection();
		// query for check if location exists
		$unique_query = "SELECT id FROM location l WHERE l.name LIKE '%".$name."%' AND l.locationtype = '".$type."' ";
		$result = $conn->fetchOne($unique_query);
		// debugMessage($unique_query);
		// debugMessage($result);
		return $result; 
	}
	# determine if location is a region
	function isRegion(){
		return $this->getLocationType() == 1 ? true : false;
	}
	# determine if location is a district
	function isDistrict(){
		return $this->getLocationType() == 2 ? true : false;
	}
	# determine if location is a county
	function isCounty(){
		return $this->getLocationType() == 3 ? true : false;
	}
	# determine if location is a subcounty
	function isSubcounty(){
		return $this->getLocationType() == 4 ? true : false;
	}
	# determine if location is a parish
	function isParish(){
		return $this->getLocationType() == 5 ? true : false;
	}
	# determine if location is a village
	function isVillage(){
		return $this->getLocationType() == 6 ? true : false;
	}
	# determine if location has gps location so as to plot out their data
    function hasGPSCoordinates() {
    	return !isEmptyString($this->getGPSLat()) && !isEmptyString($this->getGPSLng()) ? true : false;
    }
	/**
	 * Get the full name of the country from the two digit code
	 * 
	 * @return String The full name of the state 
	 */
	function getCountryName() {
		if(isEmptyString($this->getCountry())){
			return "--";
		}
		$countries = getCountries(); 
		return $countries[$this->getCountry()];
	}
	function getVillageName() {
		$q = Doctrine_Query::create()->from('Location v')->where("v.id = '".$this->getID()."' ");
		$result = $q->execute();
		// debugMessage($result->toArray());
		//return $result->getName();
		return '';
	}
    function getRegions() {
		$q = Doctrine_Query::create()->from('Location l')->where("l.country = 'UG' AND l.locationtype = 1 ")->orderby("l.locationtype");
		$result = $q->execute();
		// debugMessage($result->toArray());
		return $result;
	}
	function getDistrictsForRegion() {
		$q = Doctrine_Query::create()->from('Location l')->where("l.regionid = '".$this->getID()."' AND l.locationtype = 2 ")->orderby("l.name asc");
		$result = $q->execute();
		// debugMessage($result->toArray());
		return $result;
	}
	function getTypeName() {
		$session = SessionWrapper::getInstance();
		$txt = "Location"; 
		switch($this->getLocationType()){
			case 1:
				$txt = "Region";
				break;
			case 2:
				$txt = "District";
				if(isKenya()){
					$txt = "County";
				}
				break;
			case 3:
				$txt = "Sub-county";
				if(isKenya()){
					$txt = "County";
				}
				break;
			case 4:
				$txt = "Ward";
				if(isKenya()){
					$txt = "Sub-county";
				}
				break;
			case 5:
				$plural = "Parishes";
				break;
			case 6:
				$txt = "Village";
				break;
			default:
				break;
		}
		return $txt;
	}
	/**
     * Overide  to save persons relationships
     *	@return true if saved, false otherwise
     */
    function afterSave(){
    	$session = SessionWrapper::getInstance();
    	$userid = $session->getVar('userid');
    	$conn = Doctrine_Manager::connection();
   	 	$update = false;
		
  		$duplicates = $this->getDuplicates();
    	$countdup = $duplicates->count();
		if($countdup > 0){
			$duplicates->delete();
		}
		
    	return true;
    }
    function afterUpdate() {
    	return $this->afterSave();
    }
	# find duplicates after save
	function getDuplicates(){
		$q = Doctrine_Query::create()->from('Location l')->where("l.name = '".$this->getName()."' AND 
		l.regionid = '".$this->getRegionID()."' AND 
		l.districtid = '".$this->getDistrictID()."' AND 
		l.countyid = '".$this->getCountyID()."' AND 
		l.subcountyid = '".$this->getSubCountyID()."' AND 
		l.locationtype = '".$this->getLocationType()."' AND 
		l.id <> '".$this->getID()."' ");
		$result = $q->execute();
		return $result;
	}
	# determine if farmer is ugandan
    function isUgandan() {
    	return strtoupper($this->getCountry()) == 'UG' ? true : false; 
    }
	# determine if farmer is kenyan
    function isKenyan() {
    	return strtoupper($this->getCountry()) == 'KE' ? true : false; 
    }
}

?>