<?php

namespace App\Console\Commands;

use App\Models\Pregnancy;
use App\Services\BreedingService;
use Illuminate\Console\Command;
use RuntimeException;

class ProcessDuePregnancies extends Command
{
    protected $signature = 'cavernas:process-pregnancies';

    protected $description = 'Process pregnancies whose four-week due date has arrived.';

    public function handle(BreedingService $breeding): int
    {
        Pregnancy::query()->where('status', 'pregnant')->where('due_at', '<=', now())->chunkById(50, function ($pregnancies) use ($breeding): void {
            foreach ($pregnancies as $pregnancy) {
                try {
                    $breeding->giveBirth($pregnancy);
                } catch (RuntimeException $exception) {
                    $this->warn('Pregnancy '.$pregnancy->id.' was not processed: '.$exception->getMessage());
                }
            }
        });

        $this->info('Due pregnancies processed.');

        return self::SUCCESS;
    }
}
