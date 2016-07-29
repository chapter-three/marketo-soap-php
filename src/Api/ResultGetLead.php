<?php

namespace ChapterThree\MarketoSoap\Api;

/**
 * Corresponds to the data type defined in WSDL.
 */
class ResultGetLead {

  /**
   * @var int
   */
  public $count;

  /**
   * @var (object)ArrayOfLeadRecord
   */
  public $leadRecordList;

}
