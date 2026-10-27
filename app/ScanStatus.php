<?php

namespace App;

enum ScanStatus: string
{
    case Idle = 'idle';
    case Queued = 'queued';
    case Running = 'running';
}
