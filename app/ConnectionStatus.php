<?php

namespace App;

enum ConnectionStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
}
