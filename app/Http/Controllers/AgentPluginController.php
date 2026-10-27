<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class AgentPluginController extends Controller
{
    public function download(): BinaryFileResponse
    {
        $pluginPath = base_path('wp-agent/shoal-agent.php');

        abort_unless(is_file($pluginPath), 404);

        $zipPath = tempnam(sys_get_temp_dir(), 'shoal-agent-').'.zip';

        $zip = new ZipArchive;

        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500);

        $zip->addEmptyDir('shoal-agent');
        $zip->addFile($pluginPath, 'shoal-agent/shoal-agent.php');
        $zip->close();

        return response()->download($zipPath, 'shoal-agent.zip')
            ->deleteFileAfterSend();
    }
}
