<?php

namespace Dcp\Enums\EDMS;

enum DocumentStatus: int
{
    case DRAFT = 1;
    case APPROVED = 2;
    case EFFECTIVE = 3;
    case WITHDRAW = 4;
}
