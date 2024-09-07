<?php

declare(strict_types=1);

namespace AdapterCompatibilityTest\PHP82;

function functionWithFalseTypes(false $param): false {}

function functionWithNullTypes(null $param): null {}
