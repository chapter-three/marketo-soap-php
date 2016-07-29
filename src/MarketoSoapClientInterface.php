<?php

namespace ChapterThree\MarketoSoap;

/**
 * Interface SoapClientInterface.
 *
 * @package ChapterThree\MarketoSoap
 */
interface MarketoSoapClientInterface {

  /**
   * MarketoSoapClient constructor.
   *
   * @param $userID
   *   Marketo client access ID is found within your Marketo admin SOAP API
   *   panel under Integration.
   * @param $secretKey
   *   The Marketo SOAP API secret key.
   * @param $endpoint
   *   The Url for the Marketo SOAP API.
   */
  public function __construct($userID, $secretKey, $endpoint);


  /**
   * Get lead.
   *
   * @param string $keyType
   *   Field you wish to query the lead by.
   * @param string $keyValue
   *   Value you wish to query the lead by.
   *
   * @return \stdClass|NULL
   *   Lead object, or NULL on error.
   *
   * @see http://developers.marketo.com/documentation/soap/getlead/
   * @see \ChapterThree\MarketoSoap\Api\MktSampleMktowsClient::getLead
   */
  public function getLead($keyType, $keyValue);

}
