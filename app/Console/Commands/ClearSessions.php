<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearSessions extends Command
{
        protected $signature = 'sessions:clear';

        protected $description = 'Borra todas las sesiones de usuario';

        public function handle()
        {
            // Borrar todas las sesiones de la tabla 'sessions'
            DB::table('sessions')->delete();

            $this->info('Todas las sesiones han sido borradas.');
        }

}
