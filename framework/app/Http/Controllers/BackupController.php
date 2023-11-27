<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackupController extends Controller
{
    public function createBackup()
    {
        try {
            // Configuración de la zona horaria colombiana
            date_default_timezone_set('America/Bogota');

            // Nombre del archivo de respaldo con la hora colombiana
            $backupFileName = 'heavycloud_' . date('Y-m-d_H-i-s') . '.sql';

            // Ruta donde se guardará el archivo de respaldo
            $backupFilePath = storage_path('backup/') . $backupFileName;

            // Verificar si el directorio de respaldos existe, si no, créalo
            if (!file_exists(storage_path('backup/'))) {
                mkdir(storage_path('backup/'), 0755, true);
            }

            $process = new Process([
                'C:\xampp\mysql\bin\mysqldump.exe',  // Reemplaza con la ruta correcta en tu sistema
                '--host=' . env('DB_HOST'),
                '--user=' . env('DB_USERNAME'),
                '--password=' . env('DB_PASSWORD'),
                env('DB_DATABASE'),
            ]);
            

            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Almacenar el archivo SQL
            file_put_contents($backupFilePath, $process->getOutput());

            // Almacenar en la sesión que se ha realizado la descarga
            Session::put('backupDownloaded', $backupFileName);

            // Redirigir a la vista con el mensaje de éxito
            return redirect()->route('Admin')->with('success', 'Copia de seguridad creada y descargada correctamente.');
        } catch (\Exception $e) {
            // Redirigir a la vista con el mensaje de error
            return redirect()->route('Admin')->with('error', 'Error al crear o descargar la copia de seguridad: ' . $e->getMessage());
        }
    }

    public function downloadBackup()
    {
        $backupFileName = Session::get('backupDownloaded');

        // Ruta donde se encuentra el archivo de respaldo
        $backupFilePath = storage_path('app/backups/') . $backupFileName;

        // Verificar si el archivo existe antes de descargarlo
        if (file_exists($backupFilePath)) {
            // Devuelve el archivo como respuesta para que el usuario pueda descargarlo
            return Response::download($backupFilePath, $backupFileName)->deleteFileAfterSend(true);
        } else {
            // Redirigir a la vista con el mensaje de error
            return redirect()->route('Admin')->with('error', 'Error al descargar la copia de seguridad. El archivo no existe.');
        }
    }
}
