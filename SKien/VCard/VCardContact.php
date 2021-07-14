<?php
declare(strict_types=1);

namespace SKien\VCard;

/**
 * Class representing all data to one contact.
 *
 * Uses helpers from trait VCardHelper
 * @see VCardHelper
 *
* history:
 * date         version
 * 2020-02-23   initial version.
 * 2020-05-28   renamed namespace to fit PSR-4 recommendations for autoloading.
 * 2020-07-22   added missing PHP 7.4 type hints / docBlock changes
 * 2021-07-14   The transparency of images is retained
 *
 * @package SKien-VCard
 * @since 1.0.0
 * @version 1.0.4
 * @author Stefanius <s.kientzler@online.de>
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
    protected array $aAddress = array();
    /** @var array   array of phone numbers */
    protected array $aPhone = array();
    /** @var array   array of email addresses   */
    protected array $aEMail = array();
    /** @var array   array of categories    */
    protected array $aCategories = array();
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
     * Create empty contact
     */
    public function __construct()
    {
    }

    /**
     * Add address.
     * Only one address should be marked as preferred. In case of multiple addresses
     * specified as preferred, last call counts!
     * @param VCardAddress $oAddress
     * @param bool $bPreferred
     */
    public function addAddress(VCardAddress $oAddress, bool $bPreferred) : void
    {
        $oAddress->setPreferred($bPreferred);
        $this->aAddress[] = $oAddress;
    }

    /**
     * Aadd phone number.
     * Can also be used to set FAX number
     * there may be defined multiple numbers with same type.
     * @param string $strPhone
     * @param string $strType   one of VCard::WORK, VCard::HOME, VCard::CELL, VCard::FAX
     * @param bool $bPreferred
     */
    public function addPhone(string $strPhone, string $strType, bool $bPreferred) : void
    {
        if ($bPreferred && strpos($strType, 'PREF') === false) {
            $strType .= ',PREF';
        }
        $this->aPhone[] = array('strPhone' => $strPhone, 'strType' => $strType);
    }

    /**
     * aAdd mail address.
     * @param string $strEMail
     * @param bool $bPreferred
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
     * Add category.
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
     * MS-extension!
     * windows contacts: export/import.
     * outlook: import only.
     * only male or female accepted
     * @param string $strGender
     */
    public function setGender(string $strGender) : void
    {
        $chGender = strtolower(substr($strGender, 0, 1));
        if (in_array($chGender, array('w', 'f', '1'))) {
            // weibl., female
            $this->iGender = 1;
        } elseif (in_array($chGender, array('m', '2'))) {
            // männl., male
            $this->iGender = 2;
        }
    }

    /**
     * Set portrait from image file.
     * supported types are JPG, PNG, GIF and BMP
     * @param string $strFilename
     */
    public function setPortraitFile(string $strFilename) : void
    {
        if (filter_var($strFilename, FILTER_VALIDATE_URL)) {
            // get type from extension
            $strType = strtolower((string) pathinfo($strFilename, PATHINFO_EXTENSION));
            $this->blobPortrait = 'data:image/' . $strType . ';base64,';

            // use curl to be independet of [allow_url_fopen] enabled on system
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
     * @param string $strLastName
     * @param string $strFirstName
     */
    public function setName(string $strLastName, string $strFirstName) : void
    {
        $this->strLastName = $strLastName;
        $this->strFirstName = $strFirstName;
    }

    /**
     * @param string $strPrefix
     */
    public function setPrefix(string $strPrefix) : void
    {
        $this->strPrefix = $strPrefix;
    }

    /**
     * @param string $strSuffix
     */
    public function setSuffix(string $strSuffix) : void
    {
        $this->strSuffix = $strSuffix;
    }

    /**
     * @param string $strNickName
     */
    public function setNickName(string $strNickName) : void
    {
        $this->strNickName = $strNickName;
    }

    /**
     * @param string $strOrganisation
     */
    public function setOrganisation(string $strOrganisation) : void
    {
        $this->strOrganisation = $strOrganisation;
    }

    /**
     * @param string $strSection
     */
    public function setSection(string $strSection) : void
    {
        $this->strSection = $strSection;
    }

    /**
     * @param string $strPosition
     */
    public function setPosition(string $strPosition) : void
    {
        $this->strPosition = $strPosition;
    }

    /**
     * @param string $strRole
     */
    public function setRole(string $strRole) : void
    {
        $this->strPosition = $strRole;
    }

    /**
     * @param string $strHomepage
     */
    public function setHomepage(string $strHomepage) : void
    {
        $this->strHomepage = $strHomepage;
    }

    /**
     * @param string $strNote
     */
    public function setNote(string $strNote) : void
    {
        $this->strNote = $strNote;
    }

    /**
     * Set portrait from data.
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
     * can be referenced by index or type.
     * type requests (=> $i non numeric value):
     * - first address matches specified type is used (contact may contains multiple addresses of same type)
     * - if VCard::PREF specified, first address in contact used, if no preferred item found
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
     * can be referenced by index or type.
     * type requests (=> $i non numeric value):
     * - first phone matches specified type is used (contact may contains multiple phone numbers of same type)
     * - if VCard::PREF specified, first number in contact used, if no preferred item found
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
     * Number of EMail addresses.
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
     * Number of categories.
     * @return int
     */
    public function getCategoriesCount() : int
    {
        return count($this->aCategories);
    }

    /**
     * @param int $i
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
     * Get date of birth.
     * @return string   format YYYY-DD-MM
     */
    public function getDateOfBirth() : string
    {
        return $this->strDateOfBirth;
    }

    /**
     * Get gender (MS only).
     * @return int  0: not set, 1: male, 2: female
     */
    public function getGender() : int
    {
        return $this->iGender;
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
