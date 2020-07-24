<?php
namespace SKien\VCard;

/**
 * class representing all data to one contact 
 *
 * uses helpers from trait VCardHelper
 * @see VCardHelper
 *
* history:
 * date         version
 * 2020-02-23   initial version.
 * 2020-05-28   renamed namespace to fit PSR-4 recommendations for autoloading.
 * 2020-07-22   added missing PHP 7.4 type hints / docBlock changes 
 * 
 * @package SKien-VCard
 * @since 1.0.0
 * @version 1.0.3
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class VCardContact
{
    use VCardHelper;
    
    /** @var string  lastname   */
    protected string $strLastName = '';
    /** @var string  firstname  */
    protected string $strFirstName = '';
    /** @var string  prefix (salutation, title,...) */
    protected string $strPrefix = '';
    /** @var string  suffix (graduation,...)    */
    protected string $strSuffix = '';
    /** @var string  nickname   */
    protected string $strNickName = '';
    /** @var string  organisation name  */
    protected string $strOrganisation = '';
    /** @var string  position within the organisation   */
    protected string $strPosition = '';
    /** @var string  section within the organisation    */
    protected string $strSection = '';
    /** @var string  role / profession  */
    protected string $strRole = '';
    /** @var array   array of VCardAddress objects  */
    protected ?array $aAddress     = null;
    /** @var int     index of preferred address */
    protected int $iPrefAddress = 0;
    /** @var array   array of phone numbers */
    protected ?array $aPhone       = null;
    /** @var int     index of preferred phone number    */
    protected int $iPrefPhone = 0;
    /** @var array   array of email addresses   */
    protected ?array $aEMail       = null;
    /** @var int     index of preferred mail address    */
    protected int $iPrefEMail = 0;
    /** @var array   array of categories    */
    protected ?array $aCategories  = null;
    /** @var string  homepage URL   */
    protected string $strHomepage = '';
    /** @var string  date of birth in format YYYY-DD-MM */
    protected string $strDateOfBirth = '';
    /** @var int     gender (0: not specified, 1: female, 2: male)  */
    protected int $iGender = 0;
    /** @var string  note   */
    protected string $strNote = '';
    /** @var string  address label (readonly)   */
    protected string $strLabel = '';
    /** @var string  binary portrait base64 coded   */
    protected string $blobPortrait = '';
    
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
    public function buildData() : string
    {
        $buffer = '';
        
        $buffer .= 'BEGIN:VCARD' . PHP_EOL;
        $buffer .= 'VERSION:3.0' . PHP_EOL;

        // name properties 
        $strName  = $this->maskString($this->strLastName) . ';';    // family name
        $strName .= $this->maskString($this->strFirstName) . ';';   // given name
        $strName .= ';';                                            // additional name(s)
        $strName .= $this->maskString($this->strPrefix) . ';';      // honorific prefixes
        $strName .= $this->maskString($this->strSuffix);            // honorific suffixes
        
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
            if (strlen($strType) > 0 && strlen($strImage) > 0 ) {
                $strName = 'PHOTO;TYPE=' . $strType . ';ENCODING=B';
                $buffer .= $this->buildProperty($strName, $strImage, false);
                $buffer .= PHP_EOL; // even though in vcard 3.0 spec blank line after binary value no longer is requires, MS Outlook need it... 
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
     * @param array $aParams
     * @param string $strValue
     */
    public function addProperty(string $strName, array $aParams, string $strValue) 
    {
        // table to parse property depending on propertyname.
        // value have to be either name of method with signature
        //
        //      methodname( string strValue, array aParams )
        //
        // or 
        //      propertyname  ( => unmasked value will be assigned to property)
        //         
        $aMethodOrProperty = array(
                'N'             => 'parseName',
                'ADR'           => 'parseAdr',
                'TEL'           => 'parseTel',
                'EMAIL'         => 'parseEMail',
                'CATEGORIES'    => 'parseCategories',
                'CATEGORY'      => 'parseCategories',
                'ORG'           => 'parseOrg',
                'PHOTO'         => 'parsePhoto',
                'NICKNAME'      => 'strNickName',
                'TITLE'         => 'strPosition',
                'ROLE'          => 'strRole',
                'URL'           => 'strHomepage',
                'NOTE'          => 'strNote',
                'LABEL'         => 'strLabel',
                'BDAY'          => 'strDateOfBirth',
                'X-WAB-GENDER'  => 'iGender'
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
     *  - family name
     *  - given name
     *  - additional name(s) (not supported)
     *  - honorific prefixes
     *  - honorific suffixes
     *  delimitered by semicolon (be aware of masked delimiters)
     *  
     * @param string $strValue
     * @param array $aParams
     */
    protected function parseName(string $strValue, array $aParams)
    {
        $aSplit = $this->explodeMaskedString(';', $strValue);
        $this->strLastName = $this->unmaskString($aSplit[0]);       // family name
        if (isset($aSplit[1])) {
            $this->strFirstName = $this->unmaskString($aSplit[1]);  // given name
        }
        if (isset($aSplit[3])) {
            $this->strPrefix = $this->unmaskString($aSplit[3]);     // honorific prefixes
        }
        if (isset($aSplit[4])) {
            $this->strSuffix = $this->unmaskString($aSplit[4]);     // honorific suffixes
        }
    }

    /**
     * @param string $strValue
     * @param array $aParams
     * @see VCardAddress::parseFullAddress()
     */
    protected function parseAdr(string $strValue, array $aParams)
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
     * @param array $aParams
     */
    protected function parseTel(string $strValue, array $aParams)
    {
        $strValue = $this->unmaskString($strValue);
        $this->addPhone($strValue, $aParams['TYPE'], strpos($aParams['TYPE'], 'PREF') !== false);
    }

    /**
     * unmask value and add to email list
     * @param string $strValue
     * @param array $aParams
     */
    protected function parseEMail(string $strValue, array $aParams)
    {
        $strValue = $this->unmaskString($strValue);
        $this->addEMail($strValue, strpos($aParams['TYPE'], 'PREF') !== false);
    }

    /**
     * split into company and section
     * @param string $strValue
     * @param array $aParams
     */
    protected function parseOrg(string $strValue, array $aParams)
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
     * @param array $aParams
     */
    protected function parseCategories(string $strValue, array $aParams)
    {
        $aSplit = $this->explodeMaskedString(',', $strValue);
        foreach ($aSplit as $strCategory) {
            $this->addCategory($this->unmaskString($strCategory));
        }
    }

    /**
     * @param string $strValue
     * @param array $aParams
     */
    protected function parsePhoto(string $strValue, array $aParams)
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
    public function addAddress(VCardAddress $oAddress, bool $bPreferred) 
    {
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
     * @param string $strType   one of VCard::WORK, VCard::HOME, VCard::CELL, VCard::FAX
     * @param bool $bPreferred
     */
    public function addPhone(string $strPhone, string $strType, bool $bPreferred) 
    {
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
    public function addEMail(string $strEMail, bool $bPreferred) 
    {
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
    public function addCategory(string $strCategory) 
    {
        $this->aCategories[] = $strCategory;
    }

    /**
     * set date of birth
     * @param mixed $DateOfBirth    may be string (format YYYY-MM-DD), int (unixtimestamp) or DateTime - object
     */
    public function setDateOfBirth($DateOfBirth) 
    {
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
     * MS-extension!
     * 
     * windows contacts: export/import.
     * outlook: import only.
     *
     * only male or female accepted
     *
     * @param string $strGender
     */
    public function setGender(string $strGender) 
    {
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
     * @param string $strFilename
     */
    public function setPortraitFile(string $strFilename) 
    {
        if (filter_var($strFilename, FILTER_VALIDATE_URL)) {
            // get type from extension
            $strType = strtolower(pathinfo($strFilename, PATHINFO_EXTENSION));
            $this->blobPortrait = 'data:image/' . $strType . ';base64,';
                
            // use curl to be independet of [allow_url_fopen] enabled on system
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $strFilename);
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
    public function setName(string $strLastName, string $strFirstName) 
    {
        $this->strLastName = $strLastName;
        $this->strFirstName = $strFirstName;
    }
    
    /**
     * @param string $strPrefix
     */
    public function setPrefix(string $strPrefix) 
    {
        $this->strPrefix = $strPrefix;
    }
    
    /**
     * @param string $strSuffix
     */
    public function setStrSuffix(string $strSuffix) 
    {
        $this->strSuffix = $strSuffix;
    }
    
    /**
     * @param string $strNickName
     */
    public function setNickName(string $strNickName) 
    {
        $this->strNickName = $strNickName;
    }
    
    /**
     * @param string $strOrganisation
     */
    public function setOrganisation(string $strOrganisation) 
    {
        $this->strOrganisation = $strOrganisation;
    }
    
    /**
     * @param string $strSection
     */
    public function setSection(string $strSection) 
    {
        $this->strSection = $strSection;
    }
    
    /**
     * @param string $strPosition
     */
    public function setPosition(string $strPosition) 
    {
        $this->strPosition = $strPosition;
    }
    
    /**
     * @param string $strRole
     */
    public function setRole(string $strRole)
    {
        $this->strPosition = $strRole;
    }
    
    /**
     * @param string $strHomepage
     */
    public function setHomepage(string $strHomepage) 
    {
        $this->strHomepage = $strHomepage;
    }
    
    /**
     * @param string $strNote
     */
    public function setNote(string $strNote) 
    {
        $this->strNote = $strNote;
    }

    /**
     * set portrait from data 
     * @param string $blobPortrait base64 encoded image
     */
    public function setPortraitBlob(string $blobPortrait) 
    {
        $this->blobPortrait = $blobPortrait;
    }
    
    /**
     * save portrait as file
     * supportet types are JPG, PNG and GIF
     * type will be detected from fileextension
     * 
     * @param string $strFilename
     */
    public function savePortrait(string $strFilename) 
    {
        if (strlen($this->blobPortrait) > 0 ) {
            $strType = '';
            $strImage = '';
            $this->parseImageData($this->blobPortrait, $strType, $strImage);
            if (strlen($strType) > 0 && strlen($strImage) > 0 ) {
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
     * @return int
     */
    public function getAddressCount() : int
    {
        return count($this->aAddress);
    }
    
    /**
     * get address.
     * can be referenced by index or type. 
     * type requests (=> $i non numeric value):
     * - first address matches specified type is used (contact may contains multiple addresses of same type)
     * - if VCard::PREF specified, first address in contact used, if no preferred item found
     * 
     * @param mixed $i     reference to address (int => index, string => type)
     * @return VCardAddress or null
     */
    public function getAddress($i) : ?VCardAddress
    {
        $oAddr = null;
        if (is_numeric($i)) {
            if ($i >= 0 && $i < count($this->aAddress)) {
                $oAddr = $this->aAddress[$i];
            }
        } else {
            foreach ($this->aAddress as $oAddr )    {
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
     * @return int
     */
    public function getPhoneCount() : int
    {
        return count($this->aPhone);
    }
    
    /**
     * get address.
     * can be referenced by index or type. 
     * type requests (=> $i non numeric value):
     * - first phone matches specified type is used (contact may contains multiple phone numbers of same type)
     * - if VCard::PREF specified, first number in contact used, if no preferred item found
     * 
     * @param mixed $i     reference to address (int => index, string => type)
     * @return array or null
     */
    public function getPhone($i) : ?array
    {
        $aPhone = null;
        if (is_numeric($i)) {
            if ($i >= 0 && $i < count($this->aPhone)) {
                $aPhone = $this->aPhone[$i];
            }
        } else {
            foreach ($this->aPhone as $aPhone ) {
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
     * @return int
     */
    public function getEMailCount() : int
    {
        return count($this->aEMail);
    }
    
    /**
     * @param int $i
     * @return string
     */
    public function getEMail(int $i) : string
    {
        $strEMail = '';
        if ($i >= 0 && $i < count($this->aEMail)) {
            $strEMail = $this->aEMail[$i];
        }
        return $strEMail;
    }
    
    /**
     * number of categories
     * @return int
     */
    public function getCategoriesCount() : int
    {
        return count($this->aCategories);
    }
    
    /**
     * @param int $i
     * @return string or null
     */
    public function getCategory(int $i) : int
    {
        $strCategory = '';
        if ($i >= 0 && $i < count($this->aCategories)) {
            $strCategory = $this->aCategories[$i];
        }
        return $strCategory;
    }
    
    /**
     * Return Categories separated by comma.
     * @return string|null
     */
    public function getCategories() : string
    {
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
    public function getName() : string
    {
        return $this->strFirstName . ' ' . $this->strLastName;
    }
    
    /**
     * @return string
     */
    public function getLastName() : string
    {
        return $this->strLastName;
    }
    
    /**
     * @return string
     */
    public function getFirstName() : string
    {
        return $this->strFirstName;
    }
    
    /**
     * @return string
     */
    public function getNickName() : string
    {
        return $this->strNickName;
    }
    
    /**
     * @return string
     */
    public function getOrganisation() : string
    {
        return $this->strOrganisation;
    }
    
    /**
     * @return string
     */
    public function getPosition() : string
    {
        return $this->strPosition;
    }
    
    /**
     * @return string
     */
    public function getRole() : string
    {
        return $this->strRole;
    }
    
    /**
     * @return string
     */
    public function getHomepage() : string
    {
        return $this->strHomepage;
    }
    
    /**
     * get date of birth
     * @return string   format YYYY-DD-MM
     */
    public function getDateOfBirth() : string
    {
        return $this->strDateOfBirth;
    }
    
    /**
     * @return string
     */
    public function getSection() : string
    {
        return $this->strSection;
    }
    
    /**
     * @return string
     */
    public function getNote() : string
    {
        return $this->strNote;
    }
    
    /**
     * @return string
     */
    public function getLabel() : string
    {
        return $this->strLabel;
    }
    
    /**
     * @return string
     */
    public function getPrefix() : string
    {
        return $this->strPrefix;
    }
    
    /**
     * @return string
     */
    public function getSuffix() : string
    {
        return $this->strSuffix;
    }
    
    /**
     * @return string base64 encoded image
     */
    public function getPortraitBlob() : string
    {
        return $this->blobPortrait;
    }
}
