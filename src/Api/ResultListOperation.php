<?php

namespace ChapterThree\MarketoSoap\Api;

/**
 * Corresponds to the data type defined in WSDL.
 */
class ResultListOperation {

  /**
   * @var boolean
   */
  public $success;

  /**
   * @var (object)ArrayOfLeadStatus
   */
  public $statusList;

}
