<?php
namespace lib\VCard;

/**
 * class representing all data to one contact 
 *
 * uses helpers from trait VCardHelper
 * @see VCardHelper
 * 
 * @package lib\VCard
 * @author Stefanius <s.kien@online.de>
 */
class VCardContact
{
	use VCardHelper;
	
	/** lastname
	 *  @var string	 */
	protected $strLastName = '';
	/** firstname
	 *  @var string	 */
	protected $strFirstName = '';
	/** prefix (salutation, title,...)
	 *  @var string	 */
	protected $strPrefix = '';
	/** suffix (graduation,...)
	 *  @var string	 */
	protected $strSuffix = '';
	/** nickname
	 *  @var string	 */
	protected $strNickName = '';
	/** organisation name
	 *  @var string	 */
	protected $strOrganisation = '';
	/** position within the organisation
	 *  @var string	 */
	protected $strPosition = '';
	/** section within the organisation
	 *  @var string	 */
	protected $strSection = '';
	/** role / profession
	 *  @var string	 */
	protected $strRole = '';
	/** array of VCardAddress objects
	 *  @var array	 */
	protected $aAddress		= null;
	/** index of preferred address
	 *  @var int	 */
	protected $iPrefAddress = 0;
	/** array of phone numbers
	 *  @var array	 */
	protected $aPhone		= null;
	/** index of preferred phone number
	 *  @var int	 */
	protected $iPrefPhone = 0;
	/** array of email addresses
	 *  @var array	 */
	protected $aEMail		= null;
	/** index of preferred mail address
	 *  @var int	 */
	protected $iPrefEMail = 0;
	/** array of categories
	 *  @var array	 */
	protected $aCategories		= null;
	/** homepage URL
	 *  @var string	 */
	protected $strHomepage = '';
	/** date of birth in format YYYY-DD-MM
	 *  @var string	 */
	protected $strDateOfBirth = '';
	/** gender (0: not specified, 1: female, 2: male)
	 *  @var int	 */
	protected $iGender = 0;
	/** note
	 *  @var string	 */
	protected $strNote = '';
	/** address label (readonly)
	 *  @var string	 */
	protected $strLabel = '';
	/** binary portrait base64 coded 
	 *  @var string	 */
	protected $blobPortrait = '';
	
	/**
	 * create empty contact
	 */
	public function __construct()
	{
		$this->aAddress = array();
		$this->aPhone = array();
		$this->aEMail = array();
		$this->aCategories = array();
	}
	
	/**
	 * create data buffer 
	 * @return string
	 */
	public function buildData()
	{
		$buffer = '';
		
		$buffer .= 'BEGIN:VCARD' . PHP_EOL;
		$buffer .= 'VERSION:3.0' . PHP_EOL;

		// name properties 
		$strName  = $this->maskString($this->strLastName) . ';';	// family name
		$strName .= $this->maskString($this->strFirstName) . ';';	// given name
		$strName .= ';';											// additional name(s)
		$strName .= $this->maskString($this->strPrefix) . ';';		// honorific prefixes
		$strName .= $this->maskString($this->strSuffix); 			// honorific suffixes
		
		$buffer .= $this->buildProperty('N', $strName, false);
		$buffer .= $this->buildProperty('FN', $this->strFirstName . ' ' . $this->strLastName);
		$buffer .= $this->buildProperty('NICKNAME', $this->strNickName);
		
		// organisation
		$strOrg  = $this->maskString($this->strOrganisation) . ';';
		$strOrg .= $this->maskString($this->strSection);
		
		$buffer .= $this->buildProperty('ORG', $strOrg, false);
		$buffer .= $this->buildProperty('TITLE', $this->strPosition);
		$buffer .= $this->buildProperty('ROLE', $this->strRole);
		
		// addresses
		foreach ($this->aAddress as $i => $oAddress ) {
			$buffer .= $oAddress->buildFullAddress($i == $this->iPrefAddress);
			$buffer .= $oAddress->buildLabel($i == $this->iPrefAddress);
		}
		// set preferred address also as default postal address for MS
		$buffer .= $this->buildProperty('X-MS-OL-DEFAULT-POSTAL-ADDRESS', ($this->iPrefAddress + 1));
		
		// phone numbers
		foreach ($this->aPhone as $i => $aPhone ) {
			$strName = 'TEL;TYPE=' . $aPhone['strType'];
			if ($i == $this->iPrefPhone) {
				$strName .= ',PREF';
			}
			$buffer .= $this->buildProperty($strName, $aPhone['strPhone']);
		}
			
		// mailaddresses
		foreach ($this->aEMail as $i => $strEMail ) {
			$strName = 'EMAIL;TYPE=INTERNET';
			if ($i == $this->iPrefEMail) {
				$strName .= ',PREF';
			}
			$buffer .= $this->buildProperty($strName, $strEMail);
		}
		// homepage
		$buffer .= $this->buildProperty('URL;TYPE=WORK', $this->strHomepage);
		
		// personal data
		$buffer .= $this->buildProperty('BDAY', $this->strDateOfBirth);
		if ($this->iGender > 0) {
			$buffer .= $this->buildProperty('X-WAB-GENDER', $this->iGender);
		}
		// categories
		if (count($this->aCategories) > 0 ) {
			$strSep = '';
			$strValue = '';
			foreach ($this->aCategories as $strCategory) {
				$strValue .= $strSep . $this->maskString($strCategory);
				$strSep = ',';
			}
			$buffer .= $this->buildProperty('CATEGORIES', $strValue, false);
		}
		
		// annotation
		$buffer .= $this->buildProperty('NOTE', $this->strNote);
		
		// photo
		if ( strlen($this->blobPortrait) > 0) {
			// extract image type from binary data
			$strType = '';
			$strImage = '';
			$this->parseImageData($this->blobPortrait, $strType, $strImage);
			if (strlen($strType) > 0 && strlen($strImage) > 0 )	{
				$strName = 'PHOTO;TYPE=' . $strType . ';ENCODING=B';
				$buffer .= $this->buildProperty($strName, $strImage, false);
				$buffer .= PHP_EOL;	// even though in vcard 3.0 spec blank line after binary value no longer is requires, MS Outlook need it... 
			}
		}
		
		// END
		$buffer .= 'END:VCARD' . PHP_EOL;
		
		return $buffer;
	}
	
	/**
	 * add property from import file.
	 * 
	 * @param string $strName
	 * @param \ArrayObject $aParams
	 * @param string $strValue
	 */
	public function addProperty($strName, $aParams, $strValue) {
		// table to parse property depending on propertyname.
		// value have to be either name of method with signature
		//
		// 		methodname( string strValue, array aParams )
		//
		// or 
		//		propertyname  ( => unmasked value will be assigned to property)
		//		   
		$aMethodOrProperty = array(
				'N'				=> 'parseName',
				'ADR'			=> 'parseAdr',
				'TEL'			=> 'parseTel',
				'EMAIL'			=> 'parseEMail',
				'CATEGORIES' 	=> 'parseCategories',
				'CATEGORY'	 	=> 'parseCategories',
				'ORG'			=> 'parseOrg',
				'PHOTO'			=> 'parsePhoto',
				'NICKNAME'		=> 'strNickName',
				'TITLE'			=> 'strPosition',
				'ROLE'			=> 'strRole',
				'URL'			=> 'strHomepage',
				'NOTE'			=> 'strNote',
				'LABEL'			=> 'strLabel',
				'BDAY'			=> 'strDateOfBirth',
				'X-WAB-GENDER'	=> 'iGender'
		);
		
		// supported only by vcard version 2.1
		if (isset($aParams['ENCODING']) && $aParams['ENCODING'] == 'QUOTED-PRINTABLE') {
			$strValue = quoted_printable_decode($strValue);
		}
		
		if (isset($aMethodOrProperty[$strName])) {
			$strPtr = $aMethodOrProperty[$strName];
			if (method_exists($this, $strPtr)) {
				// call method
				call_user_func_array(array($this, $strPtr), array($strValue, $aParams));
			} elseif (property_exists($this, $strPtr)) {
				// assign unmasket vsalue to property
				$this->$strPtr = $this->unmaskString($strValue);
			}
		}
	}
	
	/**
	 * explode string into name components:
	 * 	- family name
	 *  - given name
	 *  - additional name(s) (not supported)
	 *  - honorific prefixes
	 *  - honorific suffixes
	 *  delimitered by semicolon (be aware of masked delimiters)
	 *  
	 * @param string $strValue
	 * @param \ArrayObject $aParams
	 */
	protected function parseName($strValue, $aParams)
	{
		$aSplit = $this->explodeMaskedString(';', $strValue);
		$this->strLastName = $this->unmaskString($aSplit[0]);		// family name
		if (isset($aSplit[1])) {
			$this->strFirstName = $this->unmaskString($aSplit[1]);	// given name
		}
		if (isset($aSplit[3])) {
			$this->strPrefix = $this->unmaskString($aSplit[3]);		// honorific prefixes
		}
		if (isset($aSplit[4])) {
			$this->strSuffix = $this->unmaskString($aSplit[4]);		// honorific suffixes
		}
	}

	/**
	 * @param string $strValue
	 * @see VCardAddress::parseFullAddress()
	 * @param \ArrayObject $aParams
	 */
	protected function parseAdr($strValue, $aParams)
	{
		$oAdr = new VCardAddress();
		$oAdr->parseFullAddress($strValue, $aParams);
		if (isset($aParams['TYPE'])) {
			if (strpos($aParams['TYPE'], 'PREF') !== false) {
				$this->iPrefAddress = count($this->aAddress);
			}
		}
		$this->aAddress[] = $oAdr;
	}

	/**
	 * unmask value and add with typeinfo to phone list
	 * @param string $strValue
	 * @param \ArrayObject $aParams
	 */
	protected function parseTel($strValue, $aParams)
	{
		$strValue = $this->unmaskString($strValue);
		$this->addPhone($strValue, $aParams['TYPE'], strpos($aParams['TYPE'], 'PREF') !== false);
	}

	/**
	 * unmask value and add to email list
	 * @param string $strValue
	 * @param \ArrayObject $aParams
	 */
	protected function parseEMail($strValue, $aParams)
	{
		$strValue = $this->unmaskString($strValue);
		$this->addEMail($strValue, strpos($aParams['TYPE'], 'PREF') !== false);
	}

	/**
	 * split into company and section
	 * @param string $strValue
	 * @param \ArrayObject $aParams
	 */
	protected function parseOrg($strValue, $aParams)
	{
		$aSplit = $this->explodeMaskedString(';', $strValue);
		$this->strOrganisation = $this->unmaskString($aSplit[0]);
		if (isset($aSplit[1])) {
			$this->strSection = $this->unmaskString($aSplit[1]);
		}
	}

	/**
	 * split comma separated categories
	 * @param string $strValue
	 * @param \ArrayObject $aParams
	 */
	protected function parseCategories($strValue, $aParams)
	{
		$aSplit = $this->explodeMaskedString(',', $strValue);
		foreach ($aSplit as $strCategory) {
			$this->addCategory($this->unmaskString($strCategory));
		}
	}

	/**
	 * @param string $strValue
	 * @param \ArrayObject $aParams
	 */
	protected function parsePhoto($strValue, $aParams)
	{
		$strEncoding = isset($aParams['ENCODING']) ? $aParams['ENCODING'] : '';
		if ($strEncoding == 'B' || $strEncoding == 'BASE64') {
			$strType = strtolower($aParams['TYPE']);
			$this->blobPortrait = 'data:image/' . $strType . ';base64,' . $strValue;
		} else {
			// assuming URL value... e.g. export from google contacts
			$this->setPortraitFile($strValue);  
		}
	}
	
	/**
	 * add address.
	 * only one address should be marked as preferred. In case of multiple addresses
	 * specified as preferred, last call counts
	 * 
	 * @param VCardAddress $oAddress
	 * @param bool $bPreferred
	 */
	public function addAddress(VCardAddress $oAddress, $bPreferred) {
		if ($bPreferred) {
			$this->iPrefAddress = count($this->aAddress);
		}
		$this->aAddress[] = $oAddress;
	}

	/**
	 * add phone number.
	 * can also be used to set FAX number
	 * there may be defined multiple numbers with same type 
	 * @param string $strPhone
	 * @param string $strType	one of VCard::WORK, VCard::HOME, VCard::CELL, VCard::FAX
	 * @param bool $bPreferred
	 */
	public function addPhone($strPhone, $strType, $bPreferred) {
		if ($bPreferred) {
			$this->iPrefPhone = count($this->aPhone);
		}
		$this->aPhone[] = array('strPhone' => $strPhone, 'strType' => $strType);
	}

	/**
	 * add mail address 
	 * @param string $strEMail
	 * @param bool $bPreferred
	 */
	public function addEMail($strEMail, $bPreferred) {
		if ($bPreferred) {
			// just set preferred mail on top of the list!
			array_unshift($this->aEMail, $strEMail);
		} else {
			$this->aEMail[] = $strEMail;
		}
	}

	/**
	 * add category
	 * @param string $strCategory
	 */
	public function addCategory($strCategory) {
		$this->aCategories[] = $strCategory;
	}

	/**
	 * set date of birth
	 * @param mixed $DateOfSignature 	may be string (format YYYY-MM-DD), int (unixtimestamp) or DateTime - object
	 */
	public function setDateOfBirth($DateOfBirth) {
		if (is_object($DateOfBirth) && get_class($DateOfBirth) == 'DateTime') {
			// DateTime -object
			$this->strDateOfBirth = $DateOfBirth->format('Y-m-d');
		} else if (is_numeric($DateOfBirth)) {
			$this->strDateOfBirth = date('Y-m-d', $DateOfBirth);
		} else {
			$this->strDateOfBirth = $DateOfBirth;
		}
	}

	/**
	 * gender.
	 * MS-extension!<br/>
	 * windows contacts: export/import
	 * outlook: import only
	 *
	 * only male or female accepted
	 *
	 * @param string $strGender
	 */
	public function setGender($strGender) {
		$chGender = strtolower(substr($strGender, 0, 1));
		if (in_array($chGender, array('w', 'f'))) {
			// weibl., female
			$this->iGender = 1;
		} elseif ($chGender == 'm') {
			// männl., male
			$this->iGender = 2;
		}
	}

	/**
	 * set portrait from image file
	 * supported types are JPG, PNG, GIF and BMP
	 * @param sring $strFilename
	 */
	public function setPortraitFile($strFilename) {
		if (filter_var($strFilename, FILTER_VALIDATE_URL)) {
			// get type from extension
			$strType = strtolower(pathinfo($strFilename, PATHINFO_EXTENSION));
			$this->blobPortrait = 'data:image/' . $strType . ';base64,';
				
			// use curl to be independet of [allow_url_fopen] enabled on system
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $strFilename);
			curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			
			$img = curl_exec($curl);
			curl_close($curl);
			
			$this->blobPortrait .= base64_encode($img);
			
		} elseif(file_exists($strFilename)) {
			switch (exif_imagetype($strFilename)) {
				case IMAGETYPE_JPEG:
					$this->blobPortrait = 'data:image/jpg;base64,';
					break;
				case IMAGETYPE_PNG:
					$this->blobPortrait = 'data:image/png;base64,';
					break;
				case IMAGETYPE_GIF:
					$this->blobPortrait = 'data:image/gif;base64,';
					break;
				case IMAGETYPE_BMP:
					$this->blobPortrait = 'data:image/bmp;base64,';
					break;
				default:
					break;
			}
			$img = file_get_contents($strFilename);
			
			$this->blobPortrait .= base64_encode($img);
		}
	}
	
	/**
	 * @param string $strLastName
	 * @param string $strFirstName
	 */
	public function setName($strLastName, $strFirstName) {
		$this->strLastName = $strLastName;
		$this->strFirstName = $strFirstName;
	}
	
	/**
	 * @param string $strPrefix
	 */
	public function setPrefix($strPrefix) {
		$this->strPrefix = $strPrefix;
	}
	
	/**
	 * @param string $strSuffix
	 */
	public function setStrSuffix($strSuffix) {
		$this->strSuffix = $strSuffix;
	}
	
	/**
	 * @param string $strNickName
	 */
	public function setNickName($strNickName) {
		$this->strNickName = $strNickName;
	}
	
	/**
	 * @param string $strOrganisation
	 */
	public function setOrganisation($strOrganisation) {
		$this->strOrganisation = $strOrganisation;
	}
	
	/**
	 * @param string $strSection
	 */
	public function setSection($strSection) {
		$this->strSection = $strSection;
	}
	
	/**
	 * @param string $strPosition
	 */
	public function setPosition($strPosition) {
		$this->strPosition = $strPosition;
	}
	
	/**
	 * @param string $strRole
	 */
	public function setRole($strRole) {
		$this->strPosition = $strRole;
	}
	
	/**
	 * @param string $strHomepage
	 */
	public function setHomepage($strHomepage) {
		$this->strHomepage = $strHomepage;
	}
	
	/**
	 * @param string $strNote
	 */
	public function setNote($strNote) {
		$this->strNote = $strNote;
	}

	/**
	 * set portrait from data 
	 * @param sring $blobPortrait base64 encoded image
	 */
	public function setPortraitBlob($blobPortrait) {
		$this->blobPortrait = $blobPortrait;
	}
	
	/**
	 * save portrait as file
	 * supportet types are JPG, PNG and GIF
	 * type will be detected from fileextension
	 * 
	 * @param string $strFilename
	 */
	public function savePortrait($strFilename) {
		if (strlen($this->blobPortrait) > 0 ) {
			$strType = '';
			$strImage = '';
			$this->parseImageData($this->blobPortrait, $strType, $strImage);
			if (strlen($strType) > 0 && strlen($strImage) > 0 )	{
				$img = $this->imageFromString($strImage, $strType);
				$strExt = strtoupper(pathinfo($strFilename, PATHINFO_EXTENSION));
				switch ($strExt) {
					case 'JPG':
					case 'JPEG':
						imagejpeg($img, $strFilename);
						break;
					case 'PNG':
						imagepng($img, $strFilename);
						break;
					case 'GIF':
						imagegif($img, $strFilename);
						break;
				}
			}
		}
	}

	/**
	 * number of addresses the contact contains
	 * @return number
	 */
	public function getAddressCount() {
		return count($this->aAddress);
	}
	
	/**
	 * get address.
	 * can be referenced by index or type. 
	 * type requests (=> $i non numeric value):
	 * - first address matches specified type is used (contact may contains multiple addresses of same type)
	 * - if VCard::PREF specified, first address in contact used, if no preferred item found
	 * 
	 * @param int/string $i		reference to address (int => index, string => type)
	 * @return VCardAddress or null
	 */
	public function getAddress($i) {
		$oAddr = null;
		if (is_numeric($i)) {
			if ($i >= 0 && $i < count($this->aAddress)) {
				$oAddr = $this->aAddress[$i];
			}
		} else {
			foreach ($this->aAddress as $oAddr )	{
				if (strpos($oAddr->getType(), $i) !== false) {
					return $oAddr;
				}
				$oAddr = null;
			}
		}
		if (!$oAddr && $i == VCard::PREF && count($this->aAddress) > 0) {
			// if preferred item requested and no address in contact defined as prefered, just return first...
			$oAddr = $this->aAddress[0];
		}
		return $oAddr;
	}

	/**
	 * number of phone numbers
	 * @return number
	 */
	public function getPhoneCount() {
		return count($this->aPhone);
	}
	
	/**
	 * get address.
	 * can be referenced by index or type. 
	 * type requests (=> $i non numeric value):
	 * - first phone matches specified type is used (contact may contains multiple phone numbers of same type)
	 * - if VCard::PREF specified, first number in contact used, if no preferred item found
	 * 
	 * @param int/string $i		reference to address (int => index, string => type)
	 * @return \ArrayObject or null
	 */
	public function getPhone($i) {
		$aPhone = null;
		if (is_numeric($i)) {
			if ($i >= 0 && $i < count($this->aPhone)) {
				$aPhone = $this->aPhone[$i];
			}
		} else {
			foreach ($this->aPhone as $aPhone )	{
				if (strpos($aPhone['strType'], $i) !== false) {
					return $aPhone;
				}
				$aPhone = null;
			}
		}
		if (!$aPhone && $i == VCard::PREF && count($this->aPhone) > 0) {
			// if preferred item requested and no phone in contact defined as prefered, just return first...
			$aPhone = $this->aPhone[0];
		}
		return $aPhone;
	}
	
	/**
	 * number of EMail addresses
	 * @return number
	 */
	public function getEMailCount() {
		return count($this->aEMail);
	}
	
	/**
	 * @param int $i
	 * @return string or null
	 */
	public function getEMail($i) {
		$strEMail = '';
		if ($i >= 0 && $i < count($this->aEMail)) {
			$strEMail = $this->aEMail[$i];
		}
		return $strEMail;
	}
	
	/**
	 * number of categories
	 * @return number
	 */
	public function getCategoriesCount() {
		return count($this->aCategories);
	}
	
	/**
	 * @param int $i
	 * @return string or null
	 */
	public function getCategory($i) {
		$strCategory = '';
		if ($i >= 0 && $i < count($this->aCategories)) {
			$strCategory = $this->aCategories[$i];
		}
		return $strCategory;
	}
	
	/**
	 * @param int $i
	 * @return string or null
	 */
	public function getCategories() {
		$strCategories = '';
		$strSep = '';
		foreach ($this->aCategories as $strCategory) {
			$strCategories .= $strSep . $strCategory;
			$strSep = ',';
		}
		return $strCategories;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->strFirstName . ' ' . $this->strLastName;
	}
	
	/**
	 * @return string
	 */
	public function getLastName() {
		return $this->strLastName;
	}
	
	/**
	 * @return string
	 */
	public function getFirstName() {
		return $this->strFirstName;
	}
	
	/**
	 * @return string
	 */
	public function getNickName() {
		return $this->strNickName;
	}
	
	/**
	 * @return string
	 */
	public function getOrganisation() {
		return $this->strOrganisation;
	}
	
	/**
	 * @return string
	 */
	public function getPosition() {
		return $this->strPosition;
	}
	
	/**
	 * @return string
	 */
	public function getRole() {
		return $this->strRole;
	}
	
	/**
	 * @return string
	 */
	public function getHomepage() {
		return $this->strHomepage;
	}
	
	/**
	 * get date of birth
	 * @return string
	 */
	public function getDateOfBirth() {
		return $this->strDateOfBirth;
	}
	
	/**
	 * @return string
	 */
	public function getSection() {
		return $this->strSection;
	}
	
	/**
	 * @return string
	 */
	public function getNote() {
		return $this->strNote;
	}
	
	/**
	 * @return string
	 */
	public function getLabel() {
		return $this->strLabel;
	}
	
	/**
	 * @return string
	 */
	public function getPrefix() {
		return $this->strPrefix;
	}
	
	/**
	 * @return string
	 */
	public function getSuffix() {
		return $this->strSuffix;
	}
	
	/**
	 * @return string base64 encoded image
	 */
	public function getPortraitBlob() {
		return $this->blobPortrait;
	}
}
