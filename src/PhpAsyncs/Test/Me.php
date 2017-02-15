<?php

/**
 * @author Mike Ovchinnikov <ovc.mike@gmail.com>
 */

namespace PhpAsyncs\Test;

use PhpAsyncs\IExample;

class Me implements IExample
{
    public function run()
    {
        var_dump("me");
    }
}