<?php
/**
 * Created by PhpStorm.
 * User: brian
 * Date: 7/21/16
 * Time: 5:35 PM
 */

namespace ChapterThree\MarketoSoap\Api;

/**
 * Corresponds to the data type defined in WSDL.
 */
class ParamsSyncMultipleLeads {

  /**
   * @var (object)ArrayOfLeadRecord
   */
  public $leadRecordList;

  /**
   * @var boolean
   */
  public $dedupEnabled;

}
