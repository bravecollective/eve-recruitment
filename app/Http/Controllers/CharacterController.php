<?php

namespace App\Http\Controllers;

use App\Connectors\CoreConnection;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;

class CharacterController extends Controller
{
    /**
     * Add a character from Core to the local database
     */
    public function addCharacter(Request $r)
    {
        $characterId = (int) $r->input('id');

        // check if user exists
        if (User::find($characterId) !== null) {
            return redirect('/')->with('error', 'That character already exists.');
        }

        // get Core Id
        try {
            $coreAccountId = CoreConnection::getCharacterAccount($characterId);
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Character not found on Core.');
        }

        // get necessary data
        $characterName = null;
        $corporationId = null;
        $corporationName = null;
        $allianceId = null;
        $allianceName = null;
        try {
            $characters = CoreConnection::getCharactersForUser($characterId);
        } catch (\Exception $e) {
            // do nothing
        }
        if (isset($characters) && is_array($characters)) {
            foreach ($characters as $character) {
                if ((int) $character->id !== $characterId) {
                    continue;
                }
                $characterName = $character->name;
                $corporationId = $character->corporation->id;
                $corporationName = $character->corporation->name;
                if ($character->corporation->alliance) {
                    $allianceId = $character->corporation->alliance->id;
                    $allianceName = $character->corporation->alliance->name;
                }
                break;
            }
        }
        if (!$corporationId) {
            return redirect('/')->with('error', 'An error occurred, please try again.');
        }

        // add account
        $account = Account::where('core_account_id', $coreAccountId)->first();
        if ($account === null) {
            $account = new Account();
            $account->main_user_id = $characterId;
            $account->core_account_id = $coreAccountId;
            $account->save();
        }

        // add character
        $user = new User();
        $user->account_id = $account->id;
        $user->core_account_id = $coreAccountId;
        $user->name = $characterName;
        $user->character_id = $characterId;
        $user->corporation_id = $corporationId;
        $user->corporation_name = $corporationName;
        $user->alliance_id = $allianceId;
        $user->alliance_name = $allianceName;
        $user->save();

        return redirect('/')->with('info', 'Character added.');
    }
}
