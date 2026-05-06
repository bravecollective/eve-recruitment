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
        $users_updated = 0;
        $mains_preserved = 0;
        $users_deleted = 0;
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

                    // If the main is the only thing left on the account, we'll delete it in the account checks below
                    // In the meantime, set the core_account_id to null so it doesn't break account redistribution, and set has_valid_token to 0 so its apps don't break
                    $user->core_account_id = $coreAccountId;
                    $user->has_valid_token = 0;
                    $user->save();
                    $mains_preserved++;
                    continue;

                }
                $user->delete();
                $users_deleted++;
                
            }
            else {

                $user->core_account_id = $coreAccountId;
                $user->save();
                $users_updated++;

            }
        }

        echo "Updated $users_updated characters, deleted $users_deleted characters, preserved $mains_preserved main characters\n";

        $accounts_updated = 0;
        $accounts_distributed = 0;
        $accounts_migrated = 0;
        $accounts_in_archive = 0;
        $accounts_deleted = 0;

        foreach ($accounts as $account)
        {

            $core_accounts = User::where('account_id', $account->id)->whereNotNull('core_account_id')->pluck('core_account_id')->unique()->toArray();

            if (empty($core_accounts)) {

                // No Core Accounts
                if ($account->safeToDelete()) {

                    // If the account has no associated data, we can safely delete it and its characters
                    $account->delete();
                    $accounts_deleted++;

                }
                else {

                    // Is the listed main attached to another account? Migrate the data and delete the original account
                    $main_user = User::where('character_id', $account->main_user_id)->where('account_id', '!=', $account->id)->first();

                    if ($main_user !== null) {

                        $account->migrate($main_user->account_id);
                        $account->delete();
                        $accounts_migrated++;

                    }
                    else {
                        
                        // We don't have a valid main, keep the account intact so that its data remains accessible
                        $account->core_account_id = $user->core_account_id;
                        $account->save();
                        $account->verifyMainExists();
                        $accounts_in_archive++;

                    }

                }

            }
            elseif (count($core_accounts) > 1) {

                // Multiple Core Accounts
                if (in_array($account->core_account_id, $core_accounts)) {

                    // One of the core accounts is the one we have on record, break off the others into their own account(s)
                    $account->distributeUsers($account->core_account_id, $core_accounts);
                    $account->verifyMainExists();
                    $accounts_distributed++;

                }
                else {

                    // None of the core accounts matches the current one, we have to figure out how to split up the account data
                    $main_user = User::where('account_id', $account->id)->where('character_id', $account->main_user_id)->whereNotNull('core_account_id')->first();
                    if ($main_user !== null) {
                        
                        // We have the main's core account, use that one and break off the others into their own account(s)
                        $account->core_account_id = $main_user->core_account_id;
                        $account->save();

                        $account->distributeUsers($account->core_account_id, $core_accounts);
                        $account->verifyMainExists();
                        $accounts_distributed++;

                    }
                    else {

                        // We don't have a valid main. Pick the account with the most users
                        $highest_user_core_account = User::select(\DB::raw('COUNT(*) as num_users, core_account_id'))->where('account_id', $account->id)->whereNotNull('core_account_id')->groupBy('core_account_id')->sortByDesc('num_users')->first();
                        
                        $account->core_account_id = $highest_user_core_account->core_account_id;
                        $account->save();
                        
                        $account->distributeUsers($highest_user_core_account->core_account_id, $core_accounts);
                        $account->verifyMainExists();
                        $accounts_distributed++;

                    }

                }

            }
            else {

                // One Core Account
                $account->core_account_id = $user->core_account_id;
                $account->save();
                $account->verifyMainExists();
                $accounts_updated++;

            }

        }

        echo "Updated $accounts_updated accounts, deleted $accounts_deleted accounts, migrated $accounts_migrated accounts, redistributed $accounts_distributed accounts, $accounts_in_archive accounts are in an archived state\n";
    }
}
