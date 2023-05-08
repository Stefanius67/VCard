<?php
declare(strict_types=1);

namespace SKien\VCard;

/**
 * Class representing one address within a contact.
 *
 * #### Add an address to a contact for writing:
 * Create a new instance of a `VCardAdress`, set its property and add the address
 * to a contact using `VCardContact::addAddress()`
 *
 * #### Retrieve an address within a contact:
 * Use `VCardContact::getAddress()` to retrieve existing address within a given
 * contact.
 *
 * @see  VCardContact::addAddress()
 * @see  VCardContact::getAddress()
 *
 * @package VCard
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class VCardAddress
{
    use VCardHelper;

    /** @var string  street (including house number)    */
    protected string $strStr = '';
    /** @var string  city   */
    protected string $strCity = '';
    /** @var string  postcode   */
    protected string $strPostcode = '';
    /** @var string  country */
    protected string $strCountry = '';
    /** @var string  region */
    protected string $strRegion = '';
    /** @var string  post office box */
    protected string $strPOBox = '';
    /** @var string  extended address (e.g. apartment or suite number) */
    protected string $strExtAddress = '';
    /** @var string  type (VCard::HOME, VCard::WORK, VCard::POSTAL, VCard::PARCEL)  */
    protected string $strType = '';
    /** @var bool  preferred address  */
    protected bool $bPreferred = false;

    /**
     * Full address information.
     * Build semicolon delimitered string containing:
     *  - post office address (postbox)
     *  - extended address (e.g. apartment or suite number)
     *  - street (including house number)
     *  - city
     *  - region
     *  - postal code
     *  - country
     * @return string
     * @internal only should be called by the VCardContactWriter
     */
    public function buildFullAddress() : string
    {
        $strField = 'ADR;TYPE=' . $this->strType;
        if ($this->bPreferred) {
            $strField .= ',PREF';
        }
        // postbox
        // extended address
        // street (including house number)
        // city
        // region
        // postal code
        // country
        // values separated by semikolon
        $strValue  = $this->maskString($this->strPOBox) . ';';
        $strValue .= $this->maskString($this->strExtAddress) . ';';
        $strValue .= $this->maskString($this->strStr) . ';';
        $strValue .= $this->maskString($this->strCity) . ';';
        $strValue .= $this->maskString($this->strRegion) . ';';
        $strValue .= $this->maskString($this->strPostcode) . ';';
        $strValue .= $this->maskString($this->strCountry);

        return $this->buildProperty($strField, $strValue, false);
    }

    /**
     * Build label for ther address.
     * @return string
     * @internal only should be called by the VCardContactWriter
     */
    public function buildLabel(): string
    {
        $strField = 'LABEL;TYPE=' . $this->strType;
        if ($this->bPreferred) {
            $strField .= ',PREF';
        }

        // values separated by semikolon
        $strValue  = $this->strStr . PHP_EOL;
        $strValue .= $this->strPostcode . ' ' . $this->strCity . PHP_EOL;
        if (strlen($this->strRegion) > 0 || strlen($this->strCountry) > 0) {
            $strSep = (empty($this->strRegion) || empty($this->strCountry)) ? '' : ' - ';
            $strValue .= $this->strRegion . $strSep . $this->strCountry . PHP_EOL;
        }

        return $this->buildProperty($strField, $strValue);
    }

    /**
     * explode string into address components:
     *  - post office address (postbox)
     *  - extended address (e.g. apartment or suite number)
     *  - street (including house number)
     *  - city
     *  - region
     *  - postal code
     *  - country
     *  delimitered by semicolon (be aware of masked delimiters)
     *
     * @param string $strValue
     * @param array<string,string>  $aParams
     * @internal only should be called by the VCardContactReader
     */
    public function parseFullAddress(string $strValue, array $aParams) : void
    {
        $aSplit = $this->explodeMaskedString(';', $strValue);

        $this->strPOBox = $this->unmaskString($aSplit[0] ?? '');
        $this->strExtAddress = $this->unmaskString($aSplit[1] ?? '');
        $this->strStr = $this->unmaskString($aSplit[2] ?? '');
        $this->strCity = $this->unmaskString($aSplit[3] ?? '');
        $this->strRegion = $this->unmaskString($aSplit[4] ?? '');
        $this->strPostcode = $this->unmaskString($aSplit[5] ?? '');
        $this->strCountry = $this->unmaskString($aSplit[6] ?? '');
        $this->strType = $aParams['TYPE'] ?? VCard::HOME;
    }

    /**
     * Set street.
     * @param string $strStr
     */
    public function setStr(string $strStr) : void
    {
        $this->strStr = $strStr;
    }

    /**
     * Set city.
     * @param string $strCity
     */
    public function setCity(string $strCity) : void
    {
        $this->strCity = $strCity;
    }

    /**
     * Set Postcode
     * @param string $strPostcode
     */
    public function setPostcode(string $strPostcode) : void
    {
        $this->strPostcode = $strPostcode;
    }

    /**
     * Set country.
     * @param string $strCountry
     */
    public function setCountry(string $strCountry) : void
    {
        $this->strCountry = $strCountry;
    }

    /**
     * Set region.
     * @param string $strRegion
     */
    public function setRegion(string $strRegion) : void
    {
        $this->strRegion = $strRegion;
    }

    /**
     * Set post office box.
     * @param string $strPOBox
     */
    public function setPOBox(string $strPOBox) : void
    {
        $this->strPOBox = $strPOBox;
    }

    /**
     * Set extended address (e.g. apartment or suite number).
     * @param string $strExtAddress
     */
    public function setExtAddress(string $strExtAddress) : void
    {
        $this->strExtAddress = $strExtAddress;
    }

    /**
     * Set type.
     * Any combination of the predefined types VCard::PREF, VCard::WORK, VCard::HOME
     * VCard::POSTAL, VCard::PARCEL, VCard::INTER or VCard::DOMESTIC can be set.
     * @param string|array<string> $type    one single type or an array of multiple types
     */
    public function setType($type) : void
    {
        $this->strType = is_array($type) ? implode(',', $type) : $type;
    }

    /**
     * Set this address as preferred.
     * @param bool $bPreferred
     */
    public function setPreferred(bool $bPreferred) : void
    {
        $this->bPreferred = $bPreferred;
    }

    /**
     * Get street.
     * @return string $strStr
     */
    public function getStr() : string
    {
        return $this->strStr;
    }

    /**
     * Get city.
     * @return string  $strCity
     */
    public function getCity() : string
    {
        return $this->strCity;
    }

    /**
     * Get postcode.
     * @return string  $strPostcode
     */
    public function getPostcode() : string
    {
        return $this->strPostcode;
    }

    /**
     * Get country.
     * @return string  $strCountry
     */
    public function getCountry() : string
    {
        return $this->strCountry;
    }

    /**
     * Get region.
     * @return string  $strRegion
     */
    public function getRegion() : string
    {
        return $this->strRegion;
    }

    /**
     * Get post office box.
     * @return string  $strPOBox
     */
    public function getPOBox() : string
    {
        return $this->strPOBox;
    }

    /**
     * Get extended address (e.g. apartment or suite number).
     * @return string  $strRegion
     */
    public function getExtAddress() : string
    {
        return $this->strExtAddress;
    }

    /**
     * Get type.
     * @return string  $strType can b comma separated list of multiple types.
     */
    public function getType() : string
    {
        return $this->strType;
    }

    /**
     * Get preferred state.
     * @return bool $bPreferred
     */
    public function isPreferred() : bool
    {
        return $this->bPreferred;
    }
}