<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BackupTableController extends Controller
{
    public function createTableBackup(Request $request)
    {
        $tables = $request->input('tables', []); // Obtener las tablas seleccionadas

        if (empty($tables)) {
            return response()->json(['message' => 'No se han seleccionado tablas válidas.']);
        }

        $backupFileName = 'heavycloud_' . date('Y-m-d_H-i-s') . '.sql'; // Nombre del archivo de respaldo
        $backupPath = storage_path('app/backup/' . $backupFileName); // Corregir el nombre de la variable

        $command = [
            'C:\xampp\mysql\bin\mysqldump.exe',
            '-u' . env('DB_USERNAME'),
            '-p' . env('DB_PASSWORD'),
            env('DB_DATABASE'),
            '--tables',
            implode(' ', array_map('escapeshellarg', $tables)),
            '>',
            $backupPath
        ];

        $process = new Process($command);
        $process->setTimeout(0); // Sin límite de tiempo de ejecución

        try {
            $process->mustRun(); // Ejecutar el proceso

            // Descargar el archivo de respaldo
            return response()->download($backupPath)->deleteFileAfterSend(true);
        } catch (\Symfony\Component\Process\Exception\ProcessFailedException $exception) {
            return response()->json(['message' => 'Error al crear el respaldo: ' . $exception->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Se produjo un error: ' . $e->getMessage()], 500);
        }
    }
}
