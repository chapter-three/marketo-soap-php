<?php

namespace ChapterThree\MarketoSoap;

use ChapterThree\MarketoSoap\Api\ActivityTypeFilter;
use ChapterThree\MarketoSoap\Api\ArrayOfActivityType;
use ChapterThree\MarketoSoap\Api\ArrayOfAttribute;
use ChapterThree\MarketoSoap\Api\ArrayOfLeadKey;
use ChapterThree\MarketoSoap\Api\Attribute;
use ChapterThree\MarketoSoap\Api\LeadKey;
use ChapterThree\MarketoSoap\Api\LeadRecord;
use ChapterThree\MarketoSoap\Api\ParamsGetCampaignsForSource;
use ChapterThree\MarketoSoap\Api\ParamsGetLead;
use ChapterThree\MarketoSoap\Api\ParamsGetLeadActivity;
use ChapterThree\MarketoSoap\Api\ParamsRequestCampaign;
use ChapterThree\MarketoSoap\Api\ParamsSyncLead;
use ChapterThree\MarketoSoap\Api\StreamPosition;

/**
 * Class SoapClient.
 *
 * @package ChapterThree\MarketoSoap
 */
class MarketoSoapClient implements MarketoSoapClientInterface {

  const MKTOWS_NAMESPACE = 'http://www.marketo.com/mktows/';

  /**
   * Enable debug output.
   *
   * @var bool
   */
  public $debug = FALSE;

  /**
   * Marketo user id.
   *
   * @var string
   */
  protected $userId;

  /**
   * Marketo secret key.
   *
   * @var string
   */
  protected $secretKey;

  /**
   * Marketo endpoint url.
   *
   * @var string
   */
  protected $endpoint;

  /**
   * SOAP Client.
   *
   * @var \SoapClient
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  public function __construct($userID, $secretKey, $endpoint) {
    $this->userId = $userID;
    $this->secretKey = $secretKey;
    $this->endpoint = $endpoint;
  }

  /**
   * Get SOAP client object.
   */
  protected function client() {
    if (empty($this->client)) {
      $options = array("connection_timeout" => 20, "location" => $this->endpoint);
      if ($this->debug) {
        $options["trace"] = TRUE;
      }
      $this->client = new \SoapClient($this->endpoint . '?WSDL', $options);
    }
    return $this->client;
  }

  /**
   * Gets auth header for SOAP requests.
   *
   * @return \SoapHeader
   *   Header object to add to request.
   */
  private function _getAuthenticationHeader() {
    $timestamp = date("c");
    $encryptString = $timestamp . $this->userId;
    $signature = hash_hmac('sha1', $encryptString, $this->secretKey);

    $attrs = (object) [
      'mktowsUserId' => $this->userId,
      'requestSignature' => $signature,
      'requestTimestamp' => $timestamp,
    ];

    $soapHdr = new \SoapHeader(self::MKTOWS_NAMESPACE, 'AuthenticationHeader', $attrs);
    return $soapHdr;
  }

  /**
   * {@inheritdoc}
   */
  public function getLead($keyType, $keyValue) {
    $retLead = NULL;

    $leadKey = new LeadKey();
    $leadKey->keyType = $keyType;
    $leadKey->keyValue = $keyValue;

    $params = new ParamsGetLead();
    $params->leadKey = $leadKey;

    $options = array();

    $authHdr = $this->_getAuthenticationHeader();

    try {
      $success = $this->client()
        ->__soapCall('getLead', array($params), $options, $authHdr);

      if ($this->debug) {
        $req = $this->client()->__getLastRequest();
        echo "RAW request:\n$req\n";
        $resp = $this->client()->__getLastResponse();
        echo "RAW response:\n$resp\n";
      }

      if (isset($success->result)) {
        if ($success->result->count > 1) {
          // Is this okay?  If not, raise exception.
        }
        if (isset($success->result->leadRecordList->leadRecord)) {
          $leadRecList = $success->result->leadRecordList->leadRecord;
          if (!is_array($leadRecList)) {
            /** @var array $leadRecList */
            $leadRecList = array($leadRecList);
            /** @var integer $count */
            $count = count($leadRecList);
            if ($count > 0) {
              $retLead = $leadRecList[$count - 1];
            }
          }
        }
      }
    }
    catch (\SoapFault $ex) {
      $ok = FALSE;
      $faultCode = NULL;
      if (!empty($ex->faultCode)) {
        $faultCode = $ex->faultCode;
      }
      switch ($this->getErrorCode($ex)) {

        case MarketoSoapError::ERR_LEAD_NOT_FOUND:
          $ok = TRUE;
          break;

        default:

      }
      if (!$ok) {
        if ($faultCode != NULL) {
          if (strpos($faultCode, 'Client')) {
            // This is a client error.  Check the other codes and handle.
          }
          elseif (strpos($faultCode, 'Server')) {
            // This is a server error.  Call Marketo support with details.
          }
          else {
            // W3C spec has changed :)
            // But seriously, Call Marketo support with details.
          }
        }
        else {
          // Not a good place to be.
        }
      }
    }
    catch (\Exception $ex) {
      $msg = $ex->getMessage();
      $req = $this->client()->__getLastRequest();
      echo "Error occurred for request: $msg\n$req\n";
      var_dump($ex);
      exit(1);
    }

    return $retLead;
  }


  public function syncLead($key, $attrs) {
    // Build array of Attribute objects.
    $attrArray = array();
    foreach ($attrs as $attrName => $attrValue) {
      $a = new Attribute();
      $a->attrName = $attrName;
      $a->attrValue = $attrValue;
      $attrArray[] = $a;
    }
    $aryOfAttr = new ArrayOfAttribute();
    $aryOfAttr->attribute = $attrArray;

    // Build LeadRecord.
    $leadRec = new LeadRecord();
    $leadRec->leadAttributeList = $aryOfAttr;

    // Set the unique lead key.
    if (is_numeric($key)) {
      // Marketo system ID.
      $leadRec->Id = $key;
    }
    else {
      // @todo Add email format validation - should be SMTP email address.
      $leadRec->Email = $key;
    }

    // Build API params.
    $params = new ParamsSyncLead();
    $params->leadRecord = $leadRec;

    // Don't return the full lead record - just the ID.
    $params->returnLead = FALSE;
    // Add the marketo tracking cookie if it exists.
    if (isset($_COOKIE['_mkto_trk'])) {
      $params->marketoCookie = $_COOKIE['_mkto_trk'];
    }

    $options = array();

    $authHdr = $this->_getAuthenticationHeader();
    try {
      $success = $this->client()->__soapCall('syncLead', array($params), $options, $authHdr);
      if ($this->debug) {
        $req = $this->client()->__getLastRequest();
        echo "RAW request:\n$req\n";
        $resp = $this->client()->__getLastResponse();
        echo "RAW response:\n$resp\n";
      }
    }
    catch (\SoapFault $ex) {
      // @todo: Replace with a callback invocation.
      // watchdog("marketo", "Marketo SOAP error:" . print_r($ex->detail, TRUE), NULL, WATCHDOG_WARNING);

      $ok = FALSE;
      $faultCode = NULL;
      if (!empty($ex->faultCode)) {
        $faultCode = $ex->faultCode;
      }
      switch ($this->getErrorCode($ex)) {

        case MarketoSoapError::ERR_LEAD_SYNC_FAILED:
          // Retry once and handle error if retry fails.
          break;

        default:
      }
      if (!$ok) {
        if ($faultCode != NULL) {
          if (strpos($faultCode, 'Client')) {
            // This is a client error.  Check the other codes and handle.
          }
          elseif (strpos($faultCode, 'Server')) {
            // This is a server error.  Call Marketo support with details.
          }
          else {
            // W3C spec has changed :)
            // But seriously, Call Marketo support with details.
          }
        }
        else {
          // Not a good place to be.
        }
      }
    }
    catch (\Exception $ex) {
      $msg = $ex->getMessage();
      $req = $this->client()->__getLastRequest();
      echo "Error occurred for request: $msg\n$req\n";
      var_dump($ex);
      exit(1);
    }
    // @todo when no api keys are set, this doesn't get set previously.
    return $success;
  }

  public function getCampaignsForSource() {
    $retList = NULL;

    $params = new ParamsGetCampaignsForSource();
    // We want campaigns configured for access through the SOAP API.
    $params->source = 'MKTOWS';

    $options = NULL;

    $authHdr = $this->_getAuthenticationHeader();

    try {
      $success = $this->client()->__soapCall('getCampaignsForSource', array($params), $options, $authHdr);

      if ($this->debug) {
        $req = $this->client()->__getLastRequest();
        echo "RAW request:\n$req\n";
        $resp = $this->client()->__getLastResponse();
        echo "RAW response:\n$resp\n";
      }

      if (isset($success->result->returnCount) && $success->result->returnCount > 0) {
        if (isset($success->result->campaignRecordList->campaignRecord)) {
          $retList = array();
          // campaignRecordList is ArrayOfCampaignRecord from WSDL.
          $campRecList = $success->result->campaignRecordList->campaignRecord;
          // Force to array when one 1 item is returned (quirk of PHP SOAP)
          if (!is_array($campRecList)) {
            $campRecList = array($campRecList);
          }
          // $campRec is CampaignRecord from WSDL.
          /** @var \stdClass $campRec */
          foreach ($campRecList as $campRec) {
            $retList[$campRec->name] = $campRec->id;
          }
        }
      }
    }
    catch (\SoapFault $ex) {
      if ($this->debug) {
        $req = $this->client()->__getLastRequest();
        echo "RAW request:\n$req\n";
        $resp = $this->client()->__getLastResponse();
        echo "RAW response:\n$resp\n";
      }
      $ok = FALSE;
      $faultCode = !empty($ex->faultCode) ? $ex->faultCode : NULL;
      switch ($this->getErrorCode($ex)) {

        case MarketoSoapError::ERR_CAMP_NOT_FOUND:
          // Handle error for campaign not found.
          break;

        default:
          // Handle other errors.
      }
      if (!$ok) {
        if ($faultCode != NULL) {
          if (strpos($faultCode, 'Client')) {
            // This is a client error.  Check the other codes and handle.
          }
          elseif (strpos($faultCode, 'Server')) {
            // This is a server error.  Call Marketo support with details.
          }
          else {
            // W3C spec has changed :)
            // But seriously, Call Marketo support with details.
          }
        }
        else {
          // Not a good place to be.
        }
      }
    }
    catch (\Exception $ex) {
      $msg = $ex->getMessage();
      $req = $this->client()->__getLastRequest();
      echo "Error occurred for request: $msg\n$req\n";
      var_dump($ex);
      exit(1);
    }

    return $retList;
  }

  public function requestCampaign($campId, $leadEmail) {
    $retStat = FALSE;

    $leadKey = new LeadKey();
    $leadKey->keyType = 'IDNUM';
    $leadKey->keyValue = $leadEmail;

    $leadList = new ArrayOfLeadKey();
    $leadList->leadKey = array($leadKey);

    $params = new ParamsRequestCampaign();
    $params->campaignId = $campId;
    $params->leadList = $leadList;
    $params->source = 'MKTOWS';

    $authHdr = $this->_getAuthenticationHeader();

    try {
      $options = NULL;
      $success = $this->client()->__soapCall('requestCampaign', array($params), $options, $authHdr);

      if ($this->debug) {
        $req = $this->client()->__getLastRequest();
        echo "RAW request:\n$req\n";
        $resp = $this->client()->__getLastResponse();
        echo "RAW response:\n$resp\n";
      }

      if (isset($success->result->success)) {
        $retStat = $success->result->success;
      }
    }
    catch (\SoapFault $ex) {
      $ok = FALSE;
      $faultCode = !empty($ex->faultCode) ? $ex->faultCode : NULL;
      switch ($this->getErrorCode($ex)) {

        case MarketoSoapError::ERR_LEAD_NOT_FOUND:
          // Handle error for campaign not found.
          break;

        default:
          // Handle other errors.
      }
      if (!$ok) {
        if ($faultCode != NULL) {
          if (strpos($faultCode, 'Client')) {
            // This is a client error.  Check the other codes and handle.
          }
          elseif (strpos($faultCode, 'Server')) {
            // This is a server error.  Call Marketo support with details.
          }
          else {
            // W3C spec has changed :)
            // But seriously, Call Marketo support with details.
          }
        }
        else {
          // Not a good place to be.
        }
      }
    }
    catch (\Exception $ex) {
      $msg = $ex->getMessage();
      $req = $this->client()->__getLastRequest();
      echo "Error occurred for request: $msg\n$req\n";
      var_dump($ex);
      exit(1);
    }

    return $retStat;
  }

  /**
   * Enter description here...
   *
   * @param string $leadId
   *   Lead ID.
   * @param string $listName
   *   Name of static list.
   * @param string $sinceTimestamp
   *   Some valid PHP time string like 2009-12-25 01:00:00.
   * @param int $lastId
   *   ID of last activity.
   * @return array
   */
  public function wasLeadAddedToListSince($leadId, $listName, $sinceTimestamp, $lastId) {
    $wasAdded = FALSE;
    $actRec = NULL;

    $leadKey = new LeadKey();
    $leadKey->keyType = 'IDNUM';
    $leadKey->keyValue = $leadId;
    $params = new ParamsGetLeadActivity();
    $params->leadKey = $leadKey;

    $actTypes = array();
    $actTypes[] = 'AddToList';
    $actArray = new ArrayOfActivityType();
    $actArray->activityType = $actTypes;
    $filter = new ActivityTypeFilter();
    $filter->includeTypes = $actArray;
    $params->activityFilter = $filter;

    $startPos = new StreamPosition();
    $startPos->oldestCreatedAt = date(DATE_W3C, $sinceTimestamp);
    $params->startPosition = $startPos;

    $params->batchSize = 100;

    $doPage = TRUE;
    while ($doPage) {
      $authHdr = $this->_getAuthenticationHeader();

      try {
        $options = NULL;
        $success = $this->client()->__soapCall('getLeadActivity', array($params), $options, $authHdr);

        if ($this->debug) {
          $req = $this->client()->__getLastRequest();
          echo "RAW request:\n$req\n";
          $resp = $this->client()->__getLastResponse();
          echo "RAW response:\n$resp\n";
        }

        if (isset($success->leadActivityList)) {
          // leadActivityList is LeadActivityList in WSDL.
          $result = $success->leadActivityList;
          if ($result->returnCount > 0) {
            // actRecList is ArrayOfActivityRecord from WSDL.
            $actRecList = $result->activityRecordList;
            // Force to array when one 1 item is returned (quirk of PHP SOAP)
            if (!is_array($actRecList)) {
              $actRecList = array($actRecList);
            }
            foreach ($actRecList as $actRec) {
              if ($actRec->id > $lastId && $actRec->mktgAssetName == $listName) {
                $wasAdded = TRUE;
                break 2;
              }
            }
            $newStartPos = $success->leadActivityList->newStartPosition;
            $params->startPosition = $newStartPos;
          }
          else {
            $doPage = FALSE;
          }
        }
      }
      catch (\SoapFault $ex) {
        $ok = FALSE;
        $faultCode = !empty($ex->faultCode) ? $ex->faultCode : NULL;
        switch ($this->getErrorCode($ex)) {

          case MarketoSoapError::ERR_LEAD_NOT_FOUND:
            // Handle error for lead not found.
            break;

          default:
            // Handle other errors.
        }
        if (!$ok) {
          if ($faultCode != NULL) {
            if (strpos($faultCode, 'Client')) {
              // This is a client error.  Check the other codes and handle.
            }
            elseif (strpos($faultCode, 'Server')) {
              // This is a server error.  Call Marketo support with details.
            }
            else {
              // W3C spec has changed :)
              // But seriously, Call Marketo support with details.
            }
          }
          else {
            // Not a good place to be.
          }
        }
        break;
      }
      catch (\Exception $ex) {
        $msg = $ex->getMessage();
        $req = $this->client()->__getLastRequest();
        echo "Error occurred for request: $msg\n$req\n";
        var_dump($ex);
        exit(1);
      }
    }

    return array($wasAdded, $actRec);
  }

  /**
   * Gets a SOAP exception error code.
   *
   * @see http://php.net/manual/en/class.soapfault.php
   *
   * @param \SoapFault $ex
   *   The SOAP fault object.
   * @return int
   *   The error code.
   */
  protected function getErrorCode(\SoapFault $ex) {
    return !empty($ex->detail->serviceException->code)
      ? $ex->detail->serviceException->code
      : 1;
  }
}
