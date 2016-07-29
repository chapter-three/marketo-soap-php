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
    $client = new MarketoSoapClient('bigcorp2_458073844B29ACAFC64AC0', '425794457179585644BB2299AACCBB01CC66229C2B35', 'https://example.mktoapi.com/soap/mktows/2_9');

    $this->assertEquals('http://www.marketo.com/mktows/', $client::MKTOWS_NAMESPACE);
  }
}
