<?php

namespace App;

enum CustomerStatus: string
{
    case Prospect = 'prospect';
    case Active = 'active';
    case Paused = 'paused';
    case Closed = 'closed';
}
