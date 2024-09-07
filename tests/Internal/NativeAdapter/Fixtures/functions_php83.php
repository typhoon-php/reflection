<?php

declare(strict_types=1);

namespace AdapterCompatibilityTest\PHP83;

function FunctionWithDNFTypes((\Countable&\Stringable)|false $param): (\Countable&\Stringable)|false
{
}
