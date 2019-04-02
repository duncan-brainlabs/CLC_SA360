<?php
/*
 * TransientException should be caught and retried.
 * @author ryutaro@brainlabsdigital.com
 */

namespace Brainlabs\Sheetsy;

use RuntimeException;

class TransientException extends RuntimeException
{

}
