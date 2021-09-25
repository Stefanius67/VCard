<?php
declare(strict_types=1);

namespace SKien\VCard;

/**
 * Helper class that builds the string buffer for one contact in the VCard format.
 *
 * @package VCard
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 * @internal
 */
class VCardContactWriter
{
    use VCardHelper;

    /** @var VCardContact the contact to write     */
    protected VCardContact $oContact;
    /** @var string internal buffer     */
    protected string $buffer = '';

    /**
     * Create a contact writer object.
     * @param VCardContact $oContact    the contact to build
     */
    function __construct(VCardContact $oContact)
    {
        $this->oContact = $oContact;
    }

    /**
     * Return internal string buffer.
     * @return string
     */
    public function __toString() : string
    {
        return $this->buildData();
    }

    /**
     * Build the contact as VCard compatible string.
     * @return string
     */
    public function buildData() : string
    {
        $this->startBuffer();

        $this->buffer .= $this->buildName();
        $this->buffer .= $this->buildOrganization();
        $this->buffer .= $this->buildAddresses();
        $this->buffer .= $this->buildPhoneNumbers();
        $this->buffer .= $this->buildMailaddresses();
        $this->buffer .= $this->buildHomepages();
        $this->buffer .= $this->buildAdditionalData();
        $this->buffer .= $this->buildCategories();
        $this->buffer .= $this->buildPhoto();

        $this->endBuffer();

        return $this->buffer;
    }

    /**
     * Create the start sequence.
     */
    protected function startBuffer() : void
    {
        $this->buffer = 'BEGIN:VCARD' . PHP_EOL;
        $this->buffer .= 'VERSION:3.0' . PHP_EOL;
    }

    /**
     * Create the end sequence.
     */
    protected function endBuffer() : void
    {
        // END
        $this->buffer .= 'END:VCARD' . PHP_EOL;
    }

    /**
     * Build all name relevant contact properties.
     * @return string
     */
    protected function buildName() : string
    {
        // name properties
        // family name; given name; additional name(s); honorific prefixes; honorific sufffixes
        $strName  = $this->maskString($this->oContact->getLastName()) . ';';
        $strName .= $this->maskString($this->oContact->getFirstName()) . ';';
        $strName .= ';';
        $strName .= $this->maskString($this->oContact->getPrefix()) . ';';
        $strName .= $this->maskString($this->oContact->getSuffix());

        $buffer = $this->buildProperty('N', $strName, false);
        $buffer .= $this->buildProperty('FN', $this->oContact->getName());
        $buffer .= $this->buildProperty('NICKNAME', $this->oContact->getNickName());

        return $buffer;
    }

    /**
     * Build all organization relevant contact properties.
     * @return string
     */
    protected function buildOrganization() : string
    {
        // organisation
        $strOrg  = $this->maskString($this->oContact->getOrganisation()) . ';';
        $strOrg .= $this->maskString($this->oContact->getSection());

        $buffer = $this->buildProperty('ORG', $strOrg, false);
        $buffer .= $this->buildProperty('TITLE', $this->oContact->getPosition());
        $buffer .= $this->buildProperty('ROLE', $this->oContact->getRole());

        return $buffer;
    }

    /**
     * Build all addresses.
     * @return string
     */
    protected function buildAddresses() : string
    {
        // addresses
        $buffer = '';
        $iCnt = $this->oContact->getAddressCount();
        $iPref = 0;
        for ($i = 0; $i < $iCnt; $i++) {
            $oAddress = $this->oContact->getAddress($i);
            if ($oAddress) {
                $buffer .= $oAddress->buildFullAddress();
                $buffer .= $oAddress->buildLabel();
                if ($oAddress->isPreferred()) {
                    $iPref = $i;
                }
            }
        }
        // set preferred address also as default postal address for MS
        $buffer .= $this->buildProperty('X-MS-OL-DEFAULT-POSTAL-ADDRESS', (string) ($iPref + 1));

        return $buffer;
    }

    /**
     * Build all phone numbers.
     * @return string
     */
    protected function buildPhoneNumbers() : string
    {
        // phone numbers
        $buffer = '';
        $iCnt = $this->oContact->getPhoneCount();
        for ($i = 0; $i < $iCnt; $i++) {
            $aPhone = $this->oContact->getPhone($i);
            if ($aPhone) {
                $strName = 'TEL;TYPE=' . $aPhone['strType'];
                $buffer .= $this->buildProperty($strName, $aPhone['strPhone']);
            }
        }
        return $buffer;
    }

    /**
     * Build all e-mail addresses.
     * @return string
     */
    protected function buildMailaddresses() : string
    {
        // mailaddresses
        $buffer = '';
        $iCnt = $this->oContact->getEMailCount();
        for ($i = 0; $i < $iCnt; $i++) {
            $buffer .= $this->buildProperty('EMAIL;TYPE=INTERNET', $this->oContact->getEMail($i));
        }
        return $buffer;
    }

    /**
     * Build all homepages.
     * @return string
     */
    protected function buildHomepages() : string
    {
        // homepages
        $buffer = '';
        $iCnt = $this->oContact->getHomepageCount();
        for ($i = 0; $i < $iCnt; $i++) {
            $buffer .= $this->buildProperty('URL;TYPE=WORK', $this->oContact->getHomepage($i));
        }
        return $buffer;
    }

    /**
     * Build all additional properties.
     * @return string
     */
    protected function buildAdditionalData() : string
    {
        // personal data
        $buffer = $this->buildProperty('BDAY', $this->oContact->getDateOfBirth());  /** @phpstan-ignore-line */
        if ($this->oContact->getGender() > 0) {
            $buffer .= $this->buildProperty('X-WAB-GENDER', (string) $this->oContact->getGender());
        }
        // annotation
        $buffer .= $this->buildProperty('NOTE', $this->oContact->getNote());

        return $buffer;
    }

    /**
     * Build all categories.
     * @return string
     */
    protected function buildCategories() : string
    {
        // categories
        $iCnt = $this->oContact->getCategoriesCount();
        $strSep = '';
        $strValue = '';
        for ($i = 0; $i < $iCnt; $i++) {
            $strValue .= $strSep . $this->maskString($this->oContact->getCategory($i));
            $strSep = ',';
        }
        return $this->buildProperty('CATEGORIES', $strValue, false);
    }

    /**
     * Build the photo.
     * @return string
     */
    protected function buildPhoto() : string
    {
        // photo
        $buffer = '';
        $blobPortrait = $this->oContact->getPortraitBlob();
        if (strlen($blobPortrait) > 0) {
            // extract image type from binary data
            $strType = '';
            $strImage = '';
            $this->parseImageData($blobPortrait, $strType, $strImage);
            if (strlen($strType) > 0 && strlen($strImage) > 0) {
                $strName = 'PHOTO;TYPE=' . $strType . ';ENCODING=B';
                $buffer .= $this->buildProperty($strName, $strImage, false);
                $buffer .= PHP_EOL; // even though in vcard 3.0 spec blank line after binary value no longer is requires, MS Outlook need it...
            }
        }
        return $buffer;
    }
}