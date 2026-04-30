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
        $updated = 0;
        $deleted = 0;
        $idsToCheck = [];

        foreach ($users as $user)
        {
            $idsToCheck[] = $user->character_id;
        }

        $mappedIDs = CoreConnection::getCharactersAccounts($idsToCheck);

        foreach ($users as $user)
        {
            $coreAccountId = $mappedIDs[$user->character_id];

            if ($coreAccountId === null) {
                // Neucore returned no player for this character (biomassed/sold/otherwise removed), so delete it.
                if (Account::where('main_user_id', $user->character_id)->exists()) {
                    // Skip deleting if the character is a main for an account, as that would break the account.\
                    echo "Failed to delete character {$user->character_id} because it is the main of an account\n";
                    continue;
                }
                $user->delete();
                $deleted++;
            } else {
                $user->core_account_id = $coreAccountId;
                $user->save();
                $updated++;
            }
        }

        echo "Updated $updated characters, deleted $deleted characters\n";
        $count = 0;

        foreach ($accounts as $account)
        {
            $user = User::where('account_id', $account->id)->whereNotNull('core_account_id')->first();
            if ($user === null) {
                // Account has no characters, or all the characters on the account have a NULL core account.
                continue;
            }

            $account->core_account_id = $user->core_account_id;
            $account->save();
            $count++;
        }

        echo "Updated $count accounts\n";
    }
}
