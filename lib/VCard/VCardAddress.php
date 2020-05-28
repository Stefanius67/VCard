<?php
namespace lib\VCard;

/**
 * class representing address
 *
 * uses helpers from trait VCardHelper
 * @see VCardHelper
 *
 * @package lib\VCard
 * @author Stefanius <s.kien@online.de>
 */
class VCardAddress
{
	use VCardHelper;
	
	/** street (including house number)
	 *  @var string	 */
	protected $strStr;
	/** city
	 *  @var string	 */
	protected $strCity;
	/** postcode
	 *  @var string	 */
	protected $strPostcode;
	/** country
	 *  @var string	 */
	protected $strCountry;
	/** region
	 *  @var string	 */
	protected $strRegion;
	/** type (VCard::HOME, VCard::WORK, VCard::POSTAL, VCard::PARCEL) 
	 *  @var string	 */
	protected $strType;
	
	/**
	 * full address information.
	 * build semicolon delimitered string containing:
	 * 	- post office address (not supported)
	 *  - extended address (not supported)
	 *  - street (including house number)
	 *  - city
	 *  - region
	 *  - postal code
	 *  - country
	 * 
	 * @param bool $bPreferred
	 * @return string
	 */
	public function buildFullAddress($bPreferred) {
		$strField = 'ADR;TYPE=' . $this->strType;
		if ($bPreferred) {
			$strField .= ',PREF';
		}
		
		// values separated by semikolon
		$strValue  = ';';											// post office address (not supported)
		$strValue .= ';';											// extended address (not supported)
		$strValue .= $this->maskString($this->strStr) . ';';		// street (including house number)
		$strValue .= $this->maskString($this->strCity) . ';';		// city
		$strValue .= $this->maskString($this->strRegion) . ';';		// region
		$strValue .= $this->maskString($this->strPostcode) . ';';	// postal code
		$strValue .= $this->maskString($this->strCountry);			// country
		
		return $this->buildProperty($strField, $strValue, false);
	}

	/**
	 * label for address
	 * @param unknown $bPreferred
	 * @return string
	 */
	public function buildLabel($bPreferred) {
		$strField = 'LABEL;TYPE=' . $this->strType;
		if ($bPreferred) {
			$strField .= ',PREF';
		}
		
		// values separated by semikolon
		$strValue  = $this->strStr . PHP_EOL;
		$strValue .= $this->strPostcode . ' ' . $this->strCity . PHP_EOL;
		if (strlen($this->strRegion) > 0 || strlen($this->strCountry) > 0 ) {
			$strSep = (strlen($this->strRegion) > 0 && strlen($this->strCountry) > 0 ) ? ' - ' : '';
			$strValue .= $this->strRegion . $strSep . $this->strCountry . PHP_EOL;
		}
		
		return $this->buildProperty($strField, $strValue);
	}
	
	/**
	 * explode string into address components:
	 * 	- post office address (not supported)
	 *  - extended address (not supported)
	 *  - street (including house number)
	 *  - city
	 *  - region
	 *  - postal code
	 *  - country
	 *  delimitered by semicolon (be aware of masked delimiters)
	 *  
	 * @param string $strValue
	 */
	public function parseFullAddress($strValue, $aParams)
	{
		$aSplit = $this->explodeMaskedString(';', $strValue);
		if (isset($aSplit[2])) {
			$this->strStr = $this->unmaskString($aSplit[2]);		// street (including house number)
		}
		if (isset($aSplit[3])) {
			$this->strCity = $this->unmaskString($aSplit[3]);		// city
		}
		if (isset($aSplit[4])) {
			$this->strRegion = $this->unmaskString($aSplit[4]);		// region
		}
		if (isset($aSplit[5])) {
			$this->strPostcode = $this->unmaskString($aSplit[5]);	// postal code
		}
		if (isset($aSplit[6])) {
			$this->strCountry = $this->unmaskString($aSplit[6]);	// country
		}
		if (isset($aParams['TYPE'])) {
			$this->strType = $aParams['TYPE'];
		} else {
			$this->strType = VCard::HOME;
		}
	}

	/**
	 * @param field_type $strStr
	 */
	public function setStr($strStr) {
		$this->strStr = $strStr;
	}
	
	/**
	 * @param field_type $strCity
	 */
	public function setCity($strCity) {
		$this->strCity = $strCity;
	}
	
	/**
	 * @param field_type $strPostcode
	 */
	public function setPostcode($strPostcode) {
		$this->strPostcode = $strPostcode;
	}
	
	/**
	 * @param field_type $strCountry
	 */
	public function setCountry($strCountry) {
		$this->strCountry = $strCountry;
	}
	
	/**
	 * @param field_type $strRegion
	 */
	public function setRegion($strRegion) {
		$this->strRegion = $strRegion;
	}
	
	/**
	 * @param field_type $strType
	 */
	public function setType($strType) {
		$this->strType = $strType;
	}
	
	/**
	 * @return the $strStr
	 */
	public function getStr() {
		return $this->strStr;
	}

	/**
	 * @return the $strCity
	 */
	public function getCity() {
		return $this->strCity;
	}

	/**
	 * @return the $strPostcode
	 */
	public function getPostcode() {
		return $this->strPostcode;
	}

	/**
	 * @return the $strCountry
	 */
	public function getCountry() {
		return $this->strCountry;
	}

	/**
	 * @return the $strRegion
	 */
	public function getRegion() {
		return $this->strRegion;
	}

	/**
	 * @return the $strType
	 */
	public function getType() {
		return $this->strType;
	}
}