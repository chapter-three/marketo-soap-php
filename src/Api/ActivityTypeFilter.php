<?php

namespace ChapterThree\MarketoSoap\Api;

/**
 * Corresponds to the data type defined in WSDL.
 */
class ActivityTypeFilter {

  /**
   * @var (object)ArrayOfActivityType
   */
  public $includeTypes;

  /**
   * @var (object)ArrayOfActivityType
   */
  public $excludeTypes;

}
