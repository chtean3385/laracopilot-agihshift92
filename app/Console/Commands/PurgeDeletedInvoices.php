<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;

class PurgeDeletedInvoices extends Command
{
    protected $signature = 'invoices:purge-deleted {--days=30 : Number of days after soft-delete before hard-purge}';
    protected $description = 'Hard-delete invoices that were soft-deleted more than 30 days ago';

    public function handle(): int
    {
        $days  = (int) $this->option('days');
        $count = Invoice::onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($days))
            ->forceDelete();

        $this->info("Purged {$count} invoice(s) deleted more than {$days} days ago.");

        return 0;
    }
}
