<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class SqlFileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $path = public_path('sql/wayu.sql');
        $sql = file_get_contents($path);
        DB::unprepared($sql);

        // Copiar todas las imágenes de brands
        //$this->copyImages('seeders/imagenesBrands');
        // Copiar todas las imágenes de categories
        //$this->copyImages('seeders/imagenesCategories');
        // Copiar todas las imágenes de los productos
        //$this->copyImages('seeders/imagenesProductos');
    }

    private function copyImages($pathFolder)
    {
        // Ruta donde están las imágenes originales
        $sourcePath = database_path($pathFolder);

        // Ruta de destino en storage/app/public/repo
        $destinationPath = storage_path('app/public/repo');

        // Asegurar que el directorio de destino existe
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true, true);
        }

        // Obtener todas las imágenes de la carpeta origen
        $images = File::files($sourcePath);

        foreach ($images as $image) {
            // Obtener solo el nombre de la imagen
            $imageName = $image->getFilename();

            // Copiar la imagen al storage
            File::copy($image->getPathname(), "$destinationPath/$imageName");
        }

        $this->command->info("✅ Todas las imágenes han sido copiadas correctamente.");
    }

}