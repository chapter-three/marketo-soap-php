<?php

namespace ChapterThree\MarketoSoap\Tests;
use ChapterThree\MarketoSoap\MarketoSoapClient;

/**
 * @coversDefaultClass ChapterThree\MarketoSoap\MarketoSoapClient
 */
class MarketoSoapClientTest extends \PHPUnit_Framework_TestCase {

  /**
   * @covers ::__construct
   *
   * @todo: The constructor should have everything it needs to connect.
   */
  public function testConstructor() {
    // Get API credentials from environment variables.
    $id = getenv('marketo_api_id');
    $secret = getenv('marketo_api_secret');
    $uri = getenv('marketo_api_uri');


    $this->assertEquals('bigcorp2_458073844B29ACAFC64AC0', $id);
    $this->assertEquals('425794457179585644BB2299AACCBB01CC66229C2B35', $secret);
    $this->assertEquals('https://example.mktoapi.com/soap/mktows/2_9', $uri);


    $client = new MarketoSoapClient($id, $secret, $uri);
    $this->assertEquals('http://www.marketo.com/mktows/', $client::MKTOWS_NAMESPACE);
  }
}
