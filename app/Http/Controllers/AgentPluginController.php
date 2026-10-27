<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class AgentPluginController extends Controller
{
    public static function pluginPath(): string
    {
        return base_path('wp-agent/shoal-agent.php');
    }

    /**
     * The shipped plugin version, parsed from the file header.
     */
    public static function version(): ?string
    {
        $pluginPath = self::pluginPath();

        if (! is_file($pluginPath)) {
            return null;
        }

        $header = (string) file_get_contents($pluginPath, false, null, 0, 2048);

        preg_match('/^ \* Version:\s*(\S+)/m', $header, $matches);

        return $matches[1] ?? null;
    }

    public function download(): BinaryFileResponse
    {
        $pluginPath = self::pluginPath();

        abort_unless(is_file($pluginPath), 404);

        $zipPath = tempnam(sys_get_temp_dir(), 'shoal-agent-').'.zip';

        $zip = new ZipArchive;

        abort_unless($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500);

        $zip->addEmptyDir('shoal-agent');
        $zip->addFile($pluginPath, 'shoal-agent/shoal-agent.php');

        foreach (glob(dirname($pluginPath).'/assets/*.png') ?: [] as $asset) {
            $zip->addFile($asset, 'shoal-agent/assets/'.basename($asset));
        }

        $zip->close();

        $response = response()->download($zipPath, 'shoal-agent.zip')
            ->deleteFileAfterSend();

        $response->headers->set('X-Agent-Plugin-Version', (string) self::version());

        return $response;
    }
}
