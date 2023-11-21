<?php
/**
 * User: Parag Mehta<parag@paragm.com>
 * Date: 2/27/12
 * Time: 7:27 AM
 * This file is created by www.thesslstore.com for your use. You are free to change the file as per your needs.
 */

class baserequest
{
    public $AuthRequest;
    public function __construct()
    {
        $this->AuthRequest = new apirequest();
    }
    public function __toString()
   	{
   		return var_export($this,true);
   	}
}

class baseresponse
{
    public $AuthResponse;
    public function __construct()
    {
        $this->AuthResponse = new apiresponse();
    }
    public function __toString()
	{
		return var_export($this,true);
	}
}

class curlresponse
{
	public $info;
	public $response;
	public $error='';
}

/* Common class across request/response */

class apiresponse
{
   public $isError = false;
   public $Message;
   public $Timestamp = '';
   public $ReplayToken = '';
   public $InvokingPartnerCode='';
   public function __toString()
   {
       return var_export($this,true);
   }
}

class apirequest
{
	public $PartnerCode = '';
	public $AuthToken = '';
    public $ReplayToken = '';
    public $UserAgent = '';
    public $TokenID = '';
    public $TokenCode = '';
    public $IPAddress = '';
    public $IsUsedForTokenSystem = false;
    public $Token = '';
}

class OrganizationAddress
{
    public $AddressLine1;
    public $AddressLine2;
    public $AddressLine3;
    public $City;
    public $Region;
    public $PostalCode;
    public $Country;
    public $Phone;
    public $Fax;
    public $LocalityName;
}

class OrganizationInfo
{
    public $OrganizationName;
    public $DUNS;
    public $Division;
    public $IncorporatingAgency;
    public $RegistrationNumber;
    public $JurisdictionCity;
    public $JurisdictionRegion;
    public $JurisdictionCountry;
    /**
     * @var
     */
    public $OrganizationAddress;
}

class contact
{
    public $FirstName;
    public $LastName;
    public $Phone;
    public $Fax;
    public $Email;
    public $Title;
    public $OrganizationName;
    public $AddressLine1;
    public $AddressLine2;
    public $City;
    public $Region;
    public $PostalCode;
    public $Country;
}

class oldNewPair
{
    public $OldValue;
    public $NewValue;
}

class certificate
{
    public $FileName;
    public $FileContent;
}

class orderStatus
{
    public $isTinyOrder;
    public $isTinyOrderClaimed;
    public $MajorStatus;
    public $MinorStatus;
    public $AuthenticationStatuses;
}

class productResponse
{
    public $ProductCode;
    public $ProductName;
    public $CanbeReissued;
    public $ReissueDays;
}

class productPricing
{
    public $NumberOfMonths;
    public $NumberOfServer;
    public $Price;
    public $PricePerAdditionalSAN;
    public $PricePerAdditionalServer;
    public $SRP;
}