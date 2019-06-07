<?php

namespace App\Console\Commands;

use App\Connectors\CoreConnection;
use App\Models\Account;
use App\Models\User;
use Illuminate\Console\Command;

class UpdateCoreAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:coreaccounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update user core accounts in the database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $users = User::all();
        $accounts = Account::all();
        $count = 0;

        foreach ($users as $user)
        {
            $user->core_account_id = CoreConnection::getCharacterAccount($user->character_id);
            $user->save();
            $count++;
        }

        echo "Updated $count characters\n";
        $count = 0;

        foreach ($accounts as $account)
        {
            $user = User::where('account_id', $account->id)->first();
            $account->core_account_id = $user->core_account_id;
            $account->save();
            $count++;
        }

        echo "Updated $count accounts\n";
    }
}
