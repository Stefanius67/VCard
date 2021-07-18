<?php
declare(strict_types=1);

namespace SKien\VCard;

/**
 * Class representing all data to one contact.
 * Each contact may contains multiple
 * - adresses
 * - communication numbers
 * - e-mail addresses
 * - homepages
 * - categories
 *
 * #### Add a contact to a VCard for writing:
 * Create a new instance of a `VCardContact`, set all properties and add the contact
 * to a vcard using `VCard::addContact()`
 *
 * #### Retrieve a contact from a read VCard:
 * Use `VCard::getContact()` to retrieve existing contact within vcard.
 *
 * @see VCard::addContact()
 * @see VCard::getContact()
 *
 * @package VCard
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class VCardContact
{
    use VCardHelper;

    /** gender: female (Microsoft specific) */
    public const MS_FEMALE = '1';
    /** gender: male (Microsoft specific) */
    public const MS_MALE = '2';
    /** Date type: string */
    public const DT_STRING = 0;
    /** Date type: unix timestamp */
    public const DT_UNIX_TIMESTAMP = 1;
    /** Date type: DateTime - Object */
    public const DT_OBJECT = 2;

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
    /** @var VCardAddress[] array of VCardAddress objects  */
    protected array $aAddress = array();
    /** @var array[] array of phone numbers */
    protected array $aPhone = array();
    /** @var string[] array of email addresses   */
    protected array $aEMail = array();
    /** @var string[] array of categories    */
    protected array $aCategories = array();
    /** @var string[] array of homepage URL's    */
    protected array $aHomepages = array();
    /** @var string  date of birth in format YYYY-MM-DD */
    protected string $strDateOfBirth = '';
    /** @var int     gender (0: not specified, 1: female, 2: male)  */
    protected int $iGender = 0;
    /** @var string  note   */
    protected string $strNote = '';
    /** @var string  binary portrait base64 coded   */
    protected string $blobPortrait = '';

    /**
     * Add address.
     * Only one address should be marked as preferred.
     * @param VCardAddress $oAddress
     * @param bool $bPreferred  mark address as preferred.
     */
    public function addAddress(VCardAddress $oAddress, bool $bPreferred) : void
    {
        $oAddress->setPreferred($bPreferred);
        $this->aAddress[] = $oAddress;
    }

    /**
     * Add phone number.
     * Use to set a communication number (phone, mobile, FAX, ...). <br/>
     * Any combination of the predefined communication number constants plus the definition
     * HOME or WORK can be specified as the type. <br/>
     * Multiple numbers of the same type can be set within one contact.
     * @see VCard::constants VCard communication number constants
     * @link https://datatracker.ietf.org/doc/html/rfc2426#section-3.3.1
     * @link https://en.wikipedia.org/wiki/E.164
     * @link https://www.itu.int/rec/T-REC-X.121-200010-I/en
     * @param string $strPhone      the number (SHOULD conform to the semantics of E.164 / X.121)
     * @param string|array $type    one single type or an array of multiple types
     * @param bool $bPreferred      mark number as preferred
     */
    public function addPhone(string $strPhone, $type, bool $bPreferred) : void
    {
        $strType = is_array($type) ? implode(',', $type) : $type;
        if ($bPreferred && strpos($strType, 'PREF') === false) {
            $strType .= ',PREF';
        }
        $this->aPhone[] = array('strPhone' => $strPhone, 'strType' => $strType);
    }

    /**
     * Add mail address.
     * @link https://datatracker.ietf.org/doc/html/rfc2426#section-3.3.2
     * @param string $strEMail  valid e-mail address
     * @param bool $bPreferred  mark e-mail as preferred
     */
    public function addEMail(string $strEMail, bool $bPreferred) : void
    {
        if ($bPreferred) {
            // just set preferred mail on top of the list!
            array_unshift($this->aEMail, $strEMail);
        } else {
            $this->aEMail[] = $strEMail;
        }
    }

    /**
     * Add a category.
     * @param string $strCategory
     */
    public function addCategory(string $strCategory) : void
    {
        $this->aCategories[] = $strCategory;
    }

    /**
     * Set date of birth.
     * @param mixed $DateOfBirth    may be string (format YYYY-MM-DD), int (unixtimestamp) or DateTime - object
     */
    public function setDateOfBirth($DateOfBirth) : void
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
     * Set the gender.
     * <b>Note: this is a MS-extension! </b><ul>
     * <li> windows contacts: export/import. </li>
     * <li> outlook: import only. </li></ul><br/>
     * Only the first char of the `$strGender` param (converted to lowercase) is taken into account! <ul>
     * <li> male: 'm', '2' </li>
     * <li> female: 'f', 'w', '1' </li></ul>
     * @param string $strGender
     */
    public function setGender(string $strGender) : void
    {
        $chGender = strtolower(substr($strGender, 0, 1));
        if (in_array($chGender, array('w', 'f', self::MS_FEMALE))) {
            // weibl., female
            $this->iGender = 1;
        } elseif (in_array($chGender, array('m', self::MS_MALE))) {
            // männl., male
            $this->iGender = 2;
        }
    }

    /**
     * Set portrait from image file.
     * Supported types are JPG, PNG, GIF and BMP. <br/>
     * > <b>Note: </b></br>
     * > For transparency the image type itself MUST support transparency (PNG, GIF)
     * > and when reading a portrait, it MUST be saved in the same image format!
     * @param string $strFilename
     */
    public function setPortraitFile(string $strFilename) : void
    {
        if (filter_var($strFilename, FILTER_VALIDATE_URL)) {
            // get type from extension
            $strType = strtolower((string) pathinfo($strFilename, PATHINFO_EXTENSION));
            $this->blobPortrait = 'data:image/' . $strType . ';base64,';

            // use curl to be independet of [allow_url_fopen] enabled on the system
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $strFilename);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $img = curl_exec($curl);
            curl_close($curl);

            if (is_string($img)) {
                $this->blobPortrait .= base64_encode($img);
            }
        } elseif (file_exists($strFilename)) {
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
     * Set the full name.
     * For companies just leave one of the params blank!
     * @param string $strLastName
     * @param string $strFirstName
     */
    public function setName(string $strLastName, string $strFirstName) : void
    {
        $this->strLastName = $strLastName;
        $this->strFirstName = $strFirstName;
    }

    /**
     * Set (honorific) name prefix.
     * i.E. 'Dr.', 'Prof.', ...
     * @param string $strPrefix
     */
    public function setPrefix(string $strPrefix) : void
    {
        $this->strPrefix = $strPrefix;
    }

    /**
     * Set (honorific) name suffix.
     * i.E. 'Jr.', 'M.D.', ...
     * @param string $strSuffix
     */
    public function setSuffix(string $strSuffix) : void
    {
        $this->strSuffix = $strSuffix;
    }

    /**
     * Set nickname.
     * @param string $strNickName
     */
    public function setNickName(string $strNickName) : void
    {
        $this->strNickName = $strNickName;
    }

    /**
     * Set name of the organisation.
     * @param string $strOrganisation
     */
    public function setOrganisation(string $strOrganisation) : void
    {
        $this->strOrganisation = $strOrganisation;
    }

    /**
     * Set section or organizational unit within the organisation.
     * @param string $strSection
     */
    public function setSection(string $strSection) : void
    {
        $this->strSection = $strSection;
    }

    /**
     * Set position, job title or function within the organisation.
     * @param string $strPosition
     */
    public function setPosition(string $strPosition) : void
    {
        $this->strPosition = $strPosition;
    }

    /**
     * Set role, occupation or business category within the organisation.
     * @param string $strRole
     */
    public function setRole(string $strRole) : void
    {
        $this->strPosition = $strRole;
    }

    /**
     * Set homepage
     * @param string $strHomepage
     */
    public function setHomepage(string $strHomepage) : void
    {
        // keep method for backward compatibility!
        // just set value on top of the list!
        array_unshift($this->aHomepages, $strHomepage);
        trigger_error('call of VCardContact::setHomepage() is deprecated - use VCardContact::addtHomepage() instead!', E_USER_DEPRECATED);
    }

    /**
     * Add homepage
     * @param string $strHomepage
     */
    public function addHomepage(string $strHomepage) : void
    {
        $this->aHomepages[] = $strHomepage;
    }

    /**
     * Set annotation.
     * @param string $strNote
     */
    public function setNote(string $strNote) : void
    {
        $this->strNote = $strNote;
    }

    /**
     * Set portrait from base64 encoded image data.
     * @param string $blobPortrait base64 encoded image
     */
    public function setPortraitBlob(string $blobPortrait) : void
    {
        $this->blobPortrait = $blobPortrait;
    }

    /**
     * Save portrait as file.
     * Supportet types are JPG, PNG, GIF and BMP
     * The type depends on the fileextension. If no extensiomnm given, the
     * type of the imported image will be used.
     * @param string $strFilename
     */
    public function savePortrait(string $strFilename) : void
    {
        if (strlen($this->blobPortrait) > 0) {
            $strType = '';
            $strImage = '';
            $this->parseImageData($this->blobPortrait, $strType, $strImage);
            if (strlen($strType) > 0 && strlen($strImage) > 0) {
                $img = $this->imageFromString($strImage, $strType);
                imagealphablending($img, true);
                imagesavealpha($img, true);
                $strExt = strtolower((string) pathinfo($strFilename, PATHINFO_EXTENSION));
                if (strlen($strExt) == 0) {
                    $strExt = strtolower($strType);
                    $strFilename .= '.' . $strExt;
                }
                switch ($strExt) {
                    case 'jpg':
                    case 'jpeg':
                        imagejpeg($img, $strFilename);
                        break;
                    case 'png':
                        imagepng($img, $strFilename);
                        break;
                    case 'gif':
                        imagegif($img, $strFilename);
                        break;
                    case 'bmp':
                        imagebmp($img, $strFilename);
                        break;
                }
            }
        }
    }

    /**
     * Number of addresses the contact contains.
     * @return int
     */
    public function getAddressCount() : int
    {
        return count($this->aAddress);
    }

    /**
     * Get address.
     * An address can be referenced by index or by type. <br/>
     * For Type requests (=> $i non numeric value): <ul>
     * <li> The first address matches specified type is used (contact may contains multiple
     *      addresses of same type)  </li>
     * <li> If VCard::PREF is requested, the first preferred address in contact used (even
     *      if more than one is defined as preferred), if no preferred address found, the
     *      first address within the contact will be returned!   </li></ul>
     * @param int|string $i     reference to address (int => index, string => type)
     * @return VCardAddress|null    valid address object or null, if not found
     */
    public function getAddress($i) : ?VCardAddress
    {
        $oAddr = null;
        if (is_numeric($i)) {
            if ($i >= 0 && $i < count($this->aAddress)) {
                $oAddr = $this->aAddress[$i];
            }
        } else {
            foreach ($this->aAddress as $oAddress) {
                if (strpos($oAddress->getType(), $i) !== false) {
                    $oAddr = $oAddress;
                    break;
                }
            }
        }
        if (!$oAddr && $i == VCard::PREF && count($this->aAddress) > 0) {
            // if preferred item requested and no address in contact defined as prefered, just return first...
            $oAddr = $this->aAddress[0];
        }
        return $oAddr;
    }

    /**
     * Count of phone numbers.
     * @return int
     */
    public function getPhoneCount() : int
    {
        return count($this->aPhone);
    }

    /**
     * Get phone number.
     * Requested number can be referenced by index or type. <br/>
     * For index request: `0 <= $i < self::getPhoneCount()` <br/>
     * For type requests (=> $i non numeric value): <ul>
     * <li> first phone matches specified type is used (contact may contains multiple phone numbers of same type) </li>
     * <li> if VCard::PREF specified, first number in contact used, if no preferred item found </li></ul>
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
            foreach ($this->aPhone as $aPhone) {
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
     * Number of email addresses contained.
     * @return int
     */
    public function getEMailCount() : int
    {
        return count($this->aEMail);
    }

    /**
     * Get EMail addres at given index.
     * @param int $i    index (`0 <= $i < self::getEMailCount()`)
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
     * Number of categories contained.
     * @return int
     */
    public function getCategoriesCount() : int
    {
        return count($this->aCategories);
    }

    /**
     * Get category for given index.
     * @param int $i    index (`0 <= $i < self::getCategoriesCount()`)
     * @return string
     */
    public function getCategory(int $i) : string
    {
        $strCategory = '';
        if ($i >= 0 && $i < count($this->aCategories)) {
            $strCategory = $this->aCategories[$i];
        }
        return $strCategory;
    }

    /**
     * Return Categories separated by comma.
     * @return string
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
     * Get full name.
     * `$strFirstName` followed by `$strLastName` separeted by blank.
     * @return string
     */
    public function getName() : string
    {
        $strSep = (empty($this->strFirstName) || empty($this->strLastName)) ? '' : ' ';
        return $this->strFirstName . $strSep . $this->strLastName;
    }

    /**
     * Get lastname.
     * @return string
     */
    public function getLastName() : string
    {
        return $this->strLastName;
    }

    /**
     * Get firstname.
     * @return string
     */
    public function getFirstName() : string
    {
        return $this->strFirstName;
    }

    /**
     * Get nickname.
     * @return string
     */
    public function getNickName() : string
    {
        return $this->strNickName;
    }

    /**
     * Get name of the organisation.
     * @return string
     */
    public function getOrganisation() : string
    {
        return $this->strOrganisation;
    }

    /**
     * Get position, job title or function within the organisation.
     * @return string
     */
    public function getPosition() : string
    {
        return $this->strPosition;
    }

    /**
     * Get role, occupation or business category within the organisation.
     * @return string
     */
    public function getRole() : string
    {
        return $this->strRole;
    }

    /**
     * Number of homepages contained.
     * @return int
     */
    public function getHomepageCount() : int
    {
        return count($this->aHomepages);
    }

    /**
     * Get homepage for given index.
     * @param int $i    index (`0 <= $i < self::getHomepageCount()`)
     * @return string
     */
    public function getHomepage(int $i = -1) : string
    {
        $strHomepage = '';
        if ($i === -1) {
            // default value -1 set for backward compatibility but give chance for a 'deprecated' message!
            // version < 1.05 of this package hadn't support for multiple homepages!
            trigger_error('call of VCardContact::getHomepage() without index is deprecated!', E_USER_DEPRECATED);
            $i = 0;
        }
        if ($i >= 0 && $i < count($this->aHomepages)) {
            $strHomepage = $this->aHomepages[$i];
        }
        return $strHomepage;
    }

    /**
     * Get date of birth.
     * The return type can be specified in the `$iType`parameter: <ul>
     * <li><b> self::DT_STRING (default):</b> Date as String in f´the format set with `$strFormat`param (default = 'Y-m-d') </li>
     * <li><b> self::DT_UNIX_TIMESTAMP:</b> Date as unix timestamp</li>
     * <li><b> self::DT_OBJECT:</b> Date as DateTime object </li></ul>
     *
     * if the property is not set in the contact method returns: <ul>
     * <li><b> self::DT_STRING:</b> empty string </li>
     * <li><b> self::DT_UNIX_TIMESTAMP:</b> integer 0</li>
     * <li><b> self::DT_OBJECT:</b> null </li></ul>
     *
     * @link https://datatracker.ietf.org/doc/html/rfc2426#section-3.1.5
     * @link https://www.php.net/manual/en/datetime.format.php
     * @param int $iType    self::DT_STRING (default), self::DT_UNIX_TIMESTAMP or self::DT_OBJECT
     * @param string $strFormat Date format compliant to DateTime::format() (default 'Y-m-d')
     * @return string|int|\DateTime
     */
    public function getDateOfBirth(int $iType = self::DT_STRING, string $strFormat = 'Y-m-d')
    {
        $dtBirth = new \DateTime($this->strDateOfBirth);
        switch ($iType) {
            case self::DT_UNIX_TIMESTAMP:
                return (empty($this->strDateOfBirth) ? 0 : $dtBirth->getTimestamp());
            case self::DT_OBJECT:
                return (empty($this->strDateOfBirth) ? null : $dtBirth);
            default:
                return (empty($this->strDateOfBirth) ? '' : $dtBirth->format($strFormat));
        }
    }

    /**
     * Get gender (Microsoft only).
     * @return int  0: not set, 1: female, 2: male
     */
    public function getGender() : int
    {
        return $this->iGender;
    }

    /**
     * Get section or organizational unit within the organisation.
     * @return string
     */
    public function getSection() : string
    {
        return $this->strSection;
    }

    /**
     * Get annotation.
     * @return string
     */
    public function getNote() : string
    {
        return $this->strNote;
    }

    /**
     * Get (honorific) name prefix.
     * i.E. 'Dr.', 'Prof.', ...
     * @return string
     */
    public function getPrefix() : string
    {
        return $this->strPrefix;
    }

    /**
     * Get (honorific) name suffix.
     * i.E. 'Jr.', 'M.D.', ...
     * @return string
     */
    public function getSuffix() : string
    {
        return $this->strSuffix;
    }

    /**
     * Get the image as base64 encoded string.
     * @return string base64 encoded image
     */
    public function getPortraitBlob() : string
    {
        return $this->blobPortrait;
    }
}
