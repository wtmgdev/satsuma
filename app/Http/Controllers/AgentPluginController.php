<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgentPluginController extends Controller
{
    public function download(): BinaryFileResponse
    {
        $path = base_path('wp-agent/shoal-agent.php');

        abort_unless(is_file($path), 404);

        return response()->download($path, 'shoal-agent.php');
    }
}
