<?php
declare(strict_types=1);

namespace SKien\VCard;

/**
 * Helper class that parses property lines from a VCard format file.
 *
 * @package SKien-VCard
 * @since 1.0.4
 * @version 1.0.4
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class VCardContactReader
{
    use VCardHelper;

    /** @var VCardContact the contact to write     */
    protected VCardContact $oContact;

    /**
     * Create a contact reader object.
     * @param VCardContact $oContact    the contact to build
     */
    function __construct(VCardContact $oContact)
    {
        $this->oContact = $oContact;
    }

    /**
     * Add property from import file.
     * @param string $strName
     * @param array $aParams
     * @param string $strValue
     */
    public function addProperty(string $strName, array $aParams, string $strValue) : void
    {
        // table to parse property depending on propertyname.
        // value have to be either name of method with signature
        //
        //      methodname( string strValue, array aParams )
        //
        // or
        //      propertyname  ( => unmasked value will be assigned to property of contact object)
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
            'NICKNAME'      => 'setNickName',
            'TITLE'         => 'setPosition',
            'ROLE'          => 'setRole',
            'URL'           => 'setHomepage',
            'NOTE'          => 'setNote',
            'LABEL'         => 'setLabel',
            'BDAY'          => 'setDateOfBirth',
            'X-WAB-GENDER'  => 'setGender'
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
            } elseif (method_exists($this->oContact, $strPtr)) {
                // call setter from contact with unmasket value
                call_user_func_array(array($this->oContact, $strPtr), array($strValue));
            }
        }
    }

    /**
     * Explode string into name components.
     * Order of the components separated by ';':
     *  - family name
     *  - given name
     *  - additional name(s) (not supported)
     *  - honorific prefixes
     *  - honorific suffixes
     *  delimitered by semicolon (be aware of masked delimiters)
     * @param string $strValue
     * @param array $aParams
     */
    protected function parseName(string $strValue, array $aParams) : void
    {
        $aSplit = $this->explodeMaskedString(';', $strValue);
        // family name; given name; ; honorific prefixes; honorific suffixes
        $strLastName = $this->unmaskString($aSplit[0]);
        $strFirstName = isset($aSplit[1]) ? $this->unmaskString($aSplit[1]) : '';
        $this->oContact->setName($strLastName, $strFirstName);
        if (isset($aSplit[3])) {
            $this->oContact->setPrefix($this->unmaskString($aSplit[3]));
        }
        if (isset($aSplit[4])) {
            $this->oContact->setSuffix($this->unmaskString($aSplit[4]));
        }
    }

    /**
     * @param string $strValue
     * @param array $aParams
     * @see VCardAddress::parseFullAddress()
     */
    protected function parseAdr(string $strValue, array $aParams) : void
    {
        $oAdr = new VCardAddress();
        $oAdr->parseFullAddress($strValue, $aParams);
        $this->oContact->addAddress($oAdr, false);
    }

    /**
     * Unmask value and add with typeinfo to phone list.
     * @param string $strValue
     * @param array $aParams
     */
    protected function parseTel(string $strValue, array $aParams) : void
    {
        $strValue = $this->unmaskString($strValue);
        $this->oContact->addPhone($strValue, $aParams['TYPE'], strpos($aParams['TYPE'], 'PREF') !== false);
    }

    /**
     * Unmask value and add to email list.
     * @param string $strValue
     * @param array $aParams
     */
    protected function parseEMail(string $strValue, array $aParams) : void
    {
        $strValue = $this->unmaskString($strValue);
        $this->oContact->addEMail($strValue, strpos($aParams['TYPE'], 'PREF') !== false);
    }

    /**
     * Split into company and section.
     * @param string $strValue
     * @param array $aParams
     */
    protected function parseOrg(string $strValue, array $aParams) : void
    {
        $aSplit = $this->explodeMaskedString(';', $strValue);
        $this->oContact->setOrganisation($this->unmaskString($aSplit[0]));
        if (isset($aSplit[1])) {
            $this->oContact->setSection($this->unmaskString($aSplit[1]));
        }
    }

    /**
     * Split comma separated categories.
     * @param string $strValue
     * @param array $aParams
     */
    protected function parseCategories(string $strValue, array $aParams) : void
    {
        $aSplit = $this->explodeMaskedString(',', $strValue);
        foreach ($aSplit as $strCategory) {
            $this->oContact->addCategory($this->unmaskString($strCategory));
        }
    }

    /**
     * @param string $strValue
     * @param array $aParams
     */
    protected function parsePhoto(string $strValue, array $aParams) : void
    {
        $strEncoding = isset($aParams['ENCODING']) ? $aParams['ENCODING'] : '';
        if ($strEncoding == 'B' || $strEncoding == 'BASE64') {
            $strType = strtolower($aParams['TYPE']);
            $this->oContact->setPortraitBlob('data:image/' . $strType . ';base64,' . $strValue);
        } else {
            // assuming URL value... e.g. export from google contacts
            $this->oContact->setPortraitFile($strValue);
        }
    }
}