<?php

namespace Dcp\Enums;

use Dcp\Traits\EnumToArray;

enum SiteType: string
{
    use EnumToArray;

    case DRUG_SUBSTANCE = 'DrugSubstance';
    case DRUG_PRODUCT = 'DrugProduct';
    case DEVELOPMENT = 'Development';
    case SMALL_MOLECULES = 'SmallMolecules';
    case DIAGNOSTIC = 'Diagnostic';
}
